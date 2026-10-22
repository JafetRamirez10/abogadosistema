<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoEvento;
use App\Enums\TipoEvento;
use App\Models\Despacho;
use App\Models\Evento;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evento>
 */
class EventoFactory extends Factory
{
    protected $model = Evento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('-1 month', '+2 months');

        return [
            'despacho_id' => Despacho::factory(),
            'expediente_id' => function (array $attributes) {
                return Expediente::factory()->create([
                    'despacho_id' => $attributes['despacho_id'],
                ])->getKey();
            },
            'responsable_id' => User::factory(),
            'tipo' => fake()->randomElement(TipoEvento::cases()),
            'titulo' => fake()->sentence(3),
            'inicio' => $inicio,
            'fin' => (clone $inicio)->modify('+1 hour'),
            'lugar' => fake()->optional(0.6)->juzgadoCR(),
            'dias_aviso' => 3,
            'estado' => EstadoEvento::Pendiente,
            'alerta_enviada_at' => null,
            'notas' => fake()->optional()->sentence(),
        ];
    }

    public function pendiente(): static
    {
        return $this->state(fn (): array => [
            'estado' => EstadoEvento::Pendiente,
            'alerta_enviada_at' => null,
        ]);
    }

    public function cancelada(): static
    {
        return $this->state(fn (): array => [
            'estado' => EstadoEvento::Cancelada,
        ]);
    }

    public function dentroDeAviso(int $dias = 2, int $diasAviso = 3): static
    {
        return $this->state(fn (): array => [
            'inicio' => now('America/Costa_Rica')->addDays($dias)->setTime(10, 0),
            'fin' => now('America/Costa_Rica')->addDays($dias)->setTime(11, 0),
            'dias_aviso' => $diasAviso,
            'estado' => EstadoEvento::Pendiente,
            'alerta_enviada_at' => null,
        ]);
    }
}
