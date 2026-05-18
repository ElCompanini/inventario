<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Iniciar sesión</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/hospital.jpg') }}">
    <script>if(localStorage.getItem('darkMode')==='1')document.documentElement.classList.add('dark');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body id="login-body" class="min-h-screen flex items-center justify-center font-sans
             bg-gradient-to-br from-indigo-600 to-indigo-900
             transition-colors duration-300">

    <div class="w-full max-w-md px-4">

        {{-- Logo / cabecera --}}
        <div class="text-center mb-4">
            <div class="inline-flex items-center justify-center mb-4">
                <img src="{{ asset('images/hospital.jpg') }}" alt="Logo" class="h-20 w-auto rounded-xl shadow-lg">
            </div>
            <h1 class="text-white text-2xl font-bold">{{ config('app.name') }}</h1>
            <p class="text-indigo-300 dark:text-indigo-400 text-sm mt-1">Sistema de Gestión de Inventario</p>
        </div>

        {{-- Card login --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-8 transition-colors duration-300">

            <div class="flex items-center justify-between mb-6">
                <h2 class="text-gray-800 dark:text-gray-100 text-xl font-semibold">Iniciar sesión</h2>
                <button onclick="dmToggle()" id="dm-btn"
                        class="w-8 h-8 rounded-full flex items-center justify-center
                               bg-gray-100 hover:bg-gray-200 dark:bg-slate-700 dark:hover:bg-slate-600
                               text-gray-500 dark:text-gray-400 transition-all duration-200 shrink-0"
                        title="Cambiar tema">
                    <svg id="dm-moon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                    <svg id="dm-sun" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>
            </div>

            @if($errors->any())
                <div class="mb-4 bg-red-50 dark:bg-red-900/30 border border-red-300 dark:border-red-700
                            text-red-700 dark:text-red-400 rounded-lg px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" novalidate>
                @csrf

                {{-- Email --}}
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Correo electrónico
                    </label>
                    <input
                        id="email" name="email" type="text"
                        value="{{ old('email') }}"
                        required autofocus autocomplete="username"
                        class="w-full rounded-lg px-3 py-2 text-sm transition-colors duration-200
                               border border-gray-300 dark:border-slate-600
                               bg-white dark:bg-slate-700
                               text-gray-900 dark:text-gray-100
                               placeholder-gray-400 dark:placeholder-slate-500
                               focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                               {{ $errors->has('email') ? 'border-red-400 bg-red-50 dark:bg-red-900/20' : '' }}"
                        placeholder="correo@ejemplo.com"
                    >
                </div>

                {{-- Password --}}
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Contraseña
                    </label>
                    <input
                        id="password" name="password" type="password"
                        required autocomplete="current-password"
                        class="w-full rounded-lg px-3 py-2 text-sm transition-colors duration-200
                               border border-gray-300 dark:border-slate-600
                               bg-white dark:bg-slate-700
                               text-gray-900 dark:text-gray-100
                               placeholder-gray-400 dark:placeholder-slate-500
                               focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="••••••••"
                    >
                </div>

                {{-- Remember --}}
                <div class="flex items-center mb-6">
                    <input id="remember" name="remember" type="checkbox"
                           class="w-4 h-4 text-indigo-600 border-gray-300 dark:border-slate-600 rounded
                                  dark:bg-slate-700 focus:ring-indigo-500">
                    <label for="remember" class="ml-2 text-sm text-gray-600 dark:text-gray-400">Recordarme</label>
                </div>

                <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600
                               text-white font-semibold py-2.5 rounded-lg transition-colors duration-200 text-sm">
                    Entrar
                </button>
            </form>
        </div>

        <p class="text-center text-indigo-300 dark:text-indigo-500 text-xs mt-6">
            Contacta al administrador si no tienes acceso.
        </p>
    </div>

    <script>
    var DARK_BG  = 'linear-gradient(to bottom right, #18124a, #09060f)';
    var LIGHT_BG = '';   // deja que Tailwind maneje el fondo claro

    function applyBg(isDark) {
        document.getElementById('login-body').style.backgroundImage = isDark ? DARK_BG : LIGHT_BG;
    }
    function dmToggle() {
        var isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark ? '1' : '0');
        document.getElementById('dm-sun').classList.toggle('hidden', !isDark);
        document.getElementById('dm-moon').classList.toggle('hidden', isDark);
        applyBg(isDark);
    }
    (function () {
        var isDark = document.documentElement.classList.contains('dark');
        document.getElementById('dm-sun').classList.toggle('hidden', !isDark);
        document.getElementById('dm-moon').classList.toggle('hidden', isDark);
        applyBg(isDark);
    })();
    </script>

</body>
</html>
