<?php

namespace App\Servicios;

use RuntimeException;

/**
 * Error que invalida el archivo completo (vacío, columnas faltantes, etc.).
 */
class ArchivoInvalido extends RuntimeException
{
}