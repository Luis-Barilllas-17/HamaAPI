<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Visor de endpoints de la API Hama Creation">
    <title>Respuesta API | Hama Creation</title>
    <link rel="stylesheet" href="{{ asset('css/hama.css') }}">
    <link rel="stylesheet" href="{{ asset('css/api-viewer.css') }}">
    <script src="{{ asset('js/api-viewer.js') }}" defer></script>
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <a class="brand" href="/" aria-label="Inicio de Hama Creation">
                <span class="brand-mark" aria-hidden="true"><i></i><i></i></span>
                <span>
                    <strong>HAMA CREATION</strong>
                    <small>ProyectoAPI</small>
                </span>
            </a>

            <nav class="sidebar-nav" aria-label="Navegación principal">
                <span class="nav-label">Colección</span>
                <a class="nav-item" href="/hamas">
                    <span class="nav-icon" aria-hidden="true">▦</span>
                    Patrones Hama
                </a>

                <span class="nav-label nav-label-spaced">Desarrollo</span>
                <a class="nav-item active" href="/respuesta" aria-current="page">
                    <span class="nav-icon nav-icon-code" aria-hidden="true">{ }</span>
                    Respuesta API
                </a>
            </nav>

            <div class="sidebar-footer">
                <span class="connection-dot" aria-hidden="true"></span>
                <div>
                    <strong>API disponible</strong>
                    <small>MySQL · ProyectoAPI</small>
                </div>
            </div>
        </aside>

        <div class="content-area">
            <header class="mobile-header">
                <a class="brand" href="/">
                    <span class="brand-mark" aria-hidden="true"><i></i><i></i></span>
                    <strong>HAMA CREATION</strong>
                </a>
                <a class="mobile-back-link" href="/hamas">← Patrones</a>
            </header>

            <main class="main-content api-viewer-page">
                <div class="page-header viewer-page-header">
                    <div>
                        <span class="eyebrow">LARAVEL API</span>
                        <h1>Respuesta de Hamas</h1>
                        <p>Visualiza el JSON que consumirás desde Postman.</p>
                    </div>
                    <button id="refresh-response" class="primary-button" type="button">
                        <span aria-hidden="true">↻</span>
                        Actualizar
                    </button>
                </div>

                <section class="methods-strip" aria-label="Métodos disponibles">
                    <span><b class="get">GET</b> Consultar</span>
                    <span><b class="post">POST</b> Crear</span>
                    <span><b class="put">PUT</b> Actualizar</span>
                    <span><b class="delete">DELETE</b> Eliminar</span>
                </section>

                <section class="response-panel" aria-labelledby="response-title">
                    <div class="request-bar">
                        <div class="request-endpoint">
                            <span class="method-badge">GET</span>
                            <code>/api/hamas</code>
                        </div>
                        <span id="status-badge" class="response-status loading">Consultando...</span>
                    </div>

                    <div class="response-heading">
                        <div>
                            <h2 id="response-title">Cuerpo de la respuesta</h2>
                            <div class="response-meta">
                                <span>Formato <strong id="response-type">JSON</strong></span>
                                <span>Tiempo <strong id="response-time">—</strong></span>
                                <span>Registros <strong id="response-records">—</strong></span>
                            </div>
                        </div>
                        <button id="copy-response" class="copy-button" type="button" disabled>Copiar JSON</button>
                    </div>

                    <div class="code-window">
                        <div class="code-window-bar" aria-hidden="true">
                            <span></span><span></span><span></span>
                            <small>GET /api/hamas</small>
                        </div>
                        <pre id="json-response" tabindex="0" aria-live="polite">Cargando respuesta...</pre>
                    </div>
                </section>

                <aside class="viewer-note">
                    <span aria-hidden="true">i</span>
                    <p>Este visor utiliza únicamente <strong>GET</strong>. La demostración completa de POST, GET, PUT y DELETE debe realizarse en Postman, como indica la tarea.</p>
                </aside>
            </main>
        </div>
    </div>

    <div id="viewer-toast" class="toast" role="status" aria-live="polite" aria-atomic="true"></div>
</body>
</html>
