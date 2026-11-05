<?php

namespace App\Servicios;

use App\Models\CargaArchivo;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\Usuario;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ImportadorNomina
{
    public const COLUMNAS_REQUERIDAS = ['legajo', 'apellido', 'nombre'];

    public function __construct(private LectorCsv $lector)
    {
    }

    /**
     * Valida e incorpora un archivo de nómina.
     *
     * @return array{carga: ?CargaArchivo, rechazadas: array<int, array{fila: int, legajo: string, motivo: string}>}
     *         carga es null si ninguna fila superó la validación (no se guarda nada).
     */
    public function importar(UploadedFile $archivo, Curso $curso, Usuario $analista): array
    {
        $datos = $this->lector->leer($archivo->getRealPath());
        $this->lector->exigirColumnas($datos['encabezados'], self::COLUMNAS_REQUERIDAS);

        if ($datos['filas'] === []) {
            throw new ArchivoInvalido('El archivo no tiene filas de estudiantes.');
        }

        $validas = [];
        $rechazadas = [];
        $legajosVistos = []; // legajo => número de fila donde apareció primero

        foreach ($datos['filas'] as $numero => $fila) {
            [$normalizada, $motivo] = $this->validarFila($fila, $curso, $legajosVistos);

            if ($motivo !== null) {
                $rechazadas[] = ['fila' => $numero, 'legajo' => $fila['legajo'], 'motivo' => $motivo];
                continue;
            }

            $legajosVistos[$normalizada['legajo']] = $numero;
            $validas[] = $normalizada;
        }

        if ($validas === []) {
            return ['carga' => null, 'rechazadas' => $rechazadas];
        }

        // Todo o nada: si algo falla, no queda ninguna carga ni inscripción a medias
        $carga = DB::transaction(function () use ($archivo, $curso, $analista, $validas, $rechazadas) {
            // La nómina anterior del curso, si existe, deja de estar vigente
            CargaArchivo::where('curso_id', $curso->id)
                ->where('tipo_archivo', 'nomina')
                ->where('estado', 'vigente')
                ->update(['estado' => 'reemplazada']);

            $carga = CargaArchivo::create([
                'curso_id' => $curso->id,
                'usuario_id' => $analista->id,
                'tipo_archivo' => 'nomina',
                'nombre_archivo' => mb_substr($archivo->getClientOriginalName(), 0, 255),
                'hash_archivo' => hash_file('sha256', $archivo->getRealPath()),
                'filas_validas' => count($validas),
                'filas_rechazadas' => count($rechazadas),
            ]);

            foreach ($validas as $fila) {
                // El legajo es único en todo el sistema: si el estudiante ya existe, se actualizan sus datos
                $estudiante = Estudiante::firstOrNew(['legajo' => $fila['legajo']]);
                $estudiante->apellido = $fila['apellido'];
                $estudiante->nombre = $fila['nombre'];
                if ($fila['documento'] !== '') {
                    $estudiante->documento = $fila['documento'];
                }
                $estudiante->save();

                Inscripcion::updateOrCreate(
                    ['curso_id' => $curso->id, 'estudiante_id' => $estudiante->id],
                    ['carga_id' => $carga->id, 'fecha_baja' => $fila['fecha_baja']] // HU-03, criterio 3
                );
            }

            // Se guarda una copia del archivo recibido: con el hash se puede verificar que no cambió
            $archivo->storeAs('cargas', "{$carga->id}_nomina.csv");

            return $carga;
        });

        return ['carga' => $carga, 'rechazadas' => $rechazadas];
    }

    /**
     * @return array{0: ?array, 1: ?string} [fila normalizada, motivo de rechazo]
     */
    private function validarFila(array $fila, Curso $curso, array $legajosVistos): array
    {
        $legajo = mb_strtoupper($fila['legajo']);
        $apellido = $fila['apellido'];
        $nombre = $fila['nombre'];
        $documento = preg_replace('/[.\s-]/', '', $fila['documento'] ?? '');
        $textoBaja = $fila['fecha_baja'] ?? '';

        if ($legajo === '') {
            return [null, 'Falta el legajo.'];
        }
        if (mb_strlen($legajo) > 20) {
            return [null, 'El legajo supera los 20 caracteres.'];
        }
        if (isset($legajosVistos[$legajo])) {
            return [null, "Legajo repetido: ya figura en la fila {$legajosVistos[$legajo]}."]; // HU-03, criterio 2
        }
        if ($apellido === '' || $nombre === '') {
            return [null, 'Falta el apellido o el nombre.'];
        }
        if (mb_strlen($apellido) > 60 || mb_strlen($nombre) > 60) {
            return [null, 'El apellido o el nombre superan los 60 caracteres.'];
        }
        if ($documento !== '' && ! preg_match('/^\d{1,12}$/', $documento)) {
            return [null, 'El documento debe tener solo números (hasta 12).'];
        }

        $fechaBaja = null;
        if ($textoBaja !== '') {
            $fechaBaja = $this->leerFecha($textoBaja);

            if ($fechaBaja === null) {
                return [null, 'La fecha de baja no es válida (usar AAAA-MM-DD o DD/MM/AAAA).'];
            }
            if ($fechaBaja->lt($curso->fecha_inicio) || $fechaBaja->gt($curso->fecha_fin)) {
                return [null, 'La fecha de baja está fuera del período del curso.'];
            }
        }

        return [[
            'legajo' => $legajo,
            'apellido' => $apellido,
            'nombre' => $nombre,
            'documento' => $documento,
            'fecha_baja' => $fechaBaja?->toDateString(),
        ], null];
    }

    private function leerFecha(string $texto): ?Carbon
    {
        foreach (['!Y-m-d', '!d/m/Y'] as $formato) {
            $fecha = DateTimeImmutable::createFromFormat($formato, $texto);
            $errores = DateTimeImmutable::getLastErrors();

            // getLastErrors() detecta fechas imposibles como 31/02, que PHP "corregiría" sin avisar
            if ($fecha !== false && ($errores === false || $errores['warning_count'] + $errores['error_count'] === 0)) {
                return Carbon::instance($fecha);
            }
        }

        return null;
    }
}