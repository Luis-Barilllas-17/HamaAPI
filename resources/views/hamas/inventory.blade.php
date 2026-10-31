<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Inventario personal de cuentas y colores Hama">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inventario de colores | Hama Creation</title>
    <x-hama-favicon />
    <link rel="stylesheet" href="{{ asset('css/hama.css') }}?v=12">
    <script src="{{ asset('js/passport-access.js') }}?v=3" defer></script>
    <script src="{{ asset('js/inventory.js') }}?v=3" defer></script>
</head>
<body data-auto-passport="true">
    <div class="app-layout">
        <x-hama-sidebar active="inventory" />

        <div class="content-area">
            <main class="main-content inventory-page">
                <header class="page-header creative-header inventory-header">
                    <div>
                        <span class="eyebrow">TU CAJA DE CUENTAS</span>
                        <h1>Inventario de colores</h1>
                        <p>Registra los colores Hama que tienes en casa y mantén sus cantidades al día.</p>
                    </div>
                    <div class="inventory-header-actions">
                        <button id="scan-color-button" class="secondary-button inventory-action" type="button">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/><circle cx="12" cy="12" r="4"/></svg>
                            Escanear foto
                        </button>
                        <button id="add-color-button" class="primary-button inventory-action" type="button">
                            <x-hama-icon name="create" /> Agregar color
                        </button>
                    </div>
                </header>

                <section class="inventory-hero" aria-label="Resumen del inventario">
                    <div class="palette-orbit" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
                    <div>
                        <span>CUENTAS DISPONIBLES</span>
                        <strong id="inventory-total-beads">0</strong>
                        <p>Organizadas en <b id="inventory-color-count">0</b> colores de tu colección personal.</p>
                    </div>
                    <div class="inventory-tip">
                        <span>CONSEJO</span>
                        <p>Toma una foto con luz natural. El escáner sugerirá un tono y podrás ajustarlo antes de guardarlo.</p>
                    </div>
                </section>

                <section class="inventory-panel" aria-labelledby="inventory-title">
                    <div class="panel-toolbar">
                        <div>
                            <span class="section-kicker">MIS MATERIALES</span>
                            <h2 id="inventory-title">Colores que tengo</h2>
                            <p id="inventory-status" role="status" aria-live="polite">Cargando inventario…</p>
                        </div>
                        <label class="search-field" for="color-search">
                            <span aria-hidden="true">⌕</span>
                            <input id="color-search" type="search" placeholder="Buscar por nombre o código" autocomplete="off">
                        </label>
                    </div>

                    <div id="colors-grid" class="colors-grid"></div>
                    <div id="inventory-empty" class="empty-state inventory-empty" hidden>
                        <div class="empty-beads" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                        <h3>Tu caja de colores está vacía</h3>
                        <p>Agrega tu primer paquete de cuentas o escanea una foto para reconocer su tono.</p>
                        <button id="empty-add-color" class="secondary-button" type="button">Agregar mi primer color</button>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="color-dialog" class="modal-overlay" hidden>
        <section class="inventory-dialog" role="dialog" aria-modal="true" aria-labelledby="color-dialog-title">
            <header class="inventory-dialog-header">
                <div><span class="section-kicker">INVENTARIO</span><h2 id="color-dialog-title">Agregar color</h2><p>Anota cuántas cuentas tienes disponibles.</p></div>
                <button class="dialog-close" type="button" data-close-color aria-label="Cerrar">×</button>
            </header>
            <form id="color-form" class="inventory-form">
                <input id="color-id" type="hidden">
                <label class="inventory-field" for="color-name"><span>Nombre del color</span><input id="color-name" type="text" maxlength="80" placeholder="Ej. Verde bosque" required></label>
                <div class="inventory-form-row">
                    <label class="inventory-field" for="color-hex"><span>Código hexadecimal</span><input id="color-hex" type="text" value="#48B978" maxlength="7" pattern="#[0-9A-Fa-f]{6}" required></label>
                    <label class="inventory-color-picker" for="color-picker"><span>Vista del color</span><input id="color-picker" type="color" value="#48B978"></label>
                </div>
                <label class="inventory-field" for="color-quantity"><span>Cantidad de cuentas</span><input id="color-quantity" type="number" min="0" max="10000000" step="1" value="0" required></label>
                <p id="color-form-error" class="form-error" role="alert" hidden></p>
                <div class="inventory-dialog-actions">
                    <button class="cancel-button" type="button" data-close-color>Cancelar</button>
                    <button id="save-color-button" class="primary-button" type="submit">Guardar en inventario</button>
                </div>
            </form>
        </section>
    </div>

    <div id="scanner-dialog" class="modal-overlay" hidden>
        <section class="inventory-dialog scanner-dialog" role="dialog" aria-modal="true" aria-labelledby="scanner-dialog-title">
            <header class="inventory-dialog-header">
                <div><span class="section-kicker">ESCÁNER DE COLOR</span><h2 id="scanner-dialog-title">Detectar desde una foto</h2><p>La imagen se procesa en tu navegador y no se sube al servidor.</p></div>
                <button class="dialog-close" type="button" data-close-scanner aria-label="Cerrar">×</button>
            </header>

            <div class="scanner-content">
                <label class="photo-dropzone" for="color-photo">
                    <input id="color-photo" type="file" accept="image/*">
                    <span class="photo-dropzone-icon" aria-hidden="true">◎</span>
                    <strong>Elegir una foto</strong>
                    <small>JPG, PNG o WEBP · mejor con luz natural</small>
                </label>

                <div id="scanner-preview" class="scanner-preview" hidden>
                    <canvas id="scanner-canvas" aria-label="Vista previa de la fotografía. Haz clic sobre una cuenta para tomar su color."></canvas>
                    <p>Haz clic sobre una cuenta para afinar el tono detectado.</p>
                </div>

                <form id="scanner-form" class="inventory-form scanner-result" hidden>
                    <div class="detected-color-card">
                        <span id="detected-swatch" aria-hidden="true"></span>
                        <div><small>TONO DETECTADO</small><strong id="detected-hex">#48B978</strong></div>
                    </div>
                    <label class="inventory-field" for="scanner-name"><span>Nombre para tu inventario</span><input id="scanner-name" type="text" maxlength="80" value="Color escaneado" required></label>
                    <label class="inventory-field" for="scanner-quantity"><span>Cantidad de cuentas</span><input id="scanner-quantity" type="number" min="0" max="10000000" step="1" value="0" required></label>
                    <p id="scanner-form-error" class="form-error" role="alert" hidden></p>
                    <div class="inventory-dialog-actions">
                        <button class="cancel-button" type="button" data-close-scanner>Cancelar</button>
                        <button id="save-scanned-color" class="primary-button" type="submit">Guardar color detectado</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <div id="delete-color-dialog" class="modal-overlay" hidden>
        <section class="confirmation-card" role="dialog" aria-modal="true" aria-labelledby="delete-color-title">
            <span class="confirmation-icon danger" aria-hidden="true">×</span>
            <span class="section-kicker">CONFIRMAR ACCIÓN</span>
            <h2 id="delete-color-title">¿Quitar este color?</h2>
            <p id="delete-color-copy">Dejará de aparecer en tu inventario personal.</p>
            <div class="confirmation-actions">
                <button id="cancel-color-delete" class="cancel-button" type="button">Conservar color</button>
                <button id="confirm-color-delete" class="danger-button" type="button">Sí, eliminar</button>
            </div>
        </section>
    </div>

    <div id="toast" class="toast" role="status" aria-live="polite" aria-atomic="true"></div>
</body>
</html>
