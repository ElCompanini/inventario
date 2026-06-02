@extends('layouts.app')

@section('title', 'Cambiar contraseña')

@section('content')
<div class="mb-4" style="max-width:560px; margin-left:auto; margin-right:auto;">
    <h1 class="text-lg font-bold text-gray-800 dark:text-gray-100 mt-0.5">Cambiar contraseña</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400">Actualiza la contraseña de tu propia cuenta.</p>
</div>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow p-5 mx-auto border border-gray-100 dark:border-slate-700" style="max-width:560px;">
    <form method="POST" action="{{ route('password.change.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Nueva contraseña</label>
            <input type="password" name="password" required autocomplete="new-password"
                   class="w-full border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-400">
            @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" required autocomplete="new-password"
                   class="w-full border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>

        <div class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-slate-900/60 border border-gray-200 dark:border-slate-700 rounded-lg p-3">
            Debe tener al menos una mayúscula, un número y un carácter especial (se acepta _).
        </div>

        <div class="flex justify-end gap-2 pt-1">
            <a href="{{ auth()->user()->esAdmin() ? route('admin.dashboard') : route('dashboard') }}"
               class="btn-secondary px-3 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 hover:bg-gray-200 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-lg">
                Cancelar
            </a>
            <button type="submit" class="btn-primary px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                Guardar contraseña
            </button>
        </div>
    </form>
</div>
@endsection
