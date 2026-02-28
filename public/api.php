<?php
/**
 * Controlador: Webhook / API del chatbot
 * Punto de entrada para Dialogflow (webhook) y el frontend (fetch).
 */
require dirname(__DIR__) . '/config/load_env.php';
require dirname(__DIR__) . '/app/Controllers/ChatController.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$controller = new ChatController();
$controller->handle($input);
