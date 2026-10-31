const apiUrl = '/api/v1/hamas';
const gridSize = 50;
const totalCells = gridSize * gridSize;

const state = {
    editingId: null,
    pattern: new Map(),
    activeTool: 'brush',
    isPainting: false,
    actionSnapshot: null,
    undoStack: [],
    redoStack: [],
};

const elements = {
    form: document.querySelector('#hama-form'),
    hamaId: document.querySelector('#hama-id'),
    name: document.querySelector('#nombre'),
    description: document.querySelector('#descripcion'),
    visibilityInputs: [...document.querySelectorAll('input[name="es_publico"]')],
    countInput: document.querySelector('#cantidad_cuentas'),
    paintedCount: document.querySelector('#painted-count'),
    canvasCounter: document.querySelector('#canvas-counter'),
    canvas: document.querySelector('#hama-canvas'),
    livePreview: document.querySelector('#live-preview'),
    clear: document.querySelector('#clear-grid'),
    undo: document.querySelector('#undo-pattern'),
    redo: document.querySelector('#redo-pattern'),
    color: document.querySelector('#color_principal'),
    colorValue: document.querySelector('#color-value'),
    openSavePanel: document.querySelector('#open-save-panel'),
    detailsOverlay: document.querySelector('#details-overlay'),
    closeDetails: document.querySelector('#close-details'),
    clearConfirmation: document.querySelector('#clear-confirmation'),
    cancelClear: document.querySelector('#cancel-clear'),
    confirmClear: document.querySelector('#confirm-clear'),
    submit: document.querySelector('#submit-button'),
    submitLabel: document.querySelector('#submit-label'),
    pageMode: document.querySelector('#page-mode'),
    pageTitle: document.querySelector('#editor-page-title'),
    pageCopy: document.querySelector('#editor-page-copy'),
    editorStatus: document.querySelector('#editor-status'),
    toast: document.querySelector('#toast'),
};

let previewFrame;
let toastTimer;

async function request(url, options = {}) {
    const response = await window.PassportAccess.authorizedFetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...options.headers,
        },
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(data.message || 'No se pudo completar la solicitud.');
        error.status = response.status;
        error.data = data;
        throw error;
    }

    return data;
}

function buildCanvas() {
    const fragment = document.createDocumentFragment();
    for (let index = 0; index < totalCells; index += 1) {
        const cell = document.createElement('button');
        const row = Math.floor(index / gridSize) + 1;
        const column = (index % gridSize) + 1;
        cell.type = 'button';
        cell.className = 'canvas-cell';
        cell.dataset.index = String(index);
        cell.setAttribute('role', 'gridcell');
        cell.setAttribute('aria-label', `Fila ${row}, columna ${column}, vacía`);
        fragment.append(cell);
    }
    elements.canvas.append(fragment);
}

function applyTool(index) {
    if (!Number.isInteger(index) || index < 0 || index >= totalCells) return;

    if (state.activeTool === 'fill') {
        fillArea(index);
        return;
    }

    if (state.activeTool === 'eraser') {
        if (!state.pattern.has(index)) return;
        state.pattern.delete(index);
    } else {
        const color = sanitizeColor(elements.color.value);
        if (state.pattern.get(index) === color) return;
        state.pattern.set(index, color);
    }

    renderCell(index);
    updateCount();
    clearFieldError('patron');
    clearFieldError('cantidad_cuentas');
}

function fillArea(startIndex) {
    const target = state.pattern.get(startIndex) ?? null;
    const replacement = sanitizeColor(elements.color.value);
    if (target === replacement) return;

    const pending = [startIndex];
    const visited = new Set();
    while (pending.length) {
        const index = pending.pop();
        if (visited.has(index) || (state.pattern.get(index) ?? null) !== target) continue;
        visited.add(index);
        state.pattern.set(index, replacement);

        const row = Math.floor(index / gridSize);
        const column = index % gridSize;
        if (column > 0) pending.push(index - 1);
        if (column < gridSize - 1) pending.push(index + 1);
        if (row > 0) pending.push(index - gridSize);
        if (row < gridSize - 1) pending.push(index + gridSize);
    }
    renderCanvas();
    clearFieldError('patron');
    clearFieldError('cantidad_cuentas');
}

function renderCell(index) {
    const cell = elements.canvas.children[index];
    const color = state.pattern.get(index);
    if (!cell) return;

    cell.classList.toggle('painted', Boolean(color));
    if (color) {
        cell.style.setProperty('--cell-color', color);
        cell.setAttribute('aria-label', `${cell.getAttribute('aria-label').replace(/, (vacía|color .+)$/, '')}, color ${color}`);
    } else {
        cell.style.removeProperty('--cell-color');
        cell.setAttribute('aria-label', `${cell.getAttribute('aria-label').replace(/, (vacía|color .+)$/, '')}, vacía`);
    }
}

