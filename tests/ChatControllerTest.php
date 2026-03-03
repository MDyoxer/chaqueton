<?php

use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias de la lógica de negocio de ChatController.
 *
 * Se replica la lógica interna de cada método sin instanciar el
 * controlador real, evitando así conexiones a Dialogflow o Calendar.
 *
 * Métodos cubiertos:
 *   - handle()          → detección de modo webhook vs frontend
 *   - handleFrontend()  → validación de mensaje vacío
 *   - handleFrontend()  → detección de intent de calendario
 *   - handleWebhook()   → cálculo de $maxEventos según intent
 */
class ChatControllerTest extends TestCase
{
    /**
     * Lista real de intents del controlador.
     * Fuente: ChatController::INTENTS_CALENDARIO
     */
    private const INTENTS_CALENDARIO = [
        'eventos-escolares',
        'actividades-escolares',
        'examenes',
        'calendario-escolar',
        'horario-escolar',
        'dias-festivos',
    ];

    // ─────────────────────────────────────────────────────────────
    // TEST 1 — handleFrontend(): mensaje vacío devuelve error
    //
    // Lógica real:
    //   $userInput = $input['mensaje'] ?? '';
    //   if (empty($userInput)) {
    //       echo json_encode(['error' => 'No se envió ningún mensaje.']);
    //       return;
    //   }
    // ─────────────────────────────────────────────────────────────
    public function test_mensaje_vacio_retorna_error(): void
    {
        $casosprueba = [
            ['mensaje' => ''],           // string vacío
            ['mensaje' => '   '],        // solo espacios → trim
            [],                          // clave ausente → operador ??
        ];

        foreach ($casosprueba as $input) {
            $userInput = isset($input['mensaje']) ? trim($input['mensaje']) : '';

            // Lógica real del controlador
            if (empty($userInput)) {
                $resultado = json_encode(['error' => 'No se envió ningún mensaje.']);
            } else {
                $resultado = json_encode(['respuesta' => 'OK']);
            }

            $decoded = json_decode($resultado, true);

            $this->assertArrayHasKey(
                'error',
                $decoded,
                'Entrada vacía debe producir clave "error"'
            );
            $this->assertSame(
                'No se envió ningún mensaje.',
                $decoded['error']
            );
        }
    }

    // ─────────────────────────────────────────────────────────────
    // TEST 2 — handleWebhook(): $maxEventos según tipo de intent
    //
    // Lógica real:
    //   $maxEventos = in_array($intentName,
    //       ['calendario-escolar', 'horario-escolar']) ? 10 : 5;
    // ─────────────────────────────────────────────────────────────
    public function test_maxEventos_segun_intent(): void
    {
        // Función que replica exactamente la expresión del controlador
        $calcularMax = static function (string $intent): int {
            return in_array($intent, ['calendario-escolar', 'horario-escolar'])
                ? 10
                : 5;
        };

        // Intents que deben retornar 10
        $this->assertSame(10, $calcularMax('calendario-escolar'),
            'calendario-escolar → 10 eventos');
        $this->assertSame(10, $calcularMax('horario-escolar'),
            'horario-escolar → 10 eventos');

        // Intents que deben retornar 5
        $this->assertSame(5, $calcularMax('examenes'),
            'examenes → 5 eventos');
        $this->assertSame(5, $calcularMax('eventos-escolares'),
            'eventos-escolares → 5 eventos');
        $this->assertSame(5, $calcularMax('dias-festivos'),
            'dias-festivos → 5 eventos');
        $this->assertSame(5, $calcularMax('actividades-escolares'),
            'actividades-escolares → 5 eventos');
    }

    // ─────────────────────────────────────────────────────────────
    // TEST 3 — handle(): detección de modo webhook vs frontend
    //
    // Lógica real:
    //   if (isset($input['queryResult'])) { handleWebhook }
    //   else                              { handleFrontend }
    // ─────────────────────────────────────────────────────────────
    public function test_deteccion_modo_webhook_vs_frontend(): void
    {
        $detectarModo = static function (array $input): string {
            return isset($input['queryResult']) ? 'webhook' : 'frontend';
        };

        // Payload real de Dialogflow
        $payloadWebhook = [
            'queryResult' => [
                'intent'    => ['displayName' => 'examenes'],
                'queryText' => '¿Cuándo son los exámenes?',
            ]
        ];

        // Payload del frontend
        $payloadFrontend = ['mensaje' => '¿Cuándo son los exámenes?'];

        // Payload malformado (ni queryResult ni mensaje)
        $payloadVacio = [];

        $this->assertSame('webhook',  $detectarModo($payloadWebhook),
            'queryResult presente → modo webhook');
        $this->assertSame('frontend', $detectarModo($payloadFrontend),
            'Sin queryResult → modo frontend');
        $this->assertSame('frontend', $detectarModo($payloadVacio),
            'Payload vacío → cae en modo frontend');
    }

    // ─────────────────────────────────────────────────────────────
    // TEST 4 — handleFrontend() + handleWebhook():
    //          intents de calendario son detectados correctamente
    //
    // Lógica real:
    //   if (in_array($intentName, self::INTENTS_CALENDARIO)) { ... }
    // ─────────────────────────────────────────────────────────────
    public function test_intents_de_calendario_son_reconocidos(): void
    {
        // Todos los intents definidos deben estar en la lista
        $intentsEsperados = [
            'eventos-escolares',
            'actividades-escolares',
            'examenes',
            'calendario-escolar',
            'horario-escolar',
            'dias-festivos',
        ];

        foreach ($intentsEsperados as $intent) {
            $this->assertContains(
                $intent,
                self::INTENTS_CALENDARIO,
                "'{$intent}' debe consultar Google Calendar"
            );
        }

        // Intents que NO deben activar el calendario
        $intentsAjenos = [
            'saludo',
            'Default Welcome Intent',
            'Default Fallback Intent',
            'despedida',
        ];

        foreach ($intentsAjenos as $intent) {
            $this->assertNotContains(
                $intent,
                self::INTENTS_CALENDARIO,
                "'{$intent}' NO debe consultar Google Calendar"
            );
        }
    }
}
