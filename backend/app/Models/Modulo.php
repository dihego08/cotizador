<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    use HasFactory;

    protected $table = 'modulos';
    protected $primaryKey = 'id_modulo';
    public $timestamps = false;

    protected $fillable = [
        'id_padre',
        'clave',
        'nombre',
        'icono',
        'orden',
    ];

    public function padre()
    {
        return $this->belongsTo(Modulo::class, 'id_padre', 'id_modulo');
    }

    public function submodulos()
    {
        return $this->hasMany(Modulo::class, 'id_padre', 'id_modulo')->orderBy('orden');
    }

    public function usuarios()
    {
        return $this->belongsToMany(
            Usuario::class,
            'usuario_permisos',
            'id_modulo',
            'id_usuario'
        );
    }
}
