<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Editor creativo de patrones Hama 50 por 50">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crear Hama | Hama Creation</title>
    <x-hama-favicon />
    <link rel="stylesheet" href="{{ asset('css/hama.css') }}?v=11">
    <script src="{{ asset('js/passport-access.js') }}?v=3" defer></script>
    <script src="{{ asset('js/hama-editor.js') }}?v=3" defer></script>
</head>
<body data-auto-passport="true">
    <div class="app-layout">
        <x-hama-sidebar active="create" />

        <div class="content-area">
            <main class="main-content creator-page">
                <header class="page-header creator-page-header">
                    <div>
                        <span id="page-mode" class="eyebrow">NUEVO LIENZO · 50 × 50</span>
                        <h1 id="editor-page-title">Crea algo que se sienta tuyo.</h1>
                        <p id="editor-page-copy">Elige un color, pinta cuenta por cuenta o usa relleno para dar forma a tu idea.</p>
                    </div>
                    <a class="quiet-link" href="/proyectos">← Volver a proyectos</a>
                </header>

                <form id="hama-form" class="creator-workspace" novalidate>
                    <input id="hama-id" type="hidden">
                    <input id="ancho" name="ancho" type="hidden" value="50">
                    <input id="alto" name="alto" type="hidden" value="50">
                    <input id="cantidad_cuentas" name="cantidad_cuentas" type="hidden" value="0">

                    <section class="canvas-card" aria-labelledby="pattern-editor-title">
                        <div class="canvas-card-header">
                            <div><span class="section-kicker">LIENZO</span><h2 id="pattern-editor-title">Tu patrón</h2><p>Haz clic o arrastra. Cada círculo representa una cuenta Hama.</p></div>
                            <div class="editor-history">
                                <button id="undo-pattern" type="button" disabled aria-label="Deshacer" title="Deshacer (Ctrl+Z)">↶</button>
                                <button id="redo-pattern" type="button" disabled aria-label="Rehacer" title="Rehacer (Ctrl+Y)">↷</button>
                                <span><b id="canvas-counter">0</b> / 2500</span>
                            </div>
                        </div>

                        <div class="drawing-tools creative-tools" aria-label="Herramientas de dibujo">
                            <button class="tool-button selected" type="button" data-tool="brush"><span aria-hidden="true">✎</span>Pincel</button>
                            <button class="tool-button" type="button" data-tool="fill"><span aria-hidden="true">▣</span>Relleno</button>
                            <button class="tool-button" type="button" data-tool="eraser"><span aria-hidden="true">◇</span>Borrador</button>
                            <button id="clear-grid" class="tool-button danger" type="button"><span aria-hidden="true">×</span>Limpiar</button>
                        </div>

                        <div class="canvas-scroll">
                            <div class="canvas-stage">
                                <div class="axis-top" aria-hidden="true"><span>0</span><span>10</span><span>20</span><span>30</span><span>40</span><span>50</span></div>
                                <div class="axis-left" aria-hidden="true"><span>0</span><span>10</span><span>20</span><span>30</span><span>40</span><span>50</span></div>
                                <div id="hama-canvas" class="hama-canvas" role="grid" tabindex="0" aria-label="Tablero Hama de 50 por 50 celdas"></div>
                            </div>
                        </div>
                        <small class="field-error canvas-error" id="patron-error"></small>

                        <div class="canvas-card-footer">
                            <div><strong>¿Tu diseño ya está listo?</strong><small>Agrega sus detalles y guárdalo en tu colección.</small></div>
                            <button id="open-save-panel" class="primary-button canvas-save-button" type="button"><x-hama-icon name="save" /> Guardar Hama</button>
                        </div>
                    </section>

                    <div id="details-overlay" class="drawer-overlay" hidden>
                        <aside id="details-drawer" class="studio-panel details-drawer" role="dialog" aria-modal="true" aria-labelledby="details-drawer-title">
                            <header class="drawer-header">
                                <div><span class="section-kicker">DETALLES</span><h2 id="details-drawer-title">Dale identidad</h2><p>Estos datos aparecerán en la galería y en la API.</p></div>
                                <button id="close-details" class="drawer-close" type="button" aria-label="Cerrar detalles">×</button>
                            </header>

                            <div class="drawer-content">
                                <div class="field-group">
                                    <label for="nombre">Nombre del proyecto <span>*</span></label>
                                    <input id="nombre" name="nombre" type="text" maxlength="255" placeholder="Ej. Jardín de luna" autocomplete="off" required>
                                    <small class="field-error" id="nombre-error"></small>
                                </div>

                                <div class="field-group">
                                    <label for="descripcion">La idea detrás <em>Opcional</em></label>
                                    <textarea id="descripcion" name="descripcion" rows="3" placeholder="Cuéntanos qué inspiró este patrón"></textarea>
                                    <small class="field-error" id="descripcion-error"></small>
                                </div>

                                <fieldset class="field-group visibility-field-group">
                                    <legend>Visibilidad del proyecto</legend>
                                    <div class="visibility-options">
                                        <label class="visibility-option">
                                            <input type="radio" name="es_publico" value="1" checked>
                                            <span class="visibility-option-icon public" aria-hidden="true">◉</span>
                                            <span><strong>Público</strong><small>Podrá aparecer en la API compartida.</small></span>
                                        </label>
                                        <label class="visibility-option">
                                            <input type="radio" name="es_publico" value="0">
                                            <span class="visibility-option-icon private" aria-hidden="true">●</span>
                                            <span><strong>Privado</strong><small>Solo será visible dentro de tu estudio.</small></span>
                                        </label>
                                    </div>
                                    <small class="field-error" id="es_publico-error"></small>
                                </fieldset>

                                <div class="field-group color-field-group">
                                    <label for="color_principal">Color activo <span>*</span></label>
                                    <div class="active-color-row">
                                        <input id="color_principal" name="color_principal" type="color" value="#8B78D7" required>
                                        <div><strong>Estás pintando con</strong><code id="color-value">#8B78D7</code></div>
                                    </div>
                                    <div class="quick-colors" aria-label="Paleta sugerida">
                                        <button type="button" data-color="#2F3142" style="--quick-color:#2F3142" aria-label="Carbón suave"></button>
                                        <button type="button" data-color="#8B78D7" style="--quick-color:#8B78D7" aria-label="Lavanda"></button>
                                        <button type="button" data-color="#B99FE8" style="--quick-color:#B99FE8" aria-label="Lila"></button>
                                        <button type="button" data-color="#FFFDF7" style="--quick-color:#FFFDF7" aria-label="Marfil"></button>
                                        <button type="button" data-color="#F6C56F" style="--quick-color:#F6C56F" aria-label="Miel"></button>
                                        <button type="button" data-color="#EF8D8D" style="--quick-color:#EF8D8D" aria-label="Coral"></button>
                                        <button type="button" data-color="#78C7A5" style="--quick-color:#78C7A5" aria-label="Menta"></button>
                                        <button type="button" data-color="#78AEE5" style="--quick-color:#78AEE5" aria-label="Cielo"></button>
                                    </div>
                                    <small class="field-error" id="color_principal-error"></small>
                                </div>

                                <div class="live-preview-card">
                                    <div><span class="section-kicker">VISTA PREVIA</span><strong>Tu diseño en miniatura</strong><small>Así aparecerá dentro de Proyectos.</small></div>
                                    <canvas id="live-preview" width="250" height="250" aria-label="Vista previa del patrón actual"></canvas>
                                </div>

                                <div class="pattern-metrics">
                                    <div><span>Tablero</span><strong>50 × 50</strong></div>
                                    <div><span>Cuentas colocadas</span><strong id="painted-count">0</strong></div>
                                </div>
                                <small class="field-error pattern-error" id="cantidad_cuentas-error"></small>
                            </div>

                            <footer class="drawer-footer">
                                <button id="submit-button" class="primary-button creator-save" type="submit"><x-hama-icon name="save" /><span id="submit-label">Guardar proyecto</span></button>
                                <p id="editor-status" class="save-note">Se guardará en MySQL y, si es público, quedará disponible en la API.</p>
                            </footer>
                        </aside>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <div id="clear-confirmation" class="modal-overlay" hidden>
        <section class="confirmation-card" role="dialog" aria-modal="true" aria-labelledby="clear-confirmation-title">
            <span class="confirmation-icon" aria-hidden="true">◇</span>
            <span class="section-kicker">LIMPIAR LIENZO</span>
            <h2 id="clear-confirmation-title">¿Quieres empezar de nuevo?</h2>
            <p>Se quitarán las cuentas del tablero. Puedes deshacer la acción después si cambias de idea.</p>
            <div class="confirmation-actions">
                <button id="cancel-clear" class="cancel-button" type="button">Seguir dibujando</button>
                <button id="confirm-clear" class="danger-button" type="button">Limpiar lienzo</button>
            </div>
        </section>
    </div>

    <div id="toast" class="toast" role="status" aria-live="polite" aria-atomic="true"></div>
</body>
</html>
