<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $title
 * @property string $message
 * @property int $sent_by
 * @property int $total_count
 * @property int $success_count
 * @property int $failed_count
 * @property int $skipped_count
 */
class BulkSmsLog extends Model
{
    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'title',
        'message',
        'sent_by',
        'total_count',
        'success_count',
        'failed_count',
        'skipped_count',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BulkSmsLogItem::class);
    }
}
