<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpdeskPausa extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_pausas';
    protected $primaryKey = 'id_pausa';
    public $timestamps = false;

    protected $fillable = [
        'id_atencion',
        'hora_inicio_pausa',
        'hora_fin_pausa',
        'motivo_pausa',
    ];

    protected $casts = [
        'hora_inicio_pausa' => 'datetime',
        'hora_fin_pausa' => 'datetime',
    ];

    public function atencion()
    {
        return $this->belongsTo(HelpdeskAtencion::class, 'id_atencion', 'id_atencion');
    }
}
