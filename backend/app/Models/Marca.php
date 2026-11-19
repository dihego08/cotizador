<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACuenta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    use HasFactory, PerteneceACuenta;

    protected $table = 'marcas';
    protected $primaryKey = 'id_marca';
    public $timestamps = false;

    protected $fillable = [
        'nombre_marca',
    ];

    // productos.marca guarda el nombre de la marca (texto), no el id
    public function productos()
    {
        return $this->hasMany(Producto::class, 'marca', 'nombre_marca');
    }
}
