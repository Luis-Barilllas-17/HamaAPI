const apiUrl = '/api/hamas';
const gridSize = 50;
const totalCells = gridSize * gridSize;

const state = {
    hamas: [],
    editingId: null,
    search: '',
    pattern: new Map(),
    activeTool: 'brush',
    isPainting: false,
};

const elements = {
    dialog: document.querySelector('#hama-dialog'),
    form: document.querySelector('#hama-form'),
    hamaId: document.querySelector('#hama-id'),
    nombre: document.querySelector('#nombre'),
    descripcion: document.querySelector('#descripcion'),
    ancho: document.querySelector('#ancho'),
    alto: document.querySelector('#alto'),
    cantidadCuentas: document.querySelector('#cantidad_cuentas'),
    paintedCount: document.querySelector('#painted-count'),
    canvasCounter: document.querySelector('#canvas-counter'),
    canvas: document.querySelector('#hama-canvas'),
    clearGrid: document.querySelector('#clear-grid'),
    colorPrincipal: document.querySelector('#color_principal'),
    colorValue: document.querySelector('#color-value'),
    dialogEyebrow: document.querySelector('#dialog-eyebrow'),
    dialogTitle: document.querySelector('#dialog-title'),
    dialogDescription: document.querySelector('#dialog-description'),
    submitButton: document.querySelector('#submit-button'),
    submitLabel: document.querySelector('#submit-label'),
    openCreate: document.querySelector('#open-create'),
    emptyAddButton: document.querySelector('#empty-add-button'),
    closeDialog: document.querySelector('#close-dialog'),
    cancelDialog: document.querySelector('#cancel-dialog'),
    search: document.querySelector('#hama-search'),
    grid: document.querySelector('#hamas-grid'),
    tableStatus: document.querySelector('#table-status'),
    emptyState: document.querySelector('#empty-state'),
    emptyTitle: document.querySelector('#empty-title'),
    emptyCopy: document.querySelector('#empty-copy'),
    patternCount: document.querySelector('#pattern-count'),
    totalHamas: document.querySelector('#total-hamas'),
    totalBeads: document.querySelector('#total-beads'),
    totalColors: document.querySelector('#total-colors'),
    toast: document.querySelector('#toast'),
};

const fallbackPatterns = [
    [
        '....#....',
        '...###...',
        '.#######.',
        '..#####..',
        '#########',
        '..#####..',
        '.###.###.',
        '##.....##',
        '.........',
    ],
    [
        '.##...##.',
        '####.####',
        '#########',
        '#########',
        '.#######.',
        '..#####..',
        '...###...',
        '....#....',
        '.........',
    ],
    [
        '..#####..',
        '.#######.',
        '#########',
        '##o###o##',
        '#########',
        '##.###.##',
        '#..#.#..#',
        '#.#.#.#.#',
        '.........',
    ],
];

let toastTimer;

