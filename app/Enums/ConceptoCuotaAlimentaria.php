<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ConceptoCuotaAlimentaria: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Mensual = 'mensual';
    case Aguinaldo = 'aguinaldo';
    case SalarioEscolar = 'salario_escolar';
    case Extraordinario = 'extraordinario';
    case Retroactivo = 'retroactivo';

    public function label(): string
    {
        return match ($this) {
            self::Mensual => 'Cuota mensual',
            self::Aguinaldo => 'Aguinaldo',
            self::SalarioEscolar => 'Salario escolar / inicio de lecciones',
            self::Extraordinario => 'Gasto extraordinario',
            self::Retroactivo => 'Cuotas retroactivas',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Mensual => 'primary',
            self::Aguinaldo => 'success',
            self::SalarioEscolar => 'info',
            self::Extraordinario => 'warning',
            self::Retroactivo => 'danger',
        };
    }
}
