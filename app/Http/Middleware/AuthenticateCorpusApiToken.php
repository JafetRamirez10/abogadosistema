<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateCorpusApiToken
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('services.corpus_api.token', '');
        if ($configured === '') {
            return response()->json([
                'message' => 'API de corpus no configurada (CORPUS_API_TOKEN vacío).',
            ], 503);
        }

        $provided = $request->bearerToken()
            ?? $request->header('X-Corpus-Token')
            ?? '';

        if (! is_string($provided) || $provided === '' || ! hash_equals($configured, $provided)) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }

        return $next($request);
    }
}
