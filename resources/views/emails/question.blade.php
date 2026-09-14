@php($lesson = $question->lesson)
@php($course = $lesson->section->course)
<x-mail::message>
# A learner is stuck

**{{ $question->author->name }}** asked a question on **{{ $lesson->title }}**
in {{ $course->title }}.

<x-mail::panel>
{{ $question->body }}
</x-mail::panel>

<x-mail::button :url="route('learn.lesson', [$course, $lesson])">
Answer the question
</x-mail::button>

Replying to this email reaches them directly.

{{ config('app.name') }}
</x-mail::message>
