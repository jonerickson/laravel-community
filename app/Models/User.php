<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasGroups;
use BezhanSalleh\FilamentShield\Support\Utils;
use Exception;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $signature
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property string|null $pm_expiration
 * @property string|null $extra_billing_information
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property string|null $billing_address
 * @property string|null $billing_address_line_2
 * @property string|null $billing_city
 * @property string|null $billing_state
 * @property string|null $billing_postal_code
 * @property string|null $vat_id
 * @property string|null $invoice_emails
 * @property string|null $billing_country
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserFingerprint> $fingerprints
 * @property-read int|null $fingerprints_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Group> $groups
 * @property-read int|null $groups_count
 * @property-read bool $is_banned
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Cashier\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User hasExpiredGenericTrial()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onGenericTrial()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBillingAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBillingAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBillingCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBillingCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBillingPostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBillingState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereExtraBillingInformation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereInvoiceEmails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePmExpiration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePmLastFour($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePmType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStripeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTrialEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereVatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use Billable;
    use HasApiTokens;
    use HasFactory;
    use HasGroups;
    use HasPermissions;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'signature',
        'avatar',
        'is_banned',
        'banned_at',
        'ban_reason',
        'banned_by',
    ];

    protected $hidden = [
        'remember_token',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'pm_expiration',
        'extra_billing_information',
        'billing_address',
        'billing_address_line_2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'vat_id',
    ];

    protected $appends = [
        'is_banned',
    ];

    protected $with = [
        'groups',
    ];

    /**
     * @throws Exception
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasRole(Utils::getSuperAdminName());
        }

        return $panel->getId() === 'marketplace';
    }

    public function fingerprints(): HasMany
    {
        return $this->hasMany(UserFingerprint::class);
    }

    public function isBanned(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->fingerprints()->banned()->exists(),
        )->shouldCache();
    }

    public function avatar(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value ? asset('storage/'.$value) : null,
        );
    }

    //    public function hasPermissionTo($permission, $guardName = null): bool
    //    {
    //        if ($this->hasAnyPermission($permission)) {
    //            return true;
    //        }
    //
    //        return $this->groups()
    //            ->active()
    //            ->whereHas('permissions', function ($query) use ($permission, $guardName) {
    //                $query->where('name', $permission);
    //                if ($guardName) {
    //                    $query->where('guard_name', $guardName);
    //                }
    //            })
    //            ->exists();
    //    }

    public function can($abilities, $arguments = []): bool
    {
        if (is_string($abilities)) {
            return $this->hasPermissionTo($abilities);
        }

        if (is_array($abilities)) {
            foreach ($abilities as $ability) {
                if ($this->hasPermissionTo($ability)) {
                    return true;
                }
            }

            return false;
        }

        return parent::can($abilities, $arguments);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
            'is_banned' => 'boolean',
            'banned_at' => 'datetime',
        ];
    }
}
