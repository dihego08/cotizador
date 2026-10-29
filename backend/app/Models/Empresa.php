<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

    protected $appends = ['logo_url'];

    // URL pública del logo (null si no tiene uno subido en el disco public)
    public function getLogoUrlAttribute()
    {
        if (!$this->tieneLogo()) {
            return null;
        }

        return url("/api/empresas/{$this->id_empresa}/logo") . '?v=' . optional($this->updated_at)->timestamp;
    }

    public function tieneLogo(): bool
    {
        return !empty($this->logo_path) && Storage::disk('public')->exists($this->logo_path);
    }

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
