<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\ConsultarIaAgente;
use App\Models\Expediente;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class AsistenteJuridico extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Asistente IA';

    protected static ?string $title = 'Asistente jurídico';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.asistente-juridico';

    /** @var array{pregunta: string|null, especialidad: string, usar_expediente: bool, expediente_id: int|null} */
    public ?array $data = [];

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

    public function mount(): void
    {
        $this->form->fill([
            'pregunta' => '',
            'especialidad' => 'general',
            'usar_expediente' => false,
            'expediente_id' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('especialidad')
                    ->label('Especialidad')
                    ->options([
                        'general' => 'General',
                        'familia' => 'Familia',
                        'notarial' => 'Notarial / inmobiliario',
                        'penal' => 'Penal',
                    ])
                    ->required()
                    ->native(false),
                Toggle::make('usar_expediente')
                    ->label('Incluir contexto de un expediente')
                    ->live(),
                Select::make('expediente_id')
                    ->label('Expediente')
                    ->options(fn (): array => Expediente::query()
                        ->orderByDesc('id')
                        ->limit(100)
                        ->get()
                        ->mapWithKeys(fn (Expediente $e): array => [
                            $e->getKey() => $e->numero_interno.' — '.$e->titulo,
                        ])
                        ->all())
                    ->searchable()
                    ->visible(fn (callable $get): bool => (bool) $get('usar_expediente')),
                Textarea::make('pregunta')
                    ->label('Tu consulta o instrucción')
                    ->placeholder('Ej. Creá una tarea «Revisar escritura de traspaso» o preguntá derecho. Para borrar/actualizar pedirá confirmación.')
                    ->rows(4)
                    ->required()
                    ->maxLength(4000)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function enviar(): void
    {
        $state = $this->form->getState();
        $pregunta = trim((string) ($state['pregunta'] ?? ''));
        if ($pregunta === '') {
            return;
        }

        $user = Auth::user();
        if ($user === null || ! $user->can('usar_ia')) {
            Notification::make()->danger()->title('Sin permiso de IA')->send();

            return;
        }

        $expediente = null;
        if (! empty($state['usar_expediente']) && ! empty($state['expediente_id'])) {
            $expediente = Expediente::query()->find($state['expediente_id']);
        }

        $this->mensajes[] = [
            'role' => 'user',
            'content' => $pregunta,
        ];

        try {
            $resultado = app(ConsultarIaAgente::class)->handle(
                user: $user,
                pregunta: $pregunta,
                expediente: $expediente,
                especialidad: (string) ($state['especialidad'] ?? 'general'),
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

            $this->form->fill([
                ...$state,
                'pregunta' => '',
            ]);
        } catch (TooManyRequestsHttpException $e) {
            array_pop($this->mensajes);
            Notification::make()->warning()->title('Límite de IA')->body($e->getMessage())->send();
        } catch (Throwable $e) {
            array_pop($this->mensajes);
            Notification::make()->danger()->title('Error de IA')->body($e->getMessage())->send();
        }
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
    }

    public function limpiarChat(): void
    {
        $this->mensajes = [];
        $this->pendientes = [];
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
