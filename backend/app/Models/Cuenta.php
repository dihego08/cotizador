<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cliente del SaaS. Agrupa sus empresas, usuarios y todos sus datos.
 */
class Cuenta extends Model
{
    protected $table = 'cuentas';
    protected $primaryKey = 'id_cuenta';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'estado',
        'plan',
        'max_empresas',
        'max_usuarios',
        'max_almacenes',
        'notas',
    ];

    protected $casts = [
        'es_plataforma' => 'boolean',
        'max_empresas' => 'integer',
        'max_usuarios' => 'integer',
        'max_almacenes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function empresas()
    {
        return $this->hasMany(Empresa::class, 'id_cuenta', 'id_cuenta');
    }

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'id_cuenta', 'id_cuenta');
    }

    public function estaActiva(): bool
    {
        return $this->estado === 'ACTIVA';
    }

    /**
     * Cuenta del dueño de la plataforma (solo hay una). Sus usuarios pueden ser SUPERADMIN.
     */
    public function esPlataforma(): bool
    {
        return (bool) $this->es_plataforma;
    }

    public static function plataforma(): ?self
    {
        return static::where('es_plataforma', true)->first();
    }

    /**
     * Límite del plan para 'empresas', 'usuarios' o 'almacenes' (null = sin límite).
     */
    public function limite(string $recurso): ?int
    {
        return $this->{"max_{$recurso}"};
    }
}
