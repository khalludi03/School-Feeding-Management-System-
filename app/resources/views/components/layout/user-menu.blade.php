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

<div id="user-menu-root"></div>
