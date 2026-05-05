<?php

namespace App\Models;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'agent_id',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'buyer_address',
        'buyer_occupation',
        'yeneshaa_abat',
        'price_paid',
        'currency',
        'ticket_code',
        'sold_at',
        'checked_in_at',
    ];

    protected $casts = [
        'price_paid'     => 'decimal:2',
        'sold_at'        => 'datetime',
        'checked_in_at'  => 'datetime',
        'yeneshaa_abat'  => 'boolean',
    ];

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Generate the next sequential ticket code for a company.
     * Format: {3-letter prefix}-{5-digit number}  e.g. ADD-00001
     *
     * Must be called inside a DB::transaction() to avoid race conditions.
     */
    public static function generateCode(int $companyId, string $companyName): string
    {
        // Build prefix: first 3 alphanumeric chars of company name, uppercase
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $companyName), 0, 3));
        $prefix = str_pad($prefix, 3, 'X');   // pad with X if name is very short

        // Number starts at character position (strlen + dash + 1), 1-indexed for MySQL SUBSTRING
        $numPos = strlen($prefix) + 2;

        $max = DB::table('tickets')
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->where('events.company_id', $companyId)
            ->where('tickets.ticket_code', 'like', $prefix . '-%')
            ->lockForUpdate()
            ->max(DB::raw("CAST(SUBSTRING(ticket_code, {$numPos}) AS UNSIGNED)"));

        $next = ((int) $max) + 1;

        return $prefix . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
