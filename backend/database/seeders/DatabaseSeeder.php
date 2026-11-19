<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Datos base de una instalación nueva. El primer super administrador
     * se crea después con: php artisan plataforma:superadmin {email}
     */
    public function run(): void
    {
        $this->call(CatalogoSistemaSeeder::class);
    }
}
