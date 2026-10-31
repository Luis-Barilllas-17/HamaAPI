<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Galería de creaciones Hama guardadas en ProyectoAPI">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tus creaciones | Hama Creation</title>
    <x-hama-favicon />
    <link rel="stylesheet" href="{{ asset('css/hama.css') }}?v=12">
    <script src="{{ asset('js/passport-access.js') }}?v=3" defer></script>
    <script src="{{ asset('js/projects.js') }}?v=4" defer></script>
</head>
<body data-auto-passport="true">
    <div class="app-layout">
        <x-hama-sidebar active="projects" />

        <div class="content-area">
            <main class="main-content projects-page">
                <header class="art-gallery-intro">
                    <div class="art-gallery-copy">
                        <span class="eyebrow">MI ARTE HAMA</span>
                        <h1>Tus creaciones</h1>
                        <p>Un espacio tranquilo para volver a cada pieza que has imaginado y construido cuenta por cuenta.</p>
                        <a class="primary-button" href="/crear-hama"><span aria-hidden="true">＋</span>Crear una pieza</a>
                    </div>
                </header>

                <section class="patterns-panel" aria-labelledby="projects-title">
                    <h2 id="projects-title" class="visually-hidden">Galería de creaciones</h2>
                    <div class="panel-toolbar gallery-toolbar">
                        <p id="pattern-count">Cargando creaciones…</p>
                        <label class="search-field" for="hama-search"><span aria-hidden="true">⌕</span><input id="hama-search" type="search" placeholder="Buscar una creación" autocomplete="off"></label>
                    </div>

                    <div id="table-status" class="visually-hidden" role="status" aria-live="polite">Cargando proyectos…</div>
                    <div id="hamas-grid" class="hamas-grid"></div>

                    <div id="empty-state" class="empty-state" hidden>
                        <div class="empty-beads" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
                        <h3 id="empty-title">Tu galería está esperando</h3>
                        <p id="empty-copy">Crea tu primer Hama y su vista previa aparecerá aquí.</p>
                        <a id="empty-add-button" class="secondary-button" href="/crear-hama">Crear mi primer diseño</a>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="delete-confirmation" class="modal-overlay" hidden>
        <section class="confirmation-card" role="dialog" aria-modal="true" aria-labelledby="delete-confirmation-title">
            <span class="confirmation-icon danger" aria-hidden="true">×</span>
            <span class="section-kicker">CONFIRMAR ACCIÓN</span>
            <h2 id="delete-confirmation-title">¿Eliminar esta creación?</h2>
            <p id="delete-confirmation-copy">El diseño dejará de aparecer en tu colección.</p>
            <div class="confirmation-actions">
                <button id="cancel-delete" class="cancel-button" type="button">Conservar creación</button>
                <button id="confirm-delete" class="danger-button" type="button">Sí, eliminar</button>
            </div>
        </section>
    </div>
    <div id="toast" class="toast" role="status" aria-live="polite" aria-atomic="true"></div>
</body>
</html>
