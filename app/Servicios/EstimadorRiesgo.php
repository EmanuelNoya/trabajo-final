<?php

namespace App\Servicios;

use App\Models\Curso;
use App\Models\EjecucionEstimacion;
use App\Models\Estimacion;
use App\Models\Modelo;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Ejecución de la estimación del riesgo de un curso (HU-08).
 */
class EstimadorRiesgo
{
    public function __construct(private CalculadoraIndicadores $calculadora)
    {
    }

    public function ejecutar(Curso $curso, Usuario $analista): EjecucionEstimacion
    {
        $problemas = $this->calculadora->verificar($curso);
        if ($problemas !== []) {
            throw new ErrorEstimacion(implode(' ', $problemas));
        }

        $modelo = Modelo::where('vigente', true)->first();
        if ($modelo === null) {
            throw new ErrorEstimacion('No hay ningún modelo vigente registrado.');
        }

        $evaluador = ModeloRiesgo::cargar($modelo); // verifica el hash del archivo
        $resultado = $this->calculadora->calcular($curso); // excluye a los dados de baja antes del corte (criterio 3)

        return DB::transaction(function () use ($curso, $analista, $modelo, $evaluador, $resultado) {
            // Criterio 2: cada ejecución es un registro nuevo; las anteriores no se tocan
            $ejecucion = EjecucionEstimacion::create([
                'curso_id' => $curso->id,
                'modelo_id' => $modelo->id,
                'usuario_id' => $analista->id,
                'fecha_corte' => $resultado['corte']->toDateString(),
            ]);

            // Trazabilidad: qué cargas estaban vigentes en esta ejecución
            $ejecucion->cargas()->attach(
                $curso->cargas()->where('estado', 'vigente')->pluck('id')
            );

            $filas = $resultado['estudiantes']->map(function (array $estudiante) use ($evaluador, $ejecucion) {
                // Se redondea ANTES de estimar: lo que se guarda es exactamente lo que recibió el modelo
                $indicadores = [];
                foreach (ModeloRiesgo::INDICADORES as $i) {
                    $indicadores[$i] = round($estudiante[$i], 4);
                }

                return [
                    'ejecucion_id' => $ejecucion->id,
                    'inscripcion_id' => $estudiante['inscripcion']->id,
                    ...$indicadores,
                    'probabilidad' => round($evaluador->probabilidad($indicadores), 4), // criterio 1
                ];
            })->all();

            Estimacion::insert($filas);

            return $ejecucion;
        });
    }
}