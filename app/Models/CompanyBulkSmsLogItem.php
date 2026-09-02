<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBulkSmsLogItem extends Model
{
    protected $fillable = [
        'company_bulk_sms_log_id',
        'phone',
        'recipient_name',
        'ticket_id',
        'status',
        'error_message',
    ];

    public function log(): BelongsTo
    {
        return $this->belongsTo(CompanyBulkSmsLog::class, 'company_bulk_sms_log_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
