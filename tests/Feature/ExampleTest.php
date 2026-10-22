<?php

declare(strict_types=1);

it('redirige la raíz al panel de la aplicación', function (): void {
    $this->get('/')
        ->assertRedirect('/app');
});
