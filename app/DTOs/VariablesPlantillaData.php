<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class VariablesPlantillaData
{
    /**
     * @param  array<string, string>  $variables
     */
    public function __construct(
        public array $variables,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return $this->variables;
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->variables[$key] ?? $default;
    }
}
