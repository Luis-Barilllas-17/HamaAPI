const inventoryApiUrl = '/api/v1/inventory-colors';

const state = {
    colors: [],
    editingId: null,
    pendingDeleteId: null,
    detectedColor: '#48B978',
};

const elements = {
    grid: document.querySelector('#colors-grid'),
    search: document.querySelector('#color-search'),
    status: document.querySelector('#inventory-status'),
    empty: document.querySelector('#inventory-empty'),
    totalBeads: document.querySelector('#inventory-total-beads'),
    colorCount: document.querySelector('#inventory-color-count'),
    addButton: document.querySelector('#add-color-button'),
    emptyAddButton: document.querySelector('#empty-add-color'),
    scanButton: document.querySelector('#scan-color-button'),
    colorDialog: document.querySelector('#color-dialog'),
    colorDialogTitle: document.querySelector('#color-dialog-title'),
    colorForm: document.querySelector('#color-form'),
    colorId: document.querySelector('#color-id'),
    colorName: document.querySelector('#color-name'),
    colorHex: document.querySelector('#color-hex'),
    colorPicker: document.querySelector('#color-picker'),
    colorQuantity: document.querySelector('#color-quantity'),
    colorFormError: document.querySelector('#color-form-error'),
    saveColorButton: document.querySelector('#save-color-button'),
    scannerDialog: document.querySelector('#scanner-dialog'),
    colorPhoto: document.querySelector('#color-photo'),
    scannerPreview: document.querySelector('#scanner-preview'),
    scannerCanvas: document.querySelector('#scanner-canvas'),
    scannerForm: document.querySelector('#scanner-form'),
    scannerName: document.querySelector('#scanner-name'),
    scannerQuantity: document.querySelector('#scanner-quantity'),
    detectedSwatch: document.querySelector('#detected-swatch'),
    detectedHex: document.querySelector('#detected-hex'),
    scannerFormError: document.querySelector('#scanner-form-error'),
    saveScannedButton: document.querySelector('#save-scanned-color'),
    deleteDialog: document.querySelector('#delete-color-dialog'),
    deleteCopy: document.querySelector('#delete-color-copy'),
    cancelDelete: document.querySelector('#cancel-color-delete'),
    confirmDelete: document.querySelector('#confirm-color-delete'),
    toast: document.querySelector('#toast'),
};

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
        const firstValidationError = Object.values(data.errors || {}).flat()[0];
        throw new Error(firstValidationError || data.message || 'No fue posible actualizar el inventario.');
    }

    return data;
}

async function loadInventory() {
    try {
        const response = await request(inventoryApiUrl);
        state.colors = Array.isArray(response.data) ? response.data : [];
        render();
    } catch (error) {
        elements.status.textContent = 'No fue posible conectar con el inventario';
        showToast(error.message, true);
    }
}

function render() {
    const term = elements.search.value.trim().toLocaleLowerCase('es');
    const filtered = state.colors.filter((item) => `${item.nombre} ${item.hex_color}`.toLocaleLowerCase('es').includes(term));
    const totalBeads = state.colors.reduce((total, item) => total + Number(item.cantidad || 0), 0);

    elements.totalBeads.textContent = totalBeads.toLocaleString('es-GT');
    elements.colorCount.textContent = state.colors.length.toLocaleString('es-GT');
    elements.status.textContent = term
        ? `${filtered.length} coincidencia${filtered.length === 1 ? '' : 's'}`
        : `${state.colors.length} color${state.colors.length === 1 ? '' : 'es'} registrado${state.colors.length === 1 ? '' : 's'}`;
    elements.grid.replaceChildren();

    if (filtered.length === 0) {
        const hasColors = state.colors.length > 0;
        elements.empty.hidden = false;
        elements.empty.querySelector('h3').textContent = hasColors ? 'No encontramos ese color' : 'Tu caja de colores está vacía';
        elements.empty.querySelector('p').textContent = hasColors
            ? 'Prueba buscando otro nombre o código hexadecimal.'
            : 'Agrega tu primer paquete de cuentas o escanea una foto para reconocer su tono.';
        elements.emptyAddButton.hidden = hasColors;
        return;
    }

    elements.empty.hidden = true;
    filtered.forEach((item) => elements.grid.append(createColorCard(item)));
}

