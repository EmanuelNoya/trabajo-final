<?php

namespace App\Servicios;

use App\Models\ActividadSemanal;
use App\Models\Curso;
use App\Models\Entrega;
use App\Models\Evaluacion;
use App\Models\Inscripcion;
use App\Models\ParticipacionForo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CalculadoraIndicadores
{
    public const PROPORCION_CORTE = 0.30;
    public const CALIFICACION_MAXIMA = 10;

    public const ARCHIVOS_REQUERIDOS = [
        'nomina' => 'nómina',
        'actividad' => 'actividad semanal',
        'calificaciones' => 'calificaciones y entregas',
        'foros' => 'participación en foros',
    ];

    /**
     * inicio + ⌊0,30 × duración en días⌋
     */
    public function fechaCorte(Curso $curso): Carbon
    {
        $duracion = (int) $curso->fecha_inicio->diffInDays($curso->fecha_fin);

        return $curso->fecha_inicio->copy()->addDays((int) floor(self::PROPORCION_CORTE * $duracion));
    }

    /**
     * Semanas transcurridas hasta el corte, incluida la semana en curso: ⌊días ÷ 7⌋ + 1
     */
    public function semanasHastaCorte(Curso $curso): int
    {
        $dias = (int) $curso->fecha_inicio->diffInDays($this->fechaCorte($curso));

        return intdiv($dias, 7) + 1;
    }

    /**
     * Motivos por los que no se puede calcular (criterios 1 y 3). Si está vacío, se puede calcular.
     *
     * @return string[]
     */
    public function verificar(Curso $curso): array
    {
        $corte = $this->fechaCorte($curso);
        $semanas = $this->semanasHastaCorte($curso);
        $problemas = [];

        if (today()->lt($corte)) {
            $problemas[] = "El curso todavía no alcanzó el 30 % de su duración: la fecha de corte es el {$corte->format('d/m/Y')}.";
        }

        $vigentes = $curso->cargas()->where('estado', 'vigente')->distinct()->pluck('tipo_archivo');

        foreach (self::ARCHIVOS_REQUERIDOS as $tipo => $nombre) {
            if (! $vigentes->contains($tipo)) {
                $problemas[] = "Falta el archivo de {$nombre}.";
            }
        }

        // La actividad y los foros tienen que cubrir todas las semanas hasta el corte
        foreach (['actividad' => ActividadSemanal::class, 'foros' => ParticipacionForo::class] as $tipo => $modelo) {
            if ($vigentes->contains($tipo)) {
                $ultima = (int) $modelo::vigentesDelCurso($curso)->max('semana');

                if ($ultima < $semanas) {
                    $problemas[] = 'El archivo de ' . self::ARCHIVOS_REQUERIDOS[$tipo]
                        . " llega hasta la semana {$ultima} y el corte requiere hasta la semana {$semanas}.";
                }
            }
        }

        if ($vigentes->contains('nomina') && $this->poblacion($curso, $corte)->isEmpty()) {
            $problemas[] = 'No hay estudiantes inscriptos al momento de corte.';
        }

        return $problemas;
    }

    /**
     * Indicadores de cada estudiante de la población, en escala de 0 a 1.
     */
    public function calcular(Curso $curso): array
    {
        $corte = $this->fechaCorte($curso);
        $semanas = $this->semanasHastaCorte($curso);
        $poblacion = $this->poblacion($curso, $corte);
        $ids = $poblacion->pluck('id');

        // Asistencia: semanas con actividad hasta el corte
        $semanasActivas = ActividadSemanal::vigentesDelCurso($curso)
            ->whereIn('inscripcion_id', $ids)
            ->where('con_actividad', true)
            ->where('semana', '<=', $semanas)
            ->selectRaw('inscripcion_id, COUNT(DISTINCT semana) AS total')
            ->groupBy('inscripcion_id')
            ->pluck('total', 'inscripcion_id');

        // Participación: total de intervenciones hasta el corte, como percentil dentro del curso
        $intervenciones = ParticipacionForo::vigentesDelCurso($curso)
            ->whereIn('inscripcion_id', $ids)
            ->where('semana', '<=', $semanas)
            ->selectRaw('inscripcion_id, SUM(intervenciones) AS total')
            ->groupBy('inscripcion_id')
            ->pluck('total', 'inscripcion_id');

        $totales = $ids->mapWithKeys(fn ($id) => [$id => (int) ($intervenciones[$id] ?? 0)]);
        $percentiles = $this->percentiles($totales);

        // Evaluaciones y entregas vigentes
        $evaluaciones = Evaluacion::vigentesDelCurso($curso)->orderBy('fecha_vencimiento')->get();
        $vencidas = $evaluaciones->filter(fn ($e) => $e->fecha_vencimiento->lte($corte))->keyBy('id');

        $entregas = Entrega::whereIn('evaluacion_id', $evaluaciones->pluck('id'))
            ->whereIn('inscripcion_id', $ids)
            ->get()
            ->groupBy('inscripcion_id');

        $estudiantes = $poblacion->map(function (Inscripcion $inscripcion) use (
            $semanas, $semanasActivas, $percentiles, $entregas, $vencidas, $corte
        ) {
            $propias = $entregas->get($inscripcion->id, collect());

            // Calificaciones: promedio de lo presentado y calificado hasta el corte, sobre 10
            $notas = $propias
                ->filter(fn ($e) => $e->fecha_entrega !== null && $e->fecha_entrega->lte($corte) && $e->calificacion !== null)
                ->map(fn ($e) => (float) $e->calificacion);

            // Entregas: vencidas hasta el corte presentadas dentro del plazo
            $aTiempo = $propias
                ->filter(fn ($e) => $vencidas->has($e->evaluacion_id)
                    && $e->fecha_entrega !== null
                    && $e->fecha_entrega->lte($vencidas[$e->evaluacion_id]->fecha_vencimiento))
                ->count();

            return [
                'inscripcion' => $inscripcion,
                'asistencia' => min(1, ($semanasActivas[$inscripcion->id] ?? 0) / $semanas),
                'calificaciones' => $notas->isEmpty() ? 0.0 : $notas->avg() / self::CALIFICACION_MAXIMA, // criterio 2
                'entregas' => $vencidas->isEmpty() ? 1.0 : min(1, $aTiempo / $vencidas->count()),
                'participacion' => $percentiles[$inscripcion->id],
                'sin_presentaciones' => $notas->isEmpty(),
            ];
        });

        return [
            'corte' => $corte,
            'semanas' => $semanas,
            'vencidas' => $vencidas->values(),
            'excluidos' => Inscripcion::where('curso_id', $curso->id)->count() - $poblacion->count(),
            'estudiantes' => $estudiantes,
        ];
    }

    /**
     * Inscriptos que no se dieron de baja hasta el corte, ordenados por apellido.
     */
    private function poblacion(Curso $curso, Carbon $corte): Collection
    {
        return Inscripcion::where('curso_id', $curso->id)
            ->where(fn ($q) => $q->whereNull('fecha_baja')->orWhere('fecha_baja', '>', $corte))
            ->with('estudiante')
            ->get()
            ->sortBy(fn ($i) => $i->estudiante->apellido . ' ' . $i->estudiante->nombre)
            ->values();
    }

    /**
     * Posición percentil de cada valor, con el promedio de posiciones en los empates.
     * Equivale a rank(pct=True, method="average") de pandas.
     *
     * @param  Collection<int, int>  $valores  id de inscripción => total
     * @return array<int, float>
     */
    private function percentiles(Collection $valores): array
    {
        $n = $valores->count();
        $posiciones = []; // valor => posiciones (desde 1) que ocupa en el orden ascendente

        foreach ($valores->sort()->values() as $indice => $valor) {
            $posiciones[$valor][] = $indice + 1;
        }

        return $valores
            ->map(fn ($valor) => (array_sum($posiciones[$valor]) / count($posiciones[$valor])) / $n)
            ->all();
    }
}