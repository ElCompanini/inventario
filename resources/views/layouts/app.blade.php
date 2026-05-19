<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — @yield('title', 'Inicio')</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/hospital.jpg') }}">
    {{-- Dark mode: apply class before paint to avoid flash --}}
    <script>if(localStorage.getItem('darkMode')==='1')document.documentElement.classList.add('dark');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- DataTables + Buttons (Tailwind CSS) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.tailwindcss.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.tailwindcss.min.css">
    @stack('head')
<style>
    input[name="nombre"],
    #fam-nombre-input,
    #cat-nombre-input,
    #bc-nueva-cat { text-transform: uppercase; }

    :root {
        --sb-w: 260px;
        --sb-cw: 68px;
    }

    /* ── Sidebar ── */
    #sidebar {
        width: var(--sb-w);
        /* Compound: layout transition + theme transitions merged here
           so the later theme-only rule cannot override them */
        transition: width .28s cubic-bezier(.4,0,.2,1),
                    background-color .18s ease,
                    border-color .18s ease;
    }

    #main-wrapper {
        margin-left: var(--sb-w);
        /* Explicit width prevents auto-width rounding artifacts and
           keeps the content box correctly sized at all sidebar states */
        width: calc(100vw - var(--sb-w));
        min-width: 0;
        /* Compound: layout + theme transitions merged */
        transition: margin-left .28s cubic-bezier(.4,0,.2,1),
                    width .28s cubic-bezier(.4,0,.2,1),
                    background-color .18s ease;
    }

    /* Collapsed overrides — must set width explicitly to match margin-left */
    body.sb-collapsed #main-wrapper {
        width: calc(100vw - var(--sb-cw));
    }

    /* Breathing pulse on notification badge */
    @keyframes sb-breathe {
        0%, 100% { opacity: 1;   }
        50%       { opacity: 0.4; }
    }
    .sb-badge { animation: sb-breathe 2.4s ease-in-out infinite; }

    /* DataTables export label — appears above buttons */
    .dt-buttons { display:flex !important; flex-wrap:wrap !important; align-items:center !important; gap:0.35rem !important; }
    .dt-btn-label { display:block !important; width:100% !important; background:none !important; color:#374151 !important; font-size:0.75rem !important; font-weight:600 !important; padding:0 0 0.25rem 0 !important; margin:0 !important; cursor:default !important; box-shadow:none !important; border:none !important; pointer-events:none !important; }
    .dt-btn-label:hover { background:none !important; transform:none !important; animation:none !important; }

    /* Kill all transitions on initial load to prevent animation flash */
    body.sb-no-transition *,
    body.sb-no-transition #sidebar,
    body.sb-no-transition #main-wrapper { transition: none !important; }

    /* ── Collapsed state ── */
    body.sb-collapsed #sidebar       { width: var(--sb-cw); }
    body.sb-collapsed #main-wrapper  { margin-left: var(--sb-cw); }

    /* Hide text elements */
    body.sb-collapsed .sb-label         { display: none; }
    body.sb-collapsed .sb-section-title { display: none; }
    body.sb-collapsed .sb-badge         { display: none; }
    body.sb-collapsed .sb-footer-text   { display: none; }
    body.sb-collapsed .sb-logout-btn    { display: none; }
    body.sb-collapsed #sb-toggle svg    { transform: rotate(180deg); }

    /* Nav: uniform icon spacing when collapsed — kill section gaps, center pills */
    body.sb-collapsed nav              { padding-top: 6px !important; padding-bottom: 6px !important; display: flex; flex-direction: column; align-items: center; gap: 0; }
    body.sb-collapsed nav > div       { padding: 0 !important; margin: 0 !important; display: contents; }

    /* Each link: 44×44 icon pill centered in 68px sidebar */
    body.sb-collapsed .sb-link {
        width: 44px;
        height: 44px;
        padding: 0;
        gap: 0;          /* eliminate gap-3 so label/badge remnants don't push icon off-center */
        margin: 2px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }

    /* Header: just the toggle button centered */
    body.sb-collapsed .sb-header-row {
        justify-content: center;
        padding: 0;
        height: 52px;
        border-bottom: 1px solid rgba(255,255,255,.08);
    }

    /* User block: just avatar centered */
    body.sb-collapsed .sb-user-row {
        justify-content: center;
        padding: 0;
    }
    body.sb-collapsed .sb-user-block {
        padding: 8px 0;
        border-bottom: 1px solid rgba(255,255,255,.08);
    }

    /* Toggle button — size set inline, no override needed */

    /* Dark mode sidebar — no stray shadows or borders */
    html.dark #sidebar { box-shadow: none !important; border-right: none !important; }
    /* Dark mode compact: border colors from CSS vars, not Tailwind utility */
    html.dark body.sb-collapsed .sb-header-row { border-bottom: 1px solid rgba(255,255,255,.08) !important; }
    html.dark body.sb-collapsed .sb-user-block  { border-bottom: 1px solid rgba(255,255,255,.08) !important; }

    /* Transición suave al cambiar tema — solo propiedades visuales.
       NOTA: #sidebar y #main-wrapper se excluyen de aquí porque ya tienen
       sus propias transiciones compuestas (layout + tema) declaradas arriba. */
    body, .bg-white, .bg-gray-50, .bg-gray-100,
    input, select, textarea, th, td, table {
        transition: background-color .18s ease, border-color .18s ease, color .18s ease;
    }

    /* ══ SIDEBAR LIGHT MODE ══════════════════════════════════════
       Cuando NO hay html.dark, el sidebar cambia a tema claro.   */
    html:not(.dark) #sidebar {
        background-color: #f1f5f9 !important;
        border-right: 1px solid #d1d5db !important;
        box-shadow: 2px 0 8px rgba(0,0,0,.06) !important;
    }
    html:not(.dark) #sidebar .sb-header-row,
    html:not(.dark) #sidebar .sb-user-block  { border-color: #d1d5db !important; }
    html:not(.dark) #sidebar .sb-link:not(.bg-indigo-600) { color: #374151 !important; }
    html:not(.dark) #sidebar .sb-section-title { color: #6b7280 !important; }
    html:not(.dark) #sidebar .sb-app-name  { color: #1e1b4b !important; }
    html:not(.dark) #sidebar .sb-footer-text p:last-child { color: #6b7280 !important; }
    html:not(.dark) #sb-tooltip { background: #374151 !important; }
    html:not(.dark) #sidebar #sb-toggle,
    html:not(.dark) #sidebar #dm-toggle { color: #6b7280 !important; }
    html:not(.dark) #sidebar #sb-toggle:hover,
    html:not(.dark) #sidebar #dm-toggle:hover {
        background: rgba(99,102,241,.15) !important;
        color: #4f46e5 !important;
    }
    /* Link hover light mode */
    html:not(.dark) #sidebar .sb-link:not(.bg-indigo-600):hover {
        background-color: rgba(99,102,241,.12) !important;
        color: #4f46e5 !important;
    }
    /* collapsed — bordes light */
    html:not(.dark) body.sb-collapsed .sb-header-row,
    html:not(.dark) body.sb-collapsed .sb-user-block {
        border-color: #d1d5db !important;
    }

    /* ══ DARK MODE nombre app ══════════════════════════════════ */
    .sb-app-name           { color: #1e1b4b; }   /* light mode: dark navy */
    html.dark .sb-app-name { color: #e2e8f0 !important; }  /* dark mode: slate-200 */

    /* ── Dark mode toggle button ── */
    #dm-toggle {
        width: 32px; height: 32px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 8px;
        color: #94a3b8;
        cursor: pointer;
        flex-shrink: 0;
        transition: background .18s, color .18s;
        border: none; background: none;
    }
    #dm-toggle:hover { background: rgba(99,102,241,0.25); color: #c7d2fe; }

    .sb-label, .sb-section-title, .sb-badge {
        transition: opacity .2s, max-width .25s, height .2s;
    }
    #sb-toggle svg { transition: transform .28s; }

    /* Force footer text to never exceed sidebar width */
    #sidebar .sb-footer-text {
        width: 0;
        min-width: 0;
        flex: 1 1 0;
        overflow: hidden;
    }
    #sidebar .sb-footer-text p {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
    }

    /* ═══════════════════════════════════════════════
       ENHANCED SIDEBAR ANIMATIONS — visual only
       No structural changes. Only overrides display:none
       on elements where CSS transitions can fire.
    ═══════════════════════════════════════════════ */

    /* Nav links: only visual properties transition — width/padding/margin snap instantly
       to prevent the icon from drifting left as the pill forms */
    #sidebar .sb-link {
        transition: background-color .18s ease, color .18s ease, transform .18s ease;
    }
    /* Subtle slide on hover (expanded only) */
    #sidebar .sb-link:not(.bg-indigo-600):hover {
        transform: translateX(2px);
    }
    body.sb-collapsed #sidebar .sb-link:not(.bg-indigo-600):hover {
        transform: none;
    }

    /* Labels: max-width snaps instantly so it doesn't push the icon sideways
       inside the pill; opacity fades in on expand for a nice text-appear effect */
    .sb-label {
        max-width: 220px;
        overflow: hidden;
        white-space: nowrap;
        transition: opacity .15s ease;
    }
    body.sb-collapsed .sb-label {
        display: inline !important;   /* allow transition — override display:none */
        max-width: 0;
        opacity: 0;
        pointer-events: none;
    }

    /* Section titles: smooth max-height + opacity instead of display:none */
    .sb-section-title {
        max-height: 3rem;
        overflow: hidden;
        transition: opacity .18s ease,
                    max-height .28s cubic-bezier(.4,0,.2,1),
                    margin-bottom .28s cubic-bezier(.4,0,.2,1);
    }
    body.sb-collapsed .sb-section-title {
        display: block !important;    /* allow transition — override display:none */
        max-height: 0;
        opacity: 0;
        margin-bottom: 0 !important;
        pointer-events: none;
    }

    /* Notification badges: opacity fade only */
    .sb-badge {
        transition: opacity .16s ease, width .16s ease;
    }
    body.sb-collapsed .sb-badge {
        display: inline-flex !important; /* allow transition — override display:none */
        opacity: 0;
        width: 0 !important;
        min-width: 0 !important;
        overflow: hidden;
        pointer-events: none;
    }

    /* Tooltips via fixed div — no overflow clipping issues */
    #sb-tooltip {
        position: fixed;
        background: #1e293b;
        color: #e2e8f0;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
        pointer-events: none;
        z-index: 9999;
        box-shadow: 0 4px 14px rgba(0,0,0,.4);
        opacity: 0;
        transition: opacity .12s;
    }
    #sb-tooltip.visible { opacity: 1; }

    /* ── Mobile ── */
    /* El hamburger y overlay móvil están deshabilitados — la app es desktop */
    #hamburger  { display: none !important; }
    #sb-overlay { display: none !important; }

    /* Sidebar link hover */
    #sidebar .sb-link:not(.bg-indigo-600):hover {
        background-color: rgba(99, 102, 241, 0.20);
        color: #0033ff;
    }

    /* Global focus ring */
    input:focus, select:focus, textarea:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59,130,246,.25) !important;
        outline: none !important;
    }

    /* ── Button animation templates ── */

    button.btn-primary, a.btn-primary,
    button.btn-secondary, a.btn-secondary,
    button.btn-danger, a.btn-danger,
    button.btn-ghost, a.btn-ghost {
        cursor: pointer;
    }

    /* Primary: indigo solid — press-down scale */
    button.btn-primary, a.btn-primary {
        transition: background-color .15s, transform .1s, box-shadow .15s;
    }
    button.btn-primary:active, a.btn-primary:active {
        transform: scale(.96);
        box-shadow: none;
    }

    /* Secondary: gray/outline — soft fade */
    button.btn-secondary, a.btn-secondary {
        transition: background-color .15s, border-color .15s, transform .1s;
    }
    button.btn-secondary:active, a.btn-secondary:active {
        transform: scale(.97);
        filter: brightness(.95);
    }

    /* Danger: red — quick pulse-shrink */
    button.btn-danger, a.btn-danger {
        transition: background-color .15s, transform .12s;
    }
    button.btn-danger:active, a.btn-danger:active {
        transform: scale(.95);
        filter: brightness(.9);
    }

    /* Ghost: dashed/outline only — subtle lift */
    button.btn-ghost, a.btn-ghost {
        transition: background-color .15s, border-color .15s, transform .12s;
        cursor: pointer;
    }
    button.btn-ghost:active, a.btn-ghost:active {
        transform: scale(.96);
    }
