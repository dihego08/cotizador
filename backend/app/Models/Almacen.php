<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    use HasFactory;

    protected $table = 'almacenes';
    protected $primaryKey = 'id_almacen';
    public $timestamps = true;

    protected $fillable = [
        'id_empresa',
        'id_responsable',
        'nombre_almacen',
        'ubicacion',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }

    public function responsable()
    {
        return $this->belongsTo(Usuario::class, 'id_responsable', 'id_usuario');
    }

    public function stock()
    {
        return $this->hasMany(Stock::class, 'id_almacen', 'id_almacen');
    }

    public function series()
    {
        return $this->hasMany(SerieProducto::class, 'id_almacen', 'id_almacen');
    }
}
