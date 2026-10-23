<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['usuario' => 'admin'],
            [
                'name' => 'Administrador',
                'email' => 'admin@consulta.local',
                'password' => 'Cm@2026x',
                'perfil' => User::PERFIL_SUPER,
                'setor' => 'TI',
                'bloqueado' => false,
            ]
        );
    }
}
