@php
$navItems = [
    ['title' => 'Dashboard', 'href' => route('admin.dashboard'), 'route' => 'admin.dashboard'],
    ['title' => 'Daily report', 'href' => route('admin.reports.daily'), 'route' => 'admin.reports.daily'],
    ['title' => 'Staff accounts', 'href' => route('staff.index'), 'route' => 'staff.index'],
    ['title' => 'Schools', 'href' => route('schools.index'), 'route' => 'schools.index'],
    ['title' => 'Working day calendar', 'href' => route('admin.calendar'), 'route' => 'admin.calendar'],
];
$firstCycle = \App\Models\FeedingCycle::orderBy('starts_on')->first();
@endphp

<div class="group/sidebar-wrapper flex min-h-svh w-full" style="--sidebar-width: 16rem; --sidebar-width-icon: 3rem;" data-sidebar-wrapper>
    <div class="hidden md:flex fixed inset-y-0 z-10 w-[var(--sidebar-width)] flex-col bg-sidebar text-sidebar-foreground transition-[width] duration-200 ease-linear group-data-[-collapsible=offcanvas]:w-0 group-data-[side=right]:rotate-180 group-data-[collapsible=icon]:w-[var(--sidebar-width-icon)] group-data-[side=left]:border-r border-sidebar-border" data-sidebar-container data-variant="sidebar" data-side="left" data-collapsible="offcanvas">
        <div class="flex h-full w-full flex-col bg-sidebar group-data-[variant=floating]:rounded-lg group-data-[variant=floating]:border group-data-[variant=floating]:border-sidebar-border group-data-[variant=floating]:shadow-sm" data-sidebar="sidebar" data-slot="sidebar-inner">
            <div class="flex flex-col gap-2 p-2" data-slot="sidebar-header" data-sidebar="header">
                <div class="flex items-center gap-3 px-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-sm font-bold text-primary-foreground">SFP</div>
                    <div>
                        <p class="text-xs text-sidebar-foreground/70">Workspace</p>
                        <p class="font-semibold text-sidebar-foreground">Admin</p>
                    </div>
                </div>
            </div>
            <div class="flex min-h-0 flex-1 flex-col gap-2 overflow-auto group-data-[collapsible=icon]:overflow-hidden p-2" data-slot="sidebar-content" data-sidebar="content">
                <div class="flex w-full min-w-0 flex-col p-2 gap-1" data-slot="sidebar-group" data-sidebar="group">
                    <div class="flex w-full text-sm" data-slot="sidebar-group-content" data-sidebar="group-content">
                        @foreach($navItems as $item)
                            @php
                                $isActive = request()->routeIs($item['route']);
                            @endphp
                            <a
                                href="{{ $item['href'] }}"
                                class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm ring-sidebar-ring outline-hidden transition-[width,height,padding] group-has-data-[sidebar=menu-action]/menu-item:pr-8 group-data-[collapsible=icon]:size-8! group-data-[collapsible=icon]:p-2! hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 {{ $isActive ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground' : 'text-sidebar-foreground' }} data-[active=true]:bg-sidebar-accent data-[active=true]:font-medium data-[active=true]:text-sidebar-accent-foreground data-[state=open]:hover:bg-sidebar-accent data-[state=open]:hover:text-sidebar-accent-foreground"
                                data-sidebar="menu-button"
                                data-slot="sidebar-menu-button"
                                data-active="{{ $isActive ? 'true' : 'false' }}"
                            >
                                <span class="truncate">{{ $item['title'] }}</span>
                            </a>
                        @endforeach
                        @if($firstCycle)
                            <a href="{{ route('rations.index', $firstCycle) }}" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm ring-sidebar-ring outline-hidden transition-[width,height,padding] group-has-data-[sidebar=menu-action]/menu-item:pr-8 group-data-[collapsible=icon]:size-8! group-data-[collapsible=icon]:p-2! hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 {{ request()->routeIs('rations.*') ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground' : 'text-sidebar-foreground' }}" data-sidebar="menu-button" data-slot="sidebar-menu-button" data-active="{{ request()->routeIs('rations.*') ? 'true' : 'false' }}">
                                <span class="truncate">Item rations</span>
                            </a>
                            <a href="{{ route('prices.index', $firstCycle) }}" class="peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm ring-sidebar-ring outline-hidden transition-[width,height,padding] group-has-data-[sidebar=menu-action]/menu-item:pr-8 group-data-[collapsible=icon]:size-8! group-data-[collapsible=icon]:p-2! hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 {{ request()->routeIs('prices.*') ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground' : 'text-sidebar-foreground' }}" data-sidebar="menu-button" data-slot="sidebar-menu-button" data-active="{{ request()->routeIs('prices.*') ? 'true' : 'false' }}">
                                <span class="truncate">Item prices</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="border-t border-sidebar-border p-2" data-slot="sidebar-footer" data-sidebar="footer">
                <p class="text-xs text-sidebar-foreground/70">Logged in as</p>
                <p class="font-semibold text-sidebar-foreground">{{ auth()->user()->name }}</p>
            </div>
        </div>
    </div>
</div>
