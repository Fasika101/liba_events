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
        'source',
        'attendee_id',
        'ticket_order_id',
        'payment_reference',
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

    /**
     * Human label for who sold / where the ticket came from.
     */
    public function soldByLabel(): string
    {
        if ($this->agent?->name) {
            return (string) $this->agent->name;
        }

        if (($this->source ?? null) === 'self' || $this->agent_id === null) {
            return 'Mini App';
        }

        return 'Unknown';
    }

    public function isMiniAppPurchase(): bool
    {
        return ($this->source ?? null) === 'self' || ($this->agent_id === null && filled($this->ticket_order_id));
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }

    public function ticketOrder(): BelongsTo
    {
        return $this->belongsTo(TicketOrder::class);
    }

    /**
     * Generate the next sequential ticket code for an event.
     * Format: {first 3 letters of event title}-{5-digit number}  e.g. LET-00001
     *
     * Must be called inside a DB::transaction() to avoid race conditions.
     */
    public static function generateCode(Event $event): string
    {
        $prefix = self::prefixFromEventTitle((string) $event->title, (int) $event->id);

        // Number starts at character position (strlen + dash + 1), 1-indexed for MySQL SUBSTRING
        $numPos = strlen($prefix) + 2;

        $max = DB::table('tickets')
            ->where('event_id', $event->id)
            ->where('ticket_code', 'like', $prefix.'-%')
            ->lockForUpdate()
            ->max(DB::raw("CAST(SUBSTRING(ticket_code, {$numPos}) AS UNSIGNED)"));

        $next = ((int) $max) + 1;

        return $prefix.'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public static function prefixFromEventTitle(string $title, int $eventId = 0): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $title) ?? '', 0, 3));

        if (strlen($prefix) < 3) {
            // Non-Latin titles (e.g. Amharic): stable ASCII fallback from event id
            $fallback = 'E'.str_pad((string) max($eventId, 0), 2, '0', STR_PAD_LEFT);
            $prefix = strtoupper(substr($prefix.$fallback, 0, 3));
        }

        return str_pad($prefix, 3, 'X');
    }
}
