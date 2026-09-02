<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySenderIdRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATUS_PAYMENT_SUBMITTED = 'payment_submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'company_id',
        'requested_sender_id',
        'status',
        'price_etb',
        'payment_instructions',
        'payment_screenshot_path',
        'processed_by',
        'processed_at',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'price_etb' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true);
    }
}
