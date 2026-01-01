# Group Permissions Architecture - Usage Guide

## Overview

This system allows you to pass group-based permissions (read, write, delete) from your backend to the frontend via DTOs. It's designed to be reusable for any resource that has group-based permissions.

## Components

### 1. **GroupPermissionsData** (`app/Data/GroupPermissionsData.php`)
A simple DTO with three boolean fields:
- `canRead`: Can the user view content in this resource?
- `canWrite`: Can the user create/edit content in this resource?
- `canDelete`: Can the user delete content in this resource?

### 2. **HasGroupPermissions** Trait (`app/Traits/HasGroupPermissions.php`)
Provides methods to calculate permissions based on:
- The authenticated user's groups
- The resource's attached groups
- The pivot table permissions (read, write, delete)

### 3. **ForumData** (Updated)
Now includes a `groupPermissions` field that will be populated with the user's effective permissions.

## Usage in Controllers

### Basic Example

```php
use App\Data\ForumData;
use App\Data\GroupPermissionsData;
use App\Models\Forum;

public function show(Forum $forum)
{
    $this->authorize('view', $forum);

    $forum->load('groups'); // Ensure groups are loaded

    return Inertia::render('forums/show', [
        'forum' => ForumData::from($forum)->additional([
            'groupPermissions' => GroupPermissionsData::from(
                $forum->getGroupPermissions()
            ),
        ]),
    ]);
}
```

### Collection Example

```php
public function index()
{
    $forums = Forum::query()
        ->with('groups')
        ->readableByUser() // Use the scope from HasForumPermissions
        ->get()
        ->map(function (Forum $forum) {
            return ForumData::from($forum)->additional([
                'groupPermissions' => GroupPermissionsData::from(
                    $forum->getGroupPermissions()
                ),
            ]);
        });

    return Inertia::render('forums/index', [
        'forums' => $forums,
    ]);
}
```

### Using Individual Permission Checks

```php
$forum = Forum::find(1);

// Check individual permissions
if ($forum->canUserRead()) {
    // User can read this forum
}

if ($forum->canUserWrite()) {
    // User can create topics/posts in this forum
}

if ($forum->canUserDelete()) {
    // User can delete content in this forum
}
```

## Usage in Frontend

Once you've passed the permissions to the frontend, you can use them in your React components:

```tsx
interface ForumPageProps {
    forum: App.Data.ForumData;
}

export default function ForumShow({ forum }: ForumPageProps) {
    return (
        <div>
            <h1>{forum.name}</h1>

            {/* Show create button only if user can write */}
            {forum.groupPermissions?.canWrite && (
                <Button onClick={createNewTopic}>
                    Create Topic
                </Button>
            )}

            {/* Show edit button only if user can write */}
            {forum.groupPermissions?.canWrite && (
                <Button onClick={editForum}>
                    Edit Forum
                </Button>
            )}

            {/* Show delete button only if user can delete */}
            {forum.groupPermissions?.canDelete && (
                <Button onClick={deleteForum} variant="destructive">
                    Delete Forum
                </Button>
            )}
        </div>
    );
}
```

## Extending to Other Models

To add group permissions to another model (e.g., `ForumCategory`):

### 1. Add the trait to your model:

```php
class ForumCategory extends Model
{
    use HasGroups;
    use HasGroupPermissions; // Add this

    // ...
}
```

### 2. Ensure the groups relationship has pivot fields:

In `HasGroups` trait, add your model:

```php
if (static::class === ForumCategory::class) {
    return $relation
        ->withPivot(['read', 'write', 'delete'])
        ->using(ForumCategoryGroup::class);
}
```

### 3. Add to your Data class:

```php
class ForumCategoryData extends Data
{
    // ... existing fields

    public ?GroupPermissionsData $groupPermissions = null;
}
```

### 4. Use in controllers:

```php
return ForumCategoryData::from($category)->additional([
    'groupPermissions' => GroupPermissionsData::from(
        $category->getGroupPermissions()
    ),
]);
```

## Policy Integration

You can also use these permissions in your policies:

```php
class ForumPolicy
{
    public function view(?User $user, Forum $forum): bool
    {
        return $forum->canUserRead($user);
    }

    public function update(?User $user, Forum $forum): bool
    {
        return $forum->canUserWrite($user);
    }

    public function delete(?User $user, Forum $forum): bool
    {
        return $forum->canUserDelete($user);
    }
}
```

## Query Scopes

Use the `readableByUser()` scope to filter resources:

```php
// Get all forums the user can read
$forums = Forum::readableByUser()->get();

// With specific user
$forums = Forum::readableByUser($someUser)->get();
```

## How Permissions Are Calculated

The `getGroupPermissions()` method:

1. Gets the authenticated user's groups
2. Finds the intersection between user groups and resource groups
3. Checks the pivot table for `read`, `write`, `delete` permissions
4. Returns `true` if **ANY** of the user's groups grant the permission

Example:
- User is in groups: [1, 2, 3]
- Forum has groups: [2, 4]
- Intersection: [2]
- Group 2 pivot: `read=1, write=1, delete=0`
- Result: `canRead=true, canWrite=true, canDelete=false`

## Benefits

✅ **Reusable**: Works with any model using `HasGroups` + `HasGroupPermissions`
✅ **Type-Safe**: Full TypeScript support in frontend
✅ **Consistent**: Uses existing DTO pattern
✅ **Performant**: Efficient queries with proper eager loading
✅ **Flexible**: Works with individual models or collections
✅ **Frontend-Ready**: Permissions available in React components
