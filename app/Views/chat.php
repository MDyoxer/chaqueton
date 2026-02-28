<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- PWA: viewport optimizado para móvil, sin zoom forzado -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Asistente Virtual U.T. de Coahuila</title>
    <meta name="description" content="Chatbot del asistente virtual de la Universidad Tecnológica de Coahuila. Consulta horarios, carreras, trámites y más.">

    <!-- ── PWA Manifest ── -->
    <link rel="manifest" href="manifest.json">

    <!-- ── Theme color ── -->
    <meta name="theme-color" content="#04AA6D">
    <meta name="msapplication-navbutton-color" content="#04AA6D">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- ── iOS / Safari PWA ── -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="UTC Chatbot">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="icons/icon-152.png">
    <link rel="apple-touch-icon" sizes="144x144" href="icons/icon-144.png">

    <!-- ── Android Chrome ── -->
    <meta name="mobile-web-app-capable" content="yes">

    <!-- ── Favicon ── -->
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="32x32"  href="icons/icon-96.png">
    <link rel="shortcut icon" href="icons/icon-96.png">

    <!-- ── Estilos ── -->
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="content">
        <div class="content">
            <div class="main-layout">

                <!-- Sección izquierda: Información del asistente -->
                <aside class="assistant-info">
                    <img src="utc.jpg" alt="Logo Universidad Tecnológica de Coahuila" class="utc-logo">
                    <h2>Asistente Virtual UTC</h2>
                    <p>
                        Bienvenido al asistente virtual de la Universidad Tecnológica de Coahuila.
                        Este sistema está diseñado para ayudarte con:
                    </p>
                    <ul>
                        <li>Información sobre carreras y planes de estudio</li>
                        <li>Horarios y servicios escolares</li>
                        <li>Asistencia con procesos administrativos</li>
                        <li>Dudas sobre inscripción y becas</li>
                        <li>Guía para el uso de plataformas institucionales</li>
                    </ul>
                    <p>Solo escribe tu duda en el chat y obtén una respuesta al instante.</p>
                </aside>

                <!-- Contenido principal: Chat -->
                <div class="chat-container">
                    <div class="chat-box" id="chat-box">
                        <div class="message bot">
                            <p>Hola, soy el chatbot. ¿En qué puedo ayudarte, pedazo de genio?</p>
                        </div>
                    </div>

                    <div class="chat-input">
                        <input type="text" id="mensaje" placeholder="Escribe tu mensaje aquí..." />
                        <button class="send-btn" onclick="enviar()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Banner "Agregar a pantalla de inicio" -->
    <div id="install-banner" class="install-banner" style="display:none;">
        <span>📲 Instala la app en tu dispositivo para usarla sin conexión</span>
        <button id="install-btn" class="install-btn">Instalar</button>
        <button id="install-dismiss" class="install-dismiss" aria-label="Cerrar">✕</button>
    </div>

    <!-- Indicador de estado de red -->
    <div id="offline-banner" class="offline-banner" style="display:none;">
        <i class="fas fa-wifi-slash"></i> Sin conexión — algunos mensajes pueden no enviarse
    </div>

    <script>
        // ── Enviar mensaje ──────────────────────────────────────────
        function enviar() {
            const input   = document.getElementById('mensaje');
            const texto   = input.value.trim();
            if (!texto) return;

            const chatBox = document.getElementById('chat-box');

            // Mensaje del usuario
            const userMsg = document.createElement('div');
            userMsg.className = 'message user';
            userMsg.innerHTML = `<p>${escapeHtml(texto)}</p>`;
            chatBox.appendChild(userMsg);

            input.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;

            // Indicador de escritura del bot
            const typing = document.createElement('div');
            typing.className = 'message bot typing-indicator';
            typing.innerHTML = '<span></span><span></span><span></span>';
            chatBox.appendChild(typing);
            chatBox.scrollTop = chatBox.scrollHeight;

            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mensaje: texto })
            })
            .then(res => res.json())
            .then(data => {
                typing.remove();
                const botMsg = document.createElement('div');
                botMsg.className = 'message bot';
                botMsg.innerHTML = `<p>${escapeHtml(data.respuesta || data.error || 'Sin respuesta del servidor')}</p>`;
                chatBox.appendChild(botMsg);
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(() => {
                typing.remove();
                const botMsg = document.createElement('div');
                botMsg.className = 'message bot';
                botMsg.innerHTML = `<p>⚠ Sin conexión. Verifica tu red e intenta de nuevo.</p>`;
                chatBox.appendChild(botMsg);
                chatBox.scrollTop = chatBox.scrollHeight;
            });
        }

        // Sanitizar HTML para evitar XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }

        // ── Enter para enviar ───────────────────────────────────────
        document.getElementById('mensaje').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                enviar();
            }
        });

        // ── Estado de red online/offline ────────────────────────────
        const offlineBanner = document.getElementById('offline-banner');

        function updateOnlineStatus() {
            offlineBanner.style.display = navigator.onLine ? 'none' : 'flex';
        }
        window.addEventListener('online',  updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        updateOnlineStatus();

        // ── Registro del Service Worker ─────────────────────────────
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then(reg => console.log('[SW] Registrado:', reg.scope))
                    .catch(err => console.error('[SW] Error:', err));
            });
        }

        // ── Prompt de instalación PWA ───────────────────────────────
        let deferredPrompt;
        const installBanner  = document.getElementById('install-banner');
        const installBtn     = document.getElementById('install-btn');
        const installDismiss = document.getElementById('install-dismiss');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            installBanner.style.display = 'flex';
        });

        installBtn.addEventListener('click', async () => {
            installBanner.style.display = 'none';
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log('[PWA] Instalación:', outcome);
                deferredPrompt = null;
            }
        });

        installDismiss.addEventListener('click', () => {
            installBanner.style.display = 'none';
        });

        window.addEventListener('appinstalled', () => {
            console.log('[PWA] App instalada correctamente');
            deferredPrompt = null;
        });
    </script>
</body>
</html>
