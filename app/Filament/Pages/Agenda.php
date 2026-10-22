<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\EstadoEvento;
use App\Enums\TipoEvento;
use App\Models\Evento;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Agenda extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Audiencias';

    protected static ?string $title = 'Agenda';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.agenda';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->eventosQuery())
            ->defaultSort('inicio')
            ->columns([
                Tables\Columns\TextColumn::make('inicio')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('semaforo_badge')
                    ->label('Semáforo')
                    ->badge()
                    ->color(fn (Evento $record): string => $record->semaforo->color()),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('expediente.numero_interno')
                    ->label('Expediente')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->toggleable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('lugar')
                    ->label('Lugar')
                    ->placeholder('—')
                    ->toggleable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(TipoEvento::class),
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(EstadoEvento::class)
                    ->default(EstadoEvento::Pendiente->value),
                Tables\Filters\Filter::make('de_hoy')
                    ->label('Hoy')
                    ->query(function (Builder $query): Builder {
                        $hoy = now('America/Costa_Rica')->toDateString();

                        return $query->whereDate('inicio', $hoy);
                    }),
                Tables\Filters\Filter::make('esta_semana')
                    ->label('Esta semana')
                    ->query(function (Builder $query): Builder {
                        return $query->whereBetween('inicio', [
                            now('America/Costa_Rica')->startOfWeek(),
                            now('America/Costa_Rica')->endOfWeek(),
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('ver_expediente')
                    ->label('Expediente')
                    ->icon('heroicon-o-folder-open')
                    ->url(fn (Evento $record): ?string => $record->expediente_id
                        ? route('filament.app.resources.expedientes.view', [
                            'tenant' => filament()->getTenant(),
                            'record' => $record->expediente_id,
                        ])
                        : null)
                    ->visible(fn (Evento $record): bool => filled($record->expediente_id)),
            ])
            ->emptyStateHeading('Sin eventos en la agenda')
            ->emptyStateDescription('Los plazos y audiencias del despacho aparecerán aquí.');
    }

    /**
     * @return Builder<Evento>
     */
    protected function eventosQuery(): Builder
    {
        return Evento::query()->with(['responsable', 'expediente']);
    }
}