function renderCanvas() {
    for (let index = 0; index < totalCells; index += 1) renderCell(index);
    updateCount();
}

function updateCount() {
    const count = state.pattern.size;
    elements.countInput.value = String(count);
    elements.paintedCount.textContent = count.toLocaleString('es-GT');
    elements.canvasCounter.textContent = count.toLocaleString('es-GT');
    schedulePreview();
}

function schedulePreview() {
    if (previewFrame) return;
    previewFrame = window.requestAnimationFrame(() => {
        drawPreview(elements.livePreview, state.pattern);
        previewFrame = null;
    });
}

function drawPreview(canvas, pattern) {
    const context = canvas.getContext('2d');
    const size = canvas.width / gridSize;
    context.fillStyle = '#F6F0FA';
    context.fillRect(0, 0, canvas.width, canvas.height);

    pattern.forEach((color, position) => {
        const x = position % gridSize;
        const y = Math.floor(position / gridSize);
        const centerX = (x * size) + (size / 2);
        const centerY = (y * size) + (size / 2);
        context.beginPath();
        context.arc(centerX, centerY, size * 0.46, 0, Math.PI * 2);
        context.fillStyle = color;
        context.fill();
        context.beginPath();
        context.arc(centerX, centerY, Math.max(0.75, size * 0.14), 0, Math.PI * 2);
        context.fillStyle = '#F6F0FA';
        context.fill();
    });
}

function beginAction() {
    if (state.actionSnapshot === null) state.actionSnapshot = [...state.pattern.entries()];
}

function commitAction() {
    if (state.actionSnapshot === null) return;
    if (JSON.stringify(state.actionSnapshot) !== JSON.stringify([...state.pattern.entries()])) {
        state.undoStack.push(state.actionSnapshot);
        state.undoStack = state.undoStack.slice(-60);
        state.redoStack = [];
    }
    state.actionSnapshot = null;
    updateHistory();
}

function restore(snapshot) {
    state.pattern = new Map(snapshot);
    renderCanvas();
}

function undo() {
    const snapshot = state.undoStack.pop();
    if (!snapshot) return;
    state.redoStack.push([...state.pattern.entries()]);
    restore(snapshot);
    updateHistory();
}

function redo() {
    const snapshot = state.redoStack.pop();
    if (!snapshot) return;
    state.undoStack.push([...state.pattern.entries()]);
    restore(snapshot);
    updateHistory();
}

function resetHistory() {
    state.actionSnapshot = null;
    state.undoStack = [];
    state.redoStack = [];
    updateHistory();
}

function updateHistory() {
    elements.undo.disabled = state.undoStack.length === 0;
    elements.redo.disabled = state.redoStack.length === 0;
}

function setTool(tool) {
    state.activeTool = ['brush', 'fill', 'eraser'].includes(tool) ? tool : 'brush';
    document.querySelectorAll('.tool-button[data-tool]').forEach((button) => button.classList.toggle('selected', button.dataset.tool === state.activeTool));
    elements.canvas.dataset.tool = state.activeTool;
}

function setColor(color) {
    const safe = sanitizeColor(color);
    elements.color.value = safe;
    elements.colorValue.textContent = safe;
    document.querySelectorAll('.quick-colors button').forEach((button) => button.classList.toggle('selected', button.dataset.color.toUpperCase() === safe));
}

function loadPattern(pattern) {
    state.pattern.clear();
    if (Array.isArray(pattern)) {
        pattern.forEach((bead) => {
            const position = Number(bead?.posicion);
            if (Number.isInteger(position) && position >= 0 && position < totalCells) state.pattern.set(position, sanitizeColor(bead?.color));
        });
    }
    renderCanvas();
    resetHistory();
}

function serializedPattern() {
    return [...state.pattern.entries()]
        .sort(([a], [b]) => a - b)
        .map(([posicion, color]) => ({ posicion, color }));
}

