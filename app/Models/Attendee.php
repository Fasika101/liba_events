<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendee extends Model
{
    protected $fillable = [
        'telegram_id',
        'telegram_username',
        'first_name',
        'last_name',
        'name',
        'phone',
        'locale',
    ];

    public function displayName(): string
    {
        if (filled($this->name)) {
            return $this->name;
        }

        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: ($this->telegram_username ?: 'Guest');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(TicketOrder::class)->latest();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class)->latest();
    }
}
