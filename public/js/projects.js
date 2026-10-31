const apiUrl = '/api/v1/hamas';
const gridSize = 50;
const state = { hamas: [], search: '' };

const elements = {
    grid: document.querySelector('#hamas-grid'),
    search: document.querySelector('#hama-search'),
    status: document.querySelector('#table-status'),
    count: document.querySelector('#pattern-count'),
    empty: document.querySelector('#empty-state'),
    emptyTitle: document.querySelector('#empty-title'),
    emptyCopy: document.querySelector('#empty-copy'),
    emptyButton: document.querySelector('#empty-add-button'),
    deleteConfirmation: document.querySelector('#delete-confirmation'),
    deleteCopy: document.querySelector('#delete-confirmation-copy'),
    cancelDelete: document.querySelector('#cancel-delete'),
    confirmDelete: document.querySelector('#confirm-delete'),
    toast: document.querySelector('#toast'),
};

let toastTimer;
let pendingDeleteId = null;

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
        const error = new Error(data.message || 'No fue posible consultar tus creaciones.');
        error.status = response.status;
        throw error;
    }

    return data;
}

async function loadProjects() {
    try {
        const response = await request(apiUrl);
        state.hamas = Array.isArray(response.data) ? response.data : [];
        render();
    } catch (error) {
        elements.count.textContent = 'No fue posible conectar con la API';
        elements.status.textContent = error.message;
        showToast(error.message, true);
    }
}

function render() {
    const term = state.search.trim().toLocaleLowerCase('es');
    const filtered = state.hamas.filter((hama) => `${hama.nombre} ${hama.descripcion || ''}`.toLocaleLowerCase('es').includes(term));

    elements.grid.replaceChildren();
    elements.count.textContent = term
        ? `${filtered.length} coincidencia${filtered.length === 1 ? '' : 's'}`
        : `${state.hamas.length} creación${state.hamas.length === 1 ? '' : 'es'} en tu galería`;
    elements.status.textContent = `Se muestran ${filtered.length} creaciones.`;

    if (filtered.length === 0) {
        elements.empty.hidden = false;
        elements.emptyTitle.textContent = state.hamas.length ? 'No encontramos esa creación' : 'Tu galería está esperando';
        elements.emptyCopy.textContent = state.hamas.length ? 'Prueba con otra palabra o descripción.' : 'Crea tu primer Hama y su vista previa aparecerá aquí.';
        elements.emptyButton.hidden = state.hamas.length > 0;
        return;
    }

    elements.empty.hidden = true;
    filtered.forEach((hama) => elements.grid.append(createCard(hama)));
}

function createCard(hama) {
    const card = document.createElement('article');
    const color = sanitizeColor(hama.color_principal);
    const updated = hama.updated_at ? new Intl.DateTimeFormat('es-GT', { dateStyle: 'medium' }).format(new Date(hama.updated_at)) : 'Sin fecha';

    card.className = 'hama-card';
    card.innerHTML = `
        <div class="bead-preview" style="--bead-color:${color}">
            <span class="project-date">Actualizado ${escapeHtml(updated)}</span>
            <span class="visibility-badge ${hama.es_publico ? 'public' : 'private'}">${hama.es_publico ? 'Público' : 'Privado'}</span>
            <canvas width="300" height="300" aria-label="Vista previa de ${escapeHtml(hama.nombre)}"></canvas>
        </div>
        <div class="hama-card-body">
            <div class="project-title-row"><h3>${escapeHtml(hama.nombre)}</h3><span class="color-swatch" style="--swatch-color:${color}" title="${color}"></span></div>
            <p class="hama-card-description">${escapeHtml(hama.descripcion || 'Una idea lista para seguir creciendo.')}</p>
            <div class="hama-meta"><span>${Number(hama.ancho)} × ${Number(hama.alto)}</span><span class="hama-meta-divider">·</span><span>${Number(hama.cantidad_cuentas).toLocaleString('es-GT')} cuentas</span></div>
            <div class="hama-card-footer">
                <code>${color}</code>
                <div class="card-actions">
                    <a class="action-button" href="/crear-hama?editar=${Number(hama.id)}">Continuar</a>
                    <button class="action-button delete" type="button" data-delete="${Number(hama.id)}">Eliminar</button>
                </div>
            </div>
        </div>`;

    drawPreview(card.querySelector('canvas'), normalizePattern(hama.patron));
    return card;
}

function drawPreview(canvas, pattern) {
    const context = canvas.getContext('2d');
    const size = canvas.width / gridSize;
    context.fillStyle = '#F6F0FA';
    context.fillRect(0, 0, canvas.width, canvas.height);

    pattern.forEach(({ posicion, color }) => {
        const x = posicion % gridSize;
        const y = Math.floor(posicion / gridSize);
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

function normalizePattern(pattern) {
    if (!Array.isArray(pattern)) return [];
    const positions = new Map();
    pattern.forEach((bead) => {
        const position = Number(bead?.posicion);
        if (Number.isInteger(position) && position >= 0 && position < 2500) positions.set(position, sanitizeColor(bead?.color));
    });
    return [...positions].map(([posicion, color]) => ({ posicion, color }));
}

async function deleteProject(id) {
    const hama = state.hamas.find((item) => Number(item.id) === Number(id));
    if (!hama) return;
    pendingDeleteId = Number(hama.id);
    elements.deleteCopy.textContent = `“${hama.nombre}” dejará de aparecer en tu colección. Esta acción no se puede deshacer.`;
    elements.deleteConfirmation.hidden = false;
    document.body.classList.add('overlay-open');
    window.requestAnimationFrame(() => elements.deleteConfirmation.classList.add('is-open'));
    window.setTimeout(() => elements.cancelDelete.focus(), 120);
}

function closeDeleteConfirmation() {
    elements.deleteConfirmation.classList.remove('is-open');
    document.body.classList.remove('overlay-open');
    window.setTimeout(() => { elements.deleteConfirmation.hidden = true; }, 160);
    pendingDeleteId = null;
}

async function confirmDeleteProject() {
    const hama = state.hamas.find((item) => Number(item.id) === pendingDeleteId);
    if (!hama) {
        closeDeleteConfirmation();
        return;
    }

    try {
        elements.confirmDelete.disabled = true;
        await request(`${apiUrl}/${hama.id}`, { method: 'DELETE' });
        state.hamas = state.hamas.filter((item) => Number(item.id) !== Number(hama.id));
        closeDeleteConfirmation();
        render();
        showToast('Proyecto eliminado de la colección.');
    } catch (error) {
        showToast(error.message, true);
    } finally {
        elements.confirmDelete.disabled = false;
    }
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
    return /^#[0-9A-F]{6}$/.test(normalized) ? normalized : '#8B78D7';
}

function escapeHtml(value) {
    const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(value ?? '').replace(/[&<>"']/g, (character) => entities[character]);
}

elements.search.addEventListener('input', (event) => { state.search = event.target.value; render(); });
elements.grid.addEventListener('click', (event) => {
    const button = event.target.closest('[data-delete]');
    if (button) deleteProject(button.dataset.delete);
});
elements.cancelDelete.addEventListener('click', closeDeleteConfirmation);
elements.confirmDelete.addEventListener('click', confirmDeleteProject);
elements.deleteConfirmation.addEventListener('click', (event) => {
    if (event.target === elements.deleteConfirmation) closeDeleteConfirmation();
});
window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !elements.deleteConfirmation.hidden) closeDeleteConfirmation();
});

if (new URLSearchParams(window.location.search).has('guardado')) showToast('Proyecto guardado correctamente.');
loadProjects();
