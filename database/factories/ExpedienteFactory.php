<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoExpediente;
use App\Enums\Materia;
use App\Enums\Moneda;
use App\Enums\TipoHonorario;
use App\Models\Cliente;
use App\Models\Despacho;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expediente>
 */
class ExpedienteFactory extends Factory
{
    protected $model = Expediente::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $materia = fake()->randomElement(Materia::cases());
        $codigoMateria = match ($materia) {
            Materia::Laboral => 'LA',
            Materia::Civil => 'CI',
            Materia::Familia => 'FA',
            Materia::Penal => 'PE',
            Materia::ContenciosoAdministrativo => 'CA',
            Materia::Comercial => 'CO',
            Materia::Agrario => 'AG',
            Materia::Notarial => 'NO',
            Materia::Otro => 'CI',
        };

        return [
            'despacho_id' => Despacho::factory(),
            'cliente_id' => function (array $attributes) {
                return Cliente::factory()->create([
                    'despacho_id' => $attributes['despacho_id'],
                ])->getKey();
            },
            'responsable_id' => User::factory(),
            'numero_interno' => 'EXP-'.fake()->unique()->numerify('####'),
            'numero_judicial' => fake()->optional(0.7)->numeroJudicialCR($codigoMateria),
            'titulo' => fake()->sentence(4),
            'materia' => $materia,
            'estado' => fake()->randomElement(EstadoExpediente::cases()),
            'juzgado' => fake()->optional(0.8)->juzgadoCR(),
            'fecha_inicio' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'cuantia' => fake()->optional()->randomFloat(2, 100_000, 5_000_000),
            'moneda' => fake()->randomElement([Moneda::CRC, Moneda::USD]),
            'tipo_honorario' => TipoHonorario::Fijo,
            'honorario_pactado' => fake()->optional()->randomFloat(2, 50_000, 800_000),
            'porcentaje_honorario' => null,
            'descripcion' => fake()->optional()->paragraph(),
        ];
    }
}
