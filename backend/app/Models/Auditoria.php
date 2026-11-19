<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACuenta;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    use PerteneceACuenta;

    protected $table = 'auditoria';
    protected $primaryKey = 'id_auditoria';
    public $timestamps = false;

    // Modelo interno: nunca se llena con datos del formulario
    protected $fillable = [
        'id_cuenta',
        'id_usuario',
        'accion',
        'entidad',
        'id_registro',
        'descripcion',
        'ip',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Registra una acción del usuario autenticado en la cuenta actual.
     * $idCuenta solo se indica cuando no hay cuenta definida (por ejemplo, en el login).
     */
    public static function registrar(string $accion, string $entidad, $idRegistro, string $descripcion, ?Usuario $usuario = null, ?int $idCuenta = null): void
    {
        $usuario ??= auth()->user();

        static::create([
            'id_cuenta' => $idCuenta,
            'id_usuario' => $usuario?->id_usuario,
            'accion' => $accion,
            'entidad' => $entidad,
            'id_registro' => $idRegistro !== null ? (string) $idRegistro : null,
            'descripcion' => mb_substr($descripcion, 0, 255),
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
