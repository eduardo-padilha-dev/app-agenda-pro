@props(['name' => 'calendar', 'size' => 18])
<svg {{ $attributes->merge(['class' => 'shrink-0']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('calendar') <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18M8 15h2v2H8z"/> @break
        @case('list') <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/> @break
        @case('users') <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/><circle cx="9" cy="7" r="4"/> @break
        @case('user') <circle cx="12" cy="8" r="4"/><path d="M4 21v-2a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v2"/> @break
        @case('scissors') <circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="m8.12 8.12 12.76 12.76M14 14l7-11M8.12 15.88 12 12"/> @break
        @case('logout') <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/> @break
        @case('chevron-right') <path d="m9 18 6-6-6-6"/> @break
        @case('chevron-left') <path d="m15 18-6-6 6-6"/> @break
        @case('plus') <path d="M12 5v14M5 12h14"/> @break
        @case('x') <path d="m6 6 12 12M6 18 18 6"/> @break
        @case('search') <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/> @break
        @case('menu') <path d="M4 6h16M4 12h16M4 18h16"/> @break
        @case('lock') <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/> @break
        @case('clock') <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/> @break
        @case('check') <path d="m5 12 4 4L19 6"/> @break
        @case('eye') <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/> @break
        @case('trash') <path d="M3 6h18M9 6V4h6v2M5 6l1 14h12l1-14M10 10v6M14 10v6"/> @break
        @case('user-x') <circle cx="9" cy="7" r="4"/><path d="M2 21v-2a6 6 0 0 1 6-6h2a6 6 0 0 1 6 6v2m1-13 5 5m0-5-5 5"/> @break
    @endswitch
</svg>
