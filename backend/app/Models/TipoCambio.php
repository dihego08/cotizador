<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCambio extends Model
{
    use HasFactory;

    protected $table = 'tipo_cambio';
    protected $primaryKey = 'id_tc';
    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'compra',
        'venta',
        'created_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'compra' => 'decimal:4',
        'venta' => 'decimal:4',
        'created_at' => 'datetime',
    ];
}
