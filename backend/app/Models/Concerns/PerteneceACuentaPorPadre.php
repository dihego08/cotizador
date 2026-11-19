<?php

namespace App\Models\Concerns;

use App\Support\CuentaActual;
use Illuminate\Database\Eloquent\Builder;

/**
 * Para tablas hijas sin columna id_cuenta (detalles, pagos, stock, series...).
 * Se filtran exigiendo que su registro padre exista dentro de la cuenta actual;
 * el padre aplica su propio filtro de cuenta dentro del whereHas.
 *
 * El modelo debe declarar: protected string $relacionCuenta = 'nombre_de_la_relacion';
 */
trait PerteneceACuentaPorPadre
{
    public static function bootPerteneceACuentaPorPadre(): void
    {
        static::addGlobalScope('cuenta', function (Builder $query) {
            if (app(CuentaActual::class)->definida()) {
                $query->whereHas($query->getModel()->relacionCuenta);
            }
        });
    }
}
