<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyWalletTransaction extends Model
{
    public const TYPE_TOPUP = 'topup';

    public const TYPE_DEDUCTION = 'deduction';

    protected $fillable = [
        'company_id',
        'type',
        'amount_etb',
        'sms_credits',
        'sms_credits_after',
        'wallet_balance_etb_after',
        'description',
        'sms_package_id',
        'created_by',
        'reference_type',
        'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_etb' => 'decimal:2',
            'sms_credits' => 'integer',
            'sms_credits_after' => 'integer',
            'wallet_balance_etb_after' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(SmsPackage::class, 'sms_package_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
