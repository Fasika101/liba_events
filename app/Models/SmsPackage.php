<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $sms_count
 * @property string $price_etb
 * @property string $price_per_sms
 * @property string|null $description
 * @property bool $is_active
 */
class SmsPackage extends Model
{
    protected $fillable = [
        'name',
        'sms_count',
        'price_etb',
        'price_per_sms',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sms_count' => 'integer',
            'price_etb' => 'decimal:2',
            'price_per_sms' => 'decimal:4',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(CompanySmsPurchaseRequest::class);
    }
}
