<?php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Google Calendar API - Integración para Chatbot UTC
 * Credenciales: calendar-credentials.json (Service Account de Google Calendar)
 */

/**
 * Obtiene los próximos eventos del calendario escolar.
 *
 * @param int $maxEventos Número máximo de eventos a retornar
 * @return string Texto formateado con los eventos
 */
function obtenerEventosEscolares(int $maxEventos = 5): string
{
    try {
        $client = new Google\Client();
        $client->setAuthConfig(__DIR__ . '/calendar-credentials.json');
        $client->addScope(Google\Service\Calendar::CALENDAR_READONLY);

        $service = new Google\Service\Calendar($client);

        // ID del calendario escolar — definir la variable de entorno CALENDAR_ID
        // Se obtiene en: Google Calendar > Configuración del calendario > ID del calendario
        $calendarId = getenv('CALENDAR_ID');
        if (empty($calendarId)) {
            return 'El calendario escolar no está configurado. Contacta al administrador.';
        }

        $ahora   = date('c'); // Fecha/hora actual en formato ISO 8601
        $params  = [
            'maxResults'   => $maxEventos,
            'orderBy'      => 'startTime',
            'singleEvents' => true,
            'timeMin'      => $ahora,
        ];

        $resultado = $service->events->listEvents($calendarId, $params);
        $eventos   = $resultado->getItems();

        if (empty($eventos)) {
            return 'No hay eventos escolares próximos registrados en el calendario.';
        }

        $texto = "📅 *Próximos eventos escolares:*\n\n";
        foreach ($eventos as $evento) {
            $inicio = $evento->start->dateTime ?? $evento->start->date;
            $fecha  = new DateTime($inicio);
            $fechaFormateada = $fecha->format('d/m/Y');

            // Si tiene hora (evento con hora exacta)
            if ($evento->start->dateTime) {
                $horaFormateada  = $fecha->format('H:i');
                $texto .= "• *{$evento->getSummary()}*\n";
                $texto .= "  📆 {$fechaFormateada} a las {$horaFormateada}\n";
            } else {
                // Evento de día completo (examen, día festivo, etc.)
                $texto .= "• *{$evento->getSummary()}*\n";
                $texto .= "  📆 {$fechaFormateada}\n";
            }

            // Descripción opcional del evento
            if ($evento->getDescription()) {
                $texto .= "  📝 " . $evento->getDescription() . "\n";
            }

            $texto .= "\n";
        }

        return trim($texto);

    } catch (Exception $e) {
        error_log('Error Google Calendar: ' . $e->getMessage());
        return 'No pude consultar el calendario en este momento. Intenta más tarde.';
    }
}

/**
 * Obtiene eventos de una fecha específica.
 *
 * @param string $fecha Fecha en formato 'Y-m-d' (ej: '2026-03-15')
 * @return string Texto formateado con los eventos del día
 */
function obtenerEventosPorFecha(string $fecha): string
{
    try {
        $client = new Google\Client();
        $client->setAuthConfig(__DIR__ . '/calendar-credentials.json');
        $client->addScope(Google\Service\Calendar::CALENDAR_READONLY);

        $service   = new Google\Service\Calendar($client);
        $calendarId = getenv('CALENDAR_ID');
        if (empty($calendarId)) {
            return 'El calendario escolar no está configurado. Contacta al administrador.';
        }

        $inicio = $fecha . 'T00:00:00Z';
        $fin    = $fecha . 'T23:59:59Z';

        $params = [
            'maxResults'   => 10,
            'orderBy'      => 'startTime',
            'singleEvents' => true,
            'timeMin'      => $inicio,
            'timeMax'      => $fin,
        ];

        $resultado = $service->events->listEvents($calendarId, $params);
        $eventos   = $resultado->getItems();

        $fechaDt = new DateTime($fecha);
        $fechaFormateada = $fechaDt->format('d/m/Y');

        if (empty($eventos)) {
            return "No hay eventos escolares registrados para el {$fechaFormateada}.";
        }

        $texto = "📅 *Eventos del {$fechaFormateada}:*\n\n";
        foreach ($eventos as $evento) {
            $texto .= "• *{$evento->getSummary()}*\n";
            if ($evento->start->dateTime) {
                $hora = (new DateTime($evento->start->dateTime))->format('H:i');
                $texto .= "  🕐 {$hora}\n";
            }
            if ($evento->getDescription()) {
                $texto .= "  📝 " . $evento->getDescription() . "\n";
            }
            $texto .= "\n";
        }

        return trim($texto);

    } catch (Exception $e) {
        error_log('Error Google Calendar (fecha): ' . $e->getMessage());
        return 'No pude consultar el calendario en este momento. Intenta más tarde.';
    }
}
