<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as ReglaContrasena;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    private const MENSAJES = [
        'correo.unique' => 'Ya existe un usuario con ese correo.',
    ];

    public function index(): View
    {
        $usuarios = Usuario::with('desactivadoPor')
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();

        return view('administrador.usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('administrador.usuarios.create', ['usuario' => new Usuario()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'apellido' => ['required', 'string', 'max:60'],
            'correo' => ['required', 'email', 'max:120', Rule::unique('usuario', 'correo')],
            'rol' => ['required', Rule::in(array_keys(Usuario::ROLES))],
            'password' => ['required', 'confirmed', ReglaContrasena::defaults()],
        ], self::MENSAJES);

        $usuario = Usuario::create([
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'correo' => Str::lower($datos['correo']),
            'rol' => $datos['rol'],
            'hash_contrasena' => $datos['password'],
        ]);

        return redirect()->route('usuarios.index')
            ->with('estado', "Se dio de alta a {$usuario->nombre} {$usuario->apellido}.");
    }

    public function edit(Usuario $usuario): View
    {
        return view('administrador.usuarios.edit', compact('usuario'));
    }

    public function update(Request $request, Usuario $usuario): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'apellido' => ['required', 'string', 'max:60'],
            'correo' => ['required', 'email', 'max:120', Rule::unique('usuario', 'correo')->ignore($usuario->id)],
            'rol' => ['required', Rule::in(array_keys(Usuario::ROLES))],
        ], self::MENSAJES);

        // Evita que el sistema quede sin administrador
        if ($usuario->is($request->user()) && $datos['rol'] !== 'administrador') {
            return back()->withInput()
                ->withErrors(['rol' => 'No podés quitarte el perfil de administrador.']);
        }

        $datos['correo'] = Str::lower($datos['correo']);
        $usuario->update($datos);

        return redirect()->route('usuarios.index')
            ->with('estado', "Se actualizaron los datos de {$usuario->nombre} {$usuario->apellido}.");
    }

    public function desactivar(Request $request, Usuario $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->withErrors(['usuario' => 'No podés desactivar tu propia cuenta.']);
        }

        DB::transaction(function () use ($request, $usuario) {
            $usuario->forceFill([
                'activo' => false,
                'desactivado_por' => $request->user()->id,
                'fecha_desactivacion' => now(),
            ])->save();

            // Cierra sus sesiones y anula los enlaces de restablecimiento pendientes
            DB::table('sessions')->where('user_id', $usuario->id)->delete();
            Password::deleteToken($usuario);
        });

        return back()->with('estado',
            "Se desactivó a {$usuario->nombre} {$usuario->apellido}. Sus sesiones abiertas se cerraron.");
    }

    public function activar(Usuario $usuario): RedirectResponse
    {
        $usuario->forceFill([
            'activo' => true,
            'desactivado_por' => null,
            'fecha_desactivacion' => null,
        ])->save();

        return back()->with('estado', "Se reactivó a {$usuario->nombre} {$usuario->apellido}.");
    }
}