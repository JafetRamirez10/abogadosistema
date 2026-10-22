<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoDocumento;
use App\Models\Despacho;
use App\Models\Documento;
use App\Models\Expediente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Documento>
 */
class DocumentoFactory extends Factory
{
    protected $model = Documento::class;

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
            'nombre' => fake()->randomElement([
                'Demanda laboral',
                'Contestación de demanda',
                'Poder especial judicial',
                'Escrito de pruebas',
                'Recurso de apelación',
                'Carta de cobro',
                'Contrato de honorarios',
            ]),
            'plantilla_id' => null,
            'version_actual' => 1,
            'estado' => fake()->randomElement(EstadoDocumento::cases()),
        ];
    }
}
