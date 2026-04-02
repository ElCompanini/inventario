<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Administrador',
            'email'    => 'admin@inventario.com',
            'password' => Hash::make('admin123'),
            'rol'      => 'admin',
        ]);

        User::create([
            'name'     => 'Usuario Demo',
            'email'    => 'usuario@inventario.com',
            'password' => Hash::make('user123'),
            'rol'      => 'usuario',
        ]);

        User::create([
            'name'     => 'Fernando',
            'email'    => 'fernando@inventario.com',
            'password' => Hash::make('fernando123'),
            'rol'      => 'admin',
        ]);

        User::create([
            'name'     => 'Lucas',
            'email'    => 'lucas@lucas.com',
            'password' => Hash::make('lucas'),
            'rol'      => 'admin',
        ]);
    }
}
