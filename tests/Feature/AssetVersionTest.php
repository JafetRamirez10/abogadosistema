<?php

declare(strict_types=1);

use App\Http\Middleware\AppendAssetVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

it('expone asset_version y asset_v con query v=', function (): void {
    config(['app.asset_version' => '9']);

    expect(asset_version())->toBe('9')
        ->and(asset_v('css/app.css'))->toContain('?v=9');
});

it('agrega v= a scripts y estilos en HTML', function (): void {
    config(['app.asset_version' => '3']);

    $middleware = new AppendAssetVersion;
    $request = Request::create('/app/login', 'GET');

    $response = $middleware->handle($request, function (): Response {
        return response(
            '<html><head><link href="/css/filament/app.css" rel="stylesheet"><script src="/js/filament/app.js"></script><script src="/livewire/livewire.js?id=abc"></script><link href="/css/x.css?v=1.2.3" rel="stylesheet"></head></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    });

    $html = $response->getContent();

    expect($html)
        ->toContain('/css/filament/app.css?v=3')
        ->toContain('/js/filament/app.js?v=3')
        ->toContain('/livewire/livewire.js?id=abc&v=3')
        ->toContain('/css/x.css?v=1.2.3&av=3');
});
