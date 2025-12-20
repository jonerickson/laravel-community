@php
    use App\Models\Topic;
    $isNewTopic = $content instanceof Topic;
    $followableName = $followable instanceof Topic ? $followable->title : $followable->name;
    $followableType = $followable instanceof Topic ? 'topic' : 'forum';
@endphp

<x-mail::message>
# New {{ $isNewTopic ? 'Topic' : 'Reply' }}

Hello {{ $recipient->name }},

{{ $content->author->name }} has posted a new {{ $isNewTopic ? 'topic' : 'reply' }} in a {{ $followableType }} you're following.

**Author:** {{ $content->author->name }}<br>
@if($isNewTopic)
**Topic:** {{ $content->title }}

@if($content->posts->isNotEmpty())
<x-mail::panel>
{!! Str::of($content->posts->first()->content)->stripTags()->limit()->toString() !!}
</x-mail::panel>
@endif
@else
**Topic:** {{ $content->topic->title }}

<x-mail::panel>
{!! Str::of($content->content)->stripTags()->limit()->toString() !!}
</x-mail::panel>
@endif

<x-mail::button :url="$isNewTopic ? route('forums.topics.show', ['forum' => $content->forum->slug, 'topic' => $content->slug]) : route('forums.topics.show', ['forum' => $content->topic->forum->slug, 'topic' => $content->topic->slug]) . '#'.$content->id">
View {{ $isNewTopic ? 'topic' : 'reply' }}
</x-mail::button>

You're receiving this email because you're following this {{ $followableType }}.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
