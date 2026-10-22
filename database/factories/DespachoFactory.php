<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Despacho;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Despacho>
 */
class DespachoFactory extends Factory
{
    protected $model = Despacho::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = 'Bufete '.fake()->unique()->lastNameCR().' & Asociados';

        return [
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.fake()->unique()->numerify('###'),
            'cedula_juridica' => fake()->unique()->cedulaJuridica(),
            'telefono' => fake()->telefonoCR(),
            'email' => fake()->unique()->companyEmail(),
            'direccion' => fake()->direccionCR(),
        ];
    }
}
