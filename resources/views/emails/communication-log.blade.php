@php
    $schoolName = $communicationLog->school?->name ?? config('app.name');
@endphp

<p>Hello {{ $communicationLog->recipient_name ?: 'there' }},</p>

<p>{!! nl2br(e($communicationLog->body ?: $communicationLog->subject ?: 'You have a new school notification.')) !!}</p>

@if(filled(data_get($communicationLog->metadata, 'payment_url')))
    <p><a href="{{ data_get($communicationLog->metadata, 'payment_url') }}">Open payment link</a></p>
@endif

@if(filled(data_get($communicationLog->metadata, 'portal_url')))
    <p><a href="{{ data_get($communicationLog->metadata, 'portal_url') }}">Open portal</a></p>
@endif

<p>Regards,<br>{{ $schoolName }}</p>
