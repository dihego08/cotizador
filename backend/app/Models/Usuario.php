<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACuenta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, PerteneceACuenta;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = true;

    protected $fillable = [
        'id_empresa',
        'nombre_usuario',
        'rol',
        'email',
        'password_hash',
        'debe_cambiar_password',
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
        'debe_cambiar_password' => 'boolean',
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

    /**
     * Dueño de la plataforma: administra todas las cuentas desde el panel de plataforma.
     * El rol solo vale dentro de la cuenta de plataforma; en cualquier otra cuenta no da acceso al panel.
     */
    public function esSuperAdmin(): bool
    {
        return strtoupper((string) $this->rol) === 'SUPERADMIN'
            && (bool) $this->cuenta?->esPlataforma();
    }

    /**
     * Administrador de su cuenta (acceso total dentro de ella).
     */
    public function esAdmin(): bool
    {
        return in_array(strtoupper((string) $this->rol), ['ADMIN', 'ADMINISTRADOR', 'SUPERADMIN']);
    }

    public function hasPermiso(string $clave): bool
    {
        if ($this->esAdmin()) {
            return true;
        }

        return $this->permisos()->where('clave', $clave)->exists();
    }
}
