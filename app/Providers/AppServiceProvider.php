<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AiService;
use App\Services\Ai\FakeAiService;
use App\Services\Ai\OpenAiService;
use Database\Factories\Support\CostaRicaProvider;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiService::class, function ($app): AiService {
            $key = config('services.openai.key');

            if ($app->environment('testing') || blank($key)) {
                return $app->make(FakeAiService::class);
            }

            return $app->make(OpenAiService::class);
        });
    }

    public function boot(): void
    {
        $this->registerCostaRicaFaker();
        $this->registerAssetVersioning();

        Password::defaults(static fn (): Password => Password::min(10)
            ->mixedCase()
            ->numbers()
            ->uncompromised());

        RateLimiter::for('login', function (Request $request): Limit {
            $email = (string) $request->input('email', $request->input('data.email', ''));

            return Limit::perMinute(5)->by(strtolower($email).'|'.$request->ip());
        });

        RateLimiter::for('ia', function (Request $request): array {
            $userId = (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());
            $despachoId = (string) (Filament::getTenant()?->getKey()
                ?? $request->input('despacho_id')
                ?? 'none');

            return [
                Limit::perMinute(10)->by('ia:user:'.$userId),
                Limit::perDay(200)->by('ia:despacho:'.$despachoId),
            ];
        });

        RateLimiter::for('documentos-descarga', function (Request $request): Limit {
            return Limit::perMinute(60)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        if ($this->app->environment('local')) {
            Model::preventLazyLoading();
        }

        Filament::serving(function (): void {
            $tenant = Filament::getTenant();

            if ($tenant !== null) {
                setPermissionsTeamId($tenant->getKey());

                // Spatie cachea roles/permissions en la instancia del modelo. Si se
                // cargaron antes de fijar el equipo, quedan vacías y los policies
                // devuelven 403 aunque el usuario tenga el permiso.
                $user = auth()->user();

                if ($user !== null) {
                    $user->unsetRelation('roles')->unsetRelation('permissions');
                }
            }
        });
    }

    /**
     * Cache-busting: Filament ?v=ASSET_VERSION y Vite con query de versión.
     */
    private function registerAssetVersioning(): void
    {
        $version = asset_version();

        FilamentAsset::appVersion($version);

        Vite::useScriptTagAttributes([
            'data-asset-version' => $version,
        ]);

        Vite::useStyleTagAttributes([
            'data-asset-version' => $version,
        ]);
    }

    /**
     * Registra Faker es_ES + CostaRicaProvider para factories y seeders.
     */
    private function registerCostaRicaFaker(): void
    {
        $locales = array_unique([
            (string) config('app.faker_locale', 'es_ES'),
            'es_ES',
            'es_CR',
        ]);

        foreach ($locales as $locale) {
            $abstract = FakerGenerator::class.':'.$locale;

            $this->app->singleton($abstract, function () use ($locale): FakerGenerator {
                $baseLocale = $locale === 'es_CR' ? 'es_ES' : $locale;
                $faker = FakerFactory::create($baseLocale);
                $faker->addProvider(new CostaRicaProvider($faker));

                return $faker;
            });
        }
    }
}