function createColorCard(item) {
    const card = document.createElement('article');
    const color = sanitizeColor(item.hex_color);
    const quantity = Number(item.cantidad || 0);
    const updated = item.updated_at
        ? new Intl.DateTimeFormat('es-GT', { dateStyle: 'medium' }).format(new Date(item.updated_at))
        : 'Hoy';

    card.className = 'color-inventory-card personal-color-card';
    card.innerHTML = `
        <div class="inventory-swatch" style="--inventory-color:${color}"><i></i></div>
        <div class="inventory-color-copy">
            <div class="inventory-color-heading">
                <div><strong>${escapeHtml(item.nombre)}</strong><span>${color}</span></div>
                <b>${quantity.toLocaleString('es-GT')}</b>
            </div>
            <p class="inventory-quantity-label">cuentas disponibles</p>
            <div class="inventory-card-footer">
                <small>Actualizado ${escapeHtml(updated)}</small>
                <div>
                    <button type="button" data-edit-color="${Number(item.id)}">Editar</button>
                    <button type="button" class="delete" data-delete-color="${Number(item.id)}">Eliminar</button>
                </div>
            </div>
        </div>`;

    return card;
}

function openColorDialog(item = null) {
    state.editingId = item ? Number(item.id) : null;
    elements.colorDialogTitle.textContent = item ? 'Editar color' : 'Agregar color';
    elements.colorId.value = item?.id || '';
    elements.colorName.value = item?.nombre || '';
    elements.colorHex.value = sanitizeColor(item?.hex_color || '#48B978');
    elements.colorPicker.value = elements.colorHex.value;
    elements.colorQuantity.value = Number(item?.cantidad || 0);
    hideFormError(elements.colorFormError);
    openModal(elements.colorDialog, () => elements.colorName.focus());
}

async function saveColor(event) {
    event.preventDefault();
    hideFormError(elements.colorFormError);

    const payload = {
        nombre: elements.colorName.value.trim(),
        hex_color: sanitizeColor(elements.colorHex.value),
        cantidad: Number(elements.colorQuantity.value),
    };
    const url = state.editingId ? `${inventoryApiUrl}/${state.editingId}` : inventoryApiUrl;
    const method = state.editingId ? 'PATCH' : 'POST';

    try {
        elements.saveColorButton.disabled = true;
        const response = await request(url, { method, body: JSON.stringify(payload) });
        const index = state.colors.findIndex((item) => Number(item.id) === Number(response.data.id));

        if (index >= 0) state.colors[index] = response.data;
        else state.colors.push(response.data);

        closeModal(elements.colorDialog);
        render();
        showToast(state.editingId ? 'Color actualizado correctamente.' : 'Color agregado a tu inventario.');
    } catch (error) {
        showFormError(elements.colorFormError, error.message);
    } finally {
        elements.saveColorButton.disabled = false;
    }
}

function askDelete(id) {
    const item = state.colors.find((color) => Number(color.id) === Number(id));
    if (!item) return;

    state.pendingDeleteId = Number(item.id);
    elements.deleteCopy.textContent = `“${item.nombre}” dejará de aparecer en tu inventario personal.`;
    openModal(elements.deleteDialog, () => elements.cancelDelete.focus());
}

async function confirmDelete() {
    if (!state.pendingDeleteId) return;

    try {
        elements.confirmDelete.disabled = true;
        await request(`${inventoryApiUrl}/${state.pendingDeleteId}`, { method: 'DELETE' });
        state.colors = state.colors.filter((item) => Number(item.id) !== state.pendingDeleteId);
        closeModal(elements.deleteDialog);
        state.pendingDeleteId = null;
        render();
        showToast('Color eliminado del inventario.');
    } catch (error) {
        showToast(error.message, true);
    } finally {
        elements.confirmDelete.disabled = false;
    }
}

function openScanner() {
    elements.colorPhoto.value = '';
    elements.scannerPreview.hidden = true;
    elements.scannerForm.hidden = true;
    elements.scannerName.value = 'Color escaneado';
    elements.scannerQuantity.value = '0';
    hideFormError(elements.scannerFormError);
    openModal(elements.scannerDialog, () => elements.colorPhoto.focus());
}

