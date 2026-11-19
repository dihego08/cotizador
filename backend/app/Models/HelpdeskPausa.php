<?php

namespace App\Models;

use App\Models\Concerns\PerteneceACuentaPorPadre;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpdeskPausa extends Model
{
    use HasFactory, PerteneceACuentaPorPadre;

    protected $table = 'helpdesk_pausas';
    protected $primaryKey = 'id_pausa';

    // Se aísla por cuenta a través de este registro padre
    protected string $relacionCuenta = 'atencion';
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