</style>
</head>
<body class="bg-gray-100 font-sans" style="overflow-x:hidden;">

@php $u = auth()->user(); @endphp

{{-- ═══════════════════════════════════════
     SIDEBAR
═══════════════════════════════════════ --}}
<aside id="sidebar"
       class="fixed top-0 left-0 h-screen z-40 flex flex-col bg-slate-900 select-none overflow-hidden">

    {{-- Header: logo + toggle --}}
    <div class="sb-header-row flex items-center px-3 py-2.5 border-b border-slate-700/60 flex-shrink-0 gap-2">
        <img src="{{ asset('images/hospital.jpg') }}"
             class="sb-label w-8 h-8 rounded-md object-cover flex-shrink-0" alt="Logo">
        <span class="sb-label sb-app-name flex-1 font-bold text-base truncate">{{ config('app.name') }}</span>
        <button id="sb-toggle"
                title="Colapsar menú"
                class="flex-shrink-0 rounded-md text-slate-400"
                style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;transition:background .18s,color .18s;"
                onmouseenter="this.style.background='rgba(99,102,241,0.25)';this.style.color='#c7d2fe';"
                onmouseleave="this.style.background='';this.style.color=''">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    {{-- Usuario --}}
    <div class="sb-user-block border-b border-slate-700/60 px-3 py-2.5 flex-shrink-0 overflow-hidden">
        <div class="sb-user-row flex items-center gap-2 w-full overflow-hidden">
            <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center flex-shrink-0 font-bold text-white text-xs">
                {{ strtoupper(substr($u->name, 0, 1)) }}
            </div>
            <div class="sb-footer-text flex-1 overflow-hidden" style="min-width:0;">
                <p class="text-sm font-medium truncate leading-tight" style="color:#818cf8;">{{ $u->name }}</p>
                <p class="text-xs text-slate-400 truncate leading-tight">{{ $u->esDev() ? 'Super Administrador' : ($u->esAdmin() ? 'Administrador' : 'Usuario') }}</p>
                @if($u->centroCosto)
                    <p class="text-[10px] text-slate-500 leading-tight" style="white-space:normal;word-break:break-word;">{{ $u->centroCosto->nombre_completo }}</p>
                @endif
            </div>
            {{-- Dark mode toggle --}}
            <button id="dm-toggle" class="sb-logout-btn" title="Modo oscuro/claro" onclick="dmToggle()">
                {{-- Sun icon (shown in dark mode) --}}
                <svg id="dm-icon-sun" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                     style="display:none">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m8.66-9h-1M4.34 12h-1m15.07-6.07-.7.7M6.34 17.66l-.7.7m12.73 0-.7-.7M6.34 6.34l-.7-.7M12 7a5 5 0 100 10A5 5 0 0012 7z"/>
                </svg>
                {{-- Moon icon (shown in light mode) --}}
                <svg id="dm-icon-moon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                </svg>
            </button>
            <form method="POST" action="{{ route('logout') }}" class="sb-logout-btn flex-shrink-0">
                @csrf
                <button type="submit" title="Cerrar sesión"
                        class="flex-shrink-0 rounded-md text-slate-400"
                        style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;transition:background .18s,color .18s;"
                        onmouseenter="this.style.background='rgba(99,102,241,0.25)';this.style.color='#c7d2fe';"
                        onmouseleave="this.style.background='';this.style.color=''">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-3 space-y-4 scrollbar-thin">

        {{-- ── Dashboard (solo admins) ── --}}
        @if($u->esAdmin())
        <div class="px-3">
            <a href="{{ route('admin.dashboard') }}" data-tip="Dashboard"
               class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                      {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="sb-label">Dashboard</span>
            </a>
        </div>
        @endif

        {{-- ── Gestión de Stock ── --}}
        <div class="px-3">
            <p class="sb-section-title text-[10px] font-semibold text-slate-500 uppercase tracking-widest px-2 mb-1">
                Gestión de Stock
            </p>

            <a href="{{ route('dashboard') }}" data-tip="Productos y Servicios"
               class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                      {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-300 text-slate-300' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                </svg>
                <span class="sb-label">Productos y Servicios</span>
            </a>

            <a href="{{ route('retiro.form') }}" data-tip="Retiro de Piezas"
               class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                      {{ request()->routeIs('retiro.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 text-slate-300' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
                <span class="sb-label">Retiro de Piezas</span>
            </a>
        </div>

        {{-- ── Flujo de Solicitudes ── --}}
        @if($u->tienePermiso('solicitudes') || $u->tienePermiso('aprobar_solicitudes') || $u->tienePermiso('rechazadas') || $u->tienePermiso('historial') || (!$u->esAdmin() && !$u->tieneAlgunPermiso()))
        <div class="px-3">
            <p class="sb-section-title text-[10px] font-semibold text-slate-500 uppercase tracking-widest px-2 mb-1">
                Flujo de Solicitudes
            </p>

            @if($u->tienePermiso('solicitudes') || $u->tienePermiso('aprobar_solicitudes'))
                @php
                    $pendientes = \App\Models\Solicitud::where('estado','pendiente')
                        ->whereHas('producto', fn($q) => $q->withoutGlobalScopes()
                            ->when($u->tieneFiltroCC(), fn($q2) => $q2->where('centro_costo_id', $u->centro_costo_id))
                        )
                        ->count();
                @endphp
                <a href="{{ route('admin.solicitudes') }}" data-tip="Solicitudes de Retiro"
                   class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('admin.solicitudes') && !request()->routeIs('admin.solicitudes.rechazadas') ? 'bg-indigo-600 text-white' : 'text-slate-300 text-slate-300' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="sb-label flex-1">Solicitudes de Retiro</span>
                    @if($pendientes > 0)
                        <span class="sb-badge bg-red-500 text-white text-[11px] font-bold rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">
                            {{ $pendientes }}
                        </span>
                    @endif
                </a>
            @endif

            @if($u->tienePermiso('rechazadas'))
                <a href="{{ route('admin.solicitudes.rechazadas') }}" data-tip="Solicitudes Rechazadas"
                   class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('admin.solicitudes.rechazadas') ? 'bg-indigo-600 text-white' : 'text-slate-300 text-slate-300' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="sb-label">Solicitudes Rechazadas</span>
                </a>
            @endif

            @if($u->tienePermiso('historial'))
                <a href="{{ route('admin.historial') }}" data-tip="Historial de Solicitudes"
                   class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('admin.historial') ? 'bg-indigo-600 text-white' : 'text-slate-300 text-slate-300' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="sb-label">Historial de Solicitudes</span>
                </a>
            @endif

            @unless($u->esAdmin() || $u->tieneAlgunPermiso())
                <a href="{{ route('solicitudes.mis') }}" data-tip="Mis Solicitudes"
                   class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('solicitudes.mis') ? 'bg-indigo-600 text-white' : 'text-slate-300 text-slate-300' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span class="sb-label">Mis Solicitudes</span>
                </a>
            @endunless
        </div>
        @endif

        {{-- ── Logística y Compras ── --}}
        @if($u->tienePermiso('sicd') || $u->tienePermiso('ordenes') || $u->tienePermiso('containers') || $u->tienePermiso('gastos_menores'))
        <div class="px-3">
            <p class="sb-section-title text-[10px] font-semibold text-slate-500 uppercase tracking-widest px-2 mb-1">
                Logística y Compras
            </p>

            @if($u->tienePermiso('sicd'))
                <a href="{{ route('admin.sicd.index') }}" data-tip="SICD"
                   class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('admin.sicd.*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span class="sb-label">SICD</span>
                </a>
            @endif

            @if($u->tienePermiso('ordenes'))
                <a href="{{ route('admin.ordenes.index') }}" data-tip="Órdenes de Compra"
                   class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('admin.ordenes.*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="sb-label">Órdenes de Compra</span>
                </a>
            @endif

            @if($u->tienePermiso('containers'))
                <a href="{{ route('admin.containers.index') }}" data-tip="Containers"
                   class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('admin.containers.*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    <span class="sb-label">Containers</span>
                </a>
            @endif

            @if($u->tienePermiso('gastos_menores'))
                <a href="{{ route('admin.gastos-menores.index') }}" data-tip="Gastos Menores"
                   class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                          {{ request()->routeIs('admin.gastos-menores.*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                    <span class="sb-label">Gastos Menores</span>
                </a>
            @endif
        </div>
        @endif

        {{-- ── Administración ── --}}
        @if($u->tienePermiso('computadores') || $u->tienePermiso('reportes') || $u->tienePermiso('usuarios') || $u->tienePermiso('catalogo'))
        <div class="px-3">
            <p class="sb-section-title text-[10px] font-semibold text-slate-500 uppercase tracking-widest px-2 mb-1">
                Administración
            </p>

            @if($u->tienePermiso('computadores'))
            <a href="{{ route('admin.computadores.index') }}" data-tip="Armado de Equipos"
               class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                      {{ request()->routeIs('admin.computadores.*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="sb-label">Armado Equipos</span>
            </a>
            @endif

            @if($u->tienePermiso('reportes'))
            <a href="{{ route('admin.reportes.index') }}" data-tip="Reportes"
               class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                      {{ request()->routeIs('admin.reportes.index') || request()->routeIs('admin.reportes.bincard*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="sb-label">Reportes</span>
            </a>

            <a href="{{ route('admin.reportes.historial') }}" data-tip="Historial Reporterías"
               class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                      {{ request()->routeIs('admin.reportes.historial*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="sb-label">Historial Reporterías</span>
            </a>
            @endif

            @if($u->tienePermiso('usuarios'))
            <a href="{{ route('admin.usuarios.index') }}" data-tip="Usuarios"
               class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                      {{ request()->routeIs('admin.usuarios.*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <span class="sb-label">Usuarios</span>
            </a>
            @endif

            @if($u->tienePermiso('catalogo'))
            <a href="{{ route('admin.catalogo.unidades.index') }}" data-tip="Unidades de Medida"
               class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                      {{ request()->routeIs('admin.catalogo.unidades.*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M3 12h18M3 18h18"/>
                </svg>
                <span class="sb-label">Unidades de Medida</span>
            </a>

            <a href="{{ route('admin.productos.catalogo') }}" data-tip="Catálogo de Productos y Servicios"
               class="sb-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                      {{ request()->routeIs('admin.productos.catalogo*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span class="sb-label">Catálogo Prod. y Serv.</span>
            </a>
            @endif
        </div>
        @endif

    </nav>

</aside>

{{-- Sidebar tooltip --}}
<div id="sb-tooltip"></div>

{{-- Mobile overlay --}}
<div id="sb-overlay"
     class="fixed inset-0 bg-black/50 z-30 hidden"
     onclick="sbCloseMobile()"></div>

{{-- Hamburger (mobile only) --}}
<button id="hamburger"
        class="fixed top-4 left-4 z-50 p-2 rounded-lg bg-slate-900 text-white shadow-lg"
        onclick="sbToggleMobile()">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
</button>

{{-- ═══════════════════════════════════════
     MAIN CONTENT
═══════════════════════════════════════ --}}
<div id="main-wrapper" class="min-h-screen flex flex-col">
    <main class="flex-1 w-full min-w-0 px-5 sm:px-7 lg:px-10 py-6">

        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @yield('content')
    </main>
</div>

{{-- ── Scripts ── --}}
<script>
// ── Dark mode ──
function dmToggle() {
    var isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark ? '1' : '0');
    document.getElementById('dm-icon-sun').style.display  = isDark ? '' : 'none';
    document.getElementById('dm-icon-moon').style.display = isDark ? 'none' : '';
}
// Sync icon with current state on load
(function() {
    var isDark = document.documentElement.classList.contains('dark');
    var sun  = document.getElementById('dm-icon-sun');
    var moon = document.getElementById('dm-icon-moon');
    if (sun && moon) {
        sun.style.display  = isDark ? '' : 'none';
        moon.style.display = isDark ? 'none' : '';
    }
})();

// Apply saved state BEFORE paint — disable transitions so nothing animates on load
(function () {
    if (localStorage.getItem('sbCollapsed') === '1') {
        document.body.classList.add('sb-collapsed', 'sb-no-transition');
        // Re-enable transitions after first paint
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                document.body.classList.remove('sb-no-transition');
            });
        });
    }
})();

document.getElementById('sb-toggle').addEventListener('click', function () {
    const c = document.body.classList.toggle('sb-collapsed');
    localStorage.setItem('sbCollapsed', c ? '1' : '0');
});

function sbToggleMobile() {
    document.body.classList.toggle('sb-mobile-open');
    document.getElementById('sb-overlay').classList.toggle('hidden');
}
function sbCloseMobile() {
    document.body.classList.remove('sb-mobile-open');
    document.getElementById('sb-overlay').classList.add('hidden');
}

// Tooltips for collapsed sidebar
(function () {
    const tip = document.getElementById('sb-tooltip');
    document.querySelectorAll('#sidebar .sb-link').forEach(function (link) {
        link.addEventListener('mouseenter', function (e) {
            if (!document.body.classList.contains('sb-collapsed')) return;
            const label = link.dataset.tip;
            if (!label) return;
            tip.textContent = label;
            tip.classList.add('visible');
        });
        link.addEventListener('mousemove', function (e) {
            if (!document.body.classList.contains('sb-collapsed')) return;
            tip.style.left = (e.clientX + 14) + 'px';
            tip.style.top  = (e.clientY - 12) + 'px';
        });
        link.addEventListener('mouseleave', function () {
            tip.classList.remove('visible');
        });
    });
})();
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>if(typeof pdfjsLib!=='undefined'){pdfjsLib.GlobalWorkerOptions.workerSrc='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';}</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.tailwindcss.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.tailwindcss.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min.js"></script>
@stack('scripts')
<script>
// Mayúsculas automáticas en campos de nombres de productos/categorías/familias
(function() {
    const SEL = 'input[name="nombre"], #fam-nombre-input, #cat-nombre-input, #bc-nueva-cat';
    function toUpper(el) {
        if (!el.value) return;
        const pos = el.selectionStart;
        el.value = el.value.toUpperCase();
        try { el.setSelectionRange(pos, pos); } catch(e) {}
    }
    document.addEventListener('input', function(e) {
        if (e.target.matches && e.target.matches(SEL)) toUpper(e.target);
    });
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll(SEL).forEach(toUpper);
    });
})();

// Formateador global de RUT chileno → xx.xxx.xxx-x
function _formatRut(input) {
    const raw = input.value.replace(/[^0-9kK]/gi, '').toUpperCase();
    if (raw.length === 0) { input.value = ''; return; }
    if (raw.length === 1) { input.value = raw; return; }
    const dv   = raw.slice(-1);
    const body = raw.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    input.value = body + '-' + dv;
}
document.addEventListener('input', function(e) {
    if (e.target.matches('input[name="rut_proveedor"]')) _formatRut(e.target);
});
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="rut_proveedor"]').forEach(_formatRut);
});
</script>
</body>
</html>
