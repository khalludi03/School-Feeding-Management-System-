@php
    $user = auth()->user();
    $canChangePassword = ! $user->must_change_password && ! $user->is_demo;
@endphp

<form id="logout-form" method="post" action="{{ route('logout') }}" class="hidden">
    @csrf
</form>

<script type="application/json" id="user-menu-data">
    {!! json_encode([
        'name' => $user->name,
        'canChangePassword' => $canChangePassword,
        'passwordUrl' => route('password.profile.edit'),
    ], JSON_UNESCAPED_UNICODE) !!}
</script>


@php
    $initials = collect(explode(' ', $user->name))->map(fn($s) => mb_substr($s, 0, 1))->take(2)->join('');
@endphp
<div id="user-menu-root">
    <button class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
        <span class="flex h-full w-full items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground">
            {{ strtoupper($initials) }}
        </span>
    </button>
</div>
