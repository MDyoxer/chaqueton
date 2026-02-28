<?php

require_once PROJECT_ROOT . '/vendor/autoload.php';
require_once PROJECT_ROOT . '/app/Services/CalendarService.php';

use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;

/**
 * Controlador del chatbot.
 * Maneja dos modos de operación:
 *   - Webhook: Dialogflow llama directamente a api.php con queryResult
 *   - Frontend: index.php envía el mensaje del usuario
 */
class ChatController
{
    /** Intents que deben consultar Google Calendar */
    private const INTENTS_CALENDARIO = [
        'eventos-escolares',
        'actividades-escolares',
        'examenes',
        'calendario-escolar',
        'horario-escolar',
        'dias-festivos',
    ];

    private CalendarService $calendarService;

    public function __construct()
    {
        $this->calendarService = new CalendarService();
    }

    /**
     * Punto de entrada principal. Detecta el modo y delega.
     *
     * @param array $input Cuerpo de la petición JSON decodificado
     */
    public function handle(array $input): void
    {
        if (isset($input['queryResult'])) {
            $this->handleWebhook($input);
        } else {
            $this->handleFrontend($input);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // MODO WEBHOOK: Dialogflow envía queryResult
    // ─────────────────────────────────────────────────────────────
    private function handleWebhook(array $input): void
    {
        $intentName = $input['queryResult']['intent']['displayName'] ?? '';
        $maxEventos = in_array($intentName, ['calendario-escolar', 'horario-escolar']) ? 10 : 5;

        if (in_array($intentName, self::INTENTS_CALENDARIO)) {
            $respuesta = $this->calendarService->obtenerProximosEventos($maxEventos);
            echo json_encode(['fulfillmentText' => $respuesta]);
        } else {
            // Intent no relacionado con calendario: Dialogflow responde por sí solo
            echo json_encode(['fulfillmentText' => '']);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // MODO FRONTEND: index.php envía { "mensaje": "..." }
    // ─────────────────────────────────────────────────────────────
    private function handleFrontend(array $input): void
    {
        $userInput = $input['mensaje'] ?? '';

        if (empty($userInput)) {
            echo json_encode(['error' => 'No se envió ningún mensaje.']);
            return;
        }

        // Cargar credenciales de Dialogflow desde variables de entorno
        $credencialesPath = getenv('DIALOGFLOW_CREDENTIALS')
            ? PROJECT_ROOT . '/' . getenv('DIALOGFLOW_CREDENTIALS')
            : PROJECT_ROOT . '/credenciales.json';

        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credencialesPath);

        $projectId = getenv('DIALOGFLOW_PROJECT_ID') ?: 'utconnect-lniw';

        try {
            session_start();
            $sessionId = session_id();

            $sessionsClient = new SessionsClient();
            $session        = $sessionsClient->sessionName($projectId, $sessionId);

            $textInput = new TextInput();
            $textInput->setText($userInput);
            $textInput->setLanguageCode('es');

            $queryInput = new QueryInput();
            $queryInput->setText($textInput);

            $response    = $sessionsClient->detectIntent($session, $queryInput);
            $queryResult = $response->getQueryResult();
            $intentName  = $queryResult->getIntent()
                ? $queryResult->getIntent()->getDisplayName()
                : '';
            $responseText = $queryResult->getFulfillmentText();

            // Si el intent es de calendario, consultar Google Calendar en lugar de usar
            // la respuesta de Dialogflow (el servidor PHP no puede atender el webhook
            // mientras está procesando esta petición)
            if (in_array(strtolower($intentName), self::INTENTS_CALENDARIO)) {
                $responseText = $this->calendarService->obtenerProximosEventos(5);
            }

            echo json_encode([
                'respuesta' => $responseText,
                'intent'    => $intentName,
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'error' => 'Error al consultar Dialogflow: ' . $e->getMessage(),
            ]);
        }
    }
}
