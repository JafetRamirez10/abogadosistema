<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToDespacho;
use Illuminate\Database\Eloquent\Model;

/**
 * Base opcional para modelos de Fase 2+ con aislamiento por despacho.
 */
abstract class DespachoScopedModel extends Model
{
    use BelongsToDespacho;
}
