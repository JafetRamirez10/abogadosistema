<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\ConsultarIaAgente;
use App\Models\Expediente;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class AsistenteJuridico extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Asistente IA';

    protected static ?string $title = 'Asistente jurídico';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.asistente-juridico';

    public string $mensaje = '';

    public string $especialidad = 'general';

    public bool $usarExpediente = false;

    public ?int $expedienteId = null;

    public bool $mostrandoOpciones = false;

    public bool $escribiendo = false;

    /**
     * @var list<array{role: string, content: string, fuentes?: list<array<string, mixed>>, pendientes?: list<array<string, mixed>>, acciones?: list<string>}>
     */
    public array $mensajes = [];

    /**
     * @var list<array{id: string, tool: string, arguments: array<string, mixed>, resumen: string, tipo: string}>
     */
    public array $pendientes = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->can('usar_ia') ?? false;
    }

    public function updatedUsarExpediente(bool $value): void
    {
        if (! $value) {
            $this->expedienteId = null;
        }
    }

    /**
     * @return array<int|string, string>
     */
    #[Computed]
    public function expedientesOptions(): array
    {
        return Expediente::query()
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->mapWithKeys(fn (Expediente $e): array => [
                $e->getKey() => $e->numero_interno.' — '.$e->titulo,
            ])
            ->all();
    }

    public function enviar(): void
    {
        $pregunta = trim($this->mensaje);
        if ($pregunta === '') {
            return;
        }

        $user = Auth::user();
        if ($user === null || ! $user->can('usar_ia')) {
            Notification::make()->danger()->title('Sin permiso de IA')->send();

            return;
        }

        $expediente = null;
        if ($this->usarExpediente && $this->expedienteId) {
            $expediente = Expediente::query()->find($this->expedienteId);
        }

        $this->mensajes[] = [
            'role' => 'user',
            'content' => $pregunta,
        ];
        $this->mensaje = '';
        $this->escribiendo = true;

        try {
            $resultado = app(ConsultarIaAgente::class)->handle(
                user: $user,
                pregunta: $pregunta,
                expediente: $expediente,
                especialidad: $this->especialidad,
            );

            $this->pendientes = $resultado['pendientes'];

            $this->mensajes[] = [
                'role' => 'assistant',
                'content' => $resultado['respuesta'],
                'fuentes' => array_map(static fn (array $f): array => [
                    'fuente' => $f['fuente'],
                    'referencia' => $f['referencia'],
                    'titulo' => $f['titulo'],
                    'extracto' => mb_substr($f['contenido'], 0, 220),
                ], $resultado['fuentes']),
                'pendientes' => $resultado['pendientes'],
                'acciones' => $resultado['acciones_ejecutadas'],
            ];
        } catch (TooManyRequestsHttpException $e) {
            array_pop($this->mensajes);
            $this->mensaje = $pregunta;
            Notification::make()->warning()->title('Límite de IA')->body($e->getMessage())->send();
        } catch (Throwable $e) {
            array_pop($this->mensajes);
            $this->mensaje = $pregunta;
            Notification::make()->danger()->title('Error de IA')->body($e->getMessage())->send();
        } finally {
            $this->escribiendo = false;
            $this->dispatch('chat-scrolled');
        }
    }

    public function usarSugerencia(string $texto): void
    {
        $this->mensaje = $texto;
        $this->enviar();
    }

    public function confirmarPendiente(string $pendingId): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        $pendiente = collect($this->pendientes)->firstWhere('id', $pendingId);
        if (! is_array($pendiente)) {
            Notification::make()->warning()->title('Nada que confirmar')->send();

            return;
        }

        try {
            $result = app(ConsultarIaAgente::class)->confirmar($user, $pendiente);
            $this->pendientes = array_values(array_filter(
                $this->pendientes,
                fn (array $p): bool => $p['id'] !== $pendingId,
            ));

            $tipo = strtoupper((string) ($pendiente['tipo'] ?? 'accion'));
            $this->mensajes[] = [
                'role' => 'assistant',
                'content' => ($result['ok'] ? "✅ Confirmado ({$tipo}): " : "❌ No se pudo ({$tipo}): ")
                    .$result['message'],
            ];

            if ($result['ok']) {
                Notification::make()->success()->title('Acción confirmada')->body($result['message'])->send();
            } else {
                Notification::make()->danger()->title('Falló la acción')->body($result['message'])->send();
            }
        } catch (Throwable $e) {
            Notification::make()->danger()->title('Error al confirmar')->body($e->getMessage())->send();
        }

        $this->dispatch('chat-scrolled');
    }

    public function cancelarPendiente(string $pendingId): void
    {
        $pendiente = collect($this->pendientes)->firstWhere('id', $pendingId);
        $this->pendientes = array_values(array_filter(
            $this->pendientes,
            fn (array $p): bool => $p['id'] !== $pendingId,
        ));

        $this->mensajes[] = [
            'role' => 'assistant',
            'content' => 'Cancelado. No se '.(($pendiente['tipo'] ?? '') === 'eliminar' ? 'eliminó' : 'modificó').' nada.'
                .(isset($pendiente['resumen']) ? "\n(Quedó sin efecto: ".$pendiente['resumen'].')' : ''),
        ];

        Notification::make()->gray()->title('Acción cancelada')->send();
        $this->dispatch('chat-scrolled');
    }

    public function limpiarChat(): void
    {
        $this->mensajes = [];
        $this->pendientes = [];
        $this->mensaje = '';
        $this->escribiendo = false;
    }

    public function proveedorActivo(): string
    {
        if (filled(config('services.deepseek.key'))) {
            return 'DeepSeek';
        }
        if (filled(config('services.openai.key'))) {
            return 'OpenAI';
        }

        return 'Simulado (sin API key)';
    }

    public function tenantNombre(): string
    {
        return Filament::getTenant()?->nombre ?? 'Despacho';
    }
}
