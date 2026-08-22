<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Administrador de patrones Hama Creation">
    <title>Patrones Hama | ProyectoAPI</title>
    <link rel="stylesheet" href="{{ asset('css/hama.css') }}">
    <script src="{{ asset('js/hamas.js') }}" defer></script>
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
                <a class="nav-item active" href="/hamas" aria-current="page">
                    <span class="nav-icon" aria-hidden="true">▦</span>
                    Patrones Hama
                </a>

                <span class="nav-label nav-label-spaced">Desarrollo</span>
                <a class="nav-item" href="/respuesta">
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
                <span class="mobile-status"><span></span> API activa</span>
            </header>

            <main class="main-content">
                <div class="page-header">
                    <div>
                        <span class="eyebrow">HAMA CREATION</span>
                        <h1>Patrones Hama</h1>
                        <p>Crea y organiza tus diseños de cuentas de colores.</p>
                    </div>
                    <button id="open-create" class="primary-button" type="button">
                        <span aria-hidden="true">＋</span>
                        Crear hama
                    </button>
                </div>

                <section class="summary-grid" aria-label="Resumen de patrones Hama">
                    <article class="summary-card summary-card-featured">
                        <span>Diseños guardados</span>
                        <strong id="total-hamas">0</strong>
                        <small>Patrones en tu colección</small>
                    </article>
                    <article class="summary-card">
                        <span>Cuentas necesarias</span>
                        <strong id="total-beads">0</strong>
                        <small>Total de todos los diseños</small>
                    </article>
                    <article class="summary-card">
                        <span>Colores principales</span>
                        <strong id="total-colors">0</strong>
                        <small>Colores diferentes utilizados</small>
                    </article>
                </section>

                <section class="patterns-panel" aria-labelledby="patterns-title">
                    <div class="panel-toolbar">
                        <div>
                            <h2 id="patterns-title">Mis patrones</h2>
                            <p id="pattern-count">Cargando diseños...</p>
                        </div>
                        <label class="search-field" for="hama-search">
                            <span aria-hidden="true">⌕</span>
                            <input id="hama-search" type="search" placeholder="Buscar patrón" autocomplete="off">
                        </label>
                    </div>

                    <div id="table-status" class="visually-hidden" role="status" aria-live="polite">Cargando patrones...</div>
                    <div id="hamas-grid" class="hamas-grid"></div>

                    <div id="empty-state" class="empty-state" hidden>
                        <div class="empty-beads" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
                        <h3 id="empty-title">Tu colección está vacía</h3>
                        <p id="empty-copy">Crea tu primer patrón Hama y aparecerá aquí.</p>
                        <button id="empty-add-button" class="secondary-button" type="button">Crear primer hama</button>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <dialog id="hama-dialog" class="hama-dialog" aria-labelledby="dialog-title">
        <form id="hama-form" novalidate>
            <div class="dialog-header">
                <div>
                    <span id="dialog-eyebrow">Nuevo patrón</span>
                    <h2 id="dialog-title">Crear hama</h2>
                    <p id="dialog-description">Define los datos principales de tu diseño.</p>
                </div>
                <button id="close-dialog" class="close-button" type="button" aria-label="Cerrar formulario">×</button>
            </div>

            <div class="dialog-body creator-body">
                <input id="hama-id" type="hidden">
                <input id="ancho" name="ancho" type="hidden" value="50">
                <input id="alto" name="alto" type="hidden" value="50">
                <input id="cantidad_cuentas" name="cantidad_cuentas" type="hidden" value="0">

                <div class="creator-layout">
                    <aside class="creator-controls">
                        <div class="field-group">
                            <label for="nombre">Nombre del patrón <span>*</span></label>
                            <input id="nombre" name="nombre" type="text" maxlength="255" placeholder="Ej. Estrella cósmica" autocomplete="off" required>
                            <small class="field-error" id="nombre-error"></small>
                        </div>

                        <div class="field-group">
                            <label for="descripcion">Descripción <em>Opcional</em></label>
                            <textarea id="descripcion" name="descripcion" rows="3" placeholder="Describe la idea o el estilo del patrón"></textarea>
                            <small class="field-error" id="descripcion-error"></small>
                        </div>

                        <div class="pattern-metrics">
                            <div>
                                <span>Tamaño del tablero</span>
                                <strong>50 × 50</strong>
                            </div>
                            <div>
                                <span>Cuentas colocadas</span>
                                <strong id="painted-count">0</strong>
                            </div>
                        </div>
                        <small class="field-error pattern-error" id="cantidad_cuentas-error"></small>

                        <div class="field-group color-field-group">
                            <label for="color_principal">Color activo <span>*</span></label>
                            <div class="active-color-row">
                                <input id="color_principal" name="color_principal" type="color" value="#8B5CF6" required>
                                <div>
                                    <strong>Color seleccionado</strong>
                                    <code id="color-value">#8B5CF6</code>
                                </div>
                            </div>
                            <div class="quick-colors" aria-label="Colores rápidos">
                                <button type="button" data-color="#111111" style="--quick-color:#111111" aria-label="Negro"></button>
                                <button type="button" data-color="#6D28D9" style="--quick-color:#6D28D9" aria-label="Morado"></button>
                                <button type="button" data-color="#8B5CF6" style="--quick-color:#8B5CF6" aria-label="Violeta"></button>
                                <button type="button" data-color="#FFFFFF" style="--quick-color:#FFFFFF" aria-label="Blanco"></button>
                                <button type="button" data-color="#F59E0B" style="--quick-color:#F59E0B" aria-label="Amarillo"></button>
                                <button type="button" data-color="#EF4444" style="--quick-color:#EF4444" aria-label="Rojo"></button>
                                <button type="button" data-color="#22C55E" style="--quick-color:#22C55E" aria-label="Verde"></button>
                                <button type="button" data-color="#3B82F6" style="--quick-color:#3B82F6" aria-label="Azul"></button>
                            </div>
                            <small class="field-error" id="color_principal-error"></small>
                        </div>

                        <div class="drawing-tools" aria-label="Herramientas de dibujo">
                            <button class="tool-button selected" type="button" data-tool="brush">
                                <span aria-hidden="true">✎</span>
                                Pincel
                            </button>
                            <button class="tool-button" type="button" data-tool="eraser">
                                <span aria-hidden="true">◇</span>
                                Borrador
                            </button>
                            <button id="clear-grid" class="tool-button danger" type="button">
                                <span aria-hidden="true">×</span>
                                Limpiar
                            </button>
                        </div>
                    </aside>

                    <section class="pattern-editor" aria-labelledby="pattern-editor-title">
                        <div class="pattern-editor-heading">
                            <div>
                                <h3 id="pattern-editor-title">Dibuja tu patrón</h3>
                                <p>Haz clic o arrastra sobre las celdas para colocar cuentas.</p>
                            </div>
                            <span><b id="canvas-counter">0</b> / 2500</span>
                        </div>

                        <div class="canvas-scroll">
                            <div class="canvas-stage">
                                <div class="axis-top" aria-hidden="true"><span>0</span><span>10</span><span>20</span><span>30</span><span>40</span><span>50</span></div>
                                <div class="axis-left" aria-hidden="true"><span>0</span><span>10</span><span>20</span><span>30</span><span>40</span><span>50</span></div>
                                <div id="hama-canvas" class="hama-canvas" role="grid" tabindex="0" aria-label="Tablero Hama de 50 por 50 celdas"></div>
                            </div>
                        </div>
                        <small class="field-error canvas-error" id="patron-error"></small>
                    </section>
                </div>
            </div>

            <div class="dialog-footer">
                <button id="cancel-dialog" class="cancel-button" type="button">Cancelar</button>
                <button id="submit-button" class="primary-button" type="submit">
                    <span id="submit-label">Guardar hama</span>
                </button>
            </div>
        </form>
    </dialog>

    <div id="toast" class="toast" role="status" aria-live="polite" aria-atomic="true"></div>
</body>
</html>
