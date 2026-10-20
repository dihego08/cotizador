<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';
    protected $primaryKey = 'id_venta';
    public $timestamps = true;

    protected $fillable = [
        'id_empresa',
        'id_cliente',
        'id_cotizacion',
        'id_usuario',
        'tipo_documento',
        'serie',
        'correlativo',
        'serie_correlativo',
        'moneda',
        'tipo_cambio',
        'forma_pago',
        'subtotal',
        'igv',
        'total',
        'total_pagado',
        'saldo_pendiente',
        'estado_pago',
        'estado_anulado',
        'fecha_emision',
        'fecha_vencimiento',
        'observaciones',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
        'total_pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'tipo_cambio' => 'decimal:3',
        'estado_anulado' => 'boolean',
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion', 'id_cotizacion');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class, 'id_venta', 'id_venta');
    }

    public function pagos()
    {
        return $this->hasMany(VentaPago::class, 'id_venta', 'id_venta')->orderBy('fecha_pago', 'desc');
    }
}
