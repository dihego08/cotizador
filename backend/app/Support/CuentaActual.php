<?php

namespace App\Support;

use Closure;

/**
 * Cuenta (cliente del SaaS) con la que trabaja la petición actual.
 *
 * La define el middleware EstablecerCuenta después de autenticar al usuario.
 * Mientras no esté definida (login, rutas públicas, consola) los modelos no se filtran,
 * por eso todas las rutas autenticadas deben pasar por ese middleware.
 */
class CuentaActual
{
    private ?int $id = null;

    public function definir(int $id): void
    {
        $this->id = $id;
    }

    public function limpiar(): void
    {
        $this->id = null;
    }

    public function definida(): bool
    {
        return $this->id !== null;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    /**
     * Ejecuta $callback como si la petición perteneciera a otra cuenta
     * (lo usa el panel de plataforma al crear los datos de una cuenta nueva).
     */
    public function ejecutarComo(int $id, Closure $callback): mixed
    {
        $anterior = $this->id;
        $this->id = $id;

        try {
            return $callback();
        } finally {
            $this->id = $anterior;
        }
    }
}
