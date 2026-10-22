<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoTarea: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Pendiente = 'pendiente';
    case EnProgreso = 'en_progreso';
    case Completada = 'completada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Por hacer',
            self::EnProgreso => 'En progreso',
            self::Completada => 'Hecho',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'gray',
            self::EnProgreso => 'info',
            self::Completada => 'success',
        };
    }

    public function estaAbierta(): bool
    {
        return $this !== self::Completada;
    }

    /**
     * @return list<self>
     */
    public static function columnasTablero(): array
    {
        return [
            self::Pendiente,
            self::EnProgreso,
            self::Completada,
        ];
    }
}
