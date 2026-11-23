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
        'email',
        'locale',
        'notifications_enabled',
        'channel_joined_at',
        'profile_completed_at',
    ];

    protected $casts = [
        'notifications_enabled' => 'boolean',
        'channel_joined_at' => 'datetime',
        'profile_completed_at' => 'datetime',
    ];

    public function displayName(): string
    {
        if (filled($this->name)) {
            return $this->name;
        }

        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: ($this->telegram_username ?: 'Guest');
    }

    public function needsChannelJoin(): bool
    {
        if (! filled(config('services.telegram.channel_id'))) {
            return false;
        }

        return $this->channel_joined_at === null;
    }

    public function needsProfile(): bool
    {
        return $this->profile_completed_at === null;
    }

    public function isOnboarded(): bool
    {
        return ! $this->needsChannelJoin() && ! $this->needsProfile();
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
