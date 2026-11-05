@props(['name'=>'arrow','size'=>22])
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('eye')<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>@break
@case('filter')<path d="M3 4h18l-7 8v7l-4 2v-9Z"/>@break
@case('chevron')<path d="m6 9 6 6 6-6"/>@break
@case('arrow-right')<path d="M4 12h16m-6-6 6 6-6 6"/>@break
@case('bricks')<path d="M3 3h18v18H3ZM3 9h18M3 15h18M9 3v6M15 9v6M9 15v6"/>@break
@case('pipe')<path d="M3 3h6v8h12v6H9a6 6 0 0 1-6-6ZM2 2h8M20 10v8"/>@break
@case('welding')<path d="m5 3 14 0 2 10-9 9-9-9ZM7 7h10v6H7ZM8 18l4-3 4 3"/>@break
@case('brush')<path d="m14 3 7 7-10 10-7 1 1-7ZM11 6l7 7M5 14l5 5"/>@break
@case('search')<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>@break
@case('home')<path d="m3 10 9-7 9 7v11h-6v-7H9v7H3Z"/>@break
@case('briefcase')<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V3h8v4M3 12c5 4 13 4 18 0M12 12v4"/>@break
@case('cube')<path d="m12 2 10 5v10l-10 5L2 17V7Zm0 10v10M2 7l10 5 10-5M7 4.5l10 5v6"/>@break
@case('user')<circle cx="12" cy="7" r="4"/><path d="M4 22v-3a8 8 0 0 1 16 0v3"/>@break
@case('pin')<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>@break
@case('arrow')<path d="M20 12H4m6-6-6 6 6 6"/>@break
@case('plus')<path d="M12 4v16M4 12h16"/>@break
@case('check')<path d="m5 12 4 4L20 5"/>@break
@case('shield')<path d="m12 2 9 4v7c0 5-9 9-9 9s-9-4-9-9V6Z"/><path d="m8 12 3 3 5-6"/>@break
@case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/>@break
@case('phone')<path d="m7 3 4 5-3 3c2 3 3 4 5 5l3-3 5 4c-1 6-7 5-12 0S1 5 7 3Z"/>@break
@case('compare')<path d="M4 4h6v16H4ZM14 4h6v16h-6ZM4 9h6M14 15h6"/>@break
@case('helmet')<path d="M3 17a9 9 0 0 1 18 0M9 4v7m6-7v7M2 17h20v4H2Z"/>@break
@case('menu')<path d="M3 6h18M3 12h18M3 18h18"/>@break
@case('logout')<path d="M10 3H3v18h7M9 12h13m-4-4 4 4-4 4"/>@break
@case('bell')<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/>@break
@case('message')<path d="M21 11a9 9 0 0 1-9 9H3l2-5a9 9 0 1 1 16-4Z"/><path d="M8 9h8M8 13h5"/>@break
@case('settings')<path d="m9 3 6 0 1 3 3 1 2 5-2 4-3 1-1 4H9l-1-4-3-1-2-4 2-5 3-1Z"/><circle cx="12" cy="12" r="3"/>@break
@case('chart')<path d="M3 3v18h18M7 16v-4m5 4V7m5 9V4"/>@break
@case('grid')<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>@break
@case('file')<path d="M4 2h11l5 5v15H4Zm10 0v6h6M8 12h8M8 16h8"/>@break
@case('flag')<path d="M4 22V3c5-5 11 4 16 0v11c-5 4-11-5-16 0"/>@break
@case('wallet')<rect x="3" y="6" width="18" height="15" rx="2"/><path d="M4 6V3h14v3M21 11h-7v6h7M17 14h1"/>@break
@case('tool')<path d="m3 5 6-3 12 6-3 3-8-3-4 13-4-1L7 7Z"/>@break
@case('bolt')<path d="m14 2-10 12h7l-1 8L20 9h-7Z"/>@break
@default<path d="m4 6 16 12M4 18 20 6"/>
@endswitch
</svg>
