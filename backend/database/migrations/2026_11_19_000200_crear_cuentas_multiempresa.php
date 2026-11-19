<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas que pertenecen directamente a una cuenta (cliente del SaaS), con su llave primaria.
     * Las tablas hijas (detalles, pagos, contactos, stock, series, movimientos, pausas)
     * se aíslan a través de su registro padre.
     */
    private const TABLAS = [
        'empresas' => 'id_empresa',
        'usuarios' => 'id_usuario',
        'clientes' => 'id_cliente',
        'categorias' => 'id_categoria',
        'marcas' => 'id_marca',
        'tipo_cambio' => 'id_tc',
        'productos' => 'id_producto',
        'almacenes' => 'id_almacen',
        'cotizaciones' => 'id_cotizacion',
        'ventas' => 'id_venta',
        'crm_oportunidades' => 'id_oportunidad',
        'crm_actividades' => 'id_actividad',
        'helpdesk_atenciones' => 'id_atencion',
    ];

    /**
     * Restricciones de "único" que pasan de globales a ser por cuenta, empresa o producto:
     * tabla => [índice actual, columnas actuales, índice nuevo, columnas nuevas]
     */
    private const UNICOS = [
        'empresas' => ['uk_empresas_ruc', ['ruc'], 'uk_empresas_cuenta_ruc', ['id_cuenta', 'ruc']],
        'clientes' => ['num_documento', ['num_documento'], 'uk_clientes_cuenta_documento', ['id_cuenta', 'num_documento']],
        'categorias' => ['uk_categorias_nombre', ['nombre_categoria'], 'uk_categorias_cuenta_nombre', ['id_cuenta', 'nombre_categoria']],
        'marcas' => ['nombre_marca', ['nombre_marca'], 'uk_marcas_cuenta_nombre', ['id_cuenta', 'nombre_marca']],
        'tipo_cambio' => ['fecha', ['fecha'], 'uk_tipo_cambio_cuenta_fecha', ['id_cuenta', 'fecha']],
        'cotizaciones' => ['numero_cotizacion', ['numero_cotizacion'], 'uk_cotizaciones_empresa_numero', ['id_empresa', 'numero_cotizacion']],
        'helpdesk_atenciones' => ['uk_codigo_ticket', ['codigo_ticket'], 'uk_helpdesk_empresa_ticket', ['id_empresa', 'codigo_ticket']],
        'series_productos' => ['uk_series_numero_serie', ['numero_serie'], 'uk_series_producto_numero', ['id_producto', 'numero_serie']],
    ];

    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->increments('id_cuenta');
            $table->string('nombre', 150);
            $table->enum('estado', ['ACTIVA', 'SUSPENDIDA'])->default('ACTIVA');
            $table->string('plan', 50)->nullable();
            // Límites del plan (null = sin límite)
            $table->unsignedInteger('max_empresas')->nullable();
            $table->unsignedInteger('max_usuarios')->nullable();
            $table->unsignedInteger('max_almacenes')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        // Los datos existentes pasan a una primera cuenta
        $idCuenta = null;
        if (DB::table('empresas')->exists()) {
            $nombre = DB::table('empresas')->orderByDesc('es_principal')->orderBy('id_empresa')->value('razon_social');
            $idCuenta = DB::table('cuentas')->insertGetId([
                'nombre' => $nombre,
                'estado' => 'ACTIVA',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (self::TABLAS as $tabla => $pk) {
            Schema::table($tabla, function (Blueprint $table) use ($pk) {
                $table->unsignedInteger('id_cuenta')->nullable()->after($pk);
            });

            if ($idCuenta) {
                DB::table($tabla)->update(['id_cuenta' => $idCuenta]);
            }

            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->unsignedInteger('id_cuenta')->nullable(false)->change();
                $table->index('id_cuenta', "idx_{$tabla}_cuenta");
                $table->foreign('id_cuenta', "fk_{$tabla}_cuentas")
                    ->references('id_cuenta')->on('cuentas')->cascadeOnUpdate();
            });
        }

        foreach (self::UNICOS as $tabla => [$indiceActual, , $indiceNuevo, $columnasNuevas]) {
            Schema::table($tabla, function (Blueprint $table) use ($indiceActual, $indiceNuevo, $columnasNuevas) {
                $table->dropUnique($indiceActual);
                $table->unique($columnasNuevas, $indiceNuevo);
            });
        }

        // Contraseña temporal: el usuario debe cambiarla en su primer ingreso
        Schema::table('usuarios', function (Blueprint $table) {
            $table->boolean('debe_cambiar_password')->default(false)->after('password_hash');
        });

        // Módulos que el menú ya usaba pero no existían como permiso asignable
        $this->agregarModulo('almacen', 'series', 'Series de Productos', 'fa-barcode', 5);
        $this->agregarModulo('admin_sistema', 'almacenes', 'Almacenes', 'fa-warehouse', 3);
    }

    public function down(): void
    {
        DB::table('modulos')->whereIn('clave', ['series', 'almacenes'])->delete();

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('debe_cambiar_password');
        });

        foreach (self::UNICOS as $tabla => [$indiceActual, $columnasActuales, $indiceNuevo]) {
            Schema::table($tabla, function (Blueprint $table) use ($indiceActual, $columnasActuales, $indiceNuevo) {
                $table->dropUnique($indiceNuevo);
                $table->unique($columnasActuales, $indiceActual);
            });
        }

        foreach (array_keys(self::TABLAS) as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->dropForeign("fk_{$tabla}_cuentas");
                $table->dropIndex("idx_{$tabla}_cuenta");
                $table->dropColumn('id_cuenta');
            });
        }

        Schema::dropIfExists('cuentas');
    }

    private function agregarModulo(string $clavePadre, string $clave, string $nombre, string $icono, int $orden): void
    {
        $idPadre = DB::table('modulos')->where('clave', $clavePadre)->value('id_modulo');

        if (!$idPadre || DB::table('modulos')->where('clave', $clave)->exists()) {
            return;
        }

        DB::table('modulos')->insert([
            'id_padre' => $idPadre,
            'clave' => $clave,
            'nombre' => $nombre,
            'icono' => $icono,
            'orden' => $orden,
        ]);
    }
};
