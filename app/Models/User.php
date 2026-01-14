<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\SubscriptionData;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\Role;
use App\Enums\WarningConsequenceType;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Facades\PaymentProcessor;
use App\Managers\PaymentManager;
use App\Traits\Blacklistable;
use App\Traits\CanBePaid;
use App\Traits\HasAvatar;
use App\Traits\HasEmailAuthentication;
use App\Traits\HasGroups;
use App\Traits\HasLogging;
use App\Traits\HasMultiFactorAuthentication;
use App\Traits\HasPermissions;
use App\Traits\HasReferenceId;
use App\Traits\Integrations\DiscordUser;
use App\Traits\LogsAuthActivity;
use App\Traits\Payments\StripeCustomer;
use App\Traits\Reportable;
use App\Traits\Searchable;
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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Override;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property int $id
 * @property string $reference_id
 * @property string $name
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $signature
 * @property string|null $password
 * @property string|null $remember_token
 * @property string|null $app_authentication_secret
 * @property array<array-key, mixed>|null $app_authentication_recovery_codes
 * @property bool $has_email_authentication
 * @property string|null $avatar
 * @property string|null $stripe_id
 * @property bool $payouts_enabled
 * @property string|null $external_payout_account_id
 * @property Carbon|null $external_payout_account_onboarded_at
 * @property array<array-key, mixed>|null $external_payout_account_capabilities
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property string|null $pm_expiration
 * @property string|null $billing_address
 * @property string|null $billing_address_line_2
 * @property string|null $billing_city
 * @property string|null $billing_state
 * @property string|null $billing_postal_code
 * @property string|null $billing_country
 * @property string|null $extra_billing_information
 * @property string|null $invoice_emails
 * @property string|null $vat_id
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $onboarded_at
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read WarningConsequence|null $active_consequence
 * @property-read WarningConsequenceType|null $active_consequence_type
 * @property-read Collection<int, UserWarning> $activeWarnings
 * @property-read int|null $active_warnings_count
 * @property-read Collection<int, UserWarning> $activeWarningsWithActiveConsequence
 * @property-read int|null $active_warnings_with_active_consequence_count
 * @property-read Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Collection<int, Report> $approvedReports
 * @property-read int|null $approved_reports_count
 * @property-read string|null $avatar_url
 * @property-read Blacklist|null $blacklist
 * @property-read Collection<int, \Laravel\Passport\Client> $clients
 * @property-read int|null $clients_count
 * @property-read Collection<int, Commission> $commissions
 * @property-read int|null $commissions_count
 * @property float $current_balance
 * @property-read SubscriptionData|null $current_subscription
 * @property-read \App\Data\GroupStyleData|null $display_style
 * @property-read bool $external_payout_account_onboarding_complete
 * @property-read Collection<int, Field> $fields
 * @property-read int|null $fields_count
 * @property-read Collection<int, Fingerprint> $fingerprints
 * @property-read int|null $fingerprints_count
 * @property-read UserGroup|null $pivot
 * @property-read Collection<int, Group> $groups
 * @property-read int|null $groups_count
 * @property-read bool $has_password
 * @property-read Collection<int, UserIntegration> $integrations
 * @property-read int|null $integrations_count
 * @property-read bool $is_blacklisted
 * @property-read bool $is_reported
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, \Laravel\Passport\Client> $oauthApps
 * @property-read int|null $oauth_apps_count
 * @property-read Collection<int, Order> $orders
 * @property-read int|null $orders_count
 * @property-read Collection<int, Payout> $payouts
 * @property-read int|null $payouts_count
 * @property-read Collection<int, Report> $pendingReports
 * @property-read int|null $pending_reports_count
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, Report> $rejectedReports
 * @property-read int|null $rejected_reports_count
 * @property-read Collection<int, Report> $reports
 * @property-read int|null $reports_count
 * @property-read Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read Collection<int, SupportTicket> $tickets
 * @property-read int|null $tickets_count
 * @property-read Collection<int, \Laravel\Passport\Token> $tokens
 * @property-read int|null $tokens_count
 * @property-read Collection<int, UserWarning> $userWarnings
 * @property-read int|null $user_warnings_count
 * @property-read int $warning_points
 * @property-read Collection<int, Product> $products
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
 * @method static Builder<static>|User whereExternalPayoutAccountCapabilities($value)
 * @method static Builder<static>|User whereExternalPayoutAccountId($value)
 * @method static Builder<static>|User whereExternalPayoutAccountOnboardedAt($value)
 * @method static Builder<static>|User whereExtraBillingInformation($value)
 * @method static Builder<static>|User whereHasEmailAuthentication($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereInvoiceEmails($value)
 * @method static Builder<static>|User whereLastSeenAt($value)
 * @method static Builder<static>|User whereName($value)
 * @method static Builder<static>|User whereOnboardedAt($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User wherePayoutsEnabled($value)
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
    use Blacklistable;
    use CanBePaid;
    use DiscordUser;
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
    use Impersonate;
    use LogsAuthActivity;
    use Notifiable;
    use Reportable;
    use Searchable;
    use StripeCustomer;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'signature',
        'password',
        'stripe_id',
        'payouts_enabled',
        'external_payout_account_id',
        'external_payout_account_onboarded_at',
        'external_payout_account_capabilities',
        'extra_billing_information',
        'billing_address',
        'billing_address_line_2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'vat_id',
        'trial_ends_at',
        'last_seen_at',
        'onboarded_at',
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
            return $this->hasAnyRole(Role::Administrator, Role::SupportAgent);
        }

        return $panel->getId() === 'marketplace';
    }

    public function canImpersonate(): bool
    {
        return $this->hasRole(Role::Administrator);
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
            through: [Order::class, OrderItem::class, Price::class],
            foreignKeys: ['user_id', 'order_id', 'id', 'id'],
            localKeys: ['id', 'id', 'price_id', 'product_id']
        )
            ->where('orders.status', OrderStatus::Succeeded)
            ->where('products.type', ProductType::Product)
            ->distinct();
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(UserIntegration::class);
    }

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(Field::class, 'users_fields')
            ->withPivot('value')
            ->withTimestamps()
            ->orderBy('order');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'created_by');
    }

    public function userWarnings(): HasMany
    {
        return $this->hasMany(UserWarning::class);
    }

    public function activeWarnings(): HasMany
    {
        return $this->userWarnings()->active();
    }

    public function activeWarningsWithActiveConsequence(): HasMany
    {
        return $this->activeWarnings()->activeConsequence();
    }

    public function warningPoints(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->activeWarnings->sum(fn (UserWarning $warning) => $warning->warning->points)
        )->shouldCache();
    }

    public function activeConsequence(): Attribute
    {
        return Attribute::make(
            get: fn (): ?WarningConsequence => $this->activeWarningsWithActiveConsequence
                ->sortByDesc('consequence_expires_at')
                ->first()
                ?->warningConsequence)->shouldCache();
    }

    public function activeConsequenceType(): Attribute
    {
        return Attribute::get(fn (): ?WarningConsequenceType => $this->active_consequence->type ?? null)
            ->shouldCache();
    }

    public function hasPassword(): Attribute
    {
        return Attribute::get(fn (): bool => $this->password !== null);
    }

    public function currentSubscription(): Attribute
    {
        return Attribute::get(fn (): ?SubscriptionData => PaymentProcessor::currentSubscription($this));
    }

    /**
     * @return array<int, string>
     */
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
        return 'User account '.$eventName;
    }

    public function getActivityLogName(): string
    {
        return 'user';
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'payouts_enabled' => 'boolean',
            'external_payout_account_onboarded_at' => 'datetime',
            'external_payout_account_capabilities' => 'array',
        ];
    }
}
