<?php

namespace App\Console\Commands;

use App\Models\ImportanciaIndicador;
use App\Models\Modelo;
use App\Servicios\ModeloRiesgo;
use Illuminate\Console\Command;

class RegistrarImportancia extends Command
{
    protected $signature = 'modelo:importancia
        {version : Versión del modelo ya registrada}
        {ruta : Archivo resultados.json del entrenamiento, relativo a la raíz del proyecto}
        {--clave= : Algoritmo dentro de resultados.json (por defecto, Regresión logística)}';

    protected $description = 'Registra la importancia por permutación de los indicadores de una versión del modelo';

    public function handle(): int
    {
        $modelo = Modelo::where('version', $this->argument('version'))->first();

        if ($modelo === null) {
            $this->error("No existe la versión {$this->argument('version')}.");

            return self::FAILURE;
        }

        if ($modelo->importancias()->exists()) {
            $this->error("La versión {$modelo->version} ya tiene registrada la importancia de los indicadores.");

            return self::FAILURE;
        }

        $ruta = base_path(str_replace('\\', '/', $this->argument('ruta')));
        $datos = is_file($ruta) ? json_decode(file_get_contents($ruta), true) : null;
        $clave = $this->option('clave') ?: 'Regresión logística';
        $importancia = $datos['importancia'][$clave] ?? null;

        if (! is_array($importancia)) {
            $this->error("El archivo no tiene la importancia de \"{$clave}\".");

            return self::FAILURE;
        }

        $filas = [];
        foreach (ModeloRiesgo::INDICADORES as $indicador) {
            $valor = $importancia[$indicador]['media'] ?? null;

            if (! is_numeric($valor)) {
                $this->error("Falta la importancia de {$indicador}.");

                return self::FAILURE;
            }

            $filas[] = [
                'modelo_id' => $modelo->id,
                'indicador' => $indicador,
                'importancia' => round((float) $valor, 4),
            ];
        }

        ImportanciaIndicador::insert($filas);

        $this->info("Importancia registrada para el modelo {$modelo->version}:");
        foreach ($filas as $fila) {
            $this->line("  {$fila['indicador']}: {$fila['importancia']}");
        }

        return self::SUCCESS;
    }
}