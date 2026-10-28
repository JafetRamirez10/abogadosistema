<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RolParte: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Actor = 'actor';
    case Demandado = 'demandado';
    case Tercero = 'tercero';
    case Testigo = 'testigo';
    case Perito = 'perito';
    case ApoderadoContrario = 'apoderado_contrario';
    case Imputado = 'imputado';
    case Victima = 'victima';
    case MinisterioPublico = 'ministerio_publico';
    case Oij = 'oij';
    case Defensa = 'defensa';
    case TestigoProtegido = 'testigo_protegido';
    case ObligadoAlimentario = 'obligado_alimentario';
    case BeneficiarioAlimentario = 'beneficiario_alimentario';

    public function label(): string
    {
        return match ($this) {
            self::Actor => 'Actor',
            self::Demandado => 'Demandado',
            self::Tercero => 'Tercero',
            self::Testigo => 'Testigo',
            self::Perito => 'Perito',
            self::ApoderadoContrario => 'Apoderado contrario',
            self::Imputado => 'Imputado',
            self::Victima => 'Víctima',
            self::MinisterioPublico => 'Ministerio Público',
            self::Oij => 'OIJ',
            self::Defensa => 'Defensa',
            self::TestigoProtegido => 'Testigo protegido',
            self::ObligadoAlimentario => 'Obligado alimentario',
            self::BeneficiarioAlimentario => 'Beneficiario alimentario',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Actor => 'success',
            self::Demandado => 'danger',
            self::Tercero => 'warning',
            self::Testigo => 'info',
            self::Perito => 'purple',
            self::ApoderadoContrario => 'gray',
            self::Imputado => 'danger',
            self::Victima => 'warning',
            self::MinisterioPublico => 'primary',
            self::Oij => 'gray',
            self::Defensa => 'success',
            self::TestigoProtegido => 'purple',
            self::ObligadoAlimentario => 'danger',
            self::BeneficiarioAlimentario => 'success',
        };
    }
}
