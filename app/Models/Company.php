<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property bool $is_suspended
 */
class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'is_suspended',
    ];

    protected function casts(): array
    {
        return [
            'is_suspended' => 'boolean',
        ];
    }

    public function isSuspended(): bool
    {
        return (bool) $this->is_suspended;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