async function scanPhoto(file) {
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        showToast('Selecciona una imagen válida.', true);
        return;
    }
    if (file.size > 12 * 1024 * 1024) {
        showToast('La imagen no puede superar 12 MB.', true);
        return;
    }

    const objectUrl = URL.createObjectURL(file);
    const image = new Image();

    image.onload = () => {
        const maximumWidth = 900;
        const maximumHeight = 560;
        const scale = Math.min(1, maximumWidth / image.naturalWidth, maximumHeight / image.naturalHeight);
        const canvas = elements.scannerCanvas;
        canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
        canvas.getContext('2d', { willReadFrequently: true }).drawImage(image, 0, 0, canvas.width, canvas.height);
        URL.revokeObjectURL(objectUrl);

        setDetectedColor(findDominantColor(canvas));
        elements.scannerName.value = nearestColorName(state.detectedColor);
        elements.scannerPreview.hidden = false;
        elements.scannerForm.hidden = false;
    };
    image.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        showToast('No fue posible leer esa imagen.', true);
    };
    image.src = objectUrl;
}

function findDominantColor(canvas) {
    const context = canvas.getContext('2d', { willReadFrequently: true });
    const { data } = context.getImageData(0, 0, canvas.width, canvas.height);
    const buckets = new Map();
    const step = Math.max(1, Math.floor(Math.sqrt((canvas.width * canvas.height) / 18000)));

    for (let y = 0; y < canvas.height; y += step) {
        for (let x = 0; x < canvas.width; x += step) {
            const index = (y * canvas.width + x) * 4;
            const red = data[index];
            const green = data[index + 1];
            const blue = data[index + 2];
            const alpha = data[index + 3];
            const maximum = Math.max(red, green, blue);
            const minimum = Math.min(red, green, blue);
            const saturation = maximum === 0 ? 0 : (maximum - minimum) / maximum;
            const brightness = (red + green + blue) / 3;

            if (alpha < 220 || brightness > 245 || brightness < 18 || saturation < 0.14) continue;

            const quantized = [red, green, blue].map((channel) => Math.min(255, Math.round(channel / 24) * 24));
            const key = quantized.join(',');
            const current = buckets.get(key) || { count: 0, score: 0, red: 0, green: 0, blue: 0 };
            current.count += 1;
            current.score += 0.6 + saturation;
            current.red += red;
            current.green += green;
            current.blue += blue;
            buckets.set(key, current);
        }
    }

    const winner = [...buckets.values()].sort((a, b) => b.score - a.score)[0];
    if (!winner) return '#48B978';

    return rgbToHex(
        Math.round(winner.red / winner.count),
        Math.round(winner.green / winner.count),
        Math.round(winner.blue / winner.count),
    );
}

function sampleCanvasColor(event) {
    const canvas = elements.scannerCanvas;
    const rectangle = canvas.getBoundingClientRect();
    const x = Math.min(canvas.width - 1, Math.max(0, Math.floor((event.clientX - rectangle.left) * canvas.width / rectangle.width)));
    const y = Math.min(canvas.height - 1, Math.max(0, Math.floor((event.clientY - rectangle.top) * canvas.height / rectangle.height)));
    const pixel = canvas.getContext('2d', { willReadFrequently: true }).getImageData(x, y, 1, 1).data;
    setDetectedColor(rgbToHex(pixel[0], pixel[1], pixel[2]));
    elements.scannerName.value = nearestColorName(state.detectedColor);
}

function setDetectedColor(color) {
    state.detectedColor = sanitizeColor(color);
    elements.detectedHex.textContent = state.detectedColor;
    elements.detectedSwatch.style.setProperty('--detected-color', state.detectedColor);
}

async function saveScannedColor(event) {
    event.preventDefault();
    hideFormError(elements.scannerFormError);

    const payload = {
        nombre: elements.scannerName.value.trim(),
        hex_color: state.detectedColor,
        cantidad: Number(elements.scannerQuantity.value),
    };

    try {
        elements.saveScannedButton.disabled = true;
        const response = await request(inventoryApiUrl, { method: 'POST', body: JSON.stringify(payload) });
        state.colors.push(response.data);
        closeModal(elements.scannerDialog);
        render();
        showToast('Color detectado y guardado en tu inventario.');
    } catch (error) {
        showFormError(elements.scannerFormError, error.message);
    } finally {
        elements.saveScannedButton.disabled = false;
    }
}

