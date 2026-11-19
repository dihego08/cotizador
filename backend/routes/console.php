<?php

use App\Models\Usuario;
use App\Support\CuentaPlataforma;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('plataforma:crear-superadmin {email} {--nombre=Administrador de la plataforma}', function (string $email) {
    if (Usuario::where('email', $email)->exists()) {
        $this->error("Ya existe un usuario con el correo {$email}. Use otro correo: el super administrador es un usuario exclusivo de la cuenta de plataforma.");
        return 1;
    }

    // En una instalación nueva crea la cuenta de plataforma con su empresa interna
    $cuenta = CuentaPlataforma::asegurar();
    $empresa = CuentaPlataforma::empresaInterna($cuenta);
    $password = Str::password(12, symbols: false);

    $usuario = new Usuario([
        'id_empresa' => $empresa->id_empresa,
        'nombre_usuario' => $this->option('nombre'),
        'rol' => 'SUPERADMIN',
        'email' => $email,
        'password_hash' => Hash::make($password),
        'debe_cambiar_password' => true,
        'activo' => true,
    ]);
    $usuario->id_cuenta = $cuenta->id_cuenta;
    $usuario->save();

    $this->info("Super administrador creado en la cuenta \"{$cuenta->nombre}\".");
    $this->line("  Usuario: {$email}");
    $this->line("  Contraseña temporal: {$password}");
    $this->warn('Guárdela ahora: no se vuelve a mostrar. Se pedirá cambiarla en el primer ingreso.');

    return 0;
})->purpose('Crea un super administrador en la cuenta de plataforma (que solo administra cuentas de clientes)');

// Borra los tokens de sesión vencidos (requiere el cron de Laravel: php artisan schedule:run cada minuto)
Schedule::command('sanctum:prune-expired --hours=24')->daily();
