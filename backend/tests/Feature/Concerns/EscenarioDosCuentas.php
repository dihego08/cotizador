<?php

namespace Tests\Feature\Concerns;

use App\Models\Cuenta;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Support\CuentaActual;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

/**
 * Dos cuentas (clientes del SaaS) con datos en todos los módulos,
 * creados con la propia API como lo haría cada cliente.
 */
trait EscenarioDosCuentas
{
    protected array $a;
    protected array $b;

    protected function crearEscenario(): void
    {
        $this->seed(\Database\Seeders\CatalogoSistemaSeeder::class);

        $this->a = $this->crearCuentaConDatos('A', '20100000001');
        $this->b = $this->crearCuentaConDatos('B', '20200000002');

        app(CuentaActual::class)->limpiar();
    }

    protected function crearCuenta(string $nombre, string $ruc): array
    {
        $cuenta = Cuenta::create(['nombre' => "Cuenta {$nombre}", 'estado' => 'ACTIVA']);

        return app(CuentaActual::class)->ejecutarComo($cuenta->id_cuenta, function () use ($cuenta, $nombre, $ruc) {
            $empresa = Empresa::create(['razon_social' => "Empresa {$nombre} S.A.C.", 'ruc' => $ruc, 'es_principal' => true]);

            $admin = $this->crearUsuario($empresa, 'ADMIN', "admin{$nombre}@prueba.com");
            $vendedor = $this->crearUsuario($empresa, 'VENDEDOR', "vendedor{$nombre}@prueba.com");

            return compact('cuenta', 'empresa', 'admin', 'vendedor');
        });
    }

    protected function crearUsuario(Empresa $empresa, string $rol, string $email): Usuario
    {
        return Usuario::create([
            'id_empresa' => $empresa->id_empresa,
            'nombre_usuario' => "Usuario {$email}",
            'rol' => $rol,
            'email' => $email,
            'password_hash' => Hash::make('secreto123'),
            'activo' => true,
        ]);
    }

    private function crearCuentaConDatos(string $nombre, string $ruc): array
    {
        $c = $this->crearCuenta($nombre, $ruc);
        $this->como($c['admin']);

        $id = fn($respuesta, $clave) => $respuesta->assertSuccessful()->json("data.{$clave}");

        $c['categoria'] = $id($this->postJson('/api/categorias', ['nombre_categoria' => 'Laptops']), 'id_categoria');
        $c['marca'] = $id($this->postJson('/api/marcas', ['nombre_marca' => 'HP']), 'id_marca');
        $c['tipo_cambio'] = $id($this->postJson('/api/tipo-cambio', ['fecha' => '2026-11-19', 'compra' => 3.7, 'venta' => 3.75]), 'id_tc');

        // Mismo RUC de cliente en ambas cuentas: el documento es único por cuenta, no global
        $c['cliente'] = $id($this->postJson('/api/clientes', [
            'tipo_documento' => 'RUC', 'num_documento' => '20999999999', 'razon_social' => "Cliente de {$nombre}",
        ]), 'id_cliente');
        $c['contacto'] = $id($this->postJson("/api/clientes/{$c['cliente']}/contactos", [
            'tipo_documento' => 'DNI', 'num_documento' => '40000000', 'nombres_apellidos' => "Contacto {$nombre}",
        ]), 'id_contacto');

        $c['almacen'] = $id($this->postJson('/api/almacenes', [
            'id_empresa' => $c['empresa']->id_empresa, 'nombre_almacen' => "Almacén {$nombre}",
        ]), 'id_almacen');

        $c['producto'] = $id($this->postJson('/api/productos', [
            'tipo' => 'PRODUCTO', 'codigo_interno' => 'PROD-001', 'nombre_producto' => "Laptop {$nombre}",
            'unidad_medida' => 'UND', 'id_categoria' => $c['categoria'], 'precio_costo' => 100,
        ]), 'id_producto');

        $this->postJson('/api/movimientos', [
            'id_producto' => $c['producto'], 'id_almacen_origen' => $c['almacen'],
            'tipo_movimiento' => 'ENTRADA', 'cantidad' => 10,
        ])->assertSuccessful();
        $c['stock'] = $this->getJson('/api/stock')->json('data.data.0.id_stock');

        $c['serie'] = $id($this->postJson('/api/series', [
            'id_producto' => $c['producto'], 'id_almacen' => $c['almacen'], 'numero_serie' => 'SN-0001',
        ]), 'id_serie');

        $c['cotizacion'] = $id($this->postJson('/api/cotizaciones', $this->datosCotizacion($c)), 'id_cotizacion');

        $c['venta'] = $id($this->postJson('/api/ventas', [
            'id_empresa' => $c['empresa']->id_empresa, 'id_cliente' => $c['cliente'],
            'tipo_documento' => 'FACTURA', 'fecha_emision' => '2026-11-19',
            'detalles' => [['id_producto' => $c['producto'], 'descripcion' => 'Laptop', 'cantidad' => 1, 'precio_unitario' => 500]],
        ]), 'id_venta');

        $c['oportunidad'] = $id($this->postJson('/api/crm/oportunidades', [
            'id_empresa' => $c['empresa']->id_empresa, 'id_cliente' => $c['cliente'], 'titulo' => "Trato {$nombre}",
        ]), 'id_oportunidad');
        $c['actividad'] = $id($this->postJson('/api/crm/actividades', [
            'id_empresa' => $c['empresa']->id_empresa, 'id_cliente' => $c['cliente'],
            'id_oportunidad' => $c['oportunidad'], 'tipo' => 'NOTA', 'titulo' => 'Llamada inicial',
        ]), 'id_actividad');

        $c['atencion'] = $id($this->postJson('/api/helpdesk/atenciones', [
            'id_empresa' => $c['empresa']->id_empresa, 'id_cliente' => $c['cliente'], 'id_contacto' => $c['contacto'],
            'tipo_usuario' => 'CORPORATIVO', 'documento_solicitante' => '40000000', 'nombre_solicitante' => 'Contacto',
            'categoria' => 'HARDWARE', 'canal_atencion' => 'LLAMADA', 'descripcion_requerimiento' => 'No enciende',
            'acciones_realizadas' => 'Revisión', 'estado_cierre' => 'EN_PROCESO', 'fecha_atencion' => '2026-11-19',
            'hora_inicio' => '2026-11-19 09:00:00', 'hora_fin' => '2026-11-19 10:00:00',
        ]), 'id_atencion');

        return $c;
    }

    protected function datosCotizacion(array $c, array $cambios = []): array
    {
        return array_replace_recursive([
            'id_empresa' => $c['empresa']->id_empresa,
            'id_cliente' => $c['cliente'],
            'fecha_emision' => '2026-11-19',
            'detalles' => [['id_producto' => $c['producto'], 'descripcion' => 'Laptop', 'cantidad' => 1, 'precio_unitario' => 500]],
        ], $cambios);
    }

    /**
     * Autentica como el usuario (como si enviara su token) y deja que el middleware defina la cuenta.
     */
    protected function como(Usuario $usuario): static
    {
        app(CuentaActual::class)->limpiar();
        Sanctum::actingAs($usuario);

        return $this;
    }
}
