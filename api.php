<?php
require __DIR__ . '/load_env.php';
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/calendar.php';

use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

// -----------------------------------------------------------------------
// MODO WEBHOOK: DialogFlow llama a este archivo directamente
// El JSON de DialogFlow contiene 'queryResult'
// -----------------------------------------------------------------------
if (isset($input['queryResult'])) {

    $intentName = $input['queryResult']['intent']['displayName'] ?? '';

    switch ($intentName) {
        case 'eventos-escolares':
        case 'actividades-escolares':
            $respuesta = obtenerEventosEscolares(5);
            break;

        case 'examenes':
            $respuesta = obtenerEventosEscolares(5);
            break;

        case 'calendario-escolar':
        case 'horario-escolar':
            $respuesta = obtenerEventosEscolares(10);
            break;

        case 'dias-festivos':
            $respuesta = obtenerEventosEscolares(5);
            break;

        default:
            // Intent no relacionado con calendario, dejar que DialogFlow responda solo
            echo json_encode(['fulfillmentText' => '']);
            exit;
    }

    echo json_encode(['fulfillmentText' => $respuesta]);
    exit;
}

// -----------------------------------------------------------------------
// MODO FRONTEND: el index.php envía el mensaje del usuario
// -----------------------------------------------------------------------
$userInput = $input['mensaje'] ?? '';

if (empty($userInput)) {
    echo json_encode(['error' => 'No se envió ningún mensaje.']);
    exit;
}

// Cargar credenciales de Dialogflow
$credencialesPath = getenv('DIALOGFLOW_CREDENTIALS') ?: __DIR__ . '/credenciales.json';
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

    $response     = $sessionsClient->detectIntent($session, $queryInput);
    $queryResult  = $response->getQueryResult();
    $responseText = $queryResult->getFulfillmentText();
    $intentName   = $queryResult->getIntent() ? $queryResult->getIntent()->getDisplayName() : '';

    // Intents de calendario: llamar directo a Google Calendar API
    // (el servidor PHP es monohilo y no puede atender el webhook mientras está ocupado)
    $intentesCalendario = [
        'eventos-escolares',
        'actividades-escolares',
        'examenes',
        'calendario-escolar',
        'horario-escolar',
        'dias-festivos',
    ];

    if (in_array(strtolower($intentName), $intentesCalendario)) {
        $responseText = obtenerEventosEscolares(5);
    }

    echo json_encode([
        'respuesta' => $responseText,
        'intent'    => $intentName,
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error al consultar Dialogflow: ' . $e->getMessage()
    ]);
}
