<?php
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;

header('Content-Type: application/json');

// Recibir JSON del frontend
$input = json_decode(file_get_contents('php://input'), true);
$userInput = $input['mensaje'] ?? '';

if (empty($userInput)) {
    echo json_encode(['error' => 'No se envió ningún mensaje.']);
    exit;
}

// Cargar credenciales
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/credenciales.json');

// Tu ID de proyecto Dialogflow
$projectId = 'utconnect-lniw';

try {
    session_start();
    $sessionId = session_id();

    $sessionsClient = new SessionsClient();
    $session = $sessionsClient->sessionName($projectId, $sessionId);

    $textInput = new TextInput();
    $textInput->setText($userInput);
    $textInput->setLanguageCode('es');

    $queryInput = new QueryInput();
    $queryInput->setText($textInput);

    $response = $sessionsClient->detectIntent($session, $queryInput);
    $responseText = $response->getQueryResult()->getFulfillmentText();

    echo json_encode([
        'respuesta' => $responseText
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error al consultar Dialogflow: ' . $e->getMessage()
    ]);
}
