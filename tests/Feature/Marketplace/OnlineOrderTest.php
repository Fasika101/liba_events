<?php

namespace Tests\Feature\Marketplace;

use App\Models\Attendee;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\Event;
use App\Models\TicketOrder;
use App\Models\User;
use App\Services\TicketOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineOrderTest extends TestCase
{
    use RefreshDatabase;

    private function asTelegramUser()
    {
        $attendee = Attendee::create([
            'telegram_id' => (string) random_int(100000, 999999999),
            'name' => 'Telegram User',
            'locale' => 'en',
            'notifications_enabled' => true,
            'channel_joined_at' => now(),
            'profile_completed_at' => now(),
        ]);

        return $this->withSession(['public_attendee_id' => $attendee->id]);
    }

    private function premiumOrgWithEvent(): array
    {
        $company = Company::factory()->create([
            'plan' => Company::PLAN_PREMIUM,
            'is_suspended' => false,
            'sms_credits' => 0,
            'auto_sms_on_sale' => false,
        ]);

        CompanyBankAccount::create([
            'company_id' => $company->id,
            'bank_name' => 'CBE',
            'bank_code' => 'cbe',
            'account_name' => 'Demo Org',
            'account_number' => '1000123456789',
            'is_active' => true,
        ]);

        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Sunday Gathering',
            'status' => 'active',
            'price' => 100,
            'currency' => 'ETB',
            'capacity' => 50,
            'start_at' => now()->addDays(3),
        ]);

        $admin = User::factory()->admin()->create([
            'company_id' => $company->id,
        ]);

        return compact('company', 'event', 'admin');
    }

    public function test_guest_is_redirected_to_telegram_gate(): void
    {
        $this->get(route('public.discover'))
            ->assertRedirect(route('public.gate'));
    }

    public function test_premium_events_appear_on_discover(): void
    {
        ['event' => $event] = $this->premiumOrgWithEvent();

        $this->asTelegramUser()
            ->get(route('public.discover'))
            ->assertOk()
            ->assertSee($event->title);
    }

    public function test_expired_events_hidden_on_discover_but_shown_on_organizer(): void
    {
        ['company' => $company, 'event' => $event] = $this->premiumOrgWithEvent();
        $event->update([
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDays(5),
            'title' => 'Expired Gathering',
        ]);

        $this->asTelegramUser()
            ->get(route('public.discover'))
            ->assertOk()
            ->assertDontSee('Expired Gathering');

        $html = $this->asTelegramUser()
            ->get(route('public.organizers.show', $company))
            ->assertOk()
            ->assertSee('Expired Gathering')
            ->assertSee(__('public.expired'))
            ->assertSee(__('public.event_expired'))
            ->getContent();

        $this->assertStringNotContainsString(
            'href="'.route('public.events.show', $event).'"',
            $html
        );

        $this->asTelegramUser()
            ->post(route('public.events.checkout', $event), [
                'buyer_name' => 'Abebe Kebede',
                'buyer_phone' => '+251911234567',
                'yeneshaa_abat' => '1',
                'bank_code' => 'cbe',
                'transaction_reference' => 'FT999',
            ])->assertRedirect()
            ->assertSessionHasErrors('checkout');
    }

    public function test_standard_org_events_are_hidden(): void
    {
        $company = Company::factory()->create(['plan' => Company::PLAN_STANDARD]);
        Event::create([
            'company_id' => $company->id,
            'status' => 'active',
            'title' => 'Hidden Standard Event',
            'price' => 50,
            'currency' => 'ETB',
            'start_at' => now()->addDays(2),
        ]);

        $this->asTelegramUser()
            ->get(route('public.discover'))
            ->assertOk()
            ->assertDontSee('Hidden Standard Event');
    }

    public function test_checkout_creates_pending_order_and_admin_can_mark_paid(): void
    {
        ['event' => $event, 'admin' => $admin] = $this->premiumOrgWithEvent();

        $this->asTelegramUser()
            ->post(route('public.events.checkout', $event), [
                'buyer_name' => 'Abebe Kebede',
                'buyer_phone' => '+251911234567',
                'buyer_email' => null,
                'yeneshaa_abat' => '1',
                'bank_code' => 'cbe',
                'transaction_reference' => 'FT123456789',
            ])->assertRedirect();

        $order = TicketOrder::query()->first();
        $this->assertNotNull($order);
        $this->assertSame(TicketOrder::STATUS_PENDING, $order->status);

        $this->actingAs($admin)
            ->post(route('admin.online-orders.paid', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(TicketOrder::STATUS_PAID, $order->status);
        $this->assertNotNull($order->ticket_id);
        $this->assertDatabaseHas('tickets', [
            'id' => $order->ticket_id,
            'agent_id' => null,
            'source' => 'self',
            'buyer_phone' => '+251911234567',
        ]);

        $ticket = $order->ticket()->first();
        $this->assertNotNull($ticket);
        $this->assertStringStartsWith('SUN-', $ticket->ticket_code); // "Sunday Gathering"
    }

    public function test_service_rejects_non_premium_company(): void
    {
        $company = Company::factory()->create(['plan' => Company::PLAN_STANDARD]);
        CompanyBankAccount::create([
            'company_id' => $company->id,
            'bank_name' => 'CBE',
            'account_name' => 'X',
            'account_number' => '1',
            'is_active' => true,
        ]);
        $event = Event::create([
            'company_id' => $company->id,
            'title' => 'Standard Event',
            'price' => 50,
            'currency' => 'ETB',
            'status' => 'active',
            'start_at' => now()->addDay(),
        ]);

        $this->expectException(\RuntimeException::class);
        app(TicketOrderService::class)->createPendingOrder($event, [
            'buyer_name' => 'Test',
            'buyer_phone' => '+251911234567',
            'yeneshaa_abat' => 0,
            'transaction_reference' => 'ABC',
        ]);
    }
}
