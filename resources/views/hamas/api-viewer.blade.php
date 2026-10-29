<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Centro de conexiones OAuth2 de la API Hama Creation">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Conexiones API | Hama Creation</title>
    <link rel="stylesheet" href="{{ asset('css/hama.css') }}">
    <link rel="stylesheet" href="{{ asset('css/api-viewer.css') }}?v=5">
    <script src="{{ asset('js/api-viewer.js') }}?v=5" defer></script>
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <a class="brand" href="/" aria-label="Inicio de Hama Creation">
                <span class="brand-mark" aria-hidden="true"><i></i><i></i></span>
                <span><strong>HAMA CREATION</strong><small>ProyectoAPI</small></span>
            </a>
            <nav class="sidebar-nav" aria-label="Navegación principal">
                <span class="nav-label">Colección</span>
                <a class="nav-item" href="/hamas"><span class="nav-icon" aria-hidden="true">◇</span>Patrones Hama</a>
                <span class="nav-label nav-label-spaced">Desarrollo</span>
                <a class="nav-item active" href="/conexiones" aria-current="page"><span class="nav-icon nav-icon-code" aria-hidden="true">{ }</span>Conexiones API</a>
            </nav>
            <div class="sidebar-footer">
                <span class="connection-dot" aria-hidden="true"></span>
                <div><strong>Passport activo</strong><small>OAuth2 · API v1</small></div>
            </div>
        </aside>

        <div class="content-area">
            <header class="mobile-header">
                <a class="brand" href="/"><span class="brand-mark" aria-hidden="true"><i></i><i></i></span><strong>HAMA CREATION</strong></a>
                <a class="mobile-back-link" href="/hamas">← Patrones</a>
            </header>

            <main class="main-content connections-page">
                <header class="connections-header">
                    <div>
                        <span class="eyebrow">PASSPORT · OAUTH2</span>
                        <h1>Conexión entre proyectos</h1>
                        <p>Genera accesos, valida la conexión y controla exactamente qué puede compartir ProyectoAPI.</p>
                    </div>
                    <span id="global-connection-status" class="connection-state waiting"><i aria-hidden="true"></i>Esperando una prueba</span>
                </header>

                <section class="connection-overview" aria-label="Resumen de seguridad">
                    <article><span>Protocolo</span><strong>OAuth 2.0</strong><small>Client Credentials</small></article>
                    <article><span>Protección</span><strong>Laravel Passport</strong><small>Bearer token</small></article>
                    <article><span>API compartida</span><strong>Versión 1</strong><small>/api/v1</small></article>
                </section>

                <section class="connection-grid" aria-label="Administración de tokens">
                    <article class="connection-card">
                        <div class="card-heading">
                            <span class="card-number">01</span>
                            <div><span class="card-kicker">TOKEN DE PROYECTOAPI</span><h2>Generar token para compartir</h2><p>Este token se entrega a MeshiVerse para que pueda consultar o crear proyectos Hama.</p></div>
                        </div>
                        <form id="token-generator-form" class="connection-form" novalidate>
                            <label class="form-field" for="destination-project"><span>Proyecto que recibirá el token</span><input id="destination-project" type="text" maxlength="100" value="Proyecto externo" autocomplete="off" required></label>
                            <fieldset class="scope-picker">
                                <legend>¿Qué podrá hacer?</legend>
                                <label class="scope-option">
                                    <input id="scope-read" type="checkbox" value="hamas.read" checked>
                                    <span class="scope-check" aria-hidden="true">✓</span>
                                    <span><strong>Ver proyectos Hama</strong><small>Consultar diseños y sus vistas previas.</small></span>
                                </label>
                                <label class="scope-option">
                                    <input id="scope-write" type="checkbox" value="hamas.write" checked>
                                    <span class="scope-check" aria-hidden="true">✓</span>
                                    <span><strong>Crear un Hama</strong><small>Enviar y guardar un patrón completo de 50 × 50.</small></span>
                                </label>
                            </fieldset>
                            <button id="generate-token" class="primary-button full-button" type="submit"><span aria-hidden="true">＋</span>Generar token seguro</button>
                            <p class="security-note"><span aria-hidden="true">◇</span> No necesitas Client ID ni Client Secret. Laravel Passport los administra en el servidor.</p>
                        </form>
                        <div id="generated-token-result" class="token-result" hidden>
                            <div class="result-title"><span class="success-icon" aria-hidden="true">✓</span><div><strong>Token generado</strong><small id="generated-token-expiry">Vigencia pendiente</small></div></div>
                            <label for="generated-token">Bearer token</label>
                            <textarea id="generated-token" rows="4" readonly></textarea>
                            <div class="token-actions">
                                <button id="copy-generated-token" class="secondary-action" type="button">Copiar token</button>
                                <button id="use-generated-token" class="secondary-action" type="button">Usar en esta app</button>
                                <button id="test-generated-token" class="primary-button compact-button" type="button">Probar conexión</button>
                            </div>
                        </div>
                    </article>

                    <article class="connection-card">
                        <div class="card-heading">
                            <span class="card-number">02</span>
                            <div><span class="card-kicker">TOKEN DEL OTRO PROYECTO</span><h2>Conectar con MeshiVerse</h2><p>Aquí debes pegar el token generado en MeshiVerse. ProyectoAPI detectará automáticamente su endpoint protegido.</p></div>
                        </div>
                        <form id="external-api-form" class="connection-form" novalidate>
                            <label class="form-field" for="external-handshake-url"><span>URL base de MeshiVerse</span><input id="external-handshake-url" type="url" value="http://127.0.0.1:8001" placeholder="http://127.0.0.1:8001" autocomplete="off" required></label>
                            <label class="form-field" for="external-token"><span>Token generado por MeshiVerse</span><textarea id="external-token" rows="6" placeholder="Pega aquí el nuevo token Bearer de MeshiVerse…" spellcheck="false" required></textarea></label>
                            <button class="primary-button full-button" type="submit"><span aria-hidden="true">↗</span>Comprobar token de MeshiVerse</button>
                        </form>
                        <div id="external-result" class="inline-result neutral" role="status" aria-live="polite"><span aria-hidden="true">i</span><p>Este cuadro acepta el token del otro proyecto; no pegues aquí el token generado en el cuadro 01.</p></div>
                        <p class="security-note external-guidance"><span aria-hidden="true">◇</span> Primero genera el token en <strong>http://127.0.0.1:8001/api</strong>.</p>
                    </article>
                </section>

                <section class="shared-section" aria-labelledby="shared-title">
                    <div class="section-heading">
                        <div><span class="eyebrow">CONTRATO DE DATOS</span><h2 id="shared-title">¿Qué compartirá ProyectoAPI?</h2></div>
                        <span class="limited-badge">Solo con permiso</span>
                    </div>
                    <div class="shared-grid">
                        <article class="share-card">
                            <span class="share-icon" aria-hidden="true">▦</span>
                            <div class="share-content"><span class="method-label get">GET</span><h3>Proyectos Hama guardados</h3><p>Comparte nombre, descripción, tamaño, cuentas, color principal y el patrón para mostrar su vista previa.</p><code>/api/v1/hamas</code><small><strong id="shared-hama-count">—</strong> registros disponibles después de validar · scope: hamas.read</small></div>
                        </article>
                        <article class="share-card">
                            <span class="share-icon" aria-hidden="true">✦</span>
                            <div class="share-content"><span class="method-label post">POST</span><h3>Crear un Hama 50 × 50</h3><p>Recibe los datos del diseño y su cuadrícula completa para que el otro proyecto pueda crear Hamas.</p><code>/api/v1/hamas</code><small>nombre · descripción · colores · patrón · scope: hamas.write</small></div>
                        </article>
                    </div>
                </section>

                <section id="connection-proof" class="connection-proof" aria-labelledby="proof-title">
                    <div class="proof-heading">
                        <div><span class="eyebrow">COMPROBANTE</span><h2 id="proof-title">Acceso entregado por ProyectoAPI</h2><p id="proof-message">Genera un token en el cuadro 01 y pulsa “Probar conexión” para revisar sus permisos Hama.</p></div>
                        <span id="proof-status" class="proof-status pending">Sin verificar</span>
                    </div>
                    <div class="proof-checks">
                        <article id="check-token" class="proof-check pending"><i>1</i><span><strong>Token válido</strong><small>Firma y vigencia</small></span></article>
                        <article id="check-handshake" class="proof-check pending"><i>2</i><span><strong>API conectada</strong><small>Handshake seguro</small></span></article>
                        <article id="check-read" class="proof-check pending"><i>3</i><span><strong>Consulta de Hamas</strong><small>Permiso hamas.read</small></span></article>
                        <article id="check-write" class="proof-check pending"><i>4</i><span><strong>Creación de Hamas</strong><small>Permiso hamas.write</small></span></article>
                    </div>
                    <dl id="proof-details" class="proof-details" hidden>
                        <div><dt>Proveedor</dt><dd id="proof-provider">—</dd></div>
                        <div><dt>Consumidor</dt><dd id="proof-consumer">—</dd></div>
                        <div><dt>Vence</dt><dd id="proof-expiry">—</dd></div>
                    </dl>
                </section>

            </main>
        </div>
    </div>
    <div id="viewer-toast" class="toast" role="status" aria-live="polite" aria-atomic="true"></div>
</body>
</html>
