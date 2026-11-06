<?php

namespace App\Http\Controllers\Analista;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Servicios\ArchivoInvalido;
use App\Servicios\ImportadorActividad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ActividadController extends Controller
{
    public function __construct(private ImportadorActividad $importador)
    {
    }

    public function incorporar(Request $request, Curso $curso): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'extensions:csv', 'max:2048'],
        ]);

        $this->descartarPendiente($request, $curso); // por si quedó uno sin confirmar

        $archivo = $request->file('archivo');
        $ruta = $archivo->store('pendientes'); // carpeta temporal privada
        $nombre = $archivo->getClientOriginalName();

        try {
            $analisis = $this->importador->analizar($ruta, $curso);
        } catch (ArchivoInvalido $e) {
            Storage::delete($ruta);

            return back()->withErrors(['archivo' => "No se incorporó el archivo. {$e->getMessage()}"]);
        }

        if ($analisis['validas'] === []) {
            Storage::delete($ruta);

            return back()
                ->withErrors(['archivo' => 'Ninguna fila superó la validación: no se incorporó el archivo.'])
                ->with('rechazadas', $analisis['rechazadas']);
        }

        if ($analisis['semanas_existentes'] !== []) {
            $request->session()->put($this->clave($curso), ['ruta' => $ruta, 'nombre' => $nombre]);

            return redirect()->route('cursos.actividad.confirmar', $curso);
        }

        return $this->finalizar($analisis, $ruta, $nombre, $curso, $request);
    }

    public function confirmar(Request $request, Curso $curso): View|RedirectResponse
    {
        $pendiente = $request->session()->get($this->clave($curso));

        if (! $pendiente || ! Storage::exists($pendiente['ruta'])) {
            return $this->sinPendiente($curso);
        }

        $analisis = $this->importador->analizar($pendiente['ruta'], $curso);

        return view('analista.actividad.confirmar', compact('curso', 'pendiente', 'analisis'));
    }

    public function reemplazar(Request $request, Curso $curso): RedirectResponse
    {
        $pendiente = $request->session()->pull($this->clave($curso));

        if (! $pendiente || ! Storage::exists($pendiente['ruta'])) {
            return $this->sinPendiente($curso);
        }

        $analisis = $this->importador->analizar($pendiente['ruta'], $curso);

        return $this->finalizar($analisis, $pendiente['ruta'], $pendiente['nombre'], $curso, $request);
    }

    public function cancelar(Request $request, Curso $curso): RedirectResponse
    {
        $this->descartarPendiente($request, $curso);

        return redirect()->route('cursos.show', $curso)
            ->with('estado', 'Se canceló la incorporación. La actividad del curso no se modificó.');
    }

    private function finalizar(array $analisis, string $ruta, string $nombre, Curso $curso, Request $request): RedirectResponse
    {
        $carga = $this->importador->incorporar($analisis, $ruta, $nombre, $curso, $request->user());

        return redirect()->route('cursos.show', $curso)
            ->with('estado', "Actividad incorporada: {$carga->filas_validas} filas válidas y {$carga->filas_rechazadas} rechazadas.")
            ->with('rechazadas', $analisis['rechazadas']);
    }

    private function descartarPendiente(Request $request, Curso $curso): void
    {
        $pendiente = $request->session()->pull($this->clave($curso));

        if ($pendiente) {
            Storage::delete($pendiente['ruta']);
        }
    }

    private function sinPendiente(Curso $curso): RedirectResponse
    {
        return redirect()->route('cursos.show', $curso)
            ->withErrors(['archivo' => 'No hay ningún archivo pendiente de confirmación.']);
    }

    private function clave(Curso $curso): string
    {
        return "actividad_pendiente.{$curso->id}";
    }
}