<?php

namespace App\Models;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'price',
        'currency',
        'start_at',
        'end_at',
        'capacity',
        'status',
        'photo_path',
    ];

    protected $casts = [
        'price'    => 'decimal:2',
        'capacity' => 'integer',
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    /**
     * An event is "full" only when a positive capacity is set
     * AND tickets sold have reached (or exceeded) that limit.
     */
    public function getIsFullAttribute(): bool
    {
        return $this->capacity > 0
            && ($this->tickets_count ?? $this->tickets()->count()) >= $this->capacity;
    }

    public function getSpotsLeftAttribute(): ?int
    {
        if ($this->capacity <= 0 || $this->capacity === null) return null;
        return max(0, $this->capacity - ($this->tickets_count ?? $this->tickets()->count()));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
