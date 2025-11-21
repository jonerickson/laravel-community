<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\FieldData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateProfileRequest;
use App\Models\Field;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ProfileController extends Controller
{
    public function __construct(
        #[CurrentUser]
        private readonly User $user,
    ) {
        //
    }

    public function edit(Request $request): Response
    {
        $this->user->load(['fields' => function (BelongsToMany|Field $query): void {
            $query->ordered();
        }]);

        $fields = Field::query()
            ->ordered()
            ->get()
            ->map(function (Field $field): FieldData {
                $userField = $this->user->fields->firstWhere('id', $field->id);
                $fieldData = FieldData::from($field);
                $fieldData->value = $userField?->pivot->value;

                return $fieldData;
            })
            ->toArray();

        return Inertia::render('settings/profile', [
            'status' => $request->session()->get('status'),
            'fields' => $fields,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $data = [
            'name' => $request->validated('name'),
            'signature' => $request->validated('signature'),
        ];

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->storePublicly('avatars');
            $data['avatar'] = $path;
        }

        $this->user->update($data);

        if ($request->has('fields')) {
            foreach ($request->validated('fields', []) as $fieldId => $value) {
                $this->user->fields()->syncWithoutDetaching([
                    (int) $fieldId => ['value' => $value],
                ]);
            }
        }

        return back()->with('message', 'Your profile was successfully updated.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->user->delete();

        $request->session()->regenerate();

        return to_route('login');
    }
}
