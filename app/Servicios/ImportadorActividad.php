<?php

namespace App\Servicios;

use App\Models\ActividadSemanal;
use App\Models\CargaArchivo;
use App\Models\Curso;

class ImportadorActividad extends ImportadorBase
{
    private const VALORES_SI = ['1', 'si', 'sí', 's', 'true', 'verdadero', 'x'];
    private const VALORES_NO = ['0', 'no', 'n', 'false', 'falso'];

    public function tipo(): string
    {
        return 'actividad';
    }

    public function descripcion(): string
    {
        return 'actividad semanal';
    }

    public function unidad(): string
    {
        return 'semanas';
    }

    public function analizar(string $ruta, Curso $curso): array
    {
        $datos = $this->leer($ruta, ['legajo', 'semana', 'con_actividad']);
        $nomina = $this->nomina($curso);
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

        $semanas = collect($validas)->pluck('semana')->unique()->sort()->values();

        $vigentes = ActividadSemanal::vigentesDelCurso($curso)
            ->distinct()
            ->get(['carga_id', 'semana'])
            ->map(fn ($r) => ['carga_id' => $r->carga_id, 'unidad' => $r->semana]);

        return [
            'validas' => $validas,
            'rechazadas' => $rechazadas,
            'unidades' => $semanas->all(),
        ] + $this->compararConVigentes($vigentes, $semanas);
    }

    protected function guardarRegistros(array $analisis, CargaArchivo $carga): void
    {
        $filas = array_map(fn ($f) => $f + ['carga_id' => $carga->id], $analisis['validas']);

        foreach (array_chunk($filas, 500) as $bloque) {
            ActividadSemanal::insert($bloque); // una sola consulta por bloque de 500 filas
        }
    }
}