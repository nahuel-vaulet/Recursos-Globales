</main> <!-- Close Main Container -->

<footer
    style="background: var(--bg-secondary); color: var(--text-secondary); padding: 8px 0; margin-top: auto; transition: background 0.3s ease; border-top: 1px solid var(--bg-tertiary); font-size: 0.75em;">
    <div class="container" style="text-align: center;">
        <p style="margin: 0;">&copy; <?php echo date('Y'); ?> Recursos Globales.</p>
    </div>
</footer>

<!-- [!] PWA: Registro de Service Worker y sincronización offline -->
<!-- [!] PWA: Botón de Instalación Manual -->
<div id="pwaInstallBtn"
    style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 9998; background: #00bcd4; color: #fff; padding: 12px 24px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,188,212,0.4); cursor: pointer; font-weight: bold; animation: floatUp 0.5s ease;">
    <i class="fas fa-download"></i> Instalar Aplicación
</div>

<script>
    // [!] ARCH: Registrar Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/APP-Prueba/sw.js')
                .then(reg => console.log('[PWA] Service Worker registrado:', reg.scope))
                .catch(err => console.error('[PWA] Error registrando SW:', err));
        });
    }

    // [!] PWA: Lógica de instalación manual
    let deferredPrompt;
    const installBtn = document.getElementById('pwaInstallBtn');

    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevenir que Chrome muestre el banner automático (opcional, pero mejor para control)
        e.preventDefault();
        // Guardar el evento para dispararlo después
        deferredPrompt = e;
        // Mostrar botón
        installBtn.style.display = 'block';
    });

    installBtn.addEventListener('click', async () => {
        if (!deferredPrompt) {
            alert("❌ El evento de instalación se perdió. Refrescá la página.");
            return;
        }
        // Mostrar prompt nativo
        try {
            deferredPrompt.prompt();
            // Esperar respuesta
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`[PWA] Respuesta usuario: ${outcome}`);
            
            if (outcome === 'accepted') {
                alert("✅ Instalando... (Revisá la barra de notificaciones o el menú de apps)");
            } else {
                alert("⚠️ Instalación cancelada. Tocá de nuevo el botón si te arrepentiste.");
            }
        } catch (e) {
            alert("❌ Error crítico: " + e.message);
        }

        // Limpiar
        deferredPrompt = null;
        installBtn.style.display = 'none';
    });

    // Ocultar botón si ya se instaló
    window.addEventListener('appinstalled', () => {
        installBtn.style.display = 'none';
        console.log('[PWA] Aplicación instalada');
        alert("🎉 ¡App instalada con éxito!");
    });

    // [!] PWA-OFFLINE: Sincronizar ODTs pendientes al recuperar conexión
    window.addEventListener('online', async () => {
        const pending = JSON.parse(localStorage.getItem('odt_pending_sync') || '[]');
        if (pending.length === 0) return;

        console.log('[PWA] Sincronizando', pending.length, 'ODT(s) pendientes...');

        for (let i = pending.length - 1; i >= 0; i--) {
            try {
                const item = pending[i];
                const url = '/APP-Prueba/api/odt.php' + (item._action === 'UPDATE' ? '?id=' + item.id_odt : '');
                const method = item._action === 'UPDATE' ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(item)
                });

                if (response.ok) {
                    pending.splice(i, 1);
                    console.log('[PWA] ODT sincronizada:', item.nro_odt_assa);
                }
            } catch (e) {
                console.error('[PWA] Error sync:', e);
                break;
            }
        }

        localStorage.setItem('odt_pending_sync', JSON.stringify(pending));

        if (pending.length === 0) {
            console.log('[PWA] ✅ Todas las ODTs sincronizadas');
            // Recargar si estamos en el módulo ODT
            if (window.location.pathname.includes('/odt/')) {
                location.reload();
            }
        }
    });

    // [!] ARCH: Detectar estado offline
    function updateOnlineStatus() {
        document.body.classList.toggle('offline', !navigator.onLine);
    }
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();

    // ==========================================
    // THEME TOGGLE - Modo Oscuro / Claro
    // ==========================================
    (function () {
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;

        // Cargar tema guardado o usar oscuro por defecto
        const savedTheme = localStorage.getItem('theme') || 'dark';

        if (savedTheme === 'light') {
            htmlElement.setAttribute('data-theme', 'light');
            if (themeToggle) themeToggle.checked = false;
        } else {
            htmlElement.removeAttribute('data-theme');
            if (themeToggle) themeToggle.checked = true;
        }

        // Escuchar cambios en el toggle
        if (themeToggle) {
            themeToggle.addEventListener('change', function () {
                if (this.checked) {
                    // Modo oscuro (default)
                    htmlElement.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'dark');
                } else {
                    // Modo claro
                    htmlElement.setAttribute('data-theme', 'light');
                    localStorage.setItem('theme', 'light');
                }
            });
        }
    })();

    // ==========================================
    // MEGA MENU - Click Toggle
    // ==========================================
    (function () {
        const megaTrigger = document.querySelector('.mega-menu-trigger');
        const megaMenuLink = megaTrigger ? megaTrigger.querySelector('a') : null;

        if (megaMenuLink) {
            megaMenuLink.addEventListener('click', function (e) {
                e.preventDefault();
                megaTrigger.classList.toggle('active');
            });

            // Cerrar al hacer click fuera
            document.addEventListener('click', function (e) {
                if (megaTrigger && !megaTrigger.contains(e.target)) {
                    megaTrigger.classList.remove('active');
                }
            });

            // Cerrar al presionar Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && megaTrigger) {
                    megaTrigger.classList.remove('active');
                }
            });
        }
    })();
</script>

</body>

</html>