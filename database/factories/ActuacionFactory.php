<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TipoActuacion;
use App\Models\Actuacion;
use App\Models\Despacho;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Actuacion>
 */
class ActuacionFactory extends Factory
{
    protected $model = Actuacion::class;

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
            'user_id' => User::factory(),
            'fecha' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'tipo' => fake()->randomElement(TipoActuacion::cases()),
            'descripcion' => fake()->paragraph(),
            'documento_id' => null,
        ];
    }
}
