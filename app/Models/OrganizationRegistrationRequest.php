<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $company_id
 * @property string $first_name
 * @property string $last_name
 * @property string $address
 * @property string $phone
 * @property string $organization_name
 * @property string $organization_address
 * @property string $organization_phone
 * @property string|null $admin_email
 * @property string|null $password
 * @property string|null $sms_verification_code
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $phone_verified_at
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property int|null $reviewed_by
 * @property string|null $rejection_reason
 */
class OrganizationRegistrationRequest extends Model
{
    public const STATUS_PENDING_VERIFICATION = 'pending_verification';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'address',
        'phone',
        'organization_name',
        'organization_address',
        'organization_phone',
        'admin_email',
        'password',
        'sms_verification_code',
        'status',
        'phone_verified_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'phone_verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function isPendingVerification(): bool
    {
        return $this->status === self::STATUS_PENDING_VERIFICATION;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAwaitingReview(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_VERIFICATION, self::STATUS_PENDING], true);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
