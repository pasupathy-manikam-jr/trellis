@php($lesson = $reply->lesson)
@php($course = $lesson->section->course)
<x-mail::message>
# You have an answer

**{{ $reply->author->name }}** replied to your question on **{{ $lesson->title }}**.

<x-mail::panel>
{{ $reply->body }}
</x-mail::panel>

<x-mail::button :url="route('learn.lesson', [$course, $lesson])">
Read the thread
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
