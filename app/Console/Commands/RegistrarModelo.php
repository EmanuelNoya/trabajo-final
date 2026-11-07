<?php

namespace App\Console\Commands;

use App\Models\Modelo;
use App\Servicios\ErrorEstimacion;
use App\Servicios\ModeloRiesgo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RegistrarModelo extends Command
{
    protected $signature = 'modelo:registrar
        {ruta : Ruta del archivo JSON, relativa a la raíz del proyecto}
        {--fecha= : Fecha de entrenamiento (AAAA-MM-DD), si el JSON no la trae}';

    protected $description = 'Registra una versión del modelo de riesgo y la marca como vigente';

    public function handle(): int
    {
        $ruta = str_replace('\\', '/', $this->argument('ruta'));

        try {
            $datos = ModeloRiesgo::desdeArchivo(base_path($ruta))->datos;
        } catch (ErrorEstimacion $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $fecha = $datos['fecha_entrenamiento'] ?? $this->option('fecha');

        if (! $fecha || ! strtotime($fecha)) {
            $this->error('Indicá la fecha de entrenamiento con --fecha=AAAA-MM-DD.');

            return self::FAILURE;
        }

        if (Modelo::where('version', $datos['version'])->exists()) {
            $this->error("La versión {$datos['version']} ya está registrada.");

            return self::FAILURE;
        }

        $metricas = $datos['metricas_validacion_temporal'] ?? [];

        $modelo = DB::transaction(function () use ($datos, $metricas, $fecha, $ruta) {
            Modelo::where('vigente', true)->update(['vigente' => false]); // solo uno vigente

            return Modelo::create([
                'version' => $datos['version'],
                'algoritmo' => mb_substr($datos['algoritmo'], 0, 60),
                'corte' => round((float) $datos['corte'], 4),
                'sensibilidad' => round((float) ($metricas['sensibilidad'] ?? 0), 4),
                'medida_f1' => round((float) ($metricas['f1'] ?? 0), 4),
                'fecha_entrenamiento' => $fecha,
                'ruta_archivo' => $ruta,
                'hash_archivo' => hash_file('sha256', base_path($ruta)),
                'vigente' => true,
            ]);
        });

        $this->info("Modelo {$modelo->version} registrado como vigente.");
        $this->line("SHA-256: {$modelo->hash_archivo}");

        return self::SUCCESS;
    }
}