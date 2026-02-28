<?php

/**
 * Cargador de variables de entorno desde .env
 * Define PROJECT_ROOT como constante global apuntando a la raíz del proyecto.
 * Las variables del sistema operativo tienen prioridad sobre el archivo .env
 */

define('PROJECT_ROOT', dirname(__DIR__));

function cargarEnv(string $archivo = ''): void
{
    if (empty($archivo)) {
        $archivo = PROJECT_ROOT . '/.env';
    }

    if (!file_exists($archivo)) {
        return;
    }

    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineas as $linea) {
        // Ignorar comentarios
        if (str_starts_with(trim($linea), '#')) {
            continue;
        }

        // Ignorar líneas sin '='
        if (!str_contains($linea, '=')) {
            continue;
        }

        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor);

        // El SO tiene prioridad: solo definir si no existe
        if (getenv($clave) === false) {
            putenv("{$clave}={$valor}");
            $_ENV[$clave] = $valor;
        }
    }
}

cargarEnv();
