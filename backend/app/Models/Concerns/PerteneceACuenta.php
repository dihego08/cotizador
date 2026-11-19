<?php

namespace App\Models\Concerns;

use App\Models\Cuenta;
use App\Support\CuentaActual;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/**
 * Para modelos cuya tabla tiene columna id_cuenta.
 * Todas las consultas se filtran por la cuenta actual y los registros nuevos se asignan a ella.
 */
trait PerteneceACuenta
{
    public static function bootPerteneceACuenta(): void
    {
        static::addGlobalScope('cuenta', function (Builder $query) {
            $cuenta = app(CuentaActual::class);

            if ($cuenta->definida()) {
                $query->where($query->getModel()->qualifyColumn('id_cuenta'), $cuenta->id());
            }
        });

        static::creating(function ($model) {
            $cuenta = app(CuentaActual::class);

            if (empty($model->id_cuenta) && $cuenta->definida()) {
                $model->id_cuenta = $cuenta->id();
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('id_cuenta')) {
                throw new LogicException('Un registro no puede cambiar de cuenta.');
            }
        });
    }

    public function cuenta()
    {
        return $this->belongsTo(Cuenta::class, 'id_cuenta', 'id_cuenta');
    }
}
