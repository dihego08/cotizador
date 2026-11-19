<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Reemplaza a la regla "exists": la regla nativa consulta la tabla directamente
 * y no aplica el filtro de cuenta, así que aceptaría ids de otros clientes.
 * Esta consulta el modelo, que sí lo aplica.
 *
 *   new ExisteEnCuenta(Producto::class)
 *   new ExisteEnCuenta(Producto::class, fn ($q) => $q->where('id_empresa', $idEmpresa))
 */
class ExisteEnCuenta implements ValidationRule
{
    public function __construct(
        private string $modelo,
        private ?Closure $restriccion = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $query = $this->modelo::query()->whereKey($value);

        if ($this->restriccion) {
            ($this->restriccion)($query);
        }

        if (!$query->exists()) {
            $fail('El registro seleccionado en :attribute no existe.');
        }
    }
}
