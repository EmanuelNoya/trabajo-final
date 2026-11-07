<?php

namespace Database\Seeders;

use App\Models\CargaArchivo;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\Usuario;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Contraseña de prueba
        $clave = 'Prueba.2026$';

        $usuarios = [];
        foreach (['docente', 'responsable', 'analista', 'administrador'] as $rol) {
            $usuarios[$rol] = Usuario::create([
                'nombre' => ucfirst($rol),
                'apellido' => 'Prueba',
                'correo' => "{$rol}@prueba.local",
                'hash_contrasena' => $clave,
                'rol' => $rol,
            ]);
        }

        $carrera = Carrera::create([
            'nombre' => 'Licenciatura en Informática',
            'responsable_id' => $usuarios['responsable']->id,
        ]);

        $curso = Curso::create([
            'carrera_id' => $carrera->id,
            'docente_id' => $usuarios['docente']->id,
            'asignatura' => 'Programación I',
            'comision' => 'A',
            'periodo_lectivo' => '2026-1C',
            'modalidad' => 'A distancia',
            'fecha_inicio' => '2026-03-16',
            'fecha_fin' => '2026-07-10',
        ]);

        // Nómina mínima, para probar la trazabilidad y la restricción curso + estudiante
        $carga = CargaArchivo::create([
            'curso_id' => $curso->id,
            'usuario_id' => $usuarios['analista']->id,
            'tipo_archivo' => 'nomina',
            'nombre_archivo' => 'nomina_prueba.csv',
            'hash_archivo' => hash('sha256', 'nomina_prueba'),
            'filas_validas' => 1,
            'filas_rechazadas' => 0,
        ]);

        $estudiante = Estudiante::create([
            'legajo' => 'VINF00001',
            'apellido' => 'Estudiante',
            'nombre' => 'Prueba',
        ]);

        Inscripcion::create([
            'curso_id' => $curso->id,
            'estudiante_id' => $estudiante->id,
            'carga_id' => $carga->id,
        ]);

        // Modelo de riesgo vigente (el JSON no trae la fecha de entrenamiento)
        Artisan::call('modelo:registrar', [
            'ruta' => 'resources/modelos/modelo_rl_v1.json',
            '--fecha' => '2026-10-19',
        ]);
    }
}