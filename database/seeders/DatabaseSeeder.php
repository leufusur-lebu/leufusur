<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Sistema privado de un solo administrador: no se registran usuarios
        // públicamente, así que este es el único punto donde se crea acceso.
        // firstOrCreate (no updateOrCreate) para no pisar la contraseña si ya
        // fue cambiada desde el perfil. Configura ADMIN_EMAIL/ADMIN_PASSWORD
        // en .env antes de sembrar en producción.
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@leufusur.cl')],
            [
                'name' => env('ADMIN_NAME', 'Jonathan Rodríguez'),
                'password' => env('ADMIN_PASSWORD', 'password'),
                'email_verified_at' => now(),
            ]
        );
    }
}
