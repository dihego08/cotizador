<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Concerns\EscenarioDosCuentas;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase, EscenarioDosCuentas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CatalogoSistemaSeeder::class);
        $this->a = $this->crearCuenta('A', '20100000001');
    }

    public function test_login_correcto_devuelve_token_y_datos_de_la_cuenta(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'adminA@prueba.com', 'password' => 'secreto123'])
            ->assertOk()
            ->assertJsonPath('user.cuenta.nombre', 'Cuenta A')
            ->assertJsonPath('user.is_admin', true)
            ->assertJsonPath('user.is_superadmin', false)
            ->assertJsonStructure(['token']);
    }

    public function test_el_login_se_bloquea_tras_cinco_intentos_fallidos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'adminA@prueba.com', 'password' => 'incorrecta'])->assertStatus(422);
        }

        $this->postJson('/api/auth/login', ['email' => 'adminA@prueba.com', 'password' => 'secreto123'])->assertStatus(429);
    }

    public function test_el_token_vence(): void
    {
        $token = $this->a['admin']->createToken('prueba')->plainTextToken;

        $this->withToken($token)->getJson('/api/auth/me')->assertOk();

        $this->travel(config('sanctum.expiration') + 1)->minutes();
        app('auth')->forgetGuards();

        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_un_usuario_desactivado_pierde_el_acceso_aunque_tenga_token(): void
    {
        $token = $this->a['vendedor']->createToken('prueba')->plainTextToken;
        $this->a['vendedor']->update(['activo' => false]);

        $this->withToken($token)->getJson('/api/productos')->assertForbidden()->assertJsonPath('code', 'usuario_inactivo');
    }

    public function test_con_contrasena_temporal_solo_puede_cambiarla(): void
    {
        $vendedor = $this->a['vendedor'];
        $vendedor->update(['debe_cambiar_password' => true]);
        $this->como($vendedor->fresh());

        $this->getJson('/api/productos')->assertForbidden()->assertJsonPath('code', 'debe_cambiar_password');
        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('user.debe_cambiar_password', true);

        $this->postJson('/api/auth/cambiar-password', [
            'password_actual' => 'secreto123', 'password' => 'nuevaClave2026', 'password_confirmation' => 'nuevaClave2026',
        ])->assertOk()->assertJsonPath('user.debe_cambiar_password', false);

        $this->como($vendedor->fresh());
        $this->getJson('/api/productos')->assertOk();
        $this->assertTrue(Hash::check('nuevaClave2026', $vendedor->fresh()->password_hash));
    }

    public function test_un_usuario_creado_por_el_admin_debe_cambiar_su_contrasena(): void
    {
        $this->como($this->a['admin']);

        $this->postJson('/api/usuarios', [
            'id_empresa' => $this->a['empresa']->id_empresa, 'nombre_usuario' => 'Nuevo', 'rol' => 'VENDEDOR',
            'email' => 'nuevo@prueba.com', 'password' => 'temporal123',
        ])->assertCreated();

        $this->postJson('/api/auth/login', ['email' => 'nuevo@prueba.com', 'password' => 'temporal123'])
            ->assertOk()
            ->assertJsonPath('user.debe_cambiar_password', true);
    }

    public function test_recuperar_y_restablecer_contrasena_por_correo(): void
    {
        Notification::fake();

        // Misma respuesta para un correo inexistente (no revela qué correos existen)
        $this->postJson('/api/auth/recuperar-password', ['email' => 'nadie@prueba.com'])->assertOk();
        $this->postJson('/api/auth/recuperar-password', ['email' => 'vendedorA@prueba.com'])->assertOk();

        $token = null;
        Notification::assertSentTo($this->a['vendedor'], ResetPassword::class, function ($notificacion) use (&$token) {
            $token = $notificacion->token;
            return true;
        });

        $this->postJson('/api/auth/restablecer-password', [
            'email' => 'vendedorA@prueba.com', 'token' => 'token-falso',
            'password' => 'nuevaClave2026', 'password_confirmation' => 'nuevaClave2026',
        ])->assertStatus(422);

        $this->postJson('/api/auth/restablecer-password', [
            'email' => 'vendedorA@prueba.com', 'token' => $token,
            'password' => 'nuevaClave2026', 'password_confirmation' => 'nuevaClave2026',
        ])->assertOk();

        $this->postJson('/api/auth/login', ['email' => 'vendedorA@prueba.com', 'password' => 'nuevaClave2026'])->assertOk();
    }

    public function test_el_correo_de_recuperacion_lleva_al_frontend(): void
    {
        $correo = (new ResetPassword('abc123'))->toMail($this->a['vendedor']);

        $this->assertStringStartsWith(rtrim(config('app.frontend_url'), '/') . '/restablecer-password?token=abc123', $correo->actionUrl);
        $this->assertSame('Restablecer contraseña', $correo->subject);
    }
}
