<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Página mínima viable para habilitar/deshabilitar 2FA (Google Authenticator).
 *
 * @property Form $form
 */
class ManageTwoFactorAuthentication extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.manage-two-factor-authentication';

    protected static ?string $navigationLabel = 'Autenticación 2FA';

    protected static ?string $title = 'Autenticación en dos pasos';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 10;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?string $pendingSecret = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Código de verificación')
                    ->numeric()
                    ->minLength(6)
                    ->maxLength(6)
                    ->visible(fn (): bool => filled($this->pendingSecret) || $this->user()->hasTwoFactorEnabled()),
            ])
            ->statePath('data');
    }

    public function startSetup(): void
    {
        $this->pendingSecret = $this->user()->enableTwoFactorAuthentication();

        Notification::make()
            ->title('Secreto 2FA generado')
            ->body('Escaneá el secreto en tu app authenticator y confirmá con el código.')
            ->success()
            ->send();
    }

    public function confirm(): void
    {
        $code = (string) ($this->form->getState()['code'] ?? '');

        if (! $this->user()->confirmTwoFactorAuthentication($code)) {
            Notification::make()
                ->title('Código inválido')
                ->danger()
                ->send();

            return;
        }

        $this->pendingSecret = null;

        Notification::make()
            ->title('2FA activado')
            ->success()
            ->send();
    }

    public function disable(): void
    {
        $this->user()->disableTwoFactorAuthentication();
        $this->pendingSecret = null;

        Notification::make()
            ->title('2FA desactivado')
            ->success()
            ->send();
    }

    protected function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    /**
     * @return array<string, Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form($this->makeForm()),
        ];
    }
}
