<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Moneda;
use App\Models\Despacho;
use App\Models\Expediente;
use App\Models\Hora;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hora>
 */
class HoraFactory extends Factory
{
    protected $model = Hora::class;

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
            'minutos' => fake()->randomElement([30, 60, 90, 120]),
            'descripcion' => fake()->sentence(),
            'tarifa' => fake()->optional()->randomFloat(2, 20_000, 80_000),
            'moneda' => Moneda::CRC,
            'facturable' => true,
        ];
    }
}
