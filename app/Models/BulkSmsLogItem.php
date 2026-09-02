<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bulk_sms_log_id
 * @property int|null $company_id
 * @property string $company_name
 * @property string|null $phone
 * @property string $status
 * @property string|null $error_message
 */
class BulkSmsLogItem extends Model
{
    protected $fillable = [
        'bulk_sms_log_id',
        'company_id',
        'company_name',
        'phone',
        'status',
        'error_message',
    ];

    public function log(): BelongsTo
    {
        return $this->belongsTo(BulkSmsLog::class, 'bulk_sms_log_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
