<?php

namespace App\Servicios;

use App\Models\ActividadSemanal;
use App\Models\CargaArchivo;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportadorActividad
{
    public const COLUMNAS_REQUERIDAS = ['legajo', 'semana', 'con_actividad'];

    private const VALORES_SI = ['1', 'si', 'sí', 's', 'true', 'verdadero', 'x'];
    private const VALORES_NO = ['0', 'no', 'n', 'false', 'falso'];

    public function __construct(private LectorCsv $lector)
    {
    }

    public function analizar(string $ruta, Curso $curso): array
    {
        $datos = $this->lector->leer(Storage::path($ruta));
        $this->lector->exigirColumnas($datos['encabezados'], self::COLUMNAS_REQUERIDAS); // criterio 3

        if ($datos['filas'] === []) {
            throw new ArchivoInvalido('El archivo no tiene filas de actividad.');
        }

        // legajo => id de la inscripción, solo para los estudiantes de la nómina del curso
        $nomina = Inscripcion::where('curso_id', $curso->id)
            ->with('estudiante:id,legajo')
            ->get()
            ->mapWithKeys(fn ($i) => [mb_strtoupper($i->estudiante->legajo) => $i->id]);

        $totalSemanas = $curso->cantidadSemanas();
        $validas = [];
        $rechazadas = [];
        $vistas = []; // "legajo|semana" => número de fila

        foreach ($datos['filas'] as $numero => $fila) {
            $legajo = mb_strtoupper($fila['legajo']);
            $semana = $fila['semana'];
            $valor = mb_strtolower($fila['con_actividad']);
            $clave = $legajo . '|' . (int) $semana;

            $motivo = match (true) {
                $legajo === '' => 'Falta el legajo.',
                ! $nomina->has($legajo) => 'El legajo no pertenece a la nómina del curso.',
                ! ctype_digit($semana) || (int) $semana < 1 || (int) $semana > $totalSemanas
                    => "La semana debe ser un número entre 1 y {$totalSemanas}.",
                ! in_array($valor, [...self::VALORES_SI, ...self::VALORES_NO], true)
                    => 'con_actividad debe ser 1 o 0 (también se acepta sí o no).',
                isset($vistas[$clave]) => "Semana repetida para este legajo: ya figura en la fila {$vistas[$clave]}.",
                default => null,
            };

            if ($motivo !== null) {
                $rechazadas[] = ['fila' => $numero, 'legajo' => $fila['legajo'], 'motivo' => $motivo];
                continue;
            }

            $vistas[$clave] = $numero;
            $validas[] = [
                'inscripcion_id' => $nomina[$legajo],
                'semana' => (int) $semana,
                'con_actividad' => in_array($valor, self::VALORES_SI, true),
            ];
        }

        $semanasArchivo = collect($validas)->pluck('semana')->unique()->sort()->values();

        // Semanas que ya tienen datos en cargas vigentes del curso
        $vigentes = ActividadSemanal::vigentesDelCurso($curso)
            ->distinct()
            ->get(['carga_id', 'semana']);

        $cargasAReemplazar = $vigentes->whereIn('semana', $semanasArchivo)->pluck('carga_id')->unique()->values();
        $semanasQueQuedan = $vigentes->whereNotIn('carga_id', $cargasAReemplazar)->pluck('semana');

        return [
            'validas' => $validas,
            'rechazadas' => $rechazadas,
            'semanas' => $semanasArchivo->all(),
            'semanas_existentes' => $vigentes->whereIn('semana', $semanasArchivo)
                ->pluck('semana')->unique()->sort()->values()->all(),
            // Semanas que solo estaban en las cargas reemplazadas y que el archivo nuevo no trae
            'semanas_sin_datos' => $vigentes->whereIn('carga_id', $cargasAReemplazar)->pluck('semana')
                ->diff($semanasArchivo)->diff($semanasQueQuedan)->unique()->sort()->values()->all(),
            'cargas_a_reemplazar' => $cargasAReemplazar->all(),
        ];
    }

    public function incorporar(array $analisis, string $ruta, string $nombreOriginal, Curso $curso, Usuario $analista): CargaArchivo
    {
        return DB::transaction(function () use ($analisis, $ruta, $nombreOriginal, $curso, $analista) {

            CargaArchivo::whereIn('id', $analisis['cargas_a_reemplazar'])->update(['estado' => 'reemplazada']);

            $carga = CargaArchivo::create([ // criterio 5
                'curso_id' => $curso->id,
                'usuario_id' => $analista->id,
                'tipo_archivo' => 'actividad',
                'nombre_archivo' => mb_substr($nombreOriginal, 0, 255),
                'hash_archivo' => hash_file('sha256', Storage::path($ruta)),
                'filas_validas' => count($analisis['validas']),
                'filas_rechazadas' => count($analisis['rechazadas']),
            ]);

            $filas = array_map(fn ($f) => $f + ['carga_id' => $carga->id], $analisis['validas']);

            foreach (array_chunk($filas, 500) as $bloque) {
                ActividadSemanal::insert($bloque); // una sola consulta por bloque de 500 filas
            }

            Storage::move($ruta, "cargas/{$carga->id}_actividad.csv");

            return $carga;
        });
    }
}