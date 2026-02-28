<?php
/**
 * Cargador de variables de entorno desde el archivo .env
 * Lee cada línea del .env y las registra con putenv() / $_ENV
 * Solo procesa líneas que no sean comentarios ni estén vacías.
 */
function cargarEnv(string $archivo = __DIR__ . '/.env'): void
{
    if (!file_exists($archivo)) {
        return;
    }

    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineas as $linea) {
        // Ignorar comentarios
        if (str_starts_with(trim($linea), '#')) {
            continue;
        }

        // Separar clave=valor
        if (!str_contains($linea, '=')) {
            continue;
        }

        [$clave, $valor] = explode('=', $linea, 2);
        $clave  = trim($clave);
        $valor  = trim($valor);

        // Solo definir si la variable de entorno no está ya seteada (el SO tiene prioridad)
        if (getenv($clave) === false) {
            putenv("{$clave}={$valor}");
            $_ENV[$clave] = $valor;
        }
    }
}

cargarEnv();
