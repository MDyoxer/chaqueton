# Asistente Virtual UTC

Chatbot inteligente para la **Universidad Tecnológica de Coahuila (UTC)**, desarrollado como **Progressive Web App (PWA)** con procesamiento de lenguaje natural mediante **Dialogflow** e integración en tiempo real con **Google Calendar API**.

---

## Descripción del proyecto

El Asistente Virtual UTC es una aplicación web conversacional diseñada para atender las consultas más frecuentes de estudiantes y personal de la Universidad Tecnológica de Coahuila. El sistema combina:

- **Dialogflow (Google Cloud NLP)** para interpretar el lenguaje natural del usuario y enrutar cada intención al manejador correspondiente.
- **Google Calendar API** para consultar eventos escolares, exámenes, días festivos y actividades extracurriculares directamente desde el calendario institucional.
- **PHP como backend/webhook** que actúa como intermediario entre el frontend, Dialogflow y Google Calendar.
- **PWA (Progressive Web App)** para que los usuarios puedan instalar el chatbot en su dispositivo móvil o escritorio sin necesidad de una tienda de aplicaciones.

### Intents de Google Calendar disponibles

| Intent | Descripción |
|---|---|
| `eventos-escolares` | Próximos eventos de la escuela |
| `examenes` | Fechas de exámenes |
| `calendario-escolar` | Calendario general del ciclo |
| `horario-escolar` | Horarios escolares |
| `dias-festivos` | Días sin clases |
| `actividades-escolares` | Actividades extracurriculares |

---

## Prerrequisitos y configuración del entorno

### Software requerido

| Herramienta | Versión mínima | Notas |
|---|---|---|
| PHP | 8.0 | Con extensiones `json`, `openssl`, `curl` habilitadas |
| Composer | 2.x | Gestor de dependencias PHP |
| ngrok | Cualquier versión estable | Para exponer el servidor local a Internet |
| Git | 2.x | Control de versiones |

### Servicios de Google Cloud requeridos

