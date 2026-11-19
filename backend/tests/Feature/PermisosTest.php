<?php

namespace Tests\Feature;

use App\Models\Modulo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\EscenarioDosCuentas;
use Tests\TestCase;

/**
 * Los permisos por módulo se validan en el servidor, no solo en el menú del frontend.
 */
class PermisosTest extends TestCase
{
    use RefreshDatabase, EscenarioDosCuentas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    public function test_un_usuario_sin_permisos_no_puede_modificar_ningun_modulo(): void
    {
        $this->como($this->a['vendedor']);
        $a = $this->a;

        $acciones = [
            ['POST', '/api/productos', []],
            ['PUT', "/api/productos/{$a['producto']}", []],
            ['POST', '/api/categorias', []],
            ['POST', '/api/clientes', []],
            ['DELETE', "/api/clientes/{$a['cliente']}", []],
            ['GET', '/api/cotizaciones', []],
            ['POST', '/api/cotizaciones', []],
            ['GET', '/api/ventas', []],
            ['POST', "/api/ventas/{$a['venta']}/pagos", []],
            ['GET', '/api/crm/oportunidades', []],
            ['GET', '/api/movimientos', []],
            ['POST', '/api/movimientos', []],
            ['GET', '/api/series', []],
            ['PUT', "/api/stock/{$a['stock']}/limites", []],
            ['POST', '/api/tipo-cambio', []],
            ['GET', '/api/helpdesk/atenciones', []],
            ['POST', '/api/almacenes', []],
            ['POST', '/api/empresas', []],
            ['DELETE', "/api/empresas/{$a['empresa']->id_empresa}", []],
            ['GET', '/api/usuarios', []],
            ['POST', '/api/usuarios', []],
            ['GET', '/api/modulos', []],
            ['GET', '/api/plataforma/cuentas', []],
        ];

        foreach ($acciones as [$metodo, $url, $cuerpo]) {
            $respuesta = $this->json($metodo, $url, $cuerpo);
            $this->assertSame(403, $respuesta->status(), "{$metodo} {$url} debió responder 403 y respondió {$respuesta->status()}");
        }
    }

    public function test_las_lecturas_de_catalogos_compartidos_siguen_abiertas(): void
    {
        $this->como($this->a['vendedor']);

        foreach (['/api/productos', '/api/clientes', '/api/almacenes', '/api/categorias', '/api/marcas', '/api/stock', '/api/tipo-cambio/actual', '/api/dashboard/metrics', '/api/empresas'] as $url) {
            $this->getJson($url)->assertOk();
        }
    }

    public function test_el_permiso_del_modulo_habilita_solo_ese_modulo(): void
    {
        $vendedor = $this->a['vendedor'];
        $vendedor->permisos()->sync([Modulo::where('clave', 'cotizaciones')->value('id_modulo')]);
        $this->como($vendedor->fresh());

        $this->postJson('/api/cotizaciones', $this->datosCotizacion($this->a))->assertCreated();
        $this->getJson('/api/ventas')->assertOk();
        $this->postJson('/api/productos', [])->assertForbidden();
    }

    public function test_un_no_administrador_solo_ve_su_empresa_e_ignora_el_selector(): void
    {
        $this->como($this->a['admin']);
        $otra = $this->postJson('/api/empresas', ['razon_social' => 'Segunda S.A.C.', 'ruc' => '20100000009'])
            ->assertCreated()->json('data.id_empresa');

        $this->como($this->a['vendedor']);

        $ids = $this->getJson('/api/empresas')->json('data.*.id_empresa');
        $this->assertSame([$this->a['empresa']->id_empresa], $ids);

        // El header de empresa activa solo lo pueden usar los administradores
        $productos = $this->withHeader('X-Empresa-Id', (string) $otra)->getJson('/api/productos')->json('data.data.*.id_producto');
        $this->assertSame([$this->a['producto']], $productos);
    }

    public function test_quien_gestiona_usuarios_sin_ser_admin_no_puede_escalar_privilegios(): void
    {
        $vendedor = $this->a['vendedor'];
        $vendedor->permisos()->sync([Modulo::where('clave', 'usuarios')->value('id_modulo')]);
        $this->como($vendedor->fresh());

        $nuevo = [
            'id_empresa' => $this->a['empresa']->id_empresa, 'nombre_usuario' => 'Nuevo',
            'email' => 'nuevo@prueba.com', 'password' => 'secreto123',
        ];

        $this->postJson('/api/usuarios', [...$nuevo, 'rol' => 'ADMIN'])->assertStatus(422)->assertJsonValidationErrors('rol');
        $this->postJson('/api/usuarios', [...$nuevo, 'rol' => 'SUPERADMIN'])->assertStatus(422)->assertJsonValidationErrors('rol');
        $this->putJson("/api/usuarios/{$this->a['admin']->id_usuario}", ['password' => 'otraclave123'])->assertForbidden();
        $this->deleteJson("/api/usuarios/{$this->a['admin']->id_usuario}")->assertForbidden();

        // Sí puede crear usuarios comunes
        $this->postJson('/api/usuarios', [...$nuevo, 'rol' => 'VENDEDOR'])->assertCreated();
    }

    public function test_un_administrador_no_puede_crear_super_administradores(): void
    {
        $this->como($this->a['admin']);

        $this->postJson('/api/usuarios', [
            'id_empresa' => $this->a['empresa']->id_empresa, 'nombre_usuario' => 'X', 'rol' => 'SUPERADMIN',
            'email' => 'x@prueba.com', 'password' => 'secreto123',
        ])->assertStatus(422)->assertJsonValidationErrors('rol');
    }
}
