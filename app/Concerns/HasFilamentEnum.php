<?php

declare(strict_types=1);

namespace App\Concerns;

trait HasFilamentEnum
{
    abstract public function label(): string;

    abstract public function color(): string;

    public function getLabel(): ?string
    {
        return $this->label();
    }

    /**
     * @return string | array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string} | null
     */
    public function getColor(): string|array|null
    {
        return $this->color();
    }
}
