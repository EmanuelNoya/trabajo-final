# Sistema de Detección Temprana de Riesgo Académico

Prototipo del Trabajo Final de Grado de la Licenciatura en Informática (Universidad Siglo 21).
Autor: Emanuel Noya.

El sistema estima, al 30 % del cursado, la probabilidad de que cada estudiante de un curso virtual
no lo apruebe, a partir de cuatro indicadores calculados con los archivos que exporta el aula virtual:
asistencia, calificaciones, entregas en plazo y participación en foros. La estimación la realiza un
modelo de regresión logística entrenado con el conjunto de datos OULAD y se presenta al docente
como un listado priorizado por nivel de riesgo.

Live demo:
[https://tfg-noya.free.nf/](https://tfg-noya.free.nf/)

## Funcionalidades por perfil

| Perfil | Qué puede hacer | Historias de usuario |
|---|---|---|
| Todos | Iniciar sesión, cerrar sesión, recuperar la contraseña | HU-01 |
| Administrador | Crear, editar, activar y desactivar usuarios | HU-02 |
| Analista de datos | Registrar cursos, incorporar la nómina y los archivos de actividad, calificaciones y foros, revisar los indicadores y ejecutar la estimación | HU-03 a HU-07 |
| Docente | Ver el tablero de sus cursos, filtrar por nivel, exportar el listado y consultar la situación individual de cada estudiante | HU-08 a HU-10, HU-14 |
| Responsable académico | Configurar los umbrales de riesgo con vista previa, ver el estado de los cursos de la carrera y la importancia de los indicadores | HU-11 a HU-13 |

## Requisitos

- PHP 8.3 o superior, con las extensiones `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` y `tokenizer`.
- Composer 2.
- MariaDB 10.6 o superior (desarrollado con MariaDB 11.4).
- Conexión a internet para `composer install` y para el gráfico de la situación individual
  (Chart.js se carga desde un CDN). No hace falta Node.js: la interfaz no requiere compilación.

## Puesta en marcha

1. Descomprimir el código fuente y abrir una terminal en la carpeta del proyecto.

2. Instalar las dependencias:

   ```
   composer install
   ```

3. Crear la base de datos vacía en MariaDB:

   ```sql
   CREATE DATABASE tfgnoyadb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. Crear el archivo de configuración y generar la clave de la aplicación:

   ```
   copy .env.example .env        (en Linux o macOS: cp .env.example .env)
   php artisan key:generate
   ```

   Si el usuario de MariaDB no es `root` sin contraseña, completar `DB_USERNAME` y `DB_PASSWORD` en `.env`.

5. Crear las tablas y cargar los datos de la Demo:

   ```
   php artisan migrate:fresh --seeder=DemoSeeder
   ```

   El proceso tarda unos segundos: incorpora los archivos de ejemplo con los mismos servicios que usa
   la aplicación y ejecuta la estimación de tres cursos.

6. Iniciar el servidor:

   ```
   php artisan serve
   ```

   y abrir <http://127.0.0.1:8000> en el navegador.

## Usuarios de prueba

Todos los usuarios tienen la contraseña `Prueba.2026$`. Son cuentas ficticias, creadas solo para la demo.

| Correo | Perfil | Nombre | Curso a cargo |
|---|---|---|---|
| `docente@prueba.local` | Docente | Pérez, Marta Elena | Bases de Datos |
| `msuarez@prueba.local` | Docente | Suárez, Martín | Programación I |
| `arossi@prueba.local` | Docente | Rossi, Andrea | Matemática Discreta |
| `pdiaz@prueba.local` | Docente | Díaz, Pablo | Sistemas Operativos |
| `analista@prueba.local` | Analista de datos | Ríos, Daniel | — |
| `responsable@prueba.local` | Responsable académico | Gómez, Laura | Licenciatura en Informática |
| `administrador@prueba.local` | Administrador | Herrera, Silvia | — |

Después de cinco intentos fallidos, el inicio de sesión se bloquea durante 15 minutos.

## Datos de la Demo

Los datos son ilustrativos: los estudiantes, los legajos y las calificaciones fueron generados para la
demo y no corresponden a personas reales. Están en `database/datos-demo/`, en el mismo formato de
archivo que acepta el sistema (CSV separado por punto y coma, con encabezados).

- Cuatro cursos de la Licenciatura en Informática, período 2026-2C, del 03/08/2026 al 27/11/2026.
  El momento de corte (30 % del cursado) es el 06/09/2026, en la semana 5.
- **Bases de Datos, Programación I y Matemática Discreta** tienen los cuatro archivos incorporados y la
  estimación ejecutada el 08/09/2026.
- **Sistemas Operativos** solo tiene la nómina y la actividad. Sus calificaciones y foros están en
  `database/datos-demo/para-incorporar/`, para incorporarlos desde la pantalla durante la Demo.
- Los archivos de calificaciones de Bases de Datos y Sistemas Operativos incluyen dos filas con errores
  a propósito (un legajo que no está en la nómina y una calificación fuera de escala), para mostrar el
  rechazo de filas.
- En cada curso hay tres bajas: dos antes del corte, que quedan fuera de la estimación, y una después.

## Recorrido sugerido

1. **Analista** (`analista@prueba.local`): entrar a *Cursos* → *Sistemas Operativos*, incorporar
   `sistemas-operativos_calificaciones.csv` y `sistemas-operativos_foros.csv` desde
   `database/datos-demo/para-incorporar/`, revisar el resultado (filas válidas, rechazadas y entregas
   fuera de plazo), consultar los indicadores y ejecutar la estimación.
2. **Docente** (`pdiaz@prueba.local`): abrir el tablero de Sistemas Operativos, filtrar por nivel,
   exportar el listado y ver la situación individual de un estudiante en riesgo alto.
3. **Responsable académico** (`responsable@prueba.local`): ver los cursos de la carrera ordenados por
   proporción de riesgo alto, probar otros umbrales en la vista previa (por ejemplo 0,35 y 0,65) y
   consultar la importancia de los indicadores.
4. **Administrador** (`administrador@prueba.local`): crear un usuario y desactivar otro. Si el usuario
   desactivado tiene una sesión abierta en otro navegador, se cierra en su siguiente acción.

## Recuperación de contraseña en un entorno local

Con la configuración de `.env.example` (`MAIL_MAILER=log`), el correo de restablecimiento no se envía:
se escribe en `storage/logs/laravel.log`. Para probarlo, pedir el restablecimiento desde
*¿Olvidaste tu contraseña?*, abrir ese archivo y copiar el enlace que aparece al final. El enlace es
de un solo uso y vence a los 60 minutos. En producción se configura el servidor de correo del
alojamiento (`MAIL_MAILER=smtp` y sus datos).

## Otros datos de ejemplo

`php artisan migrate:fresh --seed` carga una base mínima (un curso y un estudiante) en lugar de la
Demo. En `database/datos-ejemplo/` hay archivos para probar las validaciones de la incorporación:
nóminas y calificaciones con errores, un archivo sin una columna obligatoria y archivos de actividad y
foros de distintas semanas.

## Modelo de riesgo

- `resources/modelos/modelo_rl_v1.json`: coeficientes, intercepto y parámetros de estandarización del
  modelo `rl-oulad-30-v1`. El sistema verifica su resumen SHA-256 antes de usarlo.
- `resources/modelos/resultados.json`: importancia de los indicadores y métricas de evaluación.
- Una versión nueva del modelo se registra con `php artisan modelo:registrar {ruta} --fecha=AAAA-MM-DD`
  y su importancia con `php artisan modelo:importancia {version} {ruta}`. El `DemoSeeder` ejecuta ambos
  comandos.

## Organización del código

| Carpeta | Contenido |
|---|---|
| `app/Servicios` | Lectura e importación de archivos, cálculo de indicadores, evaluación del modelo y estimación |
| `app/Http/Controllers` | Controladores agrupados por perfil (`Administrador`, `Analista`, `Responsable`) |
| `app/Models` | Modelos Eloquent; las tablas y columnas están en español |
| `app/Policies` | Acceso del docente solo a sus cursos y del responsable solo a su carrera |
| `app/Console/Commands` | Registro del modelo y de la importancia de los indicadores |
| `database/migrations` | Estructura de la base de datos |
| `resources/views` | Vistas Blade |

## Tecnologías

Laravel 13 · PHP 8.3 · MariaDB · Blade · Chart.js 4.4.1. El modelo se entrenó en Python con pandas y
scikit-learn; la aplicación lo evalúa en PHP a partir del archivo JSON, sin depender de Python.