<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Curso;
use App\Models\UmbralRiesgo;
use App\Models\Usuario;
use App\Servicios\EstimadorRiesgo;
use App\Servicios\ImportadorActividad;
use App\Servicios\ImportadorBase;
use App\Servicios\ImportadorCalificaciones;
use App\Servicios\ImportadorForos;
use App\Servicios\ImportadorNomina;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * Datos ilustrativos de la Demo: cuatro cursos de 2026-2C de la Licenciatura en Informática.
 * Los archivos de database/datos-demo se incorporan con los mismos servicios que usa la aplicación,
 * así que pasan por las mismas validaciones (incluidas dos filas rechazadas a propósito).
 *
 * Uso: php artisan migrate:fresh --seeder=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    private const CLAVE = 'Prueba.2026$'; // contraseña de prueba de todos los usuarios
    private const DATOS = 'database/datos-demo';

    private const CURSOS = [
        'bases-de-datos' => ['Bases de Datos', '02'],
        'programacion-i' => ['Programación I', '01'],
        'matematica-discreta' => ['Matemática Discreta', '03'],
        'sistemas-operativos' => ['Sistemas Operativos', '01'], // sin calificaciones ni foros: queda sin estimar
    ];

    private const IMPORTADORES = [
        'actividad' => ImportadorActividad::class,
        'calificaciones' => ImportadorCalificaciones::class,
        'foros' => ImportadorForos::class,
    ];

    public function run(): void
    {
        // Las cargas y las estimaciones quedan con fechas del cuatrimestre, no con la fecha de hoy
        Carbon::setTestNow('2026-08-03 09:00');

        $usuario = fn (string $rol, string $correo, string $apellido, string $nombre) => Usuario::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'correo' => $correo,
            'hash_contrasena' => self::CLAVE,
            'rol' => $rol,
        ]);

        $responsable = $usuario('responsable', 'responsable@prueba.local', 'Gómez', 'Laura');
        $analista = $usuario('analista', 'analista@prueba.local', 'Ríos', 'Daniel');
        $usuario('administrador', 'administrador@prueba.local', 'Herrera', 'Silvia');

        $docentes = [
            'bases-de-datos' => $usuario('docente', 'docente@prueba.local', 'Pérez', 'Marta Elena'),
            'programacion-i' => $usuario('docente', 'msuarez@prueba.local', 'Suárez', 'Martín'),
            'matematica-discreta' => $usuario('docente', 'arossi@prueba.local', 'Rossi', 'Andrea'),
            'sistemas-operativos' => $usuario('docente', 'pdiaz@prueba.local', 'Díaz', 'Pablo'),
        ];

        $carrera = Carrera::create([
            'nombre' => 'Licenciatura en Informática',
            'responsable_id' => $responsable->id,
        ]);

        UmbralRiesgo::create([
            'carrera_id' => $carrera->id,
            'usuario_id' => $responsable->id,
            'umbral_medio' => UmbralRiesgo::MEDIO_POR_DEFECTO,
            'umbral_alto' => UmbralRiesgo::ALTO_POR_DEFECTO,
            'fecha_desde' => '2026-03-01',
        ]);

        Artisan::call('modelo:registrar', [
            'ruta' => 'resources/modelos/modelo_rl_v1.json',
            '--fecha' => '2026-10-19',
        ]);
        Artisan::call('modelo:importancia', [
            'version' => 'rl-oulad-30-v1',
            'ruta' => 'resources/modelos/resultados.json',
        ]);

        foreach (self::CURSOS as $clave => [$asignatura, $comision]) {
            $curso = Curso::create([
                'carrera_id' => $carrera->id,
                'docente_id' => $docentes[$clave]->id,
                'asignatura' => $asignatura,
                'comision' => $comision,
                'periodo_lectivo' => '2026-2C',
                'modalidad' => 'A distancia',
                'fecha_inicio' => '2026-08-03',
                'fecha_fin' => '2026-11-27',
            ]);

            // La nómina, al comenzar el cursado
            Carbon::setTestNow('2026-08-05 10:00');
            $origen = base_path(self::DATOS . "/{$clave}_nomina.csv");
            app(ImportadorNomina::class)->importar(
                new UploadedFile($origen, basename($origen), 'text/csv', null, true),
                $curso,
                $analista
            );

            // Los registros de las semanas 1 a 6, después del corte (06/09/2026)
            Carbon::setTestNow('2026-09-08 09:30');
            foreach (self::IMPORTADORES as $tipo => $clase) {
                $origen = base_path(self::DATOS . "/{$clave}_{$tipo}.csv");

                if (is_file($origen)) {
                    $this->incorporar(app($clase), $origen, $curso, $analista);
                }
            }

            // La estimación, solo en los cursos con los cuatro archivos
            if ($clave !== 'sistemas-operativos') {
                Carbon::setTestNow('2026-09-08 11:00');
                app(EstimadorRiesgo::class)->ejecutar($curso, $analista);
            }
        }

        Carbon::setTestNow();
    }

    /**
     * Mismo recorrido que la pantalla: el archivo pasa por "pendientes", se analiza y se incorpora.
     */
    private function incorporar(ImportadorBase $importador, string $origen, Curso $curso, Usuario $analista): void
    {
        $ruta = 'pendientes/' . basename($origen);
        Storage::put($ruta, file_get_contents($origen));

        $analisis = $importador->analizar($ruta, $curso);
        $importador->incorporar($analisis, $ruta, basename($origen), $curso, $analista);
    }
}