async function loadEditingProject() {
    const id = Number(new URLSearchParams(window.location.search).get('editar'));
    if (!Number.isInteger(id) || id < 1) {
        loadPattern([]);
        setColor('#8B78D7');
        return;
    }

    setSubmitting(true, 'Abriendo proyecto…');
    try {
        const response = await request(`${apiUrl}/${id}`);
        const hama = response.data;
        state.editingId = Number(hama.id);
        elements.hamaId.value = hama.id;
        elements.name.value = hama.nombre;
        elements.description.value = hama.descripcion || '';
        const visibility = hama.es_publico ? '1' : '0';
        elements.visibilityInputs.forEach((input) => { input.checked = input.value === visibility; });
        setColor(hama.color_principal);
        loadPattern(hama.patron);
        elements.pageMode.textContent = 'EDITANDO PROYECTO · 50 × 50';
        elements.pageTitle.textContent = hama.nombre;
        elements.pageCopy.textContent = 'Continúa pintando, prueba otros colores o ajusta la historia de tu diseño.';
        elements.submitLabel.textContent = 'Guardar cambios';
        updateVisibilityNote();
    } catch (error) {
        showToast(error.message, true);
    } finally {
        setSubmitting(false);
    }
}

async function saveProject(event) {
    event.preventDefault();
    clearErrors();

    if (!elements.name.value.trim()) {
        showFieldError('nombre', 'Ponle un nombre a tu proyecto.');
        elements.name.focus();
        return;
    }
    if (state.pattern.size === 0) {
        showFieldError('patron', 'Coloca al menos una cuenta en el lienzo.');
        showFieldError('cantidad_cuentas', 'Tu patrón todavía está vacío.');
        elements.canvas.focus();
        showToast('Dibuja algo antes de guardar.', true);
        return;
    }

    const pattern = serializedPattern();
    const payload = {
        nombre: elements.name.value.trim(),
        descripcion: elements.description.value.trim() || null,
        ancho: gridSize,
        alto: gridSize,
        cantidad_cuentas: pattern.length,
        color_principal: sanitizeColor(elements.color.value),
        es_publico: selectedVisibility(),
        patron: pattern,
    };

    setSubmitting(true, 'Guardando…');
    try {
        const response = await request(state.editingId ? `${apiUrl}/${state.editingId}` : apiUrl, {
            method: state.editingId ? 'PATCH' : 'POST',
            body: JSON.stringify(payload),
        });
        state.editingId = Number(response.data.id);
        elements.hamaId.value = response.data.id;
        window.history.replaceState({}, '', `/crear-hama?editar=${state.editingId}`);
        elements.pageMode.textContent = 'PROYECTO GUARDADO · 50 × 50';
        elements.pageTitle.textContent = response.data.nombre;
        elements.submitLabel.textContent = 'Guardar cambios';
        elements.editorStatus.innerHTML = `${response.data.es_publico ? 'Proyecto público y disponible para compartir.' : 'Proyecto privado, visible solo en tu estudio.'} <a href="/proyectos?guardado=1">Ver en Proyectos →</a>`;
        showToast('Tu Hama quedó guardado en la colección.');
    } catch (error) {
        if (error.status === 422 && error.data?.errors) {
            Object.entries(error.data.errors).forEach(([field, messages]) => showFieldError(field.startsWith('patron.') ? 'patron' : field, messages[0]));
            showToast('Revisa los campos marcados.', true);
        } else {
            showToast(error.message, true);
        }
    } finally {
        setSubmitting(false);
    }
}

function setSubmitting(busy, label) {
    elements.submit.disabled = busy;
    elements.submitLabel.textContent = busy ? label : (state.editingId ? 'Guardar cambios' : 'Guardar proyecto');
}

function showFieldError(field, message) {
    const input = document.getElementById(field);
    const error = document.getElementById(`${field}-error`);
    input?.classList.add('input-error');
    input?.setAttribute('aria-invalid', 'true');
    if (error) error.textContent = message;
}

function clearFieldError(field) {
    const input = document.getElementById(field);
    const error = document.getElementById(`${field}-error`);
    input?.classList.remove('input-error');
    input?.removeAttribute('aria-invalid');
    if (error) error.textContent = '';
}

function clearErrors() {
    ['nombre', 'descripcion', 'cantidad_cuentas', 'color_principal', 'es_publico', 'patron'].forEach(clearFieldError);
}

function selectedVisibility() {
    return elements.visibilityInputs.find((input) => input.checked)?.value === '1';
}

function updateVisibilityNote() {
    elements.editorStatus.textContent = selectedVisibility()
        ? 'Al guardar, este proyecto podrá aparecer en la API compartida.'
        : 'Este proyecto será privado y no se compartirá mediante la API.';
}

function showToast(message, isError = false) {
    window.clearTimeout(toastTimer);
    elements.toast.textContent = message;
    elements.toast.classList.toggle('error', isError);
    elements.toast.classList.add('show');
    toastTimer = window.setTimeout(() => elements.toast.classList.remove('show'), 3200);
}

