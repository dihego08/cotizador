<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';
    public $timestamps = false;

    protected $fillable = [
        'tipo_documento',
        'num_documento',
        'razon_social',
        'direccion',
        'departamento',
        'provincia',
        'distrito',
        'ubigeo',
        'email',
        'telefono',
        'atencion',
        'es_cliente_helpdesk',
        'created_at',
    ];

    protected $casts = [
        'es_cliente_helpdesk' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function contactos()
    {
        return $this->hasMany(ClienteContacto::class, 'id_cliente', 'id_cliente');
    }

    public function cotizaciones()
    {
        return $this->hasMany(Cotizacion::class, 'id_cliente', 'id_cliente');
    }

    public function atencionesHelpdesk()
    {
        return $this->hasMany(HelpdeskAtencion::class, 'id_cliente', 'id_cliente');
    }
}
