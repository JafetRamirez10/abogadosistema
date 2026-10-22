<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Moneda;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->nombreCR(),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->telefonoCR(),
            'tarifa_hora' => fake()->optional(0.6)->randomFloat(2, 20_000, 60_000),
            'moneda_tarifa' => Moneda::CRC,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function abogado(float $tarifa = 35_000): static
    {
        return $this->state(fn (): array => [
            'tarifa_hora' => $tarifa,
            'moneda_tarifa' => Moneda::CRC,
        ]);
    }
}
