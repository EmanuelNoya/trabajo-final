<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_SEGUNDOS = 900; // 15min

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'correo' => ['required', 'email', 'max:120'],
            'password' => ['required', 'string'],
        ], [
            'correo.required' => 'Ingresá tu correo.',
            'correo.email' => 'Ingresá un correo válido.',
            'password.required' => 'Ingresá tu contraseña.',
        ]);

        $clave = 'login|' . Str::lower($datos['correo']);

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            $minutos = (int) ceil(RateLimiter::availableIn($clave) / 60);

            throw ValidationException::withMessages([
                'correo' => "El acceso con este correo está bloqueado. Volvé a intentar en {$minutos} minutos.",
            ]);
        }

        // 'activo' => true | si es false el usuario no puede ingresar
        $credenciales = [
            'correo' => $datos['correo'],
            'password' => $datos['password'],
            'activo' => true,
        ];

        if (! Auth::attempt($credenciales)) {
            RateLimiter::hit($clave, self::BLOQUEO_SEGUNDOS);

            throw ValidationException::withMessages([
                'correo' => 'El correo o la contraseña no son correctos.',
            ]);
        }

        RateLimiter::clear($clave);
        $request->session()->regenerate(); // evita la fijacion de sesion

        return redirect()->intended(route('inicio'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}