<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MetodoPago;
use App\Enums\Moneda;
use App\Models\Despacho;
use App\Models\Expediente;
use App\Models\Pago;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    protected $model = Pago::class;

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
            'monto' => fake()->randomFloat(2, 50_000, 500_000),
            'moneda' => Moneda::CRC,
            'fecha' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'metodo' => fake()->randomElement(MetodoPago::cases()),
            'referencia' => fake()->optional()->bothify('SINPE-####'),
            'nota' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }
}
