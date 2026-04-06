<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — @yield('title', 'Inicio')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- DataTables + Buttons (Tailwind CSS) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.tailwindcss.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.tailwindcss.min.css">
    @stack('head')
<style>
    input:focus, select:focus, textarea:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.25) !important;
        outline: none !important;
    }
</style>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

    {{-- Navbar --}}
    <nav class="bg-indigo-700 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- Logo --}}
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/hospital.jpg') }}" class="w-10 h-10 rounded" alt="Logo">
                    <span class="font-bold text-lg tracking-wide">{{ config('app.name') }}</span>
                </div>


                {{-- Links de navegación --}}
                <div class="flex items-center gap-1">
                    <a href="{{ route('dashboard') }}"
                       class="px-3 py-2 rounded text-sm font-medium hover:bg-indigo-600 transition
                              {{ request()->routeIs('dashboard') ? 'bg-indigo-800' : '' }}">
                        Productos
                    </a>
                    <a href="{{ route('retiro.form') }}"
                       class="px-3 py-2 rounded text-sm font-medium hover:bg-indigo-600 transition
                              {{ request()->routeIs('retiro.*') ? 'bg-indigo-800' : '' }}">
                        Retiro
                    </a>

                    @if(auth()->user()->esAdmin())
                        {{-- Admin: solicitudes pendientes con badge --}}
                        @php
                            $pendientes = \App\Models\Solicitud::where('estado','pendiente')->count();
                        @endphp
                        <a href="{{ route('admin.solicitudes') }}"
                           class="relative px-3 py-2 rounded text-sm font-medium hover:bg-indigo-600 transition
                                  {{ request()->routeIs('admin.solicitudes') ? 'bg-indigo-800' : '' }}">
                            Solicitudes
                            @if($pendientes > 0)
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                                    {{ $pendientes }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('admin.historial') }}"
                           class="px-3 py-2 rounded text-sm font-medium hover:bg-indigo-600 transition
                                  {{ request()->routeIs('admin.historial') ? 'bg-indigo-800' : '' }}">
                            Historial
                        </a>
                        <a href="{{ route('admin.solicitudes.rechazadas') }}"
                           class="px-3 py-2 rounded text-sm font-medium hover:bg-indigo-600 transition
                                  {{ request()->routeIs('admin.solicitudes.rechazadas') ? 'bg-indigo-800' : '' }}">
                            Rechazadas
                        </a>
                        <a href="{{ route('admin.sicd.index') }}"
                           class="px-3 py-2 rounded text-sm font-medium hover:bg-indigo-600 transition
                                  {{ request()->routeIs('admin.sicd.*') ? 'bg-indigo-800' : '' }}">
                            SICD
                        </a>
                        <a href="{{ route('admin.ordenes.index') }}"
                           class="px-3 py-2 rounded text-sm font-medium hover:bg-indigo-600 transition
                                  {{ request()->routeIs('admin.ordenes.*') ? 'bg-indigo-800' : '' }}">
                            Órdenes de Compra
                        </a>
                        <a href="{{ route('admin.containers.index') }}"
                           class="px-3 py-2 rounded text-sm font-medium hover:bg-indigo-600 transition
                                  {{ request()->routeIs('admin.containers.*') ? 'bg-indigo-800' : '' }}">
                            Containers
                        </a>
                    @else
                        {{-- Usuario normal --}}
                        <a href="{{ route('solicitudes.mis') }}"
                           class="px-3 py-2 rounded text-sm font-medium hover:bg-indigo-600 transition
                                  {{ request()->routeIs('solicitudes.mis') ? 'bg-indigo-800' : '' }}">
                            Mis Solicitudes
                        </a>
                    @endif

                    {{-- Usuario y logout --}}
                    <div class="flex items-center gap-2 ml-4 pl-4 border-l border-indigo-500">
                        <span class="text-sm text-indigo-200">
                            {{ auth()->user()->name }}
                            <span class="ml-1 text-xs bg-indigo-900 px-2 py-0.5 rounded-full">
                                {{ auth()->user()->esAdmin() ? 'Admin' : 'Usuario' }}
                            </span>
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="text-sm px-3 py-1.5 bg-indigo-900 hover:bg-red-600 rounded transition font-medium">
                                Salir
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- Contenido principal --}}
    <main class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Alertas globales --}}
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

    {{-- jQuery + DataTables JS --}}
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
</body>
</html>
