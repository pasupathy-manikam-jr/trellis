<x-mail::message>
# Nicely done

You finished **{{ $certificate->course->title }}**.

Your certificate is ready. Anyone can confirm it is genuine with the serial below —
no account needed.

<x-mail::panel>
{{ $certificate->serial }}
</x-mail::panel>

<x-mail::button :url="route('certificates.verify', $certificate->serial)">
Verify certificate
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
