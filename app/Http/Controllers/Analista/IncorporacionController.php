<?php

namespace App\Http\Controllers\Analista;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Servicios\ArchivoInvalido;
use App\Servicios\ImportadorActividad;
use App\Servicios\ImportadorBase;
use App\Servicios\ImportadorCalificaciones;
use App\Servicios\ImportadorForos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Incorporación de los archivos de registros del curso. El flujo es el mismo para todos:
 * analizar, pedir confirmación si hay datos que se reemplazan, e incorporar.
 */
class IncorporacionController extends Controller
{
    /** tipo de archivo (en la URL) => importador que lo procesa */
    public const IMPORTADORES = [
        'actividad' => ImportadorActividad::class,
        'calificaciones' => ImportadorCalificaciones::class,
        'foros' => ImportadorForos::class,
    ];

    public function incorporar(Request $request, Curso $curso, string $tipo): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'extensions:csv', 'max:2048'],
        ]);

        $importador = $this->importador($tipo);
        $this->descartarPendiente($request, $curso, $tipo); // por si quedó uno sin confirmar

        $archivo = $request->file('archivo');
        $ruta = $archivo->store('pendientes'); // carpeta temporal privada
        $nombre = $archivo->getClientOriginalName();

        try {
            $analisis = $importador->analizar($ruta, $curso);
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

        // Hay datos ya incorporados que el archivo reemplazaría: se pide confirmación (HU-04, criterio 4)
        if ($analisis['existentes'] !== []) {
            $request->session()->put($this->clave($curso, $tipo), ['ruta' => $ruta, 'nombre' => $nombre]);

            return redirect()->route('cursos.incorporar.confirmar', [$curso, $tipo]);
        }

        return $this->finalizar($importador, $analisis, $ruta, $nombre, $curso, $request);
    }

    public function confirmar(Request $request, Curso $curso, string $tipo): View|RedirectResponse
    {
        $pendiente = $request->session()->get($this->clave($curso, $tipo));

        if (! $pendiente || ! Storage::exists($pendiente['ruta'])) {
            return $this->sinPendiente($curso);
        }

        $importador = $this->importador($tipo);
        $analisis = $importador->analizar($pendiente['ruta'], $curso);

        return view('analista.incorporacion.confirmar', compact('curso', 'tipo', 'importador', 'pendiente', 'analisis'));
    }

    public function reemplazar(Request $request, Curso $curso, string $tipo): RedirectResponse
    {
        $pendiente = $request->session()->pull($this->clave($curso, $tipo));

        if (! $pendiente || ! Storage::exists($pendiente['ruta'])) {
            return $this->sinPendiente($curso);
        }

        // Se analiza de nuevo: entre la subida y la confirmación pudo cambiar la nómina o las cargas
        $importador = $this->importador($tipo);
        $analisis = $importador->analizar($pendiente['ruta'], $curso);

        return $this->finalizar($importador, $analisis, $pendiente['ruta'], $pendiente['nombre'], $curso, $request);
    }

    public function cancelar(Request $request, Curso $curso, string $tipo): RedirectResponse
    {
        $this->descartarPendiente($request, $curso, $tipo);

        return redirect()->route('cursos.show', $curso)
            ->with('estado', 'Se canceló la incorporación. Los datos del curso no se modificaron.');
    }

    private function finalizar(ImportadorBase $importador, array $analisis, string $ruta, string $nombre, Curso $curso, Request $request): RedirectResponse
    {
        $carga = $importador->incorporar($analisis, $ruta, $nombre, $curso, $request->user());

        $mensaje = 'Archivo de ' . $importador->descripcion()
            . " incorporado: {$carga->filas_validas} filas válidas y {$carga->filas_rechazadas} rechazadas.";

        if (! empty($analisis['aviso'])) {
            $mensaje .= ' ' . $analisis['aviso'];
        }

        return redirect()->route('cursos.show', $curso)
            ->with('estado', $mensaje)
            ->with('rechazadas', $analisis['rechazadas']);
    }

    private function importador(string $tipo): ImportadorBase
    {
        return app(self::IMPORTADORES[$tipo]);
    }

    private function descartarPendiente(Request $request, Curso $curso, string $tipo): void
    {
        $pendiente = $request->session()->pull($this->clave($curso, $tipo));

        if ($pendiente) {
            Storage::delete($pendiente['ruta']);
        }
    }

    private function sinPendiente(Curso $curso): RedirectResponse
    {
        return redirect()->route('cursos.show', $curso)
            ->withErrors(['archivo' => 'No hay ningún archivo pendiente de confirmación.']);
    }

    private function clave(Curso $curso, string $tipo): string
    {
        return "pendiente.{$tipo}.{$curso->id}";
    }
}