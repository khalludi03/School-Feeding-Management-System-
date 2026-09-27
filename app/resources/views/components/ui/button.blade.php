@props([
    'variant' => 'default',
    'size' => 'default',
    'type' => 'button',
    'href' => null,
])

@php
$classes = match ($variant) {
    'default' => 'bg-primary text-primary-foreground hover:bg-primary-hover',
    'success' => 'bg-success text-success-foreground hover:bg-success/90',
    'destructive' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
    'outline' => 'border border-border bg-card text-foreground hover:bg-muted hover:text-foreground',
    'secondary' => 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
    'ghost' => 'hover:bg-muted hover:text-foreground',
    'link' => 'text-primary underline-offset-4 hover:underline',
};

$sizeClasses = match ($size) {
    'default' => 'h-9 px-4 py-2',
    'xs' => 'h-6 gap-1 rounded-md px-2 text-xs',
    'sm' => 'h-8 gap-1.5 rounded-md px-3',
    'lg' => 'h-10 rounded-md px-6',
    'icon' => 'size-9 justify-center',
};

$base = 'inline-flex shrink-0 items-center justify-center gap-2 rounded-md text-sm font-medium whitespace-nowrap transition-colors outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=size-])]:size-4';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base . ' ' . $classes . ' ' . $sizeClasses]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base . ' ' . $classes . ' ' . $sizeClasses]) }}>
        {{ $slot }}
    </button>
@endif