function openDetails() {
    elements.detailsOverlay.hidden = false;
    document.body.classList.add('overlay-open');
    window.requestAnimationFrame(() => elements.detailsOverlay.classList.add('is-open'));
    window.setTimeout(() => elements.name.focus(), 230);
}

function closeDetails() {
    elements.detailsOverlay.classList.remove('is-open');
    document.body.classList.remove('overlay-open');
    window.setTimeout(() => { elements.detailsOverlay.hidden = true; }, 190);
    elements.openSavePanel.focus();
}

function openClearConfirmation() {
    elements.clearConfirmation.hidden = false;
    document.body.classList.add('overlay-open');
    window.requestAnimationFrame(() => elements.clearConfirmation.classList.add('is-open'));
    window.setTimeout(() => elements.cancelClear.focus(), 120);
}

function closeClearConfirmation() {
    elements.clearConfirmation.classList.remove('is-open');
    document.body.classList.remove('overlay-open');
    window.setTimeout(() => { elements.clearConfirmation.hidden = true; }, 160);
    elements.clear.focus();
}

function clearPattern() {
    beginAction();
    state.pattern.clear();
    renderCanvas();
    commitAction();
    closeClearConfirmation();
    showToast('El lienzo quedó limpio. Puedes deshacer la acción.');
}

function sanitizeColor(color) {
    const normalized = String(color || '').toUpperCase();
    return /^#[0-9A-F]{6}$/.test(normalized) ? normalized : '#8B78D7';
}

buildCanvas();
setTool('brush');
loadEditingProject();

elements.form.addEventListener('submit', saveProject);
elements.openSavePanel.addEventListener('click', openDetails);
elements.closeDetails.addEventListener('click', closeDetails);
elements.detailsOverlay.addEventListener('click', (event) => {
    if (event.target === elements.detailsOverlay) closeDetails();
});
elements.color.addEventListener('input', (event) => setColor(event.target.value));
elements.visibilityInputs.forEach((input) => input.addEventListener('change', updateVisibilityNote));
document.querySelector('.quick-colors').addEventListener('click', (event) => {
    const button = event.target.closest('button[data-color]');
    if (button) setColor(button.dataset.color);
});
document.querySelector('.drawing-tools').addEventListener('click', (event) => {
    const button = event.target.closest('button[data-tool]');
    if (button) setTool(button.dataset.tool);
});
elements.clear.addEventListener('click', () => {
    if (state.pattern.size === 0) {
        showToast('El lienzo ya está vacío.');
        return;
    }
    openClearConfirmation();
});
elements.cancelClear.addEventListener('click', closeClearConfirmation);
elements.confirmClear.addEventListener('click', clearPattern);
elements.clearConfirmation.addEventListener('click', (event) => {
    if (event.target === elements.clearConfirmation) closeClearConfirmation();
});
elements.undo.addEventListener('click', undo);
elements.redo.addEventListener('click', redo);
elements.canvas.addEventListener('pointerdown', (event) => {
    const cell = event.target.closest('.canvas-cell');
    if (!cell) return;
    beginAction();
    applyTool(Number(cell.dataset.index));
    if (state.activeTool === 'fill') commitAction();
    else state.isPainting = true;
    event.preventDefault();
});
elements.canvas.addEventListener('pointermove', (event) => {
    if (!state.isPainting) return;
    const hovered = document.elementFromPoint(event.clientX, event.clientY)?.closest('.canvas-cell');
    if (hovered && elements.canvas.contains(hovered)) applyTool(Number(hovered.dataset.index));
    event.preventDefault();
});
elements.canvas.addEventListener('click', (event) => {
    if (event.detail !== 0) return;
    const cell = event.target.closest('.canvas-cell');
    if (cell) { beginAction(); applyTool(Number(cell.dataset.index)); commitAction(); }
});
window.addEventListener('pointerup', () => {
    if (state.isPainting) commitAction();
    state.isPainting = false;
});
window.addEventListener('pointercancel', () => {
    if (state.isPainting) commitAction();
    state.isPainting = false;
});
window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        if (!elements.clearConfirmation.hidden) closeClearConfirmation();
        else if (!elements.detailsOverlay.hidden) closeDetails();
        return;
    }
    if (!(event.ctrlKey || event.metaKey)) return;
    if (event.key.toLowerCase() === 'z' && !event.shiftKey) { event.preventDefault(); undo(); }
    if (event.key.toLowerCase() === 'y' || (event.key.toLowerCase() === 'z' && event.shiftKey)) { event.preventDefault(); redo(); }
});
