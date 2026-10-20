<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmOportunidad extends Model
{
    use HasFactory;

    protected $table = 'crm_oportunidades';
    protected $primaryKey = 'id_oportunidad';

    protected $fillable = [
        'id_empresa',
        'id_cliente',
        'id_usuario',
        'id_cotizacion',
        'titulo',
        'descripcion',
        'valor_estimado',
        'moneda',
        'etapa',
        'probabilidad',
        'prioridad',
        'fecha_cierre_estimada',
        'motivo_perdida',
    ];

    protected $casts = [
        'valor_estimado' => 'decimal:2',
        'probabilidad' => 'integer',
        'fecha_cierre_estimada' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion', 'id_cotizacion');
    }

    public function actividades()
    {
        return $this->hasMany(CrmActividad::class, 'id_oportunidad', 'id_oportunidad')->orderBy('fecha_actividad', 'desc');
    }
}
