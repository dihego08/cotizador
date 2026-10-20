<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';
    protected $primaryKey = 'id_empresa';
    public $timestamps = true;

    protected $fillable = [
        'razon_social',
        'ruc',
        'direccion',
        'telefono',
        'email',
        'logo_path',
        'nombre_gerente',
        'cargo_gerente',
        'cel_gerente',
        'es_principal',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'id_empresa', 'id_empresa');
    }

    public function almacenes()
    {
        return $this->hasMany(Almacen::class, 'id_empresa', 'id_empresa');
    }

    public function cotizaciones()
    {
        return $this->hasMany(Cotizacion::class, 'id_empresa', 'id_empresa');
    }
}
