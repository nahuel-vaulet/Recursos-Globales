<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico PWA</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
            background: #f0f0f0;
        }

        .status-box {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: white;
            border: 1px solid #ccc;
        }

        .ok {
            border-left: 5px solid green;
            color: green;
        }

        .error {
            border-left: 5px solid red;
            color: red;
        }

        .warn {
            border-left: 5px solid orange;
            color: orange;
        }
    </style>
</head>

<body>
    <h1>Diagnóstico de Instalación</h1>

    <div id="secure-ctx" class="status-box">Comprobando HTTPS/Secure Context...</div>
    <div id="manifest-check" class="status-box">Comprobando Manifest...</div>
    <div id="sw-check" class="status-box">Comprobando Service Worker...</div>
    <div id="install-criteria" class="status-box">Criterios de Instalación...</div>

    <script>
        function log(id, type, msg) {
            const el = document.getElementById(id);
            el.className = 'status-box ' + type;
            el.innerHTML = '<strong>' + id + ':</strong> ' + msg;
        }

        // 1. Secure Context
        if (window.isSecureContext) {
            log('secure-ctx', 'ok', 'OK (Seguro)');
        } else {
            log('secure-ctx', 'error', 'FALLO: No es contexto seguro. Active Chrome Flags o use HTTPS.');
        }

        // 2. Manifest
        fetch('manifest.json')
            .then(r => {
                if (r.status === 200) log('manifest-check', 'ok', 'OK (Encontrado)');
                else log('manifest-check', 'error', 'Error ' + r.status);
            })
            .catch(e => log('manifest-check', 'error', 'Error Red: ' + e.message));

        // 3. Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
                .then(reg => {
                    log('sw-check', 'ok', 'Registrado correctamente. Scope: ' + reg.scope);
                })
                .catch(err => {
                    log('sw-check', 'error', 'Fallo al registrar: ' + err.message);
                });
        } else {
            log('sw-check', 'error', 'Navegador no soporta SW');
        }

        // 4. Install Prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            log('install-criteria', 'ok', 'Evento "beforeinstallprompt" disparado! La app ES instalable.');
            const btn = document.createElement('button');
            btn.textContent = 'PROBAR INSTALACIÓN';
            btn.style.padding = '10px';
            btn.style.background = 'blue';
            btn.style.color = 'white';
            btn.onclick = () => e.prompt();
            document.body.appendChild(btn);
        });

        setTimeout(() => {
            const el = document.getElementById('install-criteria');
            if (!el.className.includes('ok')) {
                log('install-criteria', 'warn', 'Evento no disparado aún (puede tardar o fallar si no es seguro).');
            }
        }, 3000);

    </script>
</body>

</html>