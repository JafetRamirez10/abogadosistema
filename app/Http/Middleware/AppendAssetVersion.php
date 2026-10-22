<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agrega ?v= / &v= a CSS, JS y assets de Livewire/Filament en respuestas HTML
 * para forzar recarga de caché del navegador al subir ASSET_VERSION.
 */
class AppendAssetVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldVersion($response)) {
            return $response;
        }

        $version = asset_version();
        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return $response;
        }

        $updated = preg_replace_callback(
            '/\b(src|href)=(["\'])([^"\']*?)\2/i',
            static function (array $matches) use ($version): string {
                $attr = $matches[1];
                $quote = $matches[2];
                $url = $matches[3];

                if (! self::isVersionableAssetUrl($url)) {
                    return $matches[0];
                }

                // Ya versionado por la app (evita duplicar en redirects internos).
                if (preg_match('/(?:^|[?&])av=/', $url) === 1) {
                    return $matches[0];
                }

                $separator = str_contains($url, '?') ? '&' : '?';

                // Filament ya usa ?v=versión-paquete → agregamos &av=
                // Livewire usa ?id=hash → agregamos &v=
                // Assets sin query → ?v=
                $param = preg_match('/(?:^|[?&])v=/', $url) === 1 ? 'av' : 'v';

                return $attr.'='.$quote.$url.$separator.$param.'='.rawurlencode($version).$quote;
            },
            $content,
        );

        if (is_string($updated)) {
            $response->setContent($updated);
        }

        return $response;
    }

    private function shouldVersion(Response $response): bool
    {
        if (! method_exists($response, 'getContent')) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html')
            || $contentType === ''
            || str_contains($contentType, 'application/xhtml');
    }

    private static function isVersionableAssetUrl(string $url): bool
    {
        if ($url === '' || str_starts_with($url, 'data:') || str_starts_with($url, 'blob:')) {
            return false;
        }

        $path = strtolower((string) (parse_url($url, PHP_URL_PATH) ?? $url));

        if (str_contains($path, '/livewire/livewire')) {
            return true;
        }

        return (bool) preg_match('/\.(css|js)(?:$|\?)/', $path)
            || str_ends_with($path, '.css')
            || str_ends_with($path, '.js');
    }
}
