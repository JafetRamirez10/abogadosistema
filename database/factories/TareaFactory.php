<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Models\Despacho;
use App\Models\Expediente;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tarea>
 */
class TareaFactory extends Factory
{
    protected $model = Tarea::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'despacho_id' => Despacho::factory(),
            'expediente_id' => function (array $attributes) {
                return Expediente::factory()->create([
                    'despacho_id' => $attributes['despacho_id'],
                ])->getKey();
            },
            'asignada_a' => User::factory(),
            'creada_por' => User::factory(),
            'descripcion' => fake()->sentence(),
            'vence_el' => fake()->optional()->dateTimeBetween('now', '+2 weeks')?->format('Y-m-d'),
            'prioridad' => fake()->randomElement(PrioridadTarea::cases()),
            'estado' => EstadoTarea::Pendiente,
            'orden' => 0,
            'completada_at' => null,
        ];
    }

    public function enProgreso(): static
    {
        return $this->state(fn (): array => [
            'estado' => EstadoTarea::EnProgreso,
            'completada_at' => null,
        ]);
    }

    public function completada(): static
    {
        return $this->state(fn (): array => [
            'estado' => EstadoTarea::Completada,
            'completada_at' => now('America/Costa_Rica'),
        ]);
    }

    public function vencida(): static
    {
        return $this->state(fn (): array => [
            'estado' => EstadoTarea::Pendiente,
            'vence_el' => now('America/Costa_Rica')->subDays(3)->toDateString(),
            'completada_at' => null,
        ]);
    }
}
