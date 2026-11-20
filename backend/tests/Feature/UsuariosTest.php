<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\EscenarioDosCuentas;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use RefreshDatabase, EscenarioDosCuentas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CatalogoSistemaSeeder::class);
        $this->a = $this->crearCuenta('A', '20100000001');
    }

    public function test_se_puede_modificar_y_borrar_el_cargo_y_el_celular(): void
    {
        $this->como($this->a['admin']);
        $url = "/api/usuarios/{$this->a['vendedor']->id_usuario}";

        $this->putJson($url, ['cargo' => 'Ejecutivo de Ventas', 'celular' => '987654321'])->assertOk();
        $vendedor = $this->a['vendedor']->fresh();
        $this->assertSame('Ejecutivo de Ventas', $vendedor->cargo);
        $this->assertSame('987654321', $vendedor->celular);

        // Al enviarlos vacíos se borran
        $this->putJson($url, ['cargo' => '', 'celular' => ''])->assertOk();
        $vendedor = $this->a['vendedor']->fresh();
        $this->assertNull($vendedor->cargo);
        $this->assertNull($vendedor->celular);
    }
}
