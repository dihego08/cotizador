<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACuentaPorPadre;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory, PerteneceACuentaPorPadre;

    protected $table = 'stock';
    protected $primaryKey = 'id_stock';

    // Se aísla por cuenta a través de este registro padre
    protected string $relacionCuenta = 'producto';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'id_almacen',
        'cantidad_actual',
        'stock_minimo',
        'stock_maximo',
        'updated_at',
    ];

    protected $casts = [
        'cantidad_actual' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'stock_maximo' => 'decimal:2',
        'updated_at' => 'datetime',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'id_almacen', 'id_almacen');
    }
}
