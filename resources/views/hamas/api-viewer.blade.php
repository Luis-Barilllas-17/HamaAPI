<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Conexiones OAuth2 de ProyectoAPI">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Conexiones API | Configuración</title>
    <x-hama-favicon />
    <link rel="stylesheet" href="{{ asset('css/hama.css') }}?v=12">
    <link rel="stylesheet" href="{{ asset('css/api-viewer.css') }}?v=7">
    <link rel="stylesheet" href="{{ asset('css/soft-pages.css') }}?v=1">
    <script src="{{ asset('js/api-viewer.js') }}?v=6" defer></script>
</head>
<body>
    <div class="app-layout">
        <x-hama-sidebar active="settings" />

        <div class="content-area">
            <main class="main-content connections-page">
                <a class="settings-back-link" href="/configuracion">← Volver a Configuración</a>
                <header class="connections-header">
                    <div><span class="eyebrow">PASSPORT · OAUTH2</span><h1>Conexiones API</h1><p>Comparte únicamente proyectos Hama públicos, sus colores y la creación de nuevos diseños.</p></div>
                    <span id="global-connection-status" class="connection-state waiting"><i aria-hidden="true"></i>Comprobando MeshiVerse</span>
                </header>

                <section class="connection-overview" aria-label="Resumen de seguridad">
                    <article><span>Protocolo</span><strong>OAuth 2.0</strong><small>Client Credentials</small></article>
                    <article><span>Protección</span><strong>Laravel Passport</strong><small>Bearer token</small></article>
                    <article><span>API compartida</span><strong>Versión 1</strong><small>/api/v1</small></article>
                </section>

                <section class="connection-grid" aria-label="Administración de conexiones">
                    <article class="connection-card">
                        <div class="card-heading"><span class="card-number">01</span><div><span class="card-kicker">TOKEN DE PROYECTOAPI</span><h2>Generar acceso para compartir</h2><p>Entrega este token al otro proyecto para consultar Hamas públicos, ver sus colores o crear un Hama.</p></div></div>
                        <form id="token-generator-form" class="connection-form" novalidate>
                            <label class="form-field" for="destination-project"><span>Proyecto que recibirá el token</span><input id="destination-project" type="text" maxlength="100" value="MeshiVerse" autocomplete="off" required></label>
                            <fieldset class="scope-picker">
                                <legend>¿Qué podrá hacer?</legend>
                                <label class="scope-option"><input id="scope-read" type="checkbox" value="hamas.read" checked><span class="scope-check" aria-hidden="true">✓</span><span><strong>Ver Hamas públicos y colores</strong><small>Los proyectos privados y el inventario personal nunca se comparten.</small></span></label>
                                <label class="scope-option"><input id="scope-write" type="checkbox" value="hamas.write" checked><span class="scope-check" aria-hidden="true">✓</span><span><strong>Crear un Hama público</strong><small>Enviar y guardar un patrón completo de hasta 50 × 50.</small></span></label>
                            </fieldset>
                            <button id="generate-token" class="primary-button full-button" type="submit"><span aria-hidden="true">＋</span>Generar token seguro</button>
                            <p class="security-note"><span aria-hidden="true">◇</span> Passport administra las credenciales internamente.</p>
                        </form>
                        <div id="generated-token-result" class="token-result" hidden>
                            <div class="result-title"><span class="success-icon" aria-hidden="true">✓</span><div><strong>Token generado y verificado</strong><small id="generated-token-expiry">Vigencia pendiente</small></div></div>
                            <label for="generated-token">Bearer token</label>
                            <textarea id="generated-token" rows="4" readonly></textarea>
                            <div class="token-actions"><button id="copy-generated-token" class="secondary-action" type="button">Copiar token</button><button id="use-generated-token" class="secondary-action" type="button">Usar en esta app</button></div>
                        </div>
                    </article>

                    <article class="connection-card">
                        <div class="card-heading"><span class="card-number">02</span><div><span class="card-kicker">CONEXIÓN ENTRANTE</span><h2>Datos recibidos de MeshiVerse</h2><p>ProyectoAPI comprueba la API pública al abrir esta pantalla. No necesitas pegar credenciales.</p></div></div>
                        <div id="meshiverse-connection" class="automatic-connection loading" role="status" aria-live="polite"><span class="automatic-connection-icon" aria-hidden="true">↔</span><div><strong id="meshiverse-connection-title">Comprobando intercambio…</strong><p id="meshiverse-connection-message">Consultando el estado y los datos públicos de MeshiVerse.</p></div></div>
                        <dl class="connection-data-summary"><div><dt>Servicio</dt><dd id="meshiverse-service">MeshiVerse</dd></div><div><dt>Dato recibido</dt><dd id="meshiverse-resource">Comprobando…</dd></div></dl>
                        <a class="secondary-action meshiverse-view-link" href="/meshiverse">Ver datos recibidos →</a>
                    </article>
                </section>

                <section class="shared-section" aria-labelledby="shared-title">
                    <div class="section-heading"><div><span class="eyebrow">CONTRATO DE DATOS</span><h2 id="shared-title">¿Qué comparte ProyectoAPI?</h2></div><span class="limited-badge">Solo con token</span></div>
                    <div class="shared-grid">
                        <article class="share-card"><span class="share-icon" aria-hidden="true">▦</span><div class="share-content"><span class="method-label get">GET</span><h3>Hamas públicos</h3><p>Datos y patrón completo; los proyectos privados quedan fuera.</p><code>/api/v1/hamas</code><small><strong id="shared-hama-count">—</strong> diseños públicos · scope: hamas.read</small></div></article>
                        <article class="share-card"><span class="share-icon color-share-icon" aria-hidden="true">●</span><div class="share-content"><span class="method-label get">GET</span><h3>Colores compartidos</h3><p>Tonos y cantidades usados únicamente en los Hamas públicos.</p><code>/api/v1/colors</code><small><strong id="shared-color-count">—</strong> colores · scope: hamas.read</small></div></article>
                        <article class="share-card"><span class="share-icon" aria-hidden="true">✦</span><div class="share-content"><span class="method-label post">POST</span><h3>Crear un Hama público</h3><p>Recibe un patrón desde otra aplicación y lo publica en la galería compartida.</p><code>/api/v1/hamas</code><small>nombre · colores · patrón · scope: hamas.write</small></div></article>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="viewer-toast" class="toast" role="status" aria-live="polite" aria-atomic="true"></div>
</body>
</html>
