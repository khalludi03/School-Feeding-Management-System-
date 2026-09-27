@props([
    'variant' => 'default',
])

@php
$classes = match ($variant) {
    'default' => 'border-transparent bg-primary text-primary-foreground',
    'secondary' => 'border-transparent bg-secondary text-secondary-foreground',
    'destructive' => 'border-transparent bg-destructive text-destructive-foreground',
    'warning' => 'border-transparent bg-warning text-warning-foreground',
    'success' => 'border-transparent bg-success text-success-foreground',
    'neutral' => 'border-border bg-muted text-muted-foreground',
    'outline' => 'border-border bg-transparent text-muted-foreground',
};

$base = 'inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-xs font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 select-none';
@endphp

<span {{ $attributes->merge(['class' => $base . ' ' . $classes]) }}>
    {{ $slot }}
</span>
