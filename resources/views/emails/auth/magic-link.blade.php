<x-mail::message>
# Sign in to {{ config('app.name') }}

Hello {{ $user->name }},

Click the button below to sign in to your account. This link will expire in 15 minutes.

<x-mail::button :url="$url">
Sign in to {{ config('app.name') }}
</x-mail::button>

If you did not request this link, you can safely ignore this email.

Thanks,
<br />
{{ config('app.name') }}
</x-mail::message>