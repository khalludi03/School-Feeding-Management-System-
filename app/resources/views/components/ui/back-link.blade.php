@props([
    'href',
    'label' => 'Back',
])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-md border border-border bg-card px-3 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-muted hover:text-primary']) }}
>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
        <path d="m12 19-7-7 7-7" />
        <path d="M19 12H5" />
    </svg>
    <span>{{ $label }}</span>
</a>
