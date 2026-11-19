<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACuentaPorPadre;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CotizacionDetalle extends Model
{
    use HasFactory, PerteneceACuentaPorPadre;

    protected $table = 'cotizacion_detalles';
    protected $primaryKey = 'id_detalle';

    // Se aísla por cuenta a través de este registro padre
    protected string $relacionCuenta = 'cotizacion';
    public $timestamps = false;

    protected $fillable = [
        'id_cotizacion',
        'id_producto',
        'descripcion',
        'nota_item',
        'marca',
        'cantidad',
        'unidad_medida',
        'porcentaje_utilidad',
        'precio_unitario',
        'subtotal',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'porcentaje_utilidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion', 'id_cotizacion');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}
