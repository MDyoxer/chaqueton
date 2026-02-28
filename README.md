# Asistente Virtual UTC 🤖

Chatbot inteligente para la **Universidad Tecnológica de Coahuila (UTC)**, construido con **DialogFlow** y **Google Calendar API** como PWA (Progressive Web App).

## Tecnologías

- **DialogFlow** — Procesamiento de lenguaje natural
- **Google Calendar API** — Eventos y calendario escolar en tiempo real
- **PHP** — Backend / Webhook
- **ngrok** — Exposición del webhook local

## Requisitos

- PHP 8.0+
- Composer
- Cuenta de Google Cloud con DialogFlow y Google Calendar API habilitadas

## Instalación

```bash
composer install
```

## Archivos de credenciales (NO incluidos en el repositorio)

| Archivo | Descripción |
|---------|-------------|
| `credenciales.json` | Service Account de DialogFlow |
| `calendar-credentials.json` | Service Account de Google Calendar API |

## Uso

### 1. Iniciar el servidor PHP
```bash
php -S localhost:9000 -t .
```

### 2. Iniciar ngrok
```bash
ngrok http 9000
```

### 3. Configurar el Webhook en DialogFlow
En **DialogFlow → Fulfillment → Webhook** poner:
```
https://TU-URL.ngrok-free.app/api.php
```

## Intents de Google Calendar

Los siguientes intents consultan automáticamente Google Calendar API:

| Intent | Descripción |
|--------|-------------|
| `eventos-escolares` | Próximos eventos de la escuela |
| `examenes` | Fechas de exámenes |
| `calendario-escolar` | Calendario general |
| `horario-escolar` | Horarios escolares |
| `dias-festivos` | Días sin clases |
| `actividades-escolares` | Actividades extracurriculares |

## Estructura del proyecto

```
📁 dialogflow/
├── index.php                  # Frontend del chatbot (PWA)
├── api.php                    # Backend: DialogFlow + Google Calendar
├── calendar.php               # Integración con Google Calendar API
├── styles.css                 # Estilos del chatbot
├── manifest.json              # Configuración PWA
├── sw.js                      # Service Worker
└── vendor/                    # Dependencias (Composer)
```
