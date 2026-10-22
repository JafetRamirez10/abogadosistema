<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FormatoSalidaPlantilla;
use App\Enums\Materia;
use App\Models\Despacho;
use App\Models\Plantilla;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plantilla>
 */
class PlantillaFactory extends Factory
{
    protected $model = Plantilla::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'despacho_id' => Despacho::factory(),
            'nombre' => 'Plantilla '.fake()->unique()->words(3, true),
            'materia' => fake()->optional()->randomElement(Materia::cases()),
            'contenido' => "Señor(a) Juez(a):\n\nYo, {{ cliente.nombre }}, cédula {{ cliente.cedula }}, "
                .'con domicilio en {{ cliente.direccion }}, en el expediente {{ expediente.numero_interno }} '
                .'({{ expediente.titulo }}), materia {{ expediente.materia }}, ante el {{ expediente.juzgado }}, '
                .'me apersono. Abogado: {{ abogado.nombre }}. Despacho: {{ despacho.nombre }}. '
                .'Contraparte: {{ contraparte.nombre }}. Fecha: {{ fecha_hoy }}.',
            'formato_salida' => FormatoSalidaPlantilla::Ambos,
        ];
    }
}
