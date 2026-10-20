<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpdeskCategoria extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_categorias';
    protected $primaryKey = 'id_categoria';
    public $timestamps = false;

    protected $fillable = [
        'id_padre',
        'nombre',
        'descripcion',
        'activo',
        'created_at',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function padre()
    {
        return $this->belongsTo(HelpdeskCategoria::class, 'id_padre', 'id_categoria');
    }

    public function subcategorias()
    {
        return $this->hasMany(HelpdeskCategoria::class, 'id_padre', 'id_categoria');
    }
}
