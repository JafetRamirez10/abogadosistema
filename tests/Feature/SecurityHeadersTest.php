<?php

declare(strict_types=1);

it('incluye cabeceras de seguridad en el login del panel', function (): void {
    $response = $this->get('/app/login');

    $response->assertSuccessful();
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
    expect($response->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'none'");
    // HSTS solo en production; en testing/local no debe enviarse.
    expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});
