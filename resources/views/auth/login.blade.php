<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Iniciar sesión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-indigo-600 to-indigo-900 min-h-screen flex items-center justify-center font-sans">

    <div class="w-full max-w-md px-4">
        {{-- Logo / cabecera --}}
        <div class="text-center mb-4">
            <div class="inline-flex items-center justify-center mb-4">
                <img src="{{ asset('images/hospital.jpg') }}" alt="Logo" class="h-20 w-auto rounded-xl shadow-lg">
            </div>
            <h1 class="text-white text-2xl font-bold text-light bg-dark">{{ config('app.name') }}</h1>
            <p class="text-indigo-300 text-sm mt-1">Sistema de Gestión de Inventario</p>
        </div>

        {{-- Card login --}}
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-gray-800 text-xl font-semibold mb-6">Iniciar sesión</h2>

            @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" novalidate>
                @csrf

                {{-- Email --}}
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Correo electrónico
                    </label>
                    <input
                        id="email" name="email" type="text"
                        value="{{ old('email') }}"
                        required autofocus autocomplete="username"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                               {{ $errors->has('email') ? 'border-red-400 bg-red-50' : '' }}"
                        placeholder="correo@ejemplo.com"
                    >
                </div>

                {{-- Password --}}
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña
                    </label>
                    <input
                        id="password" name="password" type="password"
                        required autocomplete="current-password"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="••••••••"
                    >
                </div>

                {{-- Remember --}}
                <div class="flex items-center mb-6">
                    <input id="remember" name="remember" type="checkbox"
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded">
                    <label for="remember" class="ml-2 text-sm text-gray-600">Recordarme</label>
                </div>

                <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold
                               py-2.5 rounded-lg transition text-sm">
                    Entrar
                </button>
            </form>
        </div>

        <p class="text-center text-indigo-300 text-xs mt-6">
            Contacta al administrador si no tienes acceso.
        </p>
    </div>

</body>
</html>
