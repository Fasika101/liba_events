<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyBulkSmsLog extends Model
{
    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'company_id',
        'sent_by',
        'title',
        'message',
        'recipient_count',
        'success_count',
        'failed_count',
        'skipped_count',
    ];

    protected function casts(): array
    {
        return [
            'recipient_count' => 'integer',
            'success_count' => 'integer',
            'failed_count' => 'integer',
            'skipped_count' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompanyBulkSmsLogItem::class);
    }
}
