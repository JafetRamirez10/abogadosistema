<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FormatoSalidaPlantilla: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Docx = 'docx';
    case Pdf = 'pdf';
    case Ambos = 'ambos';

    public function label(): string
    {
        return match ($this) {
            self::Docx => 'DOCX',
            self::Pdf => 'PDF',
            self::Ambos => 'DOCX y PDF',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Docx => 'info',
            self::Pdf => 'danger',
            self::Ambos => 'success',
        };
    }
}
