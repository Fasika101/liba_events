<?php

namespace App\Models;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'price_paid'     => 'decimal:2',
        'sold_at'        => 'datetime',
        'yeneshaa_abat'  => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
