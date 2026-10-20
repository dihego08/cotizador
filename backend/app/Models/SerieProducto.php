<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SerieProducto extends Model
{
    use HasFactory;

    protected $table = 'series_productos';
    protected $primaryKey = 'id_serie';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'id_almacen',
        'numero_serie',
        'estado',
        'fecha_registro',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
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
