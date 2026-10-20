<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones';
    protected $primaryKey = 'id_cotizacion';
    public $timestamps = false;

    protected $fillable = [
        'id_empresa',
        'numero_cotizacion',
        'asunto',
        'id_cliente',
        'atencion',
        'forma_pago',
        'moneda',
        'tipo_cambio',
        'subtotal',
        'igv',
        'total',
        'validez_dias',
        'observacion',
        'condiciones_comerciales',
        'alcance',
        'restricciones',
        'fecha_emision',
        'estado',
        'id_usuario',
        'created_at',
        'vigencia',
        'tiempo_entrega',
    ];

    protected $casts = [
        'tipo_cambio' => 'decimal:3',
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_emision' => 'date',
        'created_at' => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function detalles()
    {
        return $this->hasMany(CotizacionDetalle::class, 'id_cotizacion', 'id_cotizacion');
    }
}
