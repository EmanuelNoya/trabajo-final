<?php

namespace App\Servicios;

use App\Models\CargaArchivo;
use App\Models\Curso;
use App\Models\ParticipacionForo;

class ImportadorForos extends ImportadorBase
{
    private const MAXIMO = 99999; // longitud 5 del diccionario

    public function tipo(): string
    {
        return 'foros';
    }

    public function descripcion(): string
    {
        return 'participación en foros';
    }

    public function unidad(): string
    {
        return 'semanas';
    }

    public function analizar(string $ruta, Curso $curso): array
    {
        $datos = $this->leer($ruta, ['legajo', 'semana', 'intervenciones']);
        $nomina = $this->nomina($curso);
        $totalSemanas = $curso->cantidadSemanas();

        $validas = [];
        $rechazadas = [];
        $vistas = []; // "legajo|semana" => número de fila

        foreach ($datos['filas'] as $numero => $fila) {
            $legajo = mb_strtoupper($fila['legajo']);
            $semana = $fila['semana'];
            $intervenciones = $fila['intervenciones'];
            $clave = $legajo . '|' . (int) $semana;

            $motivo = match (true) {
                $legajo === '' => 'Falta el legajo.',
                ! $nomina->has($legajo) => 'El legajo no pertenece a la nómina del curso.',
                ! ctype_digit($semana) || (int) $semana < 1 || (int) $semana > $totalSemanas
                    => "La semana debe ser un número entre 1 y {$totalSemanas}.",
                // ctype_digit rechaza los negativos ("-2"), los decimales y el texto (criterio 1)
                ! ctype_digit($intervenciones)
                    => 'La cantidad de intervenciones debe ser un número entero mayor o igual que cero.',
                (int) $intervenciones > self::MAXIMO
                    => 'La cantidad de intervenciones supera el máximo admitido (' . self::MAXIMO . ').',
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
                'intervenciones' => (int) $intervenciones,
            ];
        }

        $semanas = collect($validas)->pluck('semana')->unique()->sort()->values();

        // Criterio 2: en cada semana del archivo, el estudiante de la nómina sin fila tiene cero intervenciones
        $conDatos = collect($validas)->mapWithKeys(fn ($f) => ["{$f['inscripcion_id']}|{$f['semana']}" => true]);
        $completadas = [];

        foreach ($semanas as $semana) {
            foreach ($nomina as $inscripcionId) {
                if (! $conDatos->has("{$inscripcionId}|{$semana}")) {
                    $completadas[] = ['inscripcion_id' => $inscripcionId, 'semana' => $semana, 'intervenciones' => 0];
                }
            }
        }

        $vigentes = ParticipacionForo::vigentesDelCurso($curso)
            ->distinct()
            ->get(['carga_id', 'semana'])
            ->map(fn ($r) => ['carga_id' => $r->carga_id, 'unidad' => $r->semana]);

        return [
            'validas' => $validas,
            'rechazadas' => $rechazadas,
            'unidades' => $semanas->all(),
            'completadas' => $completadas,
            'aviso' => $completadas === [] ? null
                : 'Se registraron cero intervenciones en ' . count($completadas)
                  . ' casos de estudiante y semana que no figuraban en el archivo.',
        ] + $this->compararConVigentes($vigentes, $semanas);
    }

    protected function guardarRegistros(array $analisis, CargaArchivo $carga): void
    {
        $filas = array_map(
            fn ($f) => $f + ['carga_id' => $carga->id],
            [...$analisis['validas'], ...$analisis['completadas']]
        );

        foreach (array_chunk($filas, 500) as $bloque) {
            ParticipacionForo::insert($bloque);
        }
    }
}