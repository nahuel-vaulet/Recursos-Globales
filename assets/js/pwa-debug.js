/**
 * PWA Diagnostics Tool - ERP Recursos Globales
 * Audits the environment for WebAPK eligibility
 */

async function runPWADiagnostics() {
    const report = [];
    const addResult = (label, status, details) => {
        report.push({ label, status, details });
        console.log(`[PWA-DIAG] ${label}: ${status} - ${details}`);
    };

    console.clear();
    console.log("=== INICIANDO DIAGNÓSTICO PWA ===");

    // 1. Check Protocol & Secure Context
    const isLocalhost = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    const isSecure = window.isSecureContext;

    if (isSecure) {
        addResult('Contexto Seguro', 'PASS', 'HTTPS o Localhost detectado.');
    } else {
        addResult('Contexto Seguro', 'FAIL', 'No es seguro. Chrome requiere HTTPS o Flag "Insecure origins".');
    }

    // 2. Service Worker
    if ('serviceWorker' in navigator) {
        try {
            const regs = await navigator.serviceWorker.getRegistrations();
            if (regs.length > 0) {
                const reg = regs[0];
                addResult('Service Worker', 'PASS', `Registrado. Scope: ${reg.scope}, Estado: ${reg.active ? 'Activo' : 'Instalando'}`);
            } else {
                addResult('Service Worker', 'FAIL', 'No hay Service Worker registrado.');
            }
        } catch (e) {
            addResult('Service Worker', 'FAIL', `Error al consultar SW: ${e.message}`);
        }
    } else {
        addResult('Service Worker', 'FAIL', 'Navegador no soporta Service Workers.');
    }

    // 3. Manifest Fetch & Headers
    const manifestLink = document.querySelector('link[rel="manifest"]');
    if (manifestLink) {
        const url = manifestLink.href;
        try {
            const resp = await fetch(url);
            if (resp.status === 200) {
                addResult('Manifest HTTP', 'PASS', 'Archivo accesible (200 OK).');

                // Content Type check often fails on local XAMPP if not configured, but manifest.php fixes this
                const cType = resp.headers.get('Content-Type');
                if (cType && cType.includes('application/manifest+json')) {
                    addResult('Manifest MIME', 'PASS', `Correcto (${cType}).`);
                } else {
                    addResult('Manifest MIME', 'WARN', `Podría ser incorrecto: ${cType}. Se espera application/manifest+json.`);
                }

                const json = await resp.json();
                addResult('Manifest JSON', 'PASS', 'Sintaxis JSON válida.');

                // 4. Manifest Content Logic
                if (json.display === 'standalone') {
                    addResult('Manifest Display', 'PASS', 'Configurado como "standalone".');
                } else {
                    addResult('Manifest Display', 'FAIL', `Configurado como "${json.display}".`);
                }

                if (json.icons && json.icons.length >= 2) {
                    // Check Icon accessibility
                    const iconUrl = new URL(json.icons[0].src, url).href;
                    const iconResp = await fetch(iconUrl, { method: 'HEAD' });
                    if (iconResp.ok) {
                        addResult('Manifest Iconos', 'PASS', `Icono accesible: ${json.icons[0].src}`);
                    } else {
                        addResult('Manifest Iconos', 'FAIL', `Icono 404: ${json.icons[0].src}`);
                    }
                } else {
                    addResult('Manifest Iconos', 'FAIL', 'Faltan iconos o array vacío.');
                }

                if (json.start_url) {
                    addResult('Manifest StartURL', 'INFO', `Start URL: ${json.start_url}`);
                }

            } else {
                addResult('Manifest HTTP', 'FAIL', `Error descargando manifest: ${resp.status}`);
            }
        } catch (e) {
            addResult('Manifest Fetch', 'FAIL', `Excepción: ${e.message}`);
        }
    } else {
        addResult('Manifest Link', 'FAIL', 'No se encontró <link rel="manifest"> en el HTML.');
    }

    // 5. User Agent & Installability
    const ua = navigator.userAgent;
    addResult('User Agent', 'INFO', ua);

    if (window.deferredPrompt) {
        addResult('Evento Install', 'PASS', 'El evento "beforeinstallprompt" se disparó. La app ES instalable.');
    } else {
        // Check standalone
        if (window.matchMedia('(display-mode: standalone)').matches) {
            addResult('Evento Install', 'INFO', 'No disponible porque YA ESTÁ en modo App.');
        } else {
            addResult('Evento Install', 'WARN', 'El evento NO se disparó. Chrome no la considera instalable aún (falta engagement, o error crítico).');
        }
    }

    // Render Report
    let html = '<div style="background:white; color:black; padding:15px; border-radius:8px; max-height:80vh; overflow-y:auto; text-align:left;">';
    html += '<h3>Diagnóstico PWA</h3><ul style="padding-left:0; list-style:none;">';

    report.forEach(item => {
        let color = item.status === 'PASS' ? 'green' : (item.status === 'WARN' ? 'orange' : 'red');
        let icon = item.status === 'PASS' ? '✅' : (item.status === 'WARN' ? '⚠️' : '❌');
        html += `<li style="margin-bottom:8px; border-bottom:1px solid #eee; padding-bottom:5px;">
            <strong style="color:${color}">${icon} ${item.label}</strong><br>
            <span style="font-size:0.9em; color:#555;">${item.details}</span>
        </li>`;
    });
    html += '</ul>';
    html += `<button onclick="document.body.removeChild(this.closest('.pwa-overlay'))" style="margin-top:10px; padding:10px 20px; background:#333; color:white; border:none; border-radius:5px;">Cerrar</button>`;
    html += '</div>';

    const overlay = document.createElement('div');
    overlay.className = 'pwa-overlay';
    overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:10000; display:flex; align-items:center; justify-content:center;';
    overlay.innerHTML = html;
    document.body.appendChild(overlay);
}
