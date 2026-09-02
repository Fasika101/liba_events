<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property string|null $phone
 * @property bool $is_suspended
 * @property string $plan
 * @property int $sms_credits
 * @property string $wallet_balance_etb
 * @property bool $auto_sms_on_sale
 * @property string|null $ticket_sms_template
 * @property \Illuminate\Support\Carbon|null $premium_requested_at
 * @property \Illuminate\Support\Carbon|null $premium_enabled_at
 */
class Company extends Model
{
    public const PLAN_STANDARD = 'standard';

    public const PLAN_PREMIUM = 'premium';

    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'is_suspended',
        'plan',
        'sms_credits',
        'wallet_balance_etb',
        'auto_sms_on_sale',
        'ticket_sms_template',
        'premium_requested_at',
        'premium_enabled_at',
        'sms_api_key',
        'sms_sender_id',
    ];

    protected function casts(): array
    {
        return [
            'is_suspended' => 'boolean',
            'sms_credits' => 'integer',
            'wallet_balance_etb' => 'decimal:2',
            'auto_sms_on_sale' => 'boolean',
            'premium_requested_at' => 'datetime',
            'premium_enabled_at' => 'datetime',
            'sms_api_key' => 'encrypted',
        ];
    }

    public function isPremium(): bool
    {
        return $this->plan === self::PLAN_PREMIUM;
    }

    public function isStandard(): bool
    {
        return $this->plan === self::PLAN_STANDARD;
    }

    public function hasSmsCredits(): bool
    {
        return $this->sms_credits > 0;
    }

    public function hasSmsApiKey(): bool
    {
        return filled($this->sms_api_key);
    }

    public function canSendSms(): bool
    {
        return \App\Support\CompanySmsConfig::canSend($this);
    }

    public function effectiveSenderId(): ?string
    {
        return \App\Support\CompanySmsConfig::senderId($this);
    }

    public function defaultTicketSmsTemplate(): string
    {
        return 'Hello {buyer_name}, your ticket for {event} is confirmed. Code: {ticket_code}. View receipt: {receipt_link} — {organization}';
    }

    public function ticketSmsTemplate(): string
    {
        return filled($this->ticket_sms_template)
            ? $this->ticket_sms_template
            : $this->defaultTicketSmsTemplate();
    }

    public function isSuspended(): bool
    {
        return (bool) $this->is_suspended;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function primaryAdmin(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'admin')->oldestOfMany();
    }

    public function approvedRegistrationRequest(): HasOne
    {
        return $this->hasOne(OrganizationRegistrationRequest::class)
            ->where('status', OrganizationRegistrationRequest::STATUS_APPROVED)
            ->latestOfMany();
    }

    public function wasCreatedViaRegistration(): bool
    {
        return $this->approvedRegistrationRequest()->exists();
    }

    public function smsPhone(): ?string
    {
        if (filled($this->phone)) {
            return $this->phone;
        }

        $this->loadMissing('primaryAdmin');

        return filled($this->primaryAdmin?->phone) ? $this->primaryAdmin->phone : null;
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(CompanyWalletTransaction::class)->latest();
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(CompanySmsLog::class)->latest();
    }

    public function smsPurchaseRequests(): HasMany
    {
        return $this->hasMany(CompanySmsPurchaseRequest::class)->latest();
    }

    public function senderIdRequests(): HasMany
    {
        return $this->hasMany(CompanySenderIdRequest::class)->latest();
    }

    public function bulkSmsLogs(): HasMany
    {
        return $this->hasMany(CompanyBulkSmsLog::class)->latest();
    }
}
