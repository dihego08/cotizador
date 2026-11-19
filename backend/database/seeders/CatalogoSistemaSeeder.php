<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogos comunes a todas las cuentas: módulos (base de los permisos)
 * y categorías de helpdesk. Se puede ejecutar varias veces sin duplicar.
 */
class CatalogoSistemaSeeder extends Seeder
{
    // clave => [clave del padre, nombre, icono, orden]
    private const MODULOS = [
        'operaciones' => [null, 'Operaciones', 'fa-briefcase', 1],
        'almacen' => [null, 'Almacén', 'fa-warehouse', 2],
        'finanzas' => [null, 'Finanzas', 'fa-wallet', 3],
        'gestion_corporativa' => [null, 'Gestión Corporativa', 'fa-chart-pie', 4],
        'admin_sistema' => [null, 'Admin Sistema', 'fa-user-shield', 5],
        'cotizaciones' => ['operaciones', 'Cotizaciones', 'fa-file-invoice-dollar', 1],
        'clientes' => ['operaciones', 'Clientes', 'fa-users', 2],
        'helpdesk' => ['operaciones', 'Help Desk & Soporte', 'fa-headset', 3],
        'productos' => ['almacen', 'Productos y Servicios', 'fa-boxes-stacked', 1],
        'categorias_marcas' => ['almacen', 'Categorías y Marcas', 'fa-layer-group', 2],
        'stock' => ['almacen', 'Control de Stock', 'fa-chart-simple', 3],
        'movimientos' => ['almacen', 'Movimientos', 'fa-arrow-right-left', 4],
        'series' => ['almacen', 'Series de Productos', 'fa-barcode', 5],
        'tipo_cambio' => ['finanzas', 'Tipo de Cambio', 'fa-dollar-sign', 1],
        'corp_gerencia' => ['gestion_corporativa', 'Gerencia y Dirección', 'fa-user-tie', 1],
        'corp_control' => ['gestion_corporativa', 'Control de Gestión', 'fa-shield-halved', 2],
        'corp_admin' => ['gestion_corporativa', 'Administración General', 'fa-file-invoice', 3],
        'usuarios' => ['admin_sistema', 'Gestión de Usuarios', 'fa-users-gear', 1],
        'empresas' => ['admin_sistema', 'Gestión de Empresas', 'fa-building', 2],
        'almacenes' => ['admin_sistema', 'Almacenes', 'fa-warehouse', 3],
    ];

    // categoría => subcategorías
    private const HELPDESK = [
        'SOFTWARE Y SISTEMAS' => ['Sistema de Almacén / ERP', 'Licencias / Sistema Operativo', 'Correo / Ofimática'],
        'HARDWARE Y PERIFÉRICOS' => ['Impresoras / Teteck', 'PC / Laptop / Servidor'],
        'REDES Y CONECTIVIDAD' => ['Cableado Estructurado'],
        'SEGURIDAD Y CCTV' => ['Cámara Ezviz / Hikvision', 'Control de Acceso / Biométrico'],
    ];

    public function run(): void
    {
        foreach (self::MODULOS as $clave => [$padre, $nombre, $icono, $orden]) {
            DB::table('modulos')->updateOrInsert(['clave' => $clave], [
                'id_padre' => $padre ? DB::table('modulos')->where('clave', $padre)->value('id_modulo') : null,
                'nombre' => $nombre,
                'icono' => $icono,
                'orden' => $orden,
            ]);
        }

        if (DB::table('helpdesk_categorias')->exists()) {
            return;
        }

        foreach (self::HELPDESK as $categoria => $subcategorias) {
            $idPadre = DB::table('helpdesk_categorias')->insertGetId(['nombre' => $categoria, 'activo' => true]);
            foreach ($subcategorias as $sub) {
                DB::table('helpdesk_categorias')->insert(['id_padre' => $idPadre, 'nombre' => $sub, 'activo' => true]);
            }
        }
    }
}
