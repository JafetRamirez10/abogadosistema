<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EstadoEvento;
use App\Enums\TipoEvento;
use App\Filament\Resources\ExpedienteResource;
use App\Models\Evento;
use App\Models\Expediente;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Gate;

class CasosRecientesWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    /** @var int|string|array<string, int|null> */
    protected int|string|array $columnSpan = [
        'md' => 12,
        'lg' => 6,
    ];

    protected static ?string $heading = 'Casos recientes';

    public static function canView(): bool
    {
        return Gate::allows('viewAny', Expediente::class);
    }

    public function table(Table $table): Table
    {
        $ids = Expediente::query()
            ->latest('updated_at')
            ->limit(5)
            ->pluck('id');

        return $table
            ->query(
                Expediente::query()
                    ->whereIn('id', $ids)
                    ->with(['cliente'])
                    ->with(['eventos' => function ($query): void {
                        $query
                            ->where('tipo', TipoEvento::Audiencia)
                            ->where('estado', '!=', EstadoEvento::Cancelada)
                            ->where('inicio', '>=', now('America/Costa_Rica'))
                            ->orderBy('inicio');
                    }])
                    ->orderByDesc('updated_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('numero_interno')
                    ->label('Expediente')
                    ->url(fn (Expediente $record): string => ExpedienteResource::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->limit(28),
                Tables\Columns\TextColumn::make('materia')
                    ->label('Materia')
                    ->badge(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('proxima_audiencia')
                    ->label('Próxima audiencia')
                    ->state(function (Expediente $record): string {
                        /** @var Evento|null $proxima */
                        $proxima = $record->eventos->first();

                        return $proxima
                            ? $proxima->inicio->timezone('America/Costa_Rica')->format('d/m/Y H:i')
                            : '—';
                    }),
            ])
            ->paginated(false)
            ->headerActions([
                Tables\Actions\Action::make('verTodos')
                    ->label('Ver todos')
                    ->url(ExpedienteResource::getUrl('index'))
                    ->link(),
            ])
            ->emptyStateHeading('Sin casos recientes');
    }
}
