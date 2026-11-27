<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBankAccount extends Model
{
    protected $fillable = [
        'company_id',
        'bank_name',
        'bank_code',
        'logo_url',
        'account_name',
        'account_number',
        'instructions',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function resolvedLogoUrl(): ?string
    {
        return filled($this->logo_url) ? $this->logo_url : null;
    }

    public function copyText(): string
    {
        return (string) $this->account_number;
    }
}
