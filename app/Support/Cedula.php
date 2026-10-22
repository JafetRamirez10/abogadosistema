<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\TipoCliente;
use InvalidArgumentException;

final class Cedula
{
    public static function normalizar(string $cedula): string
    {
        return preg_replace('/\D+/', '', $cedula) ?? '';
    }

    public static function hmacKey(): string
    {
        $key = (string) config('app.hmac_key', config('app.key'));

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);

            return $decoded !== false ? $decoded : $key;
        }

        return $key;
    }

    public static function hash(string $cedula): string
    {
        return hash_hmac('sha256', self::normalizar($cedula), self::hmacKey());
    }

    public static function esValida(string $cedula, TipoCliente $tipo): bool
    {
        $normalizada = self::normalizar($cedula);

        return match ($tipo) {
            TipoCliente::Fisica => strlen($normalizada) === 9,
            TipoCliente::Juridica => strlen($normalizada) === 10,
        };
    }

    public static function validarOFallar(string $cedula, TipoCliente $tipo): string
    {
        $normalizada = self::normalizar($cedula);

        if (! self::esValida($normalizada, $tipo)) {
            $esperados = $tipo === TipoCliente::Fisica ? 9 : 10;

            throw new InvalidArgumentException(
                "La cédula debe tener {$esperados} dígitos para persona {$tipo->value}."
            );
        }

        return $normalizada;
    }

    public static function enmascarar(?string $cedula): string
    {
        if ($cedula === null || $cedula === '') {
            return '—';
        }

        $digitos = self::normalizar($cedula);

        if (strlen($digitos) === 9) {
            return sprintf(
                '%s-%s-****',
                substr($digitos, 0, 1),
                substr($digitos, 1, 4),
            );
        }

        if (strlen($digitos) === 10) {
            return sprintf(
                '%s-%s-****',
                substr($digitos, 0, 1),
                substr($digitos, 1, 3),
            );
        }

        if (strlen($digitos) <= 4) {
            return str_repeat('*', strlen($digitos));
        }

        return substr($digitos, 0, max(1, strlen($digitos) - 4)).'****';
    }
}
