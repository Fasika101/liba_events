<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySmsPurchaseRequest;
use App\Models\SmsPackage;
use App\Models\Ticket;
use App\Services\CustomerTicketSmsService;
use Illuminate\Http\Request;

class PremiumFeaturesController extends Controller
{
    public function index()
    {
        $company = $this->company();

        $packages = SmsPackage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('sms_count')
            ->get();

        $recentSms = $company->smsLogs()->with('ticket')->take(10)->get();
        $transactions = $company->walletTransactions()->with('package')->take(10)->get();
        $pendingPurchase = $company->smsPurchaseRequests()
            ->where('status', CompanySmsPurchaseRequest::STATUS_PENDING)
            ->with('package')
            ->first();

        return view('admin.premium.index', compact(
            'company',
            'packages',
            'recentSms',
            'transactions',
            'pendingPurchase'
        ));
    }

    public function requestPremium()
    {
        $company = $this->company();

        if ($company->isPremium()) {
            return back()->with('error', 'Your organization is already on Premium.');
        }

        $company->update(['premium_requested_at' => now()]);

        return back()->with('status', 'Premium upgrade request sent. Our team will contact you shortly.');
    }

    public function updateSettings(Request $request)
    {
        $company = $this->company();

        if (! $company->isPremium()) {
            return back()->with('error', 'Premium plan required.');
        }

        $data = $request->validate([
            'auto_sms_on_sale' => ['sometimes', 'boolean'],
            'ticket_sms_template' => ['nullable', 'string', 'max:500'],
        ]);

        $company->update([
            'auto_sms_on_sale' => $request->boolean('auto_sms_on_sale'),
            'ticket_sms_template' => $data['ticket_sms_template'] ?? null,
        ]);

        return back()->with('status', 'Premium settings saved.');
    }

    public function requestPackage(Request $request)
    {
        $company = $this->company();

        if (! $company->isPremium()) {
            return back()->with('error', 'Premium plan required to purchase SMS packages.');
        }

        $data = $request->validate([
            'sms_package_id' => ['required', 'exists:sms_packages,id'],
        ]);

        $exists = CompanySmsPurchaseRequest::query()
            ->where('company_id', $company->id)
            ->where('sms_package_id', $data['sms_package_id'])
            ->where('status', CompanySmsPurchaseRequest::STATUS_PENDING)
            ->exists();

        if ($exists) {
            return back()->with('error', 'You already have a pending request for this package.');
        }

        CompanySmsPurchaseRequest::create([
            'company_id' => $company->id,
            'sms_package_id' => $data['sms_package_id'],
            'requested_by' => auth()->id(),
        ]);

        return back()->with('status', 'Package purchase request submitted. Credits will be added after payment is confirmed.');
    }

    public function sendCustomSms(Request $request, CustomerTicketSmsService $customerSms)
    {
        $company = $this->company();

        if (! $company->isPremium()) {
            return back()->with('error', 'Premium plan required.');
        }

        $data = $request->validate([
            'phone' => ['required', 'regex:/^\+251[0-9]{9}$/'],
            'message' => ['required', 'string', 'min:5', 'max:480'],
        ]);

        try {
            $customerSms->sendCustomMessage($company, $data['phone'], $data['message'], $request->user());
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        }

        return back()->with('status', 'Message sent to '.$data['phone'].'. 1 SMS credit deducted.');
    }

    public function sendToCustomer(Request $request, Ticket $ticket, CustomerTicketSmsService $customerSms)
    {
        $company = $this->company();

        if (! $company->isPremium()) {
            return back()->with('error', 'Premium plan required.');
        }

        $this->authorizeTicket($ticket, $company);

        if (! filled($ticket->buyer_phone)) {
            return back()->with('error', 'This ticket has no buyer phone number.');
        }

        try {
            $customerSms->sendCustomMessage(
                $company,
                $ticket->buyer_phone,
                $request->validate(['message' => ['required', 'string', 'max:480']])['message'],
                $request->user()
            );
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Message sent to customer.');
    }

    private function company(): Company
    {
        return Company::findOrFail(auth()->user()->requireCompanyId());
    }

    private function authorizeTicket(Ticket $ticket, Company $company): void
    {
        $ticket->loadMissing('event');

        if ((int) $ticket->event?->company_id !== $company->id) {
            abort(404);
        }
    }
}
