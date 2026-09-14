<x-mail::message>
# Welcome, {{ $user->name }}

Your {{ config('app.name') }} account is ready.

Browse what is on offer, enrol, and your progress is kept as you go.

<x-mail::button :url="route('courses.index')">
Browse courses
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
