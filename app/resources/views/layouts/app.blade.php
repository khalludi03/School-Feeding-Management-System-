<!doctype html>
<html lang="en" data-theme="corporate">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', 'School Feeding') · SFP</title>
    @vite('resources/css/app.css')
    <style>
        @media print {
            header, nav, .print\:hidden, .drawer-side, .drawer-toggle, [for="admin-drawer"], [data-sidebar-wrapper] { display: none !important; }
            body { background: #fff !important; }
            .rounded-2xl, .shadow-sm { box-shadow: none !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body class="min-h-screen bg-background text-foreground antialiased">
    @if($aurora ?? true)
        <div id="aurora-root" class="aurora-shell pointer-events-none fixed inset-0 z-0" aria-hidden="true"></div>
    @endif

    @if (auth()->check() && auth()->user()->role === 'admin')
        <div class="drawer lg:drawer-open relative z-10">
            <input id="admin-drawer" type="checkbox" class="drawer-toggle" />
            <div class="drawer-content flex min-h-screen flex-col">
                <header class="sticky top-0 z-20 border-b border-white/70 bg-card/80 backdrop-blur-xl">
                    <div class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6">
                        <div class="flex items-center gap-2">
                            <label for="admin-drawer" class="btn btn-square btn-ghost lg:hidden" aria-label="Open sidebar">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block h-5 w-5 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                            </label>
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 font-semibold tracking-tight text-foreground">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-sm font-bold text-primary-foreground">SFP</span>
                                <span class="hidden sm:inline">School Feeding Management</span>
                            </a>
                        </div>
                        <nav class="flex items-center gap-3 text-sm">
                            <span class="hidden text-muted-foreground sm:inline">{{ auth()->user()->name }}</span>
                            @unless(auth()->user()->must_change_password)
                                @unless(auth()->user()->is_demo)
                                    <a class="font-medium text-primary hover:text-primary-hover hover:underline" href="{{ route('password.profile.edit') }}">Password</a>
                                @endunless
                            @endunless
                            <form method="post" action="{{ route('logout') }}">@csrf
                                <button class="rounded-lg border border-border px-3 py-2 font-medium hover:bg-muted">Sign out</button>
                            </form>
                        </nav>
                    </div>
                </header>
                <main class="flex-1 w-full px-4 py-8 sm:px-6 sm:py-12">
                    @if(session('status'))
                        <div role="status" class="mb-6 rounded-xl border border-success/20 bg-success/10 px-4 py-3 text-sm text-success">{{ session('status') }}</div>
                    @endif
                    @yield('content')
                </main>
            </div>
            <div class="drawer-side z-30">
                <label for="admin-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
                <aside class="bg-muted flex min-h-full w-64 flex-col border-r border-border">
                    <div class="flex h-16 items-center gap-3 border-b border-border px-4">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-sm font-bold text-primary-foreground">SFP</span>
                        <div>
                            <p class="text-xs text-muted-foreground">Workspace</p>
                            <p class="font-semibold">Admin</p>
                        </div>
                    </div>
                    <ul class="menu menu-md w-full grow p-4">
                        <li class="menu-title text-xs font-semibold uppercase tracking-wider text-muted-foreground">Main</li>
                        <li><a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'menu-active' : '' }}">Dashboard</a></li>
                        <li><a href="{{ route('admin.reports.daily') }}" class="{{ request()->routeIs('admin.reports.daily*') ? 'menu-active' : '' }}">Daily report</a></li>

                        <li class="menu-title mt-4 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Forms &amp; reports</li>
                        <li><a href="{{ route('admin.form4.index') }}" class="{{ request()->routeIs('admin.form4*') ? 'menu-active' : '' }}">Form 4 receipts</a></li>
                        <li><a href="{{ route('admin.form7.index') }}" class="{{ request()->routeIs('admin.form7*') ? 'menu-active' : '' }}">Form 7 chalan totals</a></li>
                        <li><a href="{{ route('admin.form10.index') }}" class="{{ request()->routeIs('admin.form10*') ? 'menu-active' : '' }}">Form 10 invoice</a></li>
                        <li><a href="{{ route('admin.form12.index') }}" class="{{ request()->routeIs('admin.form12*') ? 'menu-active' : '' }}">Form 12 stock</a></li>
                        <li><a href="{{ route('admin.form13.index') }}" class="{{ request()->routeIs('admin.form13*') ? 'menu-active' : '' }}">Form 13 consolidated</a></li>

                        <li class="menu-title mt-4 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Administration</li>
                        <li><a href="{{ route('staff.index') }}" class="{{ request()->routeIs('staff.*') ? 'menu-active' : '' }}">Staff accounts</a></li>
                        <li><a href="{{ route('schools.index') }}" class="{{ request()->routeIs('schools.*') ? 'menu-active' : '' }}">Schools</a></li>
                        <li><a href="{{ route('admin.calendar') }}" class="{{ request()->routeIs('admin.calendar*') ? 'menu-active' : '' }}">Working day calendar</a></li>

                        @php $firstCycle = \App\Models\FeedingCycle::orderBy('starts_on')->first() @endphp
                        @if($firstCycle)
                            <li class="menu-title mt-4 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Configuration</li>
                            <li><a href="{{ route('rations.index', $firstCycle) }}" class="{{ request()->routeIs('rations.*') ? 'menu-active' : '' }}">Item rations</a></li>
                            <li><a href="{{ route('prices.index', $firstCycle) }}" class="{{ request()->routeIs('prices.*') ? 'menu-active' : '' }}">Item prices</a></li>
                        @endif
                    </ul>
                    <div class="border-t border-border p-4 text-xs text-muted-foreground">
                        <p>Logged in as</p>
                        <p class="font-semibold text-foreground">{{ auth()->user()->name }}</p>
                    </div>
                </aside>
            </div>
        </div>
    @else
        <div class="relative z-10 min-h-screen">
            <header class="border-b border-white/70 bg-card/80 backdrop-blur-xl">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                    <a href="{{ auth()->check() ? route('home') : route('login') }}" class="flex items-center gap-3 font-semibold tracking-tight text-foreground">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-sm font-bold text-primary-foreground">SFP</span>
                        <span>School Feeding<span class="hidden sm:inline"> Management</span></span>
                    </a>
                    @auth
                        <nav class="flex items-center gap-3 text-sm">
                            <span class="hidden text-muted-foreground sm:inline">{{ auth()->user()->name }}</span>
                            @unless(auth()->user()->must_change_password)
                                @unless(auth()->user()->is_demo)
                                    <a class="font-medium text-primary hover:text-primary-hover hover:underline" href="{{ route('password.profile.edit') }}">Password</a>
                                @endunless
                            @endunless
                            <form method="post" action="{{ route('logout') }}">@csrf
                                <button class="rounded-lg border border-border px-3 py-2 font-medium hover:bg-muted">Sign out</button>
                            </form>
                        </nav>
                    @endauth
                </div>
            </header>
            <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
                @if(session('status'))
                    <div role="status" class="mb-6 rounded-xl border border-success/20 bg-success/10 px-4 py-3 text-sm text-success">{{ session('status') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    @endif
</body>
</html>
