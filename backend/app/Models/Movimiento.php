<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACuentaPorPadre;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    use HasFactory, PerteneceACuentaPorPadre;

    protected $table = 'movimientos';
    protected $primaryKey = 'id_movimiento';

    // Se aísla por cuenta a través de este registro padre
    protected string $relacionCuenta = 'producto';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'id_almacen',
        'id_almacen_origen',
        'id_almacen_destino',
        'id_usuario',
        'tipo_movimiento',
        'cantidad',
        'costo_unitario',
        'fecha_movimiento',
        'referencia',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'fecha_movimiento' => 'datetime',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'id_almacen', 'id_almacen');
    }

    public function almacenOrigen()
    {
        return $this->belongsTo(Almacen::class, 'id_almacen_origen', 'id_almacen');
    }

    public function almacenDestino()
    {
        return $this->belongsTo(Almacen::class, 'id_almacen_destino', 'id_almacen');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
