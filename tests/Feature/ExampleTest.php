<?php

declare(strict_types=1);

it('responde correctamente en la raíz', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful();
});
