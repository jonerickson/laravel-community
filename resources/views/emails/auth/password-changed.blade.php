<x-mail::message>
# Password Changed Successfully

Hello {{ $user->name }},

This is a confirmation that your password has been successfully changed on {{ now()->format('F j, Y \a\t g:i A') }}.

If you did not make this change, please contact our support team immediately to secure your account.

<x-mail::button :url="route('settings.profile.edit')">
Update password
</x-mail::button>

For your security, we recommend:
- Using a strong, unique password
- Enabling two-factor authentication if available
- Not sharing your password with anyone

Thanks,
<br />
{{ config('app.name') }}
</x-mail::message>
