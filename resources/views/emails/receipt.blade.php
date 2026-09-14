<x-mail::message>
# You're enrolled

**{{ $order->course->title }}** is now on your dashboard.

<x-mail::table>
| Item | |
|:-----|--:|
| Course | {{ $order->course->title }} |
| Price | {{ number_format($order->subtotal_cents / 100, 2) }} {{ $order->currency }} |
@if ($order->discount_cents > 0)
| Discount{{ $order->coupon ? ' ('.$order->coupon->code.')' : '' }} | −{{ number_format($order->discount_cents / 100, 2) }} {{ $order->currency }} |
@endif
| **Total** | **{{ number_format($order->total_cents / 100, 2) }} {{ $order->currency }}** |
</x-mail::table>

<x-mail::button :url="route('learn.show', $order->course)">
Start learning
</x-mail::button>

Order #{{ $order->id }} · {{ $order->paid_at?->format('j M Y') }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
