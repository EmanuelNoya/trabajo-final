<?php

namespace App\Servicios;

use App\Models\Modelo;

/**
 * Evalúa en PHP el modelo de regresión logística exportado desde Python:
 * p = 1 / (1 + e^-(intercepto + Σ coef_i · (x_i − media_i) / desvío_i))
 */
class ModeloRiesgo
{
    public const INDICADORES = ['asistencia', 'calificaciones', 'entregas', 'participacion'];

    private function __construct(public readonly array $datos)
    {
    }

    /**
     * Lee y valida la estructura de un archivo de modelo.
     */
    public static function desdeArchivo(string $ruta): self
    {
        $contenido = is_file($ruta) ? file_get_contents($ruta) : false;
        $datos = $contenido === false ? null : json_decode($contenido, true);

        if (! is_array($datos)) {
            throw new ErrorEstimacion('El archivo del modelo no existe o no es un JSON válido.');
        }

        foreach (['version', 'algoritmo', 'corte', 'intercepto'] as $clave) {
            if (! isset($datos[$clave])) {
                throw new ErrorEstimacion("Al archivo del modelo le falta \"{$clave}\".");
            }
        }

        foreach (self::INDICADORES as $indicador) {
            foreach (['media', 'desvio', 'coeficientes'] as $grupo) {
                if (! is_numeric($datos[$grupo][$indicador] ?? null)) {
                    throw new ErrorEstimacion("Al archivo del modelo le falta \"{$grupo}.{$indicador}\".");
                }
            }

            if ((float) $datos['desvio'][$indicador] === 0.0) {
                throw new ErrorEstimacion("El desvío de \"{$indicador}\" no puede ser cero.");
            }
        }

        return new self($datos);
    }

    /**
     * Carga un modelo registrado y verifica que su archivo no haya cambiado desde el registro.
     */
    public static function cargar(Modelo $modelo): self
    {
        $ruta = base_path($modelo->ruta_archivo);

        if (! is_file($ruta) || hash_file('sha256', $ruta) !== $modelo->hash_archivo) {
            throw new ErrorEstimacion(
                "El archivo del modelo {$modelo->version} no coincide con el registrado: no se ejecuta la estimación."
            );
        }

        return self::desdeArchivo($ruta);
    }

    /**
     * Probabilidad de desaprobar o abandonar, entre 0 y 1.
     *
     * @param  array<string, float>  $indicadores
     */
    public function probabilidad(array $indicadores): float
    {
        $z = (float) $this->datos['intercepto'];

        foreach (self::INDICADORES as $i) {
            $z += $this->datos['coeficientes'][$i]
                * ($indicadores[$i] - $this->datos['media'][$i]) / $this->datos['desvio'][$i];
        }

        return 1 / (1 + exp(-$z));
    }
}