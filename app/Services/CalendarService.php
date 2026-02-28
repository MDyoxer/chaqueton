<?php

require_once PROJECT_ROOT . '/vendor/autoload.php';

/**
 * Servicio de integración con Google Calendar API.
 * Encapsula toda la lógica de consulta al calendario escolar de la UTC.
 */
class CalendarService
{
    private Google\Service\Calendar $service;
    private string $calendarId;

    public function __construct()
    {
        $credencialesPath = PROJECT_ROOT . '/calendar-credentials.json';

        $client = new Google\Client();
        $client->setAuthConfig($credencialesPath);
        $client->addScope(Google\Service\Calendar::CALENDAR_READONLY);

        $this->service    = new Google\Service\Calendar($client);
        $this->calendarId = getenv('CALENDAR_ID') ?: '';
    }

    /**
     * Obtiene los próximos eventos del calendario escolar.
     *
     * @param int $max Número máximo de eventos a retornar
     * @return string Texto formateado con los eventos
     */
    public function obtenerProximosEventos(int $max = 5): string
    {
        if (empty($this->calendarId)) {
            return 'El calendario escolar no está configurado. Contacta al administrador.';
        }

        try {
            $params = [
                'maxResults'   => $max,
                'orderBy'      => 'startTime',
                'singleEvents' => true,
                'timeMin'      => date('c'),
            ];

            $resultado = $this->service->events->listEvents($this->calendarId, $params);
            $eventos   = $resultado->getItems();

            if (empty($eventos)) {
                return 'No hay eventos escolares próximos registrados en el calendario.';
            }

            $texto = "📅 *Próximos eventos escolares:*\n\n";
            foreach ($eventos as $evento) {
                $texto .= $this->formatearEvento($evento);
            }

            return trim($texto);

        } catch (Exception $e) {
            error_log('CalendarService::obtenerProximosEventos — ' . $e->getMessage());
            return 'No pude consultar el calendario en este momento. Intenta más tarde.';
        }
    }

    /**
     * Obtiene los eventos de un día específico.
     *
     * @param string $fecha Fecha en formato 'Y-m-d' (ej: '2026-03-15')
     * @return string Texto formateado con los eventos del día
     */
    public function obtenerEventosPorFecha(string $fecha): string
    {
        if (empty($this->calendarId)) {
            return 'El calendario escolar no está configurado. Contacta al administrador.';
        }

        try {
            $params = [
                'maxResults'   => 10,
                'orderBy'      => 'startTime',
                'singleEvents' => true,
                'timeMin'      => $fecha . 'T00:00:00Z',
                'timeMax'      => $fecha . 'T23:59:59Z',
            ];

            $resultado = $this->service->events->listEvents($this->calendarId, $params);
            $eventos   = $resultado->getItems();

            $fechaFormateada = (new DateTime($fecha))->format('d/m/Y');

            if (empty($eventos)) {
                return "No hay eventos escolares registrados para el {$fechaFormateada}.";
            }

            $texto = "📅 *Eventos del {$fechaFormateada}:*\n\n";
            foreach ($eventos as $evento) {
                $texto .= $this->formatearEvento($evento);
            }

            return trim($texto);

        } catch (Exception $e) {
            error_log('CalendarService::obtenerEventosPorFecha — ' . $e->getMessage());
            return 'No pude consultar el calendario en este momento. Intenta más tarde.';
        }
    }

    /**
     * Formatea un evento de Google Calendar a texto legible.
     */
    private function formatearEvento(Google\Service\Calendar\Event $evento): string
    {
        $inicio = $evento->start->dateTime ?? $evento->start->date;
        $fecha  = new DateTime($inicio);
        $fechaFormateada = $fecha->format('d/m/Y');
        $texto  = '';

        if ($evento->start->dateTime) {
            $texto .= "• *{$evento->getSummary()}*\n";
            $texto .= "  📆 {$fechaFormateada} a las {$fecha->format('H:i')}\n";
        } else {
            $texto .= "• *{$evento->getSummary()}*\n";
            $texto .= "  📆 {$fechaFormateada}\n";
        }

        if ($evento->getDescription()) {
            $texto .= "  📝 " . $evento->getDescription() . "\n";
        }

        return $texto . "\n";
    }
}