async function request(url, options = {}) {
    const response = await fetch(url, {
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

async function loadHamas({ quiet = false } = {}) {
    if (!quiet) {
        elements.tableStatus.textContent = 'Cargando patrones...';
    }

    try {
        const response = await request(apiUrl);
        state.hamas = Array.isArray(response.data) ? response.data : [];
        render();
    } catch (error) {
        elements.patternCount.textContent = 'No fue posible conectar con la API';
        elements.tableStatus.textContent = 'Error al cargar patrones.';
        showToast(error.message, true);
    }
}

function render() {
    updateSummary();

    const term = state.search.trim().toLocaleLowerCase('es');
    const filteredHamas = state.hamas.filter((hama) => {
        const searchableText = `${hama.nombre} ${hama.descripcion || ''}`.toLocaleLowerCase('es');
        return searchableText.includes(term);
    });

    elements.grid.innerHTML = '';
    elements.patternCount.textContent = term
        ? `${filteredHamas.length} coincidencia${filteredHamas.length === 1 ? '' : 's'}`
        : `${state.hamas.length} diseño${state.hamas.length === 1 ? '' : 's'} guardado${state.hamas.length === 1 ? '' : 's'}`;
    elements.tableStatus.textContent = `Se muestran ${filteredHamas.length} patrones Hama.`;

    if (filteredHamas.length === 0) {
        elements.emptyState.hidden = false;
        elements.emptyTitle.textContent = state.hamas.length === 0 ? 'Tu colección está vacía' : 'No encontramos ese patrón';
        elements.emptyCopy.textContent = state.hamas.length === 0
            ? 'Crea tu primer patrón Hama y aparecerá aquí.'
            : 'Prueba con otro nombre o descripción.';
        elements.emptyAddButton.hidden = state.hamas.length > 0;
        return;
    }

    elements.emptyState.hidden = true;
    filteredHamas.forEach((hama) => elements.grid.append(createHamaCard(hama)));
}

function createHamaCard(hama) {
    const card = document.createElement('article');
    const color = sanitizeColor(hama.color_principal);

    card.className = 'hama-card';
    card.innerHTML = `
        <div class="bead-preview" style="--bead-color: ${color}">
            <canvas width="250" height="250" aria-label="Vista previa de ${escapeHtml(hama.nombre)}"></canvas>
        </div>
        <div class="hama-card-body">
            <h3>${escapeHtml(hama.nombre)}</h3>
            <p class="hama-card-description">${escapeHtml(hama.descripcion || 'Sin descripción')}</p>
            <div class="hama-meta">
                <span>${Number(hama.ancho)} × ${Number(hama.alto)}</span>
                <span class="hama-meta-divider">·</span>
                <span>${Number(hama.cantidad_cuentas).toLocaleString('es-GT')} cuentas</span>
            </div>
            <div class="hama-card-footer">
                <span class="color-label">
                    <i class="color-swatch" style="--swatch-color: ${color}"></i>
                    ${color}
                </span>
                <div class="card-actions">
                    <button class="action-button" type="button" data-action="edit" data-id="${Number(hama.id)}">Editar</button>
                    <button class="action-button delete" type="button" data-action="delete" data-id="${Number(hama.id)}">Eliminar</button>
                </div>
            </div>
        </div>
    `;

    drawCardPreview(card.querySelector('canvas'), hama);

    return card;
}

function drawCardPreview(canvas, hama) {
    const context = canvas.getContext('2d');
    const pattern = normalizePattern(hama.patron);
    const primaryColor = sanitizeColor(hama.color_principal);

    context.fillStyle = '#211329';
    context.fillRect(0, 0, canvas.width, canvas.height);

    if (pattern.length > 0) {
        const cellSize = canvas.width / gridSize;

        pattern.forEach(({ posicion, color }) => {
            const x = posicion % gridSize;
            const y = Math.floor(posicion / gridSize);
            const centerX = (x * cellSize) + (cellSize / 2);
            const centerY = (y * cellSize) + (cellSize / 2);

            context.beginPath();
            context.arc(centerX, centerY, cellSize * 0.44, 0, Math.PI * 2);
            context.fillStyle = color;
            context.fill();
            context.beginPath();
            context.arc(centerX, centerY, cellSize * 0.14, 0, Math.PI * 2);
            context.fillStyle = '#211329';
            context.fill();
        });

        return;
    }

    const fallback = fallbackPatterns[Number(hama.id) % fallbackPatterns.length];
    const beadSize = 20;
    const offset = 35;

    fallback.forEach((row, rowIndex) => {
        [...row].forEach((cell, columnIndex) => {
            if (cell === '.') {
                return;
            }

            const centerX = offset + (columnIndex * beadSize) + (beadSize / 2);
            const centerY = offset + (rowIndex * beadSize) + (beadSize / 2);
            context.beginPath();
            context.arc(centerX, centerY, 8.5, 0, Math.PI * 2);
            context.fillStyle = cell === 'o' ? '#FFFFFF' : primaryColor;
            context.fill();
            context.beginPath();
            context.arc(centerX, centerY, 3, 0, Math.PI * 2);
            context.fillStyle = '#211329';
            context.fill();
        });
    });
}

function updateSummary() {
    const totalBeads = state.hamas.reduce((total, hama) => total + Number(hama.cantidad_cuentas), 0);
    const uniqueColors = new Set(
        state.hamas.flatMap((hama) => {
            const patternColors = normalizePattern(hama.patron).map((bead) => bead.color);
            return patternColors.length > 0 ? patternColors : [sanitizeColor(hama.color_principal)];
        }),
    );

    elements.totalHamas.textContent = state.hamas.length.toLocaleString('es-GT');
    elements.totalBeads.textContent = totalBeads.toLocaleString('es-GT');
    elements.totalColors.textContent = uniqueColors.size.toLocaleString('es-GT');
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

function paintCell(index) {
    if (!Number.isInteger(index) || index < 0 || index >= totalCells) {
        return;
    }

    if (state.activeTool === 'eraser') {
        if (!state.pattern.has(index)) {
            return;
        }

        state.pattern.delete(index);
    } else {
        const color = sanitizeColor(elements.colorPrincipal.value);

        if (state.pattern.get(index) === color) {
            return;
        }

        state.pattern.set(index, color);
    }

    renderCanvasCell(index);
    updateCanvasCount();
    clearFieldError('patron');
    clearFieldError('cantidad_cuentas');
}

function renderCanvasCell(index) {
    const cell = elements.canvas.children[index];
    const color = state.pattern.get(index);

    if (!cell) {
        return;
    }

    cell.classList.toggle('painted', Boolean(color));

    if (color) {
        cell.style.setProperty('--cell-color', color);
        cell.setAttribute('aria-label', `${cell.getAttribute('aria-label').replace(/, (vacía|color .+)$/, '')}, color ${color}`);
    } else {
        cell.style.removeProperty('--cell-color');
        cell.setAttribute('aria-label', `${cell.getAttribute('aria-label').replace(/, (vacía|color .+)$/, '')}, vacía`);
    }
}

function renderEntireCanvas() {
    for (let index = 0; index < totalCells; index += 1) {
        renderCanvasCell(index);
    }

    updateCanvasCount();
}

function updateCanvasCount() {
    const count = state.pattern.size;
    elements.cantidadCuentas.value = String(count);
    elements.paintedCount.textContent = count.toLocaleString('es-GT');
    elements.canvasCounter.textContent = count.toLocaleString('es-GT');
}

function loadPattern(pattern) {
    state.pattern.clear();

    normalizePattern(pattern).forEach(({ posicion, color }) => {
        state.pattern.set(posicion, color);
    });

    renderEntireCanvas();
}

function serializedPattern() {
    return [...state.pattern.entries()]
        .sort(([firstPosition], [secondPosition]) => firstPosition - secondPosition)
        .map(([posicion, color]) => ({ posicion, color }));
}

function normalizePattern(pattern) {
    if (!Array.isArray(pattern)) {
        return [];
    }

    const uniquePositions = new Map();

    pattern.forEach((bead) => {
        const position = Number(bead?.posicion);

        if (Number.isInteger(position) && position >= 0 && position < totalCells) {
            uniquePositions.set(position, sanitizeColor(bead?.color));
        }
    });

    return [...uniquePositions.entries()].map(([posicion, color]) => ({ posicion, color }));
}

function setTool(tool) {
    state.activeTool = tool === 'eraser' ? 'eraser' : 'brush';
    document.querySelectorAll('.tool-button[data-tool]').forEach((button) => {
        button.classList.toggle('selected', button.dataset.tool === state.activeTool);
    });
}

function openCreateDialog() {
    resetForm();
    openDialog();
}

function openDialog() {
    if (typeof elements.dialog.showModal === 'function') {
        elements.dialog.showModal();
    } else {
        elements.dialog.setAttribute('open', '');
    }

    window.setTimeout(() => elements.nombre.focus(), 50);
}

function closeDialog() {
    if (typeof elements.dialog.close === 'function') {
        elements.dialog.close();
    } else {
        elements.dialog.removeAttribute('open');
        resetForm();
    }
}

async function handleSubmit(event) {
    event.preventDefault();
    clearValidationErrors();

    if (state.pattern.size === 0) {
        showFieldError('patron', 'Coloca al menos una cuenta en el tablero.');
        showFieldError('cantidad_cuentas', 'El patrón debe contener al menos una cuenta.');
        elements.canvas.focus();
        showToast('Dibuja algo antes de guardar.', true);
        return;
    }

    setSubmitting(true);

    const pattern = serializedPattern();
    const payload = {
        nombre: elements.nombre.value.trim(),
        descripcion: elements.descripcion.value.trim() || null,
        ancho: gridSize,
        alto: gridSize,
        cantidad_cuentas: pattern.length,
        color_principal: sanitizeColor(elements.colorPrincipal.value),
        patron: pattern,
    };

    const isEditing = state.editingId !== null;

    try {
        await request(isEditing ? `${apiUrl}/${state.editingId}` : apiUrl, {
            method: isEditing ? 'PATCH' : 'POST',
            body: JSON.stringify(payload),
        });

        closeDialog();
        showToast(isEditing ? 'Patrón Hama actualizado.' : 'Patrón Hama creado.');
        await loadHamas({ quiet: true });
    } catch (error) {
        if (error.status === 422 && error.data.errors) {
            showValidationErrors(error.data.errors);
            showToast('Revisa los campos marcados.', true);
        } else {
            showToast(error.message, true);
        }
    } finally {
        setSubmitting(false);
    }
}

function startEditing(hamaId) {
    const hama = state.hamas.find((item) => Number(item.id) === Number(hamaId));

    if (!hama) {
        return;
    }

    state.editingId = Number(hama.id);
    elements.hamaId.value = hama.id;
    elements.nombre.value = hama.nombre;
    elements.descripcion.value = hama.descripcion || '';
    elements.ancho.value = gridSize;
    elements.alto.value = gridSize;
    setColor(hama.color_principal);
    loadPattern(hama.patron);
    elements.dialogEyebrow.textContent = 'Editar patrón';
    elements.dialogTitle.textContent = 'Editar hama';
    elements.dialogDescription.textContent = 'Modifica los datos o continúa pintando tu diseño.';
    elements.submitLabel.textContent = 'Guardar cambios';
    clearValidationErrors();
    openDialog();
}

async function deleteHama(hamaId) {
    const hama = state.hamas.find((item) => Number(item.id) === Number(hamaId));

    if (!hama || !window.confirm(`¿Eliminar "${hama.nombre}"? Esta acción no se puede deshacer.`)) {
        return;
    }

    try {
        await request(`${apiUrl}/${hama.id}`, { method: 'DELETE' });
        showToast('Patrón Hama eliminado.');
        await loadHamas({ quiet: true });
    } catch (error) {
        showToast(error.message, true);
    }
}

function resetForm() {
    state.editingId = null;
    elements.form.reset();
    elements.hamaId.value = '';
    elements.ancho.value = String(gridSize);
    elements.alto.value = String(gridSize);
    loadPattern([]);
    setColor('#8B5CF6');
    setTool('brush');
    elements.dialogEyebrow.textContent = 'Nuevo patrón';
    elements.dialogTitle.textContent = 'Crear hama';
    elements.dialogDescription.textContent = 'Define los datos principales y dibuja tu diseño.';
    elements.submitLabel.textContent = 'Guardar hama';
    clearValidationErrors();
}

function setColor(color) {
    const safeColor = sanitizeColor(color);
    elements.colorPrincipal.value = safeColor;
    elements.colorValue.textContent = safeColor;
    setTool('brush');
    document.querySelectorAll('.quick-colors button').forEach((button) => {
        button.classList.toggle('selected', button.dataset.color.toUpperCase() === safeColor);
    });
}

function showValidationErrors(errors) {
    let firstField;

    Object.entries(errors).forEach(([field, messages]) => {
        const normalizedField = field.startsWith('patron.') ? 'patron' : field;
        firstField ??= normalizedField;
        showFieldError(normalizedField, messages[0]);
    });

    if (firstField === 'patron') {
        elements.canvas.focus();
    } else {
        document.querySelector(`#${firstField}`)?.focus();
    }
}

function showFieldError(field, message) {
    const input = document.getElementById(field);
    const errorElement = document.getElementById(`${field}-error`);

    input?.classList.add('input-error');
    input?.setAttribute('aria-invalid', 'true');

    if (errorElement) {
        errorElement.textContent = message;
    }
}

function clearFieldError(field) {
    const input = document.getElementById(field);
    const errorElement = document.getElementById(`${field}-error`);

    input?.classList.remove('input-error');
    input?.removeAttribute('aria-invalid');

    if (errorElement) {
        errorElement.textContent = '';
    }
}

function clearValidationErrors() {
    ['nombre', 'descripcion', 'ancho', 'alto', 'cantidad_cuentas', 'color_principal', 'patron'].forEach(clearFieldError);
}

function setSubmitting(isSubmitting) {
    elements.submitButton.disabled = isSubmitting;
    elements.submitLabel.textContent = isSubmitting
        ? 'Guardando...'
        : (state.editingId === null ? 'Guardar hama' : 'Guardar cambios');
}

function showToast(message, isError = false) {
    window.clearTimeout(toastTimer);
    elements.toast.textContent = message;
    elements.toast.classList.toggle('error', isError);
    elements.toast.classList.add('show');
    toastTimer = window.setTimeout(() => elements.toast.classList.remove('show'), 3000);
}

function sanitizeColor(color) {
    const normalized = String(color || '').toUpperCase();
    return /^#[0-9A-F]{6}$/.test(normalized) ? normalized : '#8B5CF6';
}

function escapeHtml(value) {
    const entities = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };

    return String(value ?? '').replace(/[&<>"']/g, (character) => entities[character]);
}

buildCanvas();
resetForm();

elements.form.addEventListener('submit', handleSubmit);
elements.openCreate.addEventListener('click', openCreateDialog);
elements.emptyAddButton.addEventListener('click', openCreateDialog);
elements.closeDialog.addEventListener('click', closeDialog);
elements.cancelDialog.addEventListener('click', closeDialog);
elements.dialog.addEventListener('close', resetForm);
elements.dialog.addEventListener('click', (event) => {
    if (event.target === elements.dialog) {
        closeDialog();
    }
});
elements.search.addEventListener('input', (event) => {
    state.search = event.target.value;
    render();
});
elements.colorPrincipal.addEventListener('input', (event) => setColor(event.target.value));
document.querySelector('.quick-colors').addEventListener('click', (event) => {
    const button = event.target.closest('button[data-color]');

    if (button) {
        setColor(button.dataset.color);
    }
});
document.querySelector('.drawing-tools').addEventListener('click', (event) => {
    const toolButton = event.target.closest('button[data-tool]');

    if (toolButton) {
        setTool(toolButton.dataset.tool);
    }
});
elements.clearGrid.addEventListener('click', () => {
    if (state.pattern.size === 0 || window.confirm('¿Limpiar todas las cuentas del tablero?')) {
        loadPattern([]);
    }
});
elements.canvas.addEventListener('pointerdown', (event) => {
    const cell = event.target.closest('.canvas-cell');

    if (!cell) {
        return;
    }

    state.isPainting = true;
    paintCell(Number(cell.dataset.index));
    event.preventDefault();
});
elements.canvas.addEventListener('pointermove', (event) => {
    if (!state.isPainting) {
        return;
    }

    const hoveredElement = document.elementFromPoint(event.clientX, event.clientY);
    const cell = hoveredElement?.closest('.canvas-cell');

    if (cell && elements.canvas.contains(cell)) {
        paintCell(Number(cell.dataset.index));
    }

    event.preventDefault();
});
elements.canvas.addEventListener('click', (event) => {
    if (event.detail !== 0) {
        return;
    }

    const cell = event.target.closest('.canvas-cell');

    if (cell) {
        paintCell(Number(cell.dataset.index));
    }
});
window.addEventListener('pointerup', () => {
    state.isPainting = false;
});
window.addEventListener('pointercancel', () => {
    state.isPainting = false;
});
elements.grid.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');

    if (!button) {
        return;
    }

    if (button.dataset.action === 'edit') {
        startEditing(button.dataset.id);
    }

    if (button.dataset.action === 'delete') {
        deleteHama(button.dataset.id);
    }
});

loadHamas();
