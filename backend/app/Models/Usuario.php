<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = true;

    protected $fillable = [
        'id_empresa',
        'nombre_usuario',
        'rol',
        'email',
        'password_hash',
        'activo',
        'cargo',
        'celular',
        'firma_path',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Laravel Auth password mapping
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }

    public function permisos()
    {
        return $this->belongsToMany(
            Modulo::class,
            'usuario_permisos',
            'id_usuario',
            'id_modulo'
        );
    }

    public function hasPermiso(string $clave): bool
    {
        if (strtoupper($this->rol) === 'ADMIN' || strtoupper($this->rol) === 'ADMINISTRADOR') {
            return true;
        }

        return $this->permisos()->where('clave', $clave)->exists();
    }
}