function nearestColorName(hex) {
    const palette = [
        ['Negro', '#202523'], ['Blanco crema', '#F0EEDB'], ['Verde bosque', '#387D58'],
        ['Verde menta', '#7BC9A1'], ['Turquesa', '#258D8D'], ['Azul cielo', '#64A8D8'],
        ['Azul profundo', '#34547A'], ['Morado', '#7956A8'], ['Violeta', '#A88BD6'],
        ['Rosa', '#D887AF'], ['Rojo coral', '#D96262'], ['Naranja', '#E89B46'],
        ['Amarillo', '#E5C94F'], ['Marrón', '#805B45'], ['Beige', '#D6C39D'],
        ['Gris', '#868D89'],
    ];
    const target = hexToRgb(hex);
    let closest = palette[0];
    let distance = Number.POSITIVE_INFINITY;

    palette.forEach((entry) => {
        const sample = hexToRgb(entry[1]);
        const nextDistance = ((target.red - sample.red) ** 2) + ((target.green - sample.green) ** 2) + ((target.blue - sample.blue) ** 2);
        if (nextDistance < distance) {
            closest = entry;
            distance = nextDistance;
        }
    });

    return closest[0];
}

function openModal(modal, afterOpen) {
    modal.hidden = false;
    document.body.classList.add('overlay-open');
    window.requestAnimationFrame(() => modal.classList.add('is-open'));
    if (afterOpen) window.setTimeout(afterOpen, 120);
}

function closeModal(modal) {
    modal.classList.remove('is-open');
    document.body.classList.remove('overlay-open');
    window.setTimeout(() => { modal.hidden = true; }, 170);
}

function showFormError(element, message) {
    element.textContent = message;
    element.hidden = false;
}

function hideFormError(element) {
    element.textContent = '';
    element.hidden = true;
}

function sanitizeColor(color) {
    const normalized = String(color || '').trim().toUpperCase();
    return /^#[0-9A-F]{6}$/.test(normalized) ? normalized : '#48B978';
}

function rgbToHex(red, green, blue) {
    return `#${[red, green, blue].map((channel) => Math.max(0, Math.min(255, channel)).toString(16).padStart(2, '0')).join('')}`.toUpperCase();
}

function hexToRgb(hex) {
    const normalized = sanitizeColor(hex).slice(1);
    return {
        red: Number.parseInt(normalized.slice(0, 2), 16),
        green: Number.parseInt(normalized.slice(2, 4), 16),
        blue: Number.parseInt(normalized.slice(4, 6), 16),
    };
}

function escapeHtml(value) {
    const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(value ?? '').replace(/[&<>"']/g, (character) => entities[character]);
}

function showToast(message, isError = false) {
    window.clearTimeout(toastTimer);
    elements.toast.textContent = message;
    elements.toast.classList.toggle('error', isError);
    elements.toast.classList.add('show');
    toastTimer = window.setTimeout(() => elements.toast.classList.remove('show'), 3200);
}

elements.addButton.addEventListener('click', () => openColorDialog());
elements.emptyAddButton.addEventListener('click', () => openColorDialog());
elements.scanButton.addEventListener('click', openScanner);
elements.search.addEventListener('input', render);
elements.colorForm.addEventListener('submit', saveColor);
elements.scannerForm.addEventListener('submit', saveScannedColor);
elements.colorPhoto.addEventListener('change', (event) => scanPhoto(event.target.files?.[0]));
elements.scannerCanvas.addEventListener('click', sampleCanvasColor);

elements.colorHex.addEventListener('input', () => {
    if (/^#[0-9A-Fa-f]{6}$/.test(elements.colorHex.value)) elements.colorPicker.value = elements.colorHex.value;
});
elements.colorPicker.addEventListener('input', () => { elements.colorHex.value = elements.colorPicker.value.toUpperCase(); });

elements.grid.addEventListener('click', (event) => {
    const editButton = event.target.closest('[data-edit-color]');
    const deleteButton = event.target.closest('[data-delete-color]');
    if (editButton) openColorDialog(state.colors.find((item) => Number(item.id) === Number(editButton.dataset.editColor)));
    if (deleteButton) askDelete(deleteButton.dataset.deleteColor);
});

document.querySelectorAll('[data-close-color]').forEach((button) => button.addEventListener('click', () => closeModal(elements.colorDialog)));
document.querySelectorAll('[data-close-scanner]').forEach((button) => button.addEventListener('click', () => closeModal(elements.scannerDialog)));
elements.cancelDelete.addEventListener('click', () => closeModal(elements.deleteDialog));
elements.confirmDelete.addEventListener('click', confirmDelete);

[elements.colorDialog, elements.scannerDialog, elements.deleteDialog].forEach((modal) => {
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal(modal);
    });
});

window.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    [elements.colorDialog, elements.scannerDialog, elements.deleteDialog]
        .filter((modal) => !modal.hidden)
        .forEach(closeModal);
});

loadInventory();
