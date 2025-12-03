<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\UserData;
use App\Models\Field;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function show(User $user): Response
    {
        $user->loadMissing(['fields' => function (BelongsToMany|Field $query): void {
            $query->where('is_public', true)->ordered();
        }]);

        $user->loadMissing(['groups' => function (BelongsToMany|Group $query): void {
            $query->active()->visible()->ordered();
        }]);

        $user->setRelation('fields', $user->fields->map(function (Field $field): Field {
            $field->setAttribute('value', $field->pivot->value);

            return $field;
        }));

        return Inertia::render('users/show', [
            'user' => UserData::from($user),
        ]);
    }
}
