<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\EnviarAlertasPlazos;
use Illuminate\Console\Command;

class AlertarPlazos extends Command
{
    protected $signature = 'plazos:alertar';

    protected $description = 'Envía alertas de plazos/eventos próximos a los responsables';

    public function handle(): int
    {
        EnviarAlertasPlazos::dispatchSync();

        $this->info('Alertas de plazos procesadas.');

        return self::SUCCESS;
    }
}
