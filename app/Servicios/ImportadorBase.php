<?php

namespace App\Servicios;

use App\Models\CargaArchivo;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Base de los importadores de registros del curso (actividad, calificaciones y foros).
 * Resuelve lo común: lectura del archivo, nómina, detección de reemplazos y registro de la carga.
 * Cada importador concreto define cómo valida sus filas y cómo guarda sus registros.
 */
abstract class ImportadorBase
{
    public function __construct(protected LectorCsv $lector)
    {
    }

    /** Valor de carga_archivo.tipo_archivo */
    abstract public function tipo(): string;

    /** Nombre del archivo para los mensajes, por ejemplo "actividad semanal" */
    abstract public function descripcion(): string;

    /** Unidad que se reemplaza, en plural: "semanas" o "evaluaciones" */
    abstract public function unidad(): string;

    /**
     * Valida el archivo SIN guardar nada. Devuelve al menos:
     * validas, rechazadas, unidades, existentes, sin_datos y cargas_a_reemplazar.
     */
    abstract public function analizar(string $ruta, Curso $curso): array;

    /** Guarda los registros válidos asociados a la carga. */
    abstract protected function guardarRegistros(array $analisis, CargaArchivo $carga): void;

    /**
     * Incorpora el resultado de un análisis. Todo o nada.
     */
    public function incorporar(array $analisis, string $ruta, string $nombreOriginal, Curso $curso, Usuario $analista): CargaArchivo
    {
        return DB::transaction(function () use ($analisis, $ruta, $nombreOriginal, $curso, $analista) {
            // Las cargas que comparten unidades con el archivo nuevo dejan de estar vigentes (sus registros se conservan)
            CargaArchivo::whereIn('id', $analisis['cargas_a_reemplazar'])->update(['estado' => 'reemplazada']);

            $carga = CargaArchivo::create([
                'curso_id' => $curso->id,
                'usuario_id' => $analista->id,
                'tipo_archivo' => $this->tipo(),
                'nombre_archivo' => mb_substr($nombreOriginal, 0, 255),
                'hash_archivo' => hash_file('sha256', Storage::path($ruta)),
                'filas_validas' => count($analisis['validas']),
                'filas_rechazadas' => count($analisis['rechazadas']),
            ]);

            $this->guardarRegistros($analisis, $carga);

            Storage::move($ruta, "cargas/{$carga->id}_{$this->tipo()}.csv");

            return $carga;
        });
    }

    /**
     * Lee el archivo y exige las columnas obligatorias.
     */
    protected function leer(string $ruta, array $requeridas): array
    {
        $datos = $this->lector->leer(Storage::path($ruta));
        $this->lector->exigirColumnas($datos['encabezados'], $requeridas);

        if ($datos['filas'] === []) {
            throw new ArchivoInvalido('El archivo no tiene filas de datos.');
        }

        return $datos;
    }

    /**
     * legajo => id de la inscripción, para los estudiantes de la nómina del curso.
     */
    protected function nomina(Curso $curso): Collection
    {
        return Inscripcion::where('curso_id', $curso->id)
            ->with('estudiante:id,legajo')
            ->get()
            ->mapWithKeys(fn ($i) => [mb_strtoupper($i->estudiante->legajo) => $i->id]);
    }

    /**
     * Compara las unidades del archivo con las de las cargas vigentes del curso.
     *
     * @param  Collection  $vigentes  elementos ['carga_id' => ..., 'unidad' => ...]
     * @param  Collection  $delArchivo  unidades que trae el archivo nuevo
     */
    protected function compararConVigentes(Collection $vigentes, Collection $delArchivo): array
    {
        $coinciden = $vigentes->whereIn('unidad', $delArchivo);
        $cargas = $coinciden->pluck('carga_id')->unique()->values();
        $quedan = $vigentes->whereNotIn('carga_id', $cargas)->pluck('unidad');

        return [
            'existentes' => $coinciden->pluck('unidad')->unique()->sort()->values()->all(),
            // Unidades que solo estaban en las cargas reemplazadas y que el archivo nuevo no trae
            'sin_datos' => $vigentes->whereIn('carga_id', $cargas)->pluck('unidad')
                ->diff($delArchivo)->diff($quedan)->unique()->sort()->values()->all(),
            'cargas_a_reemplazar' => $cargas->all(),
        ];
    }
}