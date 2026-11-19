<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\EscenarioDosCuentas;
use Tests\TestCase;

/**
 * El administrador de la cuenta A no puede leer, modificar ni usar datos de la cuenta B.
 */
class AislamientoCuentasTest extends TestCase
{
    use RefreshDatabase, EscenarioDosCuentas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    /**
     * Cada endpoint que recibe un id, con el id de un registro de la cuenta B.
     * [método, url, cuerpo]
     */
    private function endpointsConIdDeB(): array
    {
        $b = $this->b;

        return [
            ['GET', "/api/empresas/{$b['empresa']->id_empresa}", []],
            ['PUT', "/api/empresas/{$b['empresa']->id_empresa}", ['razon_social' => 'Hackeada']],
            ['DELETE', "/api/empresas/{$b['empresa']->id_empresa}", []],
            ['DELETE', "/api/empresas/{$b['empresa']->id_empresa}/logo", []],

            ['GET', "/api/usuarios/{$b['vendedor']->id_usuario}", []],
            ['PUT', "/api/usuarios/{$b['vendedor']->id_usuario}", ['nombre_usuario' => 'Hackeado']],
            ['PATCH', "/api/usuarios/{$b['vendedor']->id_usuario}/toggle-estado", []],
            ['DELETE', "/api/usuarios/{$b['vendedor']->id_usuario}", []],

            ['GET', "/api/clientes/{$b['cliente']}", []],
            ['PUT', "/api/clientes/{$b['cliente']}", ['razon_social' => 'Hackeado']],
            ['DELETE', "/api/clientes/{$b['cliente']}", []],
            ['POST', "/api/clientes/{$b['cliente']}/contactos", ['tipo_documento' => 'DNI', 'num_documento' => '1', 'nombres_apellidos' => 'X']],
            ['DELETE', "/api/clientes/{$b['cliente']}/contactos/{$b['contacto']}", []],

            ['PUT', "/api/categorias/{$b['categoria']}", ['nombre_categoria' => 'Hackeada']],
            ['DELETE', "/api/categorias/{$b['categoria']}", []],
            ['PUT', "/api/marcas/{$b['marca']}", ['nombre_marca' => 'Hackeada']],
            ['DELETE', "/api/marcas/{$b['marca']}", []],

            ['GET', "/api/productos/{$b['producto']}", []],
            ['PUT', "/api/productos/{$b['producto']}", ['nombre_producto' => 'Hackeado']],
            ['DELETE', "/api/productos/{$b['producto']}", []],

            ['GET', "/api/almacenes/{$b['almacen']}", []],
            ['PUT', "/api/almacenes/{$b['almacen']}", ['nombre_almacen' => 'Hackeado']],
            ['DELETE', "/api/almacenes/{$b['almacen']}", []],

            ['PUT', "/api/stock/{$b['stock']}/limites", ['stock_minimo' => 1, 'stock_maximo' => 2]],
            ['PUT', "/api/series/{$b['serie']}/estado", ['estado' => 'BAJA']],

            ['GET', "/api/cotizaciones/{$b['cotizacion']}", []],
            ['PATCH', "/api/cotizaciones/{$b['cotizacion']}/estado", ['estado' => 'APROBADA']],
            ['POST', "/api/ventas/from-cotizacion/{$b['cotizacion']}", ['tipo_documento' => 'FACTURA', 'fecha_emision' => '2026-11-19']],

            ['GET', "/api/ventas/{$b['venta']}", []],
            ['POST', "/api/ventas/{$b['venta']}/pagos", ['monto' => 1, 'fecha_pago' => '2026-11-19', 'metodo_pago' => 'EFECTIVO']],
            ['PATCH', "/api/ventas/{$b['venta']}/anular", []],

            ['GET', "/api/crm/oportunidades/{$b['oportunidad']}", []],
            ['PUT', "/api/crm/oportunidades/{$b['oportunidad']}", ['titulo' => 'Hackeado']],
            ['PATCH', "/api/crm/oportunidades/{$b['oportunidad']}/etapa", ['etapa' => 'GANADO']],
            ['POST', "/api/crm/oportunidades/{$b['oportunidad']}/vincular-cotizacion", ['id_cotizacion' => null]],
            ['DELETE', "/api/crm/oportunidades/{$b['oportunidad']}", []],
            ['GET', "/api/crm/actividades/{$b['actividad']}/archivo", []],

            ['GET', "/api/helpdesk/atenciones/{$b['atencion']}", []],
            ['POST', "/api/helpdesk/atenciones/{$b['atencion']}/pausa", []],
            ['POST', "/api/helpdesk/atenciones/{$b['atencion']}/reanudar", []],
        ];
    }

    public function test_no_puede_acceder_por_id_a_registros_de_otra_cuenta(): void
    {
        $this->como($this->a['admin']);

        foreach ($this->endpointsConIdDeB() as [$metodo, $url, $cuerpo]) {
            $respuesta = $this->json($metodo, $url, $cuerpo);

            $this->assertSame(404, $respuesta->status(), "{$metodo} {$url} debió responder 404 y respondió {$respuesta->status()}: {$respuesta->getContent()}");
        }

        // Nada de la cuenta B cambió
        $this->assertSame('Cliente de B', Cliente::withoutGlobalScopes()->find($this->b['cliente'])->razon_social);
        $this->assertSame('Laptop B', Producto::withoutGlobalScopes()->find($this->b['producto'])->nombre_producto);
        $this->assertTrue((bool) $this->b['vendedor']->fresh()->activo);
    }

    public function test_los_listados_solo_muestran_datos_de_la_propia_cuenta(): void
    {
        $this->como($this->a['admin']);

        $listados = [
            '/api/empresas' => ['data.*.id_empresa', $this->b['empresa']->id_empresa],
            '/api/usuarios' => ['data.*.id_usuario', $this->b['vendedor']->id_usuario],
            '/api/clientes' => ['data.data.*.id_cliente', $this->b['cliente']],
            '/api/categorias' => ['data.*.id_categoria', $this->b['categoria']],
            '/api/marcas' => ['data.*.id_marca', $this->b['marca']],
            '/api/tipo-cambio' => ['data.*.id_tc', $this->b['tipo_cambio']],
            '/api/productos' => ['data.data.*.id_producto', $this->b['producto']],
            '/api/almacenes' => ['data.*.id_almacen', $this->b['almacen']],
            '/api/stock' => ['data.data.*.id_stock', $this->b['stock']],
            '/api/series' => ['data.data.*.id_serie', $this->b['serie']],
            '/api/movimientos' => ['data.data.*.id_producto', $this->b['producto']],
            '/api/cotizaciones' => ['data.data.*.id_cotizacion', $this->b['cotizacion']],
            '/api/ventas' => ['data.data.*.id_venta', $this->b['venta']],
            '/api/crm/oportunidades' => ['data.*.id_oportunidad', $this->b['oportunidad']],
            '/api/helpdesk/atenciones' => ['data.data.*.id_atencion', $this->b['atencion']],
        ];

        foreach ($listados as $url => [$ruta, $idDeB]) {
            $ids = $this->getJson($url)->assertOk()->json($ruta);

            $this->assertNotEmpty($ids, "{$url} debería listar los datos propios de la cuenta A");
            $this->assertNotContains($idDeB, $ids, "{$url} muestra un registro de la cuenta B");
        }

        // La búsqueda con varios campos (OR) no se salta el filtro
        $ids = $this->getJson('/api/clientes?search=Cliente')->json('data.data.*.id_cliente');
        $this->assertSame([$this->a['cliente']], $ids);
    }

