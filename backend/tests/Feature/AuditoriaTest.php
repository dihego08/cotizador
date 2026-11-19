<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\EscenarioDosCuentas;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase, EscenarioDosCuentas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    public function test_las_operaciones_sensibles_quedan_registradas(): void
    {
        $this->como($this->a['admin']);

        $this->patchJson("/api/ventas/{$this->a['venta']}/anular")->assertOk();
        $this->patchJson("/api/usuarios/{$this->a['vendedor']->id_usuario}/toggle-estado")->assertOk();

        $acciones = collect($this->getJson('/api/auditoria')->assertOk()->json('data.data'))
            ->map(fn($r) => "{$r['accion']} {$r['entidad']}");

        $this->assertContains('ANULAR venta', $acciones);
        $this->assertContains('DESACTIVAR usuario', $acciones);
        $this->assertContains('EMITIR venta', $acciones);
        $this->assertContains('ENTRADA movimiento', $acciones);
    }

    public function test_el_login_fallido_se_registra_en_la_cuenta_del_usuario(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'adminA@prueba.com', 'password' => 'incorrecta'])->assertStatus(422);

        $this->como($this->a['admin']);
        $this->getJson('/api/auditoria')->assertOk()->assertJsonPath('data.data.0.accion', 'LOGIN_FALLIDO');
    }

    public function test_cada_cuenta_solo_ve_su_propio_registro(): void
    {
        $this->como($this->b['admin']);
        $this->patchJson("/api/ventas/{$this->b['venta']}/anular")->assertOk();

        $this->como($this->a['admin']);
        $registros = $this->getJson('/api/auditoria')->json('data.data');

        $this->assertNotEmpty($registros);
        $this->assertEmpty(array_filter($registros, fn($r) => $r['id_cuenta'] !== $this->a['cuenta']->id_cuenta));
        $this->assertNotContains('ANULAR', array_column($registros, 'accion'));
    }

    public function test_sin_permiso_de_usuarios_no_se_ve_el_registro(): void
    {
        $this->como($this->a['vendedor'])->getJson('/api/auditoria')->assertForbidden();
    }
}
