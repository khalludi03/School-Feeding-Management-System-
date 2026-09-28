<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#f8fafc">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#0b1120">
    <title>@yield('title') · SFP</title>
        <meta name="color-scheme" content="light dark">
    <script>
        (function() {
            try {
                var stored = localStorage.getItem('sfp-theme');
                var isDark = stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.style.colorScheme = 'dark';
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.style.colorScheme = 'light';
                }
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    <style>
        .aurora-shell {
            opacity: 1;
            transition: opacity 0.5s ease-in;
        }
        @media print {
            header, nav, .print\:hidden, [data-sidebar-wrapper] { display: none !important; }
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

    @if (auth()->check())
        @php
            $firstCycle = \App\Models\FeedingCycle::orderBy('starts_on')->first();
        @endphp
        <div class="relative z-10 flex min-h-screen">
            <div id="desktop-sidebar-root" class="hidden lg:block lg:w-64"
                 data-role="{{ auth()->user()->role }}"
                 data-has-cycle="{{ $firstCycle ? 'true' : 'false' }}"
                 data-first-cycle-id="{{ $firstCycle ? $firstCycle->id : '' }}"
                 data-user-name="{{ auth()->user()->name }}"
                 data-current-path="{{ request()->path() === '/' ? '/' : '/'.request()->path() }}">
                <aside class="flex min-h-full w-64 flex-col border-r border-border bg-card">
                    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-border px-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary text-sm font-bold text-primary-foreground">SFP</span>
                        <div class="min-w-0">
                            <p class="truncate text-xs text-muted-foreground">Workspace</p>
                            <p class="truncate font-semibold">{{ auth()->user()->role === 'admin' ? 'Admin' : 'Field Staff' }}</p>
                        </div>
                    </div>
                    <nav class="flex-1 p-4"></nav>
                    <div class="shrink-0 border-t border-border p-4 text-xs text-muted-foreground">
                        <p>Logged in as</p>
                        <p class="truncate font-semibold text-foreground">{{ auth()->user()->name }}</p>
                    </div>
                </aside>
            </div>
                 
            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-20 border-b border-border bg-card/80 backdrop-blur-xl">
                    <div class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6">
                        <div class="flex items-center gap-2">
                            <div id="mobile-sidebar-root" class="lg:hidden"
                                 data-role="{{ auth()->user()->role }}"
                                 data-has-cycle="{{ $firstCycle ? 'true' : 'false' }}"
                                 data-first-cycle-id="{{ $firstCycle ? $firstCycle->id : '' }}"
                                 data-user-name="{{ auth()->user()->name }}"
                                 data-current-path="{{ request()->path() === '/' ? '/' : '/'.request()->path() }}"></div>
                                 
                            <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('staff.home') }}" class="flex items-center gap-3 font-semibold tracking-tight text-foreground">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-sm font-bold text-primary-foreground">SFP</span>
                                <span class="hidden sm:inline">School Feeding Management</span>
                            </a>
                        </div>
                        <div class="flex items-center gap-1 sm:gap-2">
                            {{-- Signed-in users get the single theme switch from inside the user menu island --}}
                            @include('components.layout.user-menu')
                        </div>
                    </div>
                </header>
                <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 sm:py-12">
                    @if(session('status'))<script>window.__FLASH__ = @json(session('status'));</script>@endif
                    @yield('content')
                </main>
            </div>
        </div>
    @else
        <div class="relative z-10 min-h-screen">
            <header class="border-b border-border bg-card/80 backdrop-blur-xl">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                    <a href="{{ route('login') }}" class="flex items-center gap-3 font-semibold tracking-tight text-foreground">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-sm font-bold text-primary-foreground">SFP</span>
                        <span>School Feeding<span class="hidden sm:inline"> Management</span></span>
                    </a>
                    <div class="flex items-center gap-1 sm:gap-2">
                        
                            <div data-theme-toggle-root>
                                <button class="inline-flex h-9 w-9 items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-[1.2rem] w-[1.2rem]"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                                    <span class="sr-only">Toggle theme</span>
                                </button>
                            </div>
                    </div>
                </div>
            </header>
            <main class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
                @if(session('status'))<script>window.__FLASH__ = @json(session('status'));</script>@endif
                @yield('content')
            </main>
        </div>
    @endif

    <div id="toaster-root"></div>
</body>
</html>
