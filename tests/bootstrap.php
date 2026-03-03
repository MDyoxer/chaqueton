<?php
define('PROJECT_ROOT', dirname(__DIR__));
require_once PROJECT_ROOT . '/vendor/autoload.php';

// Variables de entorno de prueba (sin conexiones reales)
putenv('DIALOGFLOW_PROJECT_ID=test-project');
putenv('DIALOGFLOW_CREDENTIALS=credenciales.json');
putenv('CALENDAR_ID=test@gmail.com');
