<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', 'School Feeding') · SFP</title>
    @vite('resources/css/app.css')
    <style>
        @media print {
            header, nav, .print\:hidden { display: none !important; }
            body { background: #fff !important; }
            .rounded-2xl, .shadow-sm { box-shadow: none !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    @if($aurora ?? true)
        <div id="aurora-root" class="aurora-shell pointer-events-none fixed inset-0 z-0" aria-hidden="true"></div>
    @endif
    <div class="relative z-10 min-h-screen">
        <header class="border-b border-white/70 bg-white/80 backdrop-blur-xl">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <a href="{{ auth()->check() ? route('home') : route('login') }}" class="flex items-center gap-3 font-semibold tracking-tight text-slate-950">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-700 text-sm font-bold text-white">SFP</span>
                    <span>School Feeding<span class="hidden sm:inline"> Management</span></span>
                </a>
                @auth
                    <nav class="flex items-center gap-3 text-sm">
                        <span class="hidden text-slate-600 sm:inline">{{ auth()->user()->name }}</span>
                        @role('admin')
                            <a class="font-medium text-blue-700 hover:underline" href="{{ route('admin.calendar') }}">Calendar</a>
                        @endrole
                        @unless(auth()->user()->must_change_password)
                            @unless(auth()->user()->is_demo)
                                <a class="font-medium text-blue-700 hover:underline" href="{{ route('password.profile.edit') }}">Password</a>
                            @endunless
                        @endunless
                        <form method="post" action="{{ route('logout') }}">@csrf
                            <button class="rounded-lg border border-slate-300 px-3 py-2 font-medium hover:bg-slate-100">Sign out</button>
                        </form>
                    </nav>
                @endauth
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
            @if(session('status'))
                <div role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