1. Crear un proyecto en [Google Cloud Console](https://console.cloud.google.com/).
2. Habilitar las siguientes APIs dentro del proyecto:
   - **Dialogflow API**
   - **Google Calendar API**
3. Crear dos **Cuentas de Servicio (Service Accounts)** con los roles mínimos necesarios:
   - Una para **Dialogflow** (`roles/dialogflow.client`).
   - Una para **Google Calendar** (acceso de solo lectura al calendario).
4. Descargar las claves JSON de cada cuenta de servicio.

### Archivos de credenciales (NO incluidos en el repositorio)

Estos archivos contienen información sensible y **nunca deben subirse al repositorio**. Colocarlos en la raíz del proyecto:

| Archivo | Descripción |
|---|---|
| `credenciales.json` | Clave JSON de la Service Account de Dialogflow |
| `calendar-credentials.json` | Clave JSON de la Service Account de Google Calendar |

> **Importante:** Ambos archivos están listados en `.gitignore`. Verifique que existan antes de ejecutar el proyecto.

### Variables de entorno

Todas las configuraciones sensibles se gestionan a través del archivo `.env`. El repositorio incluye `.env.example` como plantilla:

| Variable | Descripción |
|---|---|
| `DIALOGFLOW_PROJECT_ID` | ID del proyecto en Google Cloud |
| `DIALOGFLOW_CREDENTIALS` | Ruta al archivo JSON de credenciales de Dialogflow |
| `CALENDAR_ID` | ID del calendario escolar de Google Calendar |

El archivo `load_env.php` carga estas variables automáticamente al arrancar el servidor. Las variables definidas en el sistema operativo tienen prioridad sobre las del `.env`.

---

## Instrucciones para compilar y ejecutar el proyecto

### 1. Clonar el repositorio

```bash
git clone https://github.com
cd dialogflow-utc
```

### 2. Instalar dependencias PHP

```bash
composer install
```

Este comando descarga todas las librerías declaradas en `composer.json` (Google Cloud Dialogflow, Google API Client, Guzzle, etc.) dentro de la carpeta `vendor/`.

### 3. Colocar los archivos de credenciales

Copiar los archivos de credenciales descargados desde Google Cloud Console en la raíz del proyecto:

```
dialogflow-utc/
├── credenciales.json           <- Service Account Dialogflow
└── calendar-credentials.json   <- Service Account Google Calendar
```

### 4. Configurar las variables de entorno

Copiar el archivo de ejemplo y rellenar los valores reales:

```bash
copy .env.example .env
```

Editar `.env` con los valores del proyecto:

```ini
DIALOGFLOW_PROJECT_ID=tu-project-id
DIALOGFLOW_CREDENTIALS=credenciales.json
CALENDAR_ID=tu-calendario@gmail.com
```

El ID del calendario se obtiene en: **Google Calendar → Configuración del calendario → Integrar calendario → ID del calendario**.

> **Nota:** El archivo `.env` es cargado automáticamente por `load_env.php` al iniciar cualquier petición al servidor. No es necesario exportar variables manualmente.

### 5. Iniciar el servidor PHP local

```bash
php -S localhost:9000 -t public
```

El chatbot estará disponible en: `http://localhost:9000`

### 6. Exponer el servidor con ngrok

En una terminal separada:

```bash
ngrok http 9000
```

ngrok mostrará una URL pública similar a:

```
Forwarding  https://xxxx-xx-xx-xxx-xx.ngrok-free.app -> http://localhost:9000
```

### 7. Registrar el Webhook en Dialogflow

1. Ingresar a [Dialogflow Console](https://dialogflow.cloud.google.com/).
2. Seleccionar el agente **UTConnect**.
3. Ir a **Fulfillment → Webhook**.
4. Activar el webhook e ingresar la URL pública de ngrok:

```
https://xxxx-xx-xx-xxx-xx.ngrok-free.app/api.php
```

5. Hacer clic en **Save**.

A partir de ese momento, los intents configurados consultarán Google Calendar en tiempo real.

---

## Implementación de mecanismos de seguridad

### Protección de credenciales

**Sin hardcoding de claves en el código fuente.** Todas las claves, tokens e identificadores sensibles se gestionan a través de variables de entorno definidas en el archivo `.env`:

| Variable | Archivo que la consume |
|---|---|
| `DIALOGFLOW_PROJECT_ID` | `api.php` |
| `DIALOGFLOW_CREDENTIALS` | `api.php` |
| `CALENDAR_ID` | `calendar.php` |

El cargador `load_env.php` lee el `.env` automáticamente en cada petición:

```php
// api.php
$credencialesPath = getenv('DIALOGFLOW_CREDENTIALS') ?: __DIR__ . '/credenciales.json';
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credencialesPath);
$projectId = getenv('DIALOGFLOW_PROJECT_ID') ?: 'utconnect-lniw';

// calendar.php
$calendarId = getenv('CALENDAR_ID');
if (empty($calendarId)) {
    return 'El calendario escolar no está configurado. Contacta al administrador.';
}
```

Los archivos protegidos por `.gitignore`:

```
credenciales.json
calendar-credentials.json
.env
.env.local
.env.production
```

### Transmisión segura mediante HTTPS

Todas las peticiones de red se realizan exclusivamente sobre **HTTPS/TLS**:
- El frontend se comunica con el backend a través del túnel HTTPS de ngrok.
- Dialogflow llama al webhook únicamente por HTTPS.
- La Google Calendar API rechaza conexiones no cifradas por diseño.

No se almacena información sensible del usuario de forma local. Las sesiones PHP (`session_start()`) solo guardan un identificador de sesión anónimo para mantener el contexto conversacional, sin datos personales:

```php
session_start();
$sessionId = session_id(); // ID anónimo, sin datos del usuario
$session = $sessionsClient->sessionName($projectId, $sessionId);
```

### Principio de mínimo privilegio

Las Service Accounts de Google Cloud tienen únicamente los permisos mínimos necesarios:
- **Dialogflow:** `roles/dialogflow.client` — solo detectar intents.
- **Google Calendar:** `CALENDAR_READONLY` — solo lectura, sin poder modificar ni eliminar eventos.

### Validación de entradas

El backend rechaza peticiones malformadas antes de procesarlas:

```php
$userInput = $input['mensaje'] ?? '';
if (empty($userInput)) {
    echo json_encode(['error' => 'No se envió ningún mensaje.']);
    exit;
}
```

## Estructura del proyecto

El proyecto sigue una arquitectura **MVC** con separación clara entre capas:

```
dialogflow-utc/
├── public/                    ← Document root (php -S localhost:9000 -t public)
│   ├── index.php              # Vista: carga app/Views/chat.php
│   ├── api.php                # Entry point: webhook Dialogflow / API frontend
│   ├── styles.css             # Estilos de la interfaz
│   ├── manifest.json          # Configuración PWA
│   ├── sw.js                  # Service Worker (caché offline)
│   ├── utc.jpg                # Logo de la universidad
│   └── icons/                 # Íconos PWA (192px, 512px, etc.)
├── app/
│   ├── Controllers/
│   │   └── ChatController.php # Controlador: maneja webhook y peticiones frontend
│   ├── Services/
│   │   └── CalendarService.php # Servicio: integra Google Calendar API
│   └── Views/
│       └── chat.php           # Vista: HTML del chatbot
├── config/
│   └── load_env.php           # Cargador de variables de entorno (.env)
├── vendor/                    # Dependencias instaladas por Composer
├── composer.json              # Declaración de dependencias PHP
├── .env                       # [NO incluido] Variables de entorno locales
├── .env.example               # Plantilla de variables de entorno
├── .gitignore                 # Exclusiones del repositorio
├── credenciales.json          # [NO incluido] Service Account Dialogflow
└── calendar-credentials.json  # [NO incluido] Service Account Google Calendar
```

---

## Tecnologías utilizadas

| Tecnología | Rol |
|---|---|
| Dialogflow (Google Cloud) | Procesamiento de lenguaje natural |
| Google Calendar API | Consulta de eventos escolares en tiempo real |
| PHP 8.x | Backend / Webhook |
| Composer | Gestión de dependencias |
| ngrok | Túnel HTTPS para desarrollo local |
| PWA (Service Worker + Manifest) | App instalable sin tienda de aplicaciones |