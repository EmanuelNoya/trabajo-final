<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaContrasena;
use Illuminate\View\View;

class RecuperacionContrasenaController extends Controller
{
    public function solicitar(): View
    {
        return view('auth.recuperar');
    }

    public function enviarEnlace(Request $request): RedirectResponse
    {
        $request->validate([
            'correo' => ['required', 'email', 'max:120'],
        ]);

        // 'activo' => true: una cuenta desactivada no recibe el enlace.
        // El resultado (enviado, correo inexistente o pedido repetido antes de 60s)
        // no se informa: el mensaje es siempre el mismo.
        Password::sendResetLink([
            'correo' => $request->input('correo'),
            'activo' => true,
        ]);

        return back()->with('estado',
            'Si el correo corresponde a una cuenta activa, vas a recibir un enlace para restablecer la contraseña. El enlace vence en 60 minutos.');
    }

    public function formulario(Request $request, string $token): View
    {
        return view('auth.restablecer', [
            'token' => $token,
            'correo' => $request->query('correo'),
        ]);
    }

    public function restablecer(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'correo' => ['required', 'email', 'max:120'],
            'password' => ['required', 'confirmed', ReglaContrasena::defaults()],
        ]);

        $estado = Password::reset(
            [
                'correo' => $request->input('correo'),
                'activo' => true,
                'token' => $request->input('token'),
                'password' => $request->input('password'),
            ],
            function (Usuario $usuario, string $password) {
                $usuario->forceFill(['hash_contrasena' => $password])->save();

                // Cierra todas las sesiones abiertas del usuario
                DB::table('sessions')->where('user_id', $usuario->id)->delete();
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('correo'))
                ->withErrors(['correo' => 'El enlace no es válido o ya venció. Solicitá uno nuevo.']);
        }

        // Si el correo estaba bloqueado por intentos fallido se desbloquea
        RateLimiter::clear('login|' . Str::lower($request->input('correo')));

        return redirect()->route('login')
            ->with('estado', 'Tu contraseña se actualizó. Ya podés iniciar sesión.');
    }
}