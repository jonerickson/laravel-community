<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\SubscriptionData;
use App\Enums\OrderStatus;
use App\Enums\WarningConsequenceType;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Facades\PaymentProcessor;
use App\Managers\PaymentManager;
use App\Traits\HasAvatar;
use App\Traits\HasEmailAuthentication;
use App\Traits\HasGroups;
use App\Traits\HasLogging;
use App\Traits\HasMultiFactorAuthentication;
use App\Traits\HasPermissions;
use App\Traits\HasReferenceId;
use App\Traits\LogsAuthActivity;
use App\Traits\Reportable;
use Exception;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication as EmailAuthenticationContract;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar as FilamentAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Billable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Override;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property int $id
 * @property string $reference_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $signature
 * @property string|null $password
 * @property string|null $remember_token
 * @property string|null $app_authentication_secret
 * @property array<array-key, mixed>|null $app_authentication_recovery_codes
 * @property bool $has_email_authentication
 * @property string|null $avatar
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property string|null $pm_expiration
 * @property string|null $extra_billing_information
 * @property Carbon|null $trial_ends_at
 * @property string|null $billing_address
 * @property string|null $billing_address_line_2
 * @property string|null $billing_city
 * @property string|null $billing_state
 * @property string|null $billing_postal_code
 * @property string|null $vat_id
 * @property string|null $invoice_emails
 * @property string|null $billing_country
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read WarningConsequence|null $active_consequence
 * @property-read WarningConsequenceType|null $active_consequence_type
 * @property-read Collection<int, UserWarning> $activeWarnings
 * @property-read int|null $active_warnings_count
 * @property-read Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Collection<int, Report> $approvedReports
 * @property-read int|null $approved_reports_count
 * @property-read string|null $avatar_url
 * @property-read Collection<int, \Laravel\Passport\Client> $clients
 * @property-read int|null $clients_count
 * @property-read SubscriptionData|null $current_subscription
 * @property-read Collection<int, Fingerprint> $fingerprints
 * @property-read int|null $fingerprints_count
 * @property-read Collection<int, Group> $groups
 * @property-read int|null $groups_count
 * @property-read Collection<int, UserIntegration> $integrations
 * @property-read int|null $integrations_count
 * @property-read bool $is_banned
 * @property-read bool $is_reported
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, \Laravel\Passport\Client> $oauthApps
 * @property-read int|null $oauth_apps_count
 * @property-read Collection<int, Order> $orders
 * @property-read int|null $orders_count
 * @property-read Collection<int, Report> $pendingReports
 * @property-read int|null $pending_reports_count
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, Report> $rejectedReports
 * @property-read int|null $rejected_reports_count
 * @property-read int $report_count
 * @property-read Collection<int, Report> $reports
 * @property-read int|null $reports_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read Collection<int, \Laravel\Passport\Token> $tokens
 * @property-read int|null $tokens_count
 * @property-read Collection<int, UserWarning> $userWarnings
 * @property-read int|null $user_warnings_count
 * @property-read int $warning_points
 * @property-read Collection|Product[] $products
 * @property-read int|null $products_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User hasExpiredGenericTrial()
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User onGenericTrial()
 * @method static Builder<static>|User permission($permissions, $without = false)
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static Builder<static>|User whereAppAuthenticationRecoveryCodes($value)
 * @method static Builder<static>|User whereAppAuthenticationSecret($value)
 * @method static Builder<static>|User whereAvatar($value)
 * @method static Builder<static>|User whereBillingAddress($value)
 * @method static Builder<static>|User whereBillingAddressLine2($value)
 * @method static Builder<static>|User whereBillingCity($value)
 * @method static Builder<static>|User whereBillingCountry($value)
 * @method static Builder<static>|User whereBillingPostalCode($value)
 * @method static Builder<static>|User whereBillingState($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereExtraBillingInformation($value)
 * @method static Builder<static>|User whereHasEmailAuthentication($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereInvoiceEmails($value)
 * @method static Builder<static>|User whereName($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User wherePmExpiration($value)
 * @method static Builder<static>|User wherePmLastFour($value)
 * @method static Builder<static>|User wherePmType($value)
 * @method static Builder<static>|User whereReferenceId($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereSignature($value)
 * @method static Builder<static>|User whereStripeId($value)
 * @method static Builder<static>|User whereTrialEndsAt($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @method static Builder<static>|User whereVatId($value)
 * @method static Builder<static>|User withoutPermission($permissions)
 * @method static Builder<static>|User withoutRole($roles, $guard = null)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements EmailAuthenticationContract, FilamentAvatar, FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasName, MustVerifyEmail, OAuthenticatable
{
    use Billable;
    use HasApiTokens;
    use HasAvatar;
    use HasEmailAuthentication;
    use HasFactory;
    use HasGroups;
    use HasLogging;
    use HasMultiFactorAuthentication;
    use HasPermissions;
    use HasReferenceId;
    use HasRelationships;
    use LogsAuthActivity;
    use Notifiable;
    use Reportable;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'signature',
        'password',
        'is_banned',
        'banned_at',
        'ban_reason',
        'banned_by',
        'extra_billing_information',
        'billing_address',
        'billing_address_line_2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'vat_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'pm_expiration',
        'has_email_authentication',
        'app_authentication_recovery_codes',
        'app_authentication_secret',
    ];

    protected $appends = [
        'is_banned',
        'active_consequence',
        'active_consequence_type',
    ];

    protected $with = [
        'groups',
    ];

    protected $dispatchesEvents = [
        'created' => UserCreated::class,
        'updated' => UserUpdated::class,
        'deleting' => UserDeleted::class,
    ];

    /**
     * @throws Exception
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasRole('super-admin');
        }

        return $panel->getId() === 'marketplace';
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function fingerprints(): HasMany
    {
        return $this->hasMany(Fingerprint::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function products(): HasManyDeep
    {
        return $this->hasManyDeep(
            related: Product::class,
            through: [Order::class, OrderItem::class],
            foreignKeys: ['user_id', 'order_id', 'id'],
            localKeys: ['id', 'id', 'product_id']
        )
            ->where('orders.status', OrderStatus::Succeeded)
            ->distinct();
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(UserIntegration::class);
    }

    public function userWarnings(): HasMany
    {
        return $this->hasMany(UserWarning::class);
    }

    public function activeWarnings(): HasMany
    {
        return $this->userWarnings()->active();
    }

    public function warningPoints(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->activeWarnings()->get()->sum(fn (UserWarning $warning) => $warning->warning->points)
        )->shouldCache();
    }

    public function activeConsequence(): Attribute
    {
        return Attribute::make(
            get: function (): ?WarningConsequence {
                $points = $this->warning_points;

                return WarningConsequence::query()
                    ->active()
                    ->where('threshold', '<=', $points)
                    ->orderByDesc('threshold')
                    ->first();
            }
        )->shouldCache();
    }

    public function activeConsequenceType(): Attribute
    {
        return Attribute::get(fn (): ?WarningConsequenceType => $this->active_consequence->type ?? null)
            ->shouldCache();
    }

    public function isBanned(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->fingerprints()->banned()->exists(),
        )->shouldCache();
    }

    public function currentSubscription(): Attribute
    {
        return Attribute::get(fn (): ?SubscriptionData => PaymentProcessor::currentSubscription($this));
    }

    public function stripeName(): string
    {
        return $this->name;
    }

    public function stripeEmail(): string
    {
        return $this->email;
    }

    public function stripeAddress(): array
    {
        if (blank($this->billing_address)) {
            return [];
        }

        return [
            'city' => $this->billing_city,
            'country' => $this->billing_country,
            'line1' => $this->billing_address,
            'line2' => $this->billing_address_line_2,
            'postal_code' => $this->billing_postal_code,
            'state' => $this->billing_state,
        ];
    }

    public function getLoggedAttributes(): array
    {
        return [
            'name',
            'email',
            'email_verified_at',
            'signature',
            'avatar',
        ];
    }

    public function getActivityDescription(string $eventName): string
    {
        return "User account $eventName";
    }

    public function getActivityLogName(): string
    {
        return 'user';
    }

    #[Override]
    protected static function booted(): void
    {
        static::updated(function (User $user): void {
            if (! $user->hasStripeId() || ! $user->isDirty([
                'name',
                'email',
                'billing_address',
                'billing_address_line_2',
                'billing_city',
                'billing_state',
                'billing_postal_code',
            ])) {
                return;
            }

            app(PaymentManager::class)->syncCustomerInformation($user);
        });
    }

    protected function getDefaultGuardName(): string
    {
        return 'web';
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
