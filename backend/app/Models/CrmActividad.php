<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmActividad extends Model
{
    use HasFactory;

    protected $table = 'crm_actividades';
    protected $primaryKey = 'id_actividad';
    public $timestamps = false;

    protected $fillable = [
        'id_empresa',
        'id_cliente',
        'id_oportunidad',
        'id_usuario',
        'tipo',
        'titulo',
        'descripcion',
        'fecha_actividad',
        'proximo_seguimiento',
        'completado',
        'created_at',
    ];

    protected $casts = [
        'fecha_actividad' => 'datetime',
        'proximo_seguimiento' => 'date',
        'completado' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function oportunidad()
    {
        return $this->belongsTo(CrmOportunidad::class, 'id_oportunidad', 'id_oportunidad');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
