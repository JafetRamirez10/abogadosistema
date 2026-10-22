<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RolParte;
use App\Models\Expediente;
use App\Models\Parte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Parte>
 */
class ParteFactory extends Factory
{
    protected $model = Parte::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expediente_id' => Expediente::factory(),
            'despacho_id' => function (array $attributes) {
                return Expediente::query()->findOrFail($attributes['expediente_id'])->despacho_id;
            },
            'nombre' => fake()->nombreCR(),
            'cedula' => fake()->optional(0.7)->cedulaFisica(),
            'rol' => fake()->randomElement(RolParte::cases()),
            'abogado_contrario' => fake()->optional(0.4)->boolean()
                ? 'Lic. '.fake()->nombreCR()
                : null,
            'contacto' => fake()->optional()->telefonoCR(true),
        ];
    }
}
