<?php
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;

session_start();

// Texto enviado desde el formulario
$userInput = $_POST['mensaje'] ?? '';

// Cargar credenciales
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/credenciales.json');

// Tu ID de proyecto Dialogflow
$projectId = 'utconnect-lniw'; // <-- reemplazar

$responseText = '';

try {
    $sessionId = session_id();
    $sessionsClient = new SessionsClient();
    $session = $sessionsClient->sessionName($projectId, $sessionId);

    $textInput = new TextInput();
    $textInput->setText($userInput);
    $textInput->setLanguageCode('es');

    $queryInput = new QueryInput();
    $queryInput->setText($textInput);

    // Enviar mensaje a Dialogflow
    $response = $sessionsClient->detectIntent($session, $queryInput);
    $responseText = $response->getQueryResult()->getFulfillmentText();

} catch (Exception $e) {
    $responseText = 'Error al consultar Dialogflow: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Prueba Dialogflow</title>
</head>
<body>

<h2>Chat con Dialogflow</h2>

<form action="dialogflow.php" method="POST">
    <input type="text" name="mensaje" placeholder="Escribe tu pregunta" required>
    <button type="submit">Enviar</button>
</form>

<h3>Respuesta:</h3>
<p><?php echo htmlspecialchars($responseText); ?></p>

</body>
</html>
