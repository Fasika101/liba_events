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

    public function copyText(): string
    {
        $lines = [
            $this->bank_name,
            $this->account_name,
            $this->account_number,
        ];

        if (filled($this->instructions)) {
            $lines[] = $this->instructions;
        }

        return implode("\n", $lines);
    }
}
