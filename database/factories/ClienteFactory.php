<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TipoCliente;
use App\Models\Cliente;
use App\Models\Despacho;
use App\Support\Cedula;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipo = fake()->randomElement(TipoCliente::cases());
        $cedula = $tipo === TipoCliente::Fisica
            ? fake()->unique()->cedulaFisica()
            : fake()->unique()->cedulaJuridica();

        return [
            'despacho_id' => Despacho::factory(),
            'tipo' => $tipo,
            'nombre' => $tipo === TipoCliente::Fisica
                ? fake()->nombreCR()
                : fake()->razonSocialCR(),
            'cedula' => $cedula,
            'cedula_hash' => Cedula::hash($cedula),
            'telefono' => fake()->telefonoCR(),
            'email' => fake()->unique()->safeEmail(),
            'direccion' => fake()->direccionCR(),
            'notas' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function fisica(): static
    {
        return $this->state(function (): array {
            $cedula = fake()->unique()->cedulaFisica();

            return [
                'tipo' => TipoCliente::Fisica,
                'nombre' => fake()->nombreCR(),
                'cedula' => $cedula,
                'cedula_hash' => Cedula::hash($cedula),
            ];
        });
    }

    public function juridica(): static
    {
        return $this->state(function (): array {
            $cedula = fake()->unique()->cedulaJuridica();

            return [
                'tipo' => TipoCliente::Juridica,
                'cedula' => $cedula,
                'cedula_hash' => Cedula::hash($cedula),
                'nombre' => fake()->razonSocialCR(),
            ];
        });
    }
}
