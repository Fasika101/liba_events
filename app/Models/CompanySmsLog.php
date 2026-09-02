<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySmsLog extends Model
{
    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const PURPOSE_TICKET_SALE = 'ticket_sale';

    public const PURPOSE_CUSTOM = 'custom';

    public const PURPOSE_BULK = 'bulk';

    protected $fillable = [
        'company_id',
        'ticket_id',
        'phone',
        'message',
        'purpose',
        'status',
        'cost_etb',
        'credits_used',
        'error_message',
        'sent_by',
    ];

    protected function casts(): array
    {
        return [
            'cost_etb' => 'decimal:4',
            'credits_used' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
