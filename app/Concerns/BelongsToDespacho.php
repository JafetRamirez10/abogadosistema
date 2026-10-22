<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Despacho;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToDespacho
{
    public static function bootBelongsToDespacho(): void
    {
        static::addGlobalScope('despacho', function (Builder $query): void {
            $tenant = Filament::getTenant();

            if ($tenant instanceof Despacho) {
                $query->where(
                    $query->getModel()->getTable().'.despacho_id',
                    $tenant->getKey(),
                );
            }
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('despacho_id')) {
                return;
            }

            $tenant = Filament::getTenant();

            if ($tenant instanceof Despacho) {
                $model->setAttribute('despacho_id', $tenant->getKey());
            }
        });
    }

    /** @return BelongsTo<Despacho, $this> */
    public function despacho(): BelongsTo
    {
        return $this->belongsTo(Despacho::class);
    }
}
