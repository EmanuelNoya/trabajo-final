<?php

namespace App\Servicios;

use App\Models\CargaArchivo;
use App\Models\Curso;
use App\Models\Entrega;
use App\Models\Evaluacion;
use Illuminate\Support\Str;

class ImportadorCalificaciones extends ImportadorBase
{
    public const COLUMNAS_REQUERIDAS = ['legajo', 'evaluacion', 'tipo', 'fecha_vencimiento', 'fecha_entrega', 'calificacion'];

    public function tipo(): string
    {
        return 'calificaciones';
    }

    public function descripcion(): string
    {
        return 'calificaciones y entregas';
    }

    public function unidad(): string
    {
        return 'evaluaciones';
    }

    public function analizar(string $ruta, Curso $curso): array
    {
        $datos = $this->leer($ruta, self::COLUMNAS_REQUERIDAS);
        $nomina = $this->nomina($curso);

        $validas = [];
        $rechazadas = [];
        $evaluaciones = []; // denominación => tipo, fecha de vencimiento y fila donde se definió
        $vistas = [];       // "legajo|denominación" => número de fila

        foreach ($datos['filas'] as $numero => $fila) {
            $legajo = mb_strtoupper($fila['legajo']);
            $denominacion = Str::squish($fila['evaluacion']);
            $tipo = $this->normalizarTipo($fila['tipo']);
            $vencimiento = $this->lector->leerFecha($fila['fecha_vencimiento']);
            $entrega = $fila['fecha_entrega'] === '' ? null : $this->lector->leerFecha($fila['fecha_entrega']);
            $nota = str_replace(',', '.', $fila['calificacion']);
            $definida = $evaluaciones[$denominacion] ?? null;
            $clave = "{$legajo}|{$denominacion}";

            $motivo = match (true) {
                $legajo === '' => 'Falta el legajo.',
                ! $nomina->has($legajo) => 'El legajo no pertenece a la nómina del curso.',
                $denominacion === '' => 'Falta el nombre de la evaluación.',
                mb_strlen($denominacion) > 120 => 'El nombre de la evaluación supera los 120 caracteres.',
                $tipo === null => 'El tipo no es válido (trabajo práctico, actividad práctica, foro, examen parcial u otro).',
                $vencimiento === null => 'La fecha de vencimiento no es válida (usar AAAA-MM-DD o DD/MM/AAAA).',
                $vencimiento->lt($curso->fecha_inicio) || $vencimiento->gt($curso->fecha_fin)
                    => 'La fecha de vencimiento está fuera del período del curso.',
                $fila['fecha_entrega'] !== '' && $entrega === null
                    => 'La fecha de entrega no es válida (usar AAAA-MM-DD o DD/MM/AAAA).',
                $entrega !== null && $entrega->lt($curso->fecha_inicio)
                    => 'La fecha de entrega es anterior al inicio del curso.',
                $nota !== '' && ! is_numeric($nota) => 'La calificación debe ser un número.',
                $nota !== '' && ((float) $nota < 1 || (float) $nota > 10)
                    => 'La calificación está fuera de la escala de 1 a 10.', // criterio 1
                $nota !== '' && $entrega === null => 'Hay calificación pero falta la fecha de entrega.',
                $definida !== null && ($definida['tipo'] !== $tipo || $definida['fecha_vencimiento'] !== $vencimiento->toDateString())
                    => "La evaluación tiene otro tipo o fecha de vencimiento en la fila {$definida['fila']}.",
                isset($vistas[$clave]) => "Evaluación repetida para este legajo: ya figura en la fila {$vistas[$clave]}.",
                default => null,
            };

            if ($motivo !== null) {
                $rechazadas[] = ['fila' => $numero, 'legajo' => $fila['legajo'], 'motivo' => $motivo];
                continue;
            }

            $evaluaciones[$denominacion] ??= [
                'tipo' => $tipo,
                'fecha_vencimiento' => $vencimiento->toDateString(),
                'fila' => $numero,
            ];
            $vistas[$clave] = $numero;

            $validas[] = [
                'inscripcion_id' => $nomina[$legajo],
                'evaluacion' => $denominacion,
                'fecha_entrega' => $entrega?->toDateString(),           // vacía = no presentada (criterio 2)
                'calificacion' => $nota === '' ? null : round((float) $nota, 2),
            ];
        }

        $denominaciones = collect(array_keys($evaluaciones));

        $vigentes = Evaluacion::vigentesDelCurso($curso)
            ->get(['carga_id', 'denominacion'])
            ->map(fn ($e) => ['carga_id' => $e->carga_id, 'unidad' => $e->denominacion]);

        return [
            'validas' => $validas,
            'rechazadas' => $rechazadas,
            'unidades' => $denominaciones->all(),
            'evaluaciones' => $evaluaciones,
        ] + $this->compararConVigentes($vigentes, $denominaciones);
    }

    protected function guardarRegistros(array $analisis, CargaArchivo $carga): void
    {
        // Primero las evaluaciones, para conocer sus id
        $ids = [];
        foreach ($analisis['evaluaciones'] as $denominacion => $evaluacion) {
            $ids[$denominacion] = Evaluacion::create([
                'curso_id' => $carga->curso_id,
                'carga_id' => $carga->id,
                'denominacion' => $denominacion,
                'tipo' => $evaluacion['tipo'],
                'fecha_vencimiento' => $evaluacion['fecha_vencimiento'],
            ])->id;
        }

        $filas = array_map(fn ($f) => [
            'evaluacion_id' => $ids[$f['evaluacion']],
            'inscripcion_id' => $f['inscripcion_id'],
            'carga_id' => $carga->id,
            'fecha_entrega' => $f['fecha_entrega'],
            'calificacion' => $f['calificacion'],
        ], $analisis['validas']);

        foreach (array_chunk($filas, 500) as $bloque) {
            Entrega::insert($bloque);
        }
    }

    /**
     * Acepta el tipo con o sin tildes, en mayúsculas o minúsculas, y los alias TP y parcial.
     */
    private function normalizarTipo(string $texto): ?string
    {
        $buscado = Str::of($texto)->ascii()->lower()->replace('_', ' ')->squish()->toString();

        $tipos = collect(Evaluacion::TIPOS)
            ->mapWithKeys(fn ($etiqueta, $clave) => [Str::of($etiqueta)->ascii()->lower()->toString() => $clave])
            ->put('tp', 'trabajo_practico')
            ->put('parcial', 'examen_parcial');

        return $tipos->get($buscado);
    }
}