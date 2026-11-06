<?php

namespace App\Servicios;
use DateTimeImmutable;
use Illuminate\Support\Carbon;

/**
 * Lectura de los archivos CSV del sistema. Se reutiliza en todas las incorporaciones.
 * Acepta separador ";" o "," y archivos en UTF-8 (con o sin BOM) o Windows-1252 (Excel).
 */
class LectorCsv
{
    /**
     * @return array{encabezados: string[], filas: array<int, array<string, string>>}
     *         Las filas se indexan por su número de línea en el archivo (la 1 es el encabezado).
     */
    public function leer(string $ruta): array
    {
        $contenido = file_get_contents($ruta);

        if ($contenido === false || trim($contenido) === '') {
            throw new ArchivoInvalido('El archivo está vacío.');
        }

        // Quita el BOM de UTF-8 y convierte desde Windows-1252 si hace falta
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        if (! mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        }

        // Separador: el que más aparece en la línea de encabezados
        $primeraLinea = strtok($contenido, "\r\n");
        $separador = substr_count($primeraLinea, ';') >= substr_count($primeraLinea, ',') ? ';' : ',';

        $flujo = fopen('php://temp', 'r+');
        fwrite($flujo, $contenido);
        rewind($flujo);

        $encabezados = array_map(
            fn ($e) => mb_strtolower(trim((string) $e)),
            fgetcsv($flujo, null, $separador, '"', '') ?: []
        );

        $filas = [];
        $numero = 1;

        while (($valores = fgetcsv($flujo, null, $separador, '"', '')) !== false) {
            $numero++;

            if ($valores === [null]) {
                continue; // línea vacía
            }

            $valores = array_map(fn ($v) => trim((string) $v), $valores);
            $valores = array_pad(array_slice($valores, 0, count($encabezados)), count($encabezados), '');

            $filas[$numero] = array_combine($encabezados, $valores);
        }

        fclose($flujo);

        return ['encabezados' => $encabezados, 'filas' => $filas];
    }

    /**
     * Rechaza el archivo completo si falta alguna columna obligatoria e indica cuál.
     */
    public function exigirColumnas(array $encabezados, array $requeridas): void
    {
        $faltantes = array_values(array_diff($requeridas, $encabezados));

        if (count($faltantes) === 1) {
            throw new ArchivoInvalido("Falta la columna obligatoria: {$faltantes[0]}.");
        }

        if (count($faltantes) > 1) {
            throw new ArchivoInvalido('Faltan las columnas obligatorias: ' . implode(', ', $faltantes) . '.');
        }
    }

    /**
     * Lee una fecha en formato AAAA-MM-DD o DD/MM/AAAA. Devuelve null si no es válida.
     */
    public function leerFecha(string $texto): ?Carbon
    {
        foreach (['!Y-m-d', '!d/m/Y'] as $formato) {
            $fecha = DateTimeImmutable::createFromFormat($formato, $texto);
            $errores = DateTimeImmutable::getLastErrors();

            // getLastErrors() detecta fechas imposibles como 31/02, que PHP "corregiría" sin avisar
            if ($fecha !== false && ($errores === false || $errores['warning_count'] + $errores['error_count'] === 0)) {
                return Carbon::instance($fecha);
            }
        }

        return null;
    }
}