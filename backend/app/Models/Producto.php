<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';
    protected $primaryKey = 'id_producto';
    public $timestamps = true;

    protected $fillable = [
        'tipo',
        'id_categoria',
        'codigo_interno',
        'codigo_proveedor',
        'nombre_producto',
        'descripcion',
        'numero_parte',
        'marca',
        'modelo',
        'unidad_medida',
        'moneda',
        'precio_costo',
        'porcentaje_utilidad',
        'precio_venta',
        'codigo_barra',
    ];

    protected $casts = [
        'precio_costo' => 'decimal:2',
        'porcentaje_utilidad' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }

    public function stock()
    {
        return $this->hasMany(Stock::class, 'id_producto', 'id_producto');
    }

    public function series()
    {
        return $this->hasMany(SerieProducto::class, 'id_producto', 'id_producto');
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'id_producto', 'id_producto');
    }
}
