<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

it('registra el rate limiter de login a 5 por minuto', function (): void {
    $limiter = RateLimiter::limiter('login');

    expect($limiter)->toBeCallable();

    $request = Request::create('/app/login', 'POST', [
        'email' => 'demo@example.com',
    ]);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    $limit = $limiter($request);

    expect($limit)->toBeInstanceOf(Limit::class)
        ->and($limit->maxAttempts)->toBe(5);
});
