@extends('layouts.app')
@section('title', 'Field Staff home')
@section('content')
<div id="field-staff-dashboard-root"></div>

@php
    $todayFormatted = today()->format('l, j F Y');
@endphp

<script type="application/json" id="field-staff-dashboard-data">
    {!! json_encode([
        'name' => auth()->user()->name,
        'todayFormatted' => $todayFormatted,
        'actions' => [
            [
                'label' => 'Enter Delivery',
                'href' => route('field.delivery.create'),
                'description' => 'Record food received at a school.',
                'icon' => 'delivery',
                'featured' => true,
            ],
            [
                'label' => 'Confirm Zero',
                'href' => route('field.zero-confirmation.create'),
                'description' => 'Record that a scheduled item was not delivered.',
                'icon' => 'zero',
            ],
            [
                'label' => 'My Entries',
                'href' => route('field.entries'),
                'description' => 'Review entries you created or are assigned to.',
                'icon' => 'entries',
            ],
            [
                'label' => 'Daily Delivery Report',
                'href' => route('field.report'),
                'description' => 'See the upazila delivery summary.',
                'icon' => 'report',
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection
