<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonImmutable;

readonly class PeriodoData
{
    public function __construct(
        public CarbonImmutable $fechaReferencia,
        public CarbonImmutable $actualInicio,
        public CarbonImmutable $actualFin,
        public CarbonImmutable $anteriorInicio,
        public CarbonImmutable $anteriorFin,
    ) {}
}
