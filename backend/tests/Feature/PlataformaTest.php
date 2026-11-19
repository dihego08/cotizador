<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Support\CuentaActual;
use App\Support\CuentaPlataforma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\EscenarioDosCuentas;
use Tests\TestCase;

/**
 * Panel del dueño de la plataforma: alta de cuentas, planes y suspensión.
 */
class PlataformaTest extends TestCase
{
    use RefreshDatabase, EscenarioDosCuentas;

    private Usuario $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CatalogoSistemaSeeder::class);

        // La cuenta de plataforma solo tiene su empresa interna y su super administrador
        $plataforma = CuentaPlataforma::asegurar();
        $this->superadmin = app(CuentaActual::class)->ejecutarComo($plataforma->id_cuenta, fn () => $this->crearUsuario(
            CuentaPlataforma::empresaInterna($plataforma), 'SUPERADMIN', 'super@plataforma.com'
        ));

        $this->a = $this->crearCuenta('A', '20100000001');
    }

    private function datosCuentaNueva(array $cambios = []): array
    {
        return array_replace_recursive([
            'nombre' => 'Ferretería Sur',
            'plan' => 'BASICO',
            'max_usuarios' => 3,
            'empresa' => ['razon_social' => 'Ferretería Sur S.A.C.', 'ruc' => '20300000003'],
            'admin' => ['nombre_usuario' => 'Dueño Ferretería', 'email' => 'duenio@ferreteria.com', 'password' => 'temporal123'],
        ], $cambios);
    }

    public function test_solo_el_superadmin_accede_al_panel(): void
    {
        $this->como($this->a['admin'])->getJson('/api/plataforma/cuentas')->assertForbidden();
        $this->como($this->superadmin)->getJson('/api/plataforma/cuentas')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_crear_una_cuenta_con_su_empresa_y_administrador(): void
    {
        $this->como($this->superadmin);

        $id = $this->postJson('/api/plataforma/cuentas', $this->datosCuentaNueva())->assertCreated()->json('data.id_cuenta');

        $empresa = Empresa::withoutGlobalScopes()->where('id_cuenta', $id)->sole();
        $admin = Usuario::withoutGlobalScopes()->where('email', 'duenio@ferreteria.com')->sole();

        $this->assertSame($id, $empresa->id_cuenta);
        $this->assertSame($id, $admin->id_cuenta);
        $this->assertSame('ADMIN', $admin->rol);
        $this->assertTrue($admin->debe_cambiar_password);

        // El administrador nuevo entra a su cuenta, no a la de la plataforma
        $this->postJson('/api/auth/login', ['email' => 'duenio@ferreteria.com', 'password' => 'temporal123'])
            ->assertOk()
            ->assertJsonPath('user.cuenta.nombre', 'Ferretería Sur')
            ->assertJsonPath('user.debe_cambiar_password', true);
    }

    public function test_los_limites_del_plan_se_respetan(): void
    {
        $this->como($this->superadmin);
        $this->putJson("/api/plataforma/cuentas/{$this->a['cuenta']->id_cuenta}", ['max_usuarios' => 2, 'max_empresas' => 1])->assertOk();

        // La cuenta A ya tiene 2 usuarios y 1 empresa
        $this->como($this->a['admin']);
        $this->postJson('/api/usuarios', [
            'id_empresa' => $this->a['empresa']->id_empresa, 'nombre_usuario' => 'X', 'rol' => 'VENDEDOR',
            'email' => 'x@prueba.com', 'password' => 'secreto123',
        ])->assertStatus(422)->assertJsonValidationErrors('plan');
        $this->postJson('/api/empresas', ['razon_social' => 'Otra', 'ruc' => '20100000005'])
            ->assertStatus(422)->assertJsonValidationErrors('plan');
    }

    public function test_suspender_una_cuenta_bloquea_el_acceso_y_cierra_sesiones(): void
    {
        $token = $this->a['admin']->createToken('prueba')->plainTextToken;

        $this->como($this->superadmin);
        $this->patchJson("/api/plataforma/cuentas/{$this->a['cuenta']->id_cuenta}/estado", ['estado' => 'SUSPENDIDA'])->assertOk();

        $this->assertSame(0, $this->a['admin']->tokens()->count());
        $this->postJson('/api/auth/login', ['email' => 'adminA@prueba.com', 'password' => 'secreto123'])
            ->assertStatus(422)->assertJsonValidationErrors('email');

        // Reactivar devuelve el acceso
        $this->patchJson("/api/plataforma/cuentas/{$this->a['cuenta']->id_cuenta}/estado", ['estado' => 'ACTIVA'])->assertOk();
        $this->postJson('/api/auth/login', ['email' => 'adminA@prueba.com', 'password' => 'secreto123'])->assertOk();
    }

    public function test_un_token_de_una_cuenta_suspendida_queda_bloqueado(): void
    {
        $token = $this->a['vendedor']->createToken('prueba')->plainTextToken;
        Cuenta::whereKey($this->a['cuenta']->id_cuenta)->update(['estado' => 'SUSPENDIDA']);

        $this->withToken($token)->getJson('/api/productos')->assertForbidden()->assertJsonPath('code', 'cuenta_suspendida');
    }

    public function test_el_superadmin_no_puede_suspender_su_propia_cuenta(): void
    {
        $this->como($this->superadmin);

        $this->patchJson("/api/plataforma/cuentas/{$this->superadmin->id_cuenta}/estado", ['estado' => 'SUSPENDIDA'])->assertStatus(422);
    }

    public function test_el_rol_superadmin_fuera_de_la_cuenta_de_plataforma_no_da_acceso(): void
    {
        // Aunque alguien cambie el rol directamente en la base de datos
        $this->a['admin']->update(['rol' => 'SUPERADMIN']);

        $this->como($this->a['admin']->fresh());
        $this->getJson('/api/plataforma/cuentas')->assertForbidden();
        $this->getJson('/api/auth/me')->assertJsonPath('user.is_superadmin', false);
    }

    public function test_solo_puede_existir_una_cuenta_de_plataforma(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Cuenta::whereKey($this->a['cuenta']->id_cuenta)->update(['es_plataforma' => true]);
    }

    public function test_el_superadmin_solo_administra_y_no_usa_los_modulos_del_negocio(): void
    {
        $this->como($this->superadmin);

        foreach (['/api/productos', '/api/clientes', '/api/dashboard/metrics', '/api/empresas', '/api/usuarios', '/api/tipo-cambio/actual'] as $url) {
            $this->getJson($url)->assertForbidden()->assertJsonPath('code', 'solo_plataforma');
        }
        $this->postJson('/api/productos', [])->assertForbidden()->assertJsonPath('code', 'solo_plataforma');

        $this->getJson('/api/plataforma/cuentas')->assertOk();
        $this->getJson('/api/auth/me')->assertOk()
            ->assertJsonPath('user.is_superadmin', true)
            ->assertJsonPath('user.cuenta.es_plataforma', true);
    }

    public function test_el_comando_crea_el_superadmin_en_la_cuenta_de_plataforma(): void
    {
        $this->artisan('plataforma:crear-superadmin', ['email' => 'nuevo@plataforma.com'])
            ->expectsOutputToContain('Contraseña temporal')
            ->assertSuccessful();

        $usuario = Usuario::withoutGlobalScopes()->where('email', 'nuevo@plataforma.com')->sole();
        $this->assertSame('SUPERADMIN', $usuario->rol);
        $this->assertSame(Cuenta::plataforma()->id_cuenta, $usuario->id_cuenta);
        $this->assertTrue($usuario->debe_cambiar_password);

        // Un correo que ya existe (por ejemplo, de un cliente) no se convierte en super administrador
        $this->artisan('plataforma:crear-superadmin', ['email' => 'adminA@prueba.com'])->assertFailed();
        $this->assertSame('ADMIN', $this->a['admin']->fresh()->rol);
    }

    public function test_en_una_instalacion_nueva_el_comando_crea_la_cuenta_de_plataforma(): void
    {
        Cuenta::query()->update(['es_plataforma' => null]);

        $this->artisan('plataforma:crear-superadmin', ['email' => 'primero@plataforma.com'])->assertSuccessful();

        $plataforma = Cuenta::plataforma();
        $this->assertSame(CuentaPlataforma::NOMBRE, $plataforma->nombre);
        $this->assertSame($plataforma->id_cuenta, Usuario::withoutGlobalScopes()->where('email', 'primero@plataforma.com')->value('id_cuenta'));
    }

    public function test_separa_la_plataforma_de_una_cuenta_que_tambien_se_usa_como_negocio(): void
    {
        // Situación de producción: el SUPERADMIN vive en una cuenta con datos de negocio
        Cuenta::query()->update(['es_plataforma' => null]);
        $this->a['cuenta']->forceFill(['es_plataforma' => true])->save();
        $this->a['admin']->update(['rol' => 'SUPERADMIN']);
        $token = $this->a['admin']->createToken('prueba')->plainTextToken;

        $nueva = CuentaPlataforma::separarDeNegocio();

        $this->assertNotNull($nueva);
        $this->assertTrue($nueva->fresh()->esPlataforma());
        $this->assertFalse($this->a['cuenta']->fresh()->esPlataforma());

        // El SUPERADMIN pasa a la cuenta nueva; el resto de la cuenta se queda como cliente
        $superadmin = Usuario::withoutGlobalScopes()->find($this->a['admin']->id_usuario);
        $this->assertSame($nueva->id_cuenta, $superadmin->id_cuenta);
        $this->assertSame(CuentaPlataforma::empresaInterna($nueva)->id_empresa, $superadmin->id_empresa);
        $this->assertSame($this->a['cuenta']->id_cuenta, $this->a['vendedor']->fresh()->id_cuenta);
        $this->assertSame(0, $superadmin->tokens()->count());

        // Ejecutarlo otra vez no hace nada
        $this->assertNull(CuentaPlataforma::separarDeNegocio());
    }

    public function test_no_separa_una_cuenta_de_plataforma_que_solo_administra(): void
    {
        $antes = Cuenta::plataforma()->id_cuenta;

        $this->assertNull(CuentaPlataforma::separarDeNegocio());
        $this->assertSame($antes, Cuenta::plataforma()->id_cuenta);
    }
}
