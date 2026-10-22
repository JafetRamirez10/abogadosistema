<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * @property int|null $despacho_id
 */
class Activity extends SpatieActivity
{
    /** @var list<string> */
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'event',
        'batch_uuid',
        'despacho_id',
    ];

    /** @var list<string> */
    public $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(function (Activity $activity): void {
            if ($activity->despacho_id !== null) {
                return;
            }

            $tenant = Filament::getTenant();

            if ($tenant instanceof Despacho) {
                $activity->despacho_id = $tenant->getKey();

                return;
            }

            $subject = $activity->subject;

            if ($subject instanceof Model && $subject->getAttribute('despacho_id') !== null) {
                $activity->despacho_id = (int) $subject->getAttribute('despacho_id');
            }
        });
    }

    /** @return BelongsTo<Despacho, $this> */
    public function despacho(): BelongsTo
    {
        return $this->belongsTo(Despacho::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForDespacho(Builder $query, int|Despacho $despacho): Builder
    {
        $id = $despacho instanceof Despacho ? $despacho->getKey() : $despacho;

        return $query->where('despacho_id', $id);
    }
}