    public function test_no_puede_usar_ids_de_otra_cuenta_al_crear_registros(): void
    {
        $this->como($this->a['admin']);
        $a = $this->a;
        $b = $this->b;

        $intentos = [
            ['/api/cotizaciones', $this->datosCotizacion($a, ['id_cliente' => $b['cliente']]), 'id_cliente'],
            ['/api/cotizaciones', $this->datosCotizacion($a, ['detalles' => [['id_producto' => $b['producto']]]]), 'detalles.0.id_producto'],
            ['/api/cotizaciones', $this->datosCotizacion($a, ['id_empresa' => $b['empresa']->id_empresa]), 'id_empresa'],
            ['/api/ventas', [
                'id_empresa' => $a['empresa']->id_empresa, 'id_cliente' => $a['cliente'], 'tipo_documento' => 'FACTURA',
                'fecha_emision' => '2026-11-19', 'descontar_stock_almacen_id' => $b['almacen'],
                'detalles' => [['descripcion' => 'X', 'cantidad' => 1, 'precio_unitario' => 1]],
            ], 'descontar_stock_almacen_id'],
            ['/api/movimientos', [
                'id_producto' => $b['producto'], 'id_almacen_origen' => $a['almacen'], 'tipo_movimiento' => 'ENTRADA', 'cantidad' => 1,
            ], 'id_producto'],
            ['/api/series', [
                'id_producto' => $a['producto'], 'id_almacen' => $b['almacen'], 'numero_serie' => 'SN-X',
            ], 'id_almacen'],
            ['/api/productos', [
                'tipo' => 'PRODUCTO', 'codigo_interno' => 'P-2', 'nombre_producto' => 'X', 'unidad_medida' => 'UND',
                'id_categoria' => $b['categoria'],
            ], 'id_categoria'],
            ['/api/almacenes', ['id_empresa' => $b['empresa']->id_empresa, 'nombre_almacen' => 'X'], 'id_empresa'],
            ['/api/usuarios', [
                'id_empresa' => $b['empresa']->id_empresa, 'nombre_usuario' => 'X', 'rol' => 'VENDEDOR',
                'email' => 'nuevo@prueba.com', 'password' => 'secreto123',
            ], 'id_empresa'],
            ['/api/crm/oportunidades', [
                'id_empresa' => $a['empresa']->id_empresa, 'id_cliente' => $b['cliente'], 'titulo' => 'X',
            ], 'id_cliente'],
            ['/api/crm/actividades', [
                'id_empresa' => $a['empresa']->id_empresa, 'id_cliente' => $a['cliente'],
                'id_oportunidad' => $b['oportunidad'], 'tipo' => 'NOTA', 'titulo' => 'X',
            ], 'id_oportunidad'],
            ['/api/helpdesk/atenciones', [
                'id_empresa' => $a['empresa']->id_empresa, 'id_cliente' => $a['cliente'], 'id_contacto' => $b['contacto'],
                'tipo_usuario' => 'CORPORATIVO', 'documento_solicitante' => '1', 'nombre_solicitante' => 'X',
                'categoria' => 'HARDWARE', 'canal_atencion' => 'LLAMADA', 'descripcion_requerimiento' => 'X',
                'acciones_realizadas' => 'X', 'estado_cierre' => 'EN_PROCESO', 'fecha_atencion' => '2026-11-19',
                'hora_inicio' => '2026-11-19 09:00:00', 'hora_fin' => '2026-11-19 10:00:00',
            ], 'id_contacto'],
        ];

        foreach ($intentos as [$url, $cuerpo, $campo]) {
            $this->postJson($url, $cuerpo)->assertStatus(422)->assertJsonValidationErrors($campo);
        }
    }

    public function test_el_selector_de_empresa_no_acepta_empresas_de_otra_cuenta(): void
    {
        $this->como($this->a['admin']);

        $ids = $this->withHeader('X-Empresa-Id', (string) $this->b['empresa']->id_empresa)
            ->getJson('/api/productos')
            ->assertOk()
            ->json('data.data.*.id_producto');

        $this->assertSame([$this->a['producto']], $ids);
    }

    public function test_los_correlativos_son_independientes_por_empresa(): void
    {
        $this->como($this->a['admin']);
        $numeroA = $this->getJson("/api/cotizaciones/{$this->a['cotizacion']}")->json('data.numero_cotizacion');

        $this->como($this->b['admin']);
        $numeroB = $this->getJson("/api/cotizaciones/{$this->b['cotizacion']}")->json('data.numero_cotizacion');

        // Cada empresa empieza su propia numeración
        $this->assertSame($numeroA, $numeroB);
        $this->assertStringEndsWith('-0001', $numeroA);
    }
}
