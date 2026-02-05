<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\FieldData;
use App\Enums\DiscordNameSyncDirection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateProfileRequest;
use App\Jobs\Discord\SyncName;
use App\Models\Field;
use App\Models\User;
use App\Settings\IntegrationSettings;
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

    public function edit(Request $request, IntegrationSettings $settings): Response
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
            'nameLockedByDiscord' => ! $this->canChangeName($settings),
        ]);
    }

    public function update(UpdateProfileRequest $request, IntegrationSettings $settings): RedirectResponse
    {
        $nameChanged = $request->validated('name') !== $this->user->name;

        $data = [
            'signature' => $request->validated('signature'),
        ];

        if ($this->canChangeName($settings)) {
            $data['name'] = $request->validated('name');
        }

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

        if ($nameChanged && $this->shouldSyncNameToDiscord($settings)) {
            SyncName::dispatch($this->user->getKey());
        }

        return back()->with('message', 'Your profile was successfully updated.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->user->delete();

        $request->session()->regenerate();

        return to_route('login');
    }

    protected function canChangeName(IntegrationSettings $settings): bool
    {
        if (! $settings->discord_name_sync_enabled) {
            return true;
        }

        if (! $settings->discord_name_sync_enforced) {
            return true;
        }

        if ($settings->discord_name_sync_direction !== DiscordNameSyncDirection::DiscordToApp) {
            return true;
        }

        $hasDiscordIntegration = $this->user->integrations()->where('provider', 'discord')->exists();

        return ! $hasDiscordIntegration;
    }

    protected function shouldSyncNameToDiscord(IntegrationSettings $settings): bool
    {
        if (! $settings->discord_name_sync_enabled) {
            return false;
        }

        if ($settings->discord_name_sync_direction !== DiscordNameSyncDirection::AppToDiscord) {
            return false;
        }

        return $this->user->integrations()->where('provider', 'discord')->exists();
    }
}
