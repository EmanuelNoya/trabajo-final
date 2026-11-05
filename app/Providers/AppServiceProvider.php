<?php

namespace App\Providers;

use App\Models\Usuario;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Politica de contraseñas del sistema
        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers()->symbols());

        // Correo de restablecimiento
        ResetPassword::toMailUsing(function (Usuario $usuario, string $token) {
            $url = route('password.reset', ['token' => $token, 'correo' => $usuario->correo]);

            return (new MailMessage)
                ->subject('Restablecer la contraseña')
                ->greeting("Hola, {$usuario->nombre}")
                ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
                ->action('Restablecer contraseña', $url)
                ->line('El enlace vence en 60 minutos y se puede usar una sola vez.')
                ->line('Si no lo solicitaste, ignorá este mensaje: tu contraseña no cambia.')
                ->salutation(config('app.name'));
        });
    }
}