<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Announcement;
use App\Models\Comment;
use App\Models\Discount;
use App\Models\File;
use App\Models\Follow;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Group;
use App\Models\Image;
use App\Models\Like;
use App\Models\Note;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\OrderItem;
use App\Models\Page;
use App\Models\Payout;
use App\Models\Policy;
use App\Models\PolicyCategory;
use App\Models\Post;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Read;
use App\Models\Report;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserWarning;
use App\Models\View;
use App\Models\Warning;
use App\Models\WarningConsequence;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\SubscriptionItem;
use Spatie\Activitylog\Models\Activity;
use Throwable;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\warning;

class ResetCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'app:reset
        {--force : Force the operation to run when in production}
        {--all : Delete all data without prompting}
        {--announcements : Delete announcements}
        {--forums : Delete forums and topics}
        {--logs : Delete logs}
        {--orders : Delete orders}
        {--pages : Delete pages}
        {--policies : Delete policies}
        {--posts : Delete blog posts}
        {--products : Delete products}
        {--subscriptions : Delete subscriptions}
        {--support-tickets : Delete support tickets}
        {--users : Delete users (excludes admins)}
        {--warnings : Delete user warnings}';

    protected $description = 'Resets the platform data by deleting selected records.';

    /**
     * @var array<string, array<string, string>>
     */
    private array $dataTypes = [
        'announcements' => ['model' => Announcement::class, 'label' => 'Announcements'],
        'forums' => ['model' => Forum::class, 'label' => 'Forums and Topics'],
        'logs' => ['model' => Activity::class, 'label' => 'Logs'],
        'orders' => ['model' => Order::class, 'label' => 'Orders'],
        'pages' => ['model' => Page::class, 'label' => 'Pages'],
        'policies' => ['model' => Policy::class, 'label' => 'Policies and Categories'],
        'posts' => ['model' => Post::class, 'label' => 'Blog Posts'],
        'products' => ['model' => Product::class, 'label' => 'Products and Discounts'],
        'subscriptions' => ['model' => Subscription::class, 'label' => 'Subscriptions'],
        'support-tickets' => ['model' => SupportTicket::class, 'label' => 'Support Tickets'],
        'users' => ['model' => User::class, 'label' => 'Users (excludes admins)'],
        'warnings' => ['model' => UserWarning::class, 'label' => 'Warnings and History'],
    ];

    /**
     * @throws Throwable
     */
    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::SUCCESS;
        }

        if ($this->option('all')) {
            return $this->resetAll();
        }

        $selectedFromFlags = $this->getSelectedFromFlags();

        if (filled($selectedFromFlags)) {
            return $this->resetSelected($selectedFromFlags);
        }

        return $this->resetInteractive();
    }

    /**
     * @throws Throwable
     */
    private function resetAll(): int
    {
        warning('This will delete ALL data from the platform. This action cannot be undone.');

        DB::transaction(function (): void {
            foreach ($this->dataTypes as $key => $config) {
                $this->deleteData($key, $config);
            }
        });

        $this->components->info('All data has been reset successfully.');

        return self::SUCCESS;
    }

    /**
     * @return int[]|string[]
     */
    private function getSelectedFromFlags(): array
    {
        $selected = [];

        foreach (array_keys($this->dataTypes) as $key) {
            $flagName = str_replace('_', '-', $key);

            if ($this->option($flagName)) {
                $selected[] = $key;
            }
        }

        return $selected;
    }

    /**
     * @param  int[]|string[]  $selected
     *
     * @throws Throwable
     */
    private function resetSelected(array $selected): int
    {
        warning('This will permanently delete the selected data. This action cannot be undone.');

        DB::transaction(function () use ($selected): void {
            foreach ($selected as $key) {
                if (isset($this->dataTypes[$key])) {
                    $this->deleteData($key, $this->dataTypes[$key]);
                }
            }
        });

        $this->components->info('Selected data has been reset successfully.');

        return self::SUCCESS;
    }

    /**
     * @throws Throwable
     */
    private function resetInteractive(): int
    {
        $selected = multiselect(
            label: 'Select the data types you want to reset (delete)',
            options: collect($this->dataTypes)->mapWithKeys(fn ($config, $key): array => [$key => $config['label']])->sort(),
            required: true,
        );

        if (blank($selected)) {
            $this->components->info('No data types selected. Exiting.');

            return self::SUCCESS;
        }

        return $this->resetSelected($selected);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function deleteData(string $key, array $config): void
    {
        $model = $config['model'];
        $label = $config['label'];

        $this->components->task('Deleting '.$label, function () use ($key, $model): void {
            match ($key) {
                'forums' => $this->deleteForums(),
                'orders' => $this->deleteOrders(),
                'policies' => $this->deletePolicies(),
                'posts' => $this->deletePosts(),
                'products' => $this->deleteProducts(),
                'subscriptions' => $this->deleteSubscriptions(),
                'support-tickets' => $this->deleteSupportTickets(),
                'users' => $this->deleteUsers(),
                'warnings' => $this->deleteWarnings(),
                default => $model::query()->delete(),
            };
        });
    }

    private function deleteUsers(): void
    {
        File::query()->whereHasMorph('resource', Group::class)->delete();
        Group::query()->where('is_default_member', false)->where('is_default_guest', false)->delete();
        Report::query()->whereHasMorph('reportable', User::class)->delete();
        User::query()->whereDoesntHave('roles', fn ($query) => $query->where('name', Role::Administrator))->delete();
    }

    private function deleteOrders(): void
    {
        Note::query()->whereHasMorph('notable', Order::class)->delete();
        Order::query()->delete();
        OrderDiscount::query()->delete();
        OrderItem::query()->delete();
    }

    private function deleteForums(): void
    {
        Comment::query()->whereHasMorph('commentable', Post::class)->whereRelation('commentable', fn (Post|Builder $query) => $query->forum())->delete();
        Follow::query()->whereHasMorph('followable', [Forum::class, Topic::class])->delete();
        Forum::query()->delete();
        ForumCategory::query()->delete();
        Image::query()->whereHasMorph('imageable', ForumCategory::class)->delete();
        Like::query()->whereHasMorph('likeable', Post::class)->delete();
        Post::query()->forum()->delete();
        Read::query()->whereHasMorph('readable', [Post::class, Topic::class])->delete();
        Report::query()->whereHasMorph('reportable', Post::class)->delete();
        Topic::query()->delete();
        View::query()->whereHasMorph('viewable', [Post::class, Topic::class])->delete();
    }

    private function deletePosts(): void
    {
        Comment::query()->whereHasMorph('commentable', Post::class)->whereRelation('commentable', fn (Post|Builder $query) => $query->blog())->delete();
        Like::query()->whereHasMorph('likeable', Post::class)->delete();
        Post::query()->blog()->delete();
    }

    private function deleteProducts(): void
    {
        Discount::query()->delete();
        File::query()->whereHasMorph('resource', Product::class)->delete();
        Image::query()->whereHasMorph('imageable', ProductCategory::class)->delete();
        Payout::query()->delete();
        Price::query()->delete();
        Product::query()->delete();
        ProductCategory::query()->delete();
    }

    private function deleteSubscriptions(): void
    {
        Subscription::query()->delete();
        SubscriptionItem::query()->delete();
    }

    private function deletePolicies(): void
    {
        Policy::query()->delete();
        PolicyCategory::query()->delete();
    }

    private function deleteSupportTickets(): void
    {
        Comment::query()->whereHasMorph('commentable', SupportTicket::class)->delete();
        File::query()->whereHasMorph('resource', SupportTicket::class)->delete();
        Note::query()->whereHasMorph('notable', SupportTicket::class)->delete();
        SupportTicket::query()->delete();
        SupportTicketCategory::query()->delete();
    }

    private function deleteWarnings(): void
    {
        UserWarning::query()->delete();
        Warning::query()->delete();
        WarningConsequence::query()->delete();
    }
}
