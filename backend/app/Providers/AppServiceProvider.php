<?php

namespace App\Providers;

use App\Support\CuentaActual;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Una instancia por petición (se reinicia entre peticiones y entre pruebas)
        $this->app->scoped(CuentaActual::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);

        // 5 intentos de login por minuto para cada combinación correo + IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')) . '|' . $request->ip());
        });

        // Recuperación de contraseña: evita usar el formulario para enviar correos masivos
        RateLimiter::for('recuperar-password', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('restablecer-password', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(240)->by($request->user()?->id_usuario ?: $request->ip());
        });

        // Correo de recuperación en español, con enlace a la pantalla del frontend
        ResetPassword::toMailUsing(function ($usuario, string $token) {
            $url = rtrim(config('app.frontend_url'), '/') . '/restablecer-password?' . http_build_query([
                'token' => $token,
                'email' => $usuario->email,
            ]);

            return (new MailMessage)
                ->subject('Restablecer contraseña')
                ->greeting("Hola, {$usuario->nombre_usuario}")
                ->line('Recibimos una solicitud para restablecer la contraseña de su cuenta.')
                ->action('Restablecer contraseña', $url)
                ->line('El enlace vence en ' . config('auth.passwords.users.expire') . ' minutos.')
                ->line('Si usted no lo solicitó, ignore este correo: su contraseña no cambiará.')
                ->salutation('Saludos');
        });
    }
}
