<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpdeskAtencion extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_atenciones';
    protected $primaryKey = 'id_atencion';
    public $timestamps = true;

    protected $fillable = [
        'codigo_ticket',
        'id_empresa',
        'id_cliente',
        'id_contacto',
        'tipo_usuario',
        'documento_solicitante',
        'nombre_solicitante',
        'categoria',
        'id_subcategoria',
        'canal_atencion',
        'id_tecnico',
        'descripcion_requerimiento',
        'acciones_realizadas',
        'estado_cierre',
        'nivel_satisfaccion',
        'acciones_programadas',
        'fecha_proxima_revision',
        'id_tecnico_seguimiento',
        'fecha_atencion',
        'hora_inicio',
        'hora_fin',
        'tiempo_pausa_segundos',
        'tiempo_efectivo_segundos',
    ];

    protected $casts = [
        'fecha_atencion' => 'date',
        'hora_inicio' => 'datetime',
        'hora_fin' => 'datetime',
        'fecha_proxima_revision' => 'datetime',
        'tiempo_efectivo_horas' => 'decimal:2',
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

    public function contacto()
    {
        return $this->belongsTo(ClienteContacto::class, 'id_contacto', 'id_contacto');
    }

    public function subcategoria()
    {
        return $this->belongsTo(HelpdeskCategoria::class, 'id_subcategoria', 'id_categoria');
    }

    public function tecnico()
    {
        return $this->belongsTo(Usuario::class, 'id_tecnico', 'id_usuario');
    }

    public function tecnicoSeguimiento()
    {
        return $this->belongsTo(Usuario::class, 'id_tecnico_seguimiento', 'id_usuario');
    }

    public function pausas()
    {
        return $this->hasMany(HelpdeskPausa::class, 'id_atencion', 'id_atencion');
    }
}
