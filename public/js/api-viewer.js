const TOKEN_STORAGE_KEY = 'proyectoapi.passport_access_token';

const elements = {
    generatorForm: document.querySelector('#token-generator-form'),
    destinationProject: document.querySelector('#destination-project'),
    scopeRead: document.querySelector('#scope-read'),
    scopeWrite: document.querySelector('#scope-write'),
    generatedResult: document.querySelector('#generated-token-result'),
    generatedToken: document.querySelector('#generated-token'),
    generatedExpiry: document.querySelector('#generated-token-expiry'),
    copyToken: document.querySelector('#copy-generated-token'),
    useToken: document.querySelector('#use-generated-token'),
    globalStatus: document.querySelector('#global-connection-status'),
    meshiverseConnection: document.querySelector('#meshiverse-connection'),
    meshiverseTitle: document.querySelector('#meshiverse-connection-title'),
    meshiverseMessage: document.querySelector('#meshiverse-connection-message'),
    meshiverseService: document.querySelector('#meshiverse-service'),
    meshiverseResource: document.querySelector('#meshiverse-resource'),
    sharedHamaCount: document.querySelector('#shared-hama-count'),
    sharedColorCount: document.querySelector('#shared-color-count'),
    toast: document.querySelector('#viewer-toast'),
};

let activeToken = '';
let toastTimer;

async function requestJson(url, options = {}) {
    const response = await fetch(url, options);
    const raw = await response.text();
    let data = {};

    if (raw) {
        try {
            data = JSON.parse(raw);
        } catch (error) {
            data = { message: raw };
        }
    }

    if (!response.ok) {
        const validationMessage = data.errors ? Object.values(data.errors).flat().join(' ') : '';
        const message = data.error_description || validationMessage || data.message || `Solicitud rechazada (${response.status}).`;
        const requestError = new Error(message);
        requestError.status = response.status;
        throw requestError;
    }

    return data;
}

function selectedScopes() {
    return [elements.scopeRead, elements.scopeWrite]
        .filter((input) => input.checked)
        .map((input) => input.value);
}

async function generateToken(event) {
    event.preventDefault();

    const projectName = elements.destinationProject.value.trim();
    const scopes = selectedScopes();

    if (!projectName) {
        showToast('Escribe el nombre del proyecto que recibirá el token.', true);
        return;
    }

    if (scopes.length === 0) {
        showToast('Selecciona por lo menos un permiso.', true);
        return;
    }

    const button = elements.generatorForm.querySelector('button[type="submit"]');
    setButtonBusy(button, true, 'Generando…');

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const data = await requestJson('/conexiones/token', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ project_name: projectName, scopes }),
        });

        activeToken = normalizeToken(data.access_token);
        elements.generatedToken.value = activeToken;
        elements.generatedExpiry.textContent = expiryCopy(data.expires_in);
        elements.generatedResult.hidden = false;

        await verifySharedResources(activeToken, scopes);
        showToast(`Token listo y verificado para ${projectName}.`);
        elements.generatedResult.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } catch (error) {
        showToast(`No se pudo generar: ${error.message}`, true);
    } finally {
        setButtonBusy(button, false);
    }
}

async function verifySharedResources(token, scopes) {
    if (!scopes.includes('hamas.read')) {
        elements.sharedHamaCount.textContent = 'Sin permiso';
        elements.sharedColorCount.textContent = 'Sin permiso';
        return;
    }

    const headers = { Accept: 'application/json', Authorization: `Bearer ${token}` };
    const [hamas, colors] = await Promise.all([
        requestJson('/api/v1/hamas', { headers }),
        requestJson('/api/v1/colors', { headers }),
    ]);

    elements.sharedHamaCount.textContent = String(hamas.meta?.total ?? hamas.data?.length ?? 0);
    elements.sharedColorCount.textContent = String(colors.meta?.count ?? colors.data?.length ?? 0);
}

async function loadMeshiverseStatus() {
    try {
        const data = await requestJson('/meshiverse/status', { headers: { Accept: 'application/json' } });
        const dishTitle = data.dish?.title || 'Sin platillo disponible';

        elements.meshiverseService.textContent = `${data.service || 'MeshiVerse'} · ${data.version || 'v1'}`;
        elements.meshiverseResource.textContent = dishTitle;
        elements.meshiverseMessage.textContent = data.message || 'La API externa respondió correctamente.';

        if (data.connected && data.dish_available) {
            setAutomaticConnection('success', 'Datos recibidos correctamente');
            setGlobalState('connected', 'Intercambio activo');
            return;
        }

        if (data.connected) {
            setAutomaticConnection('warning', 'API conectada, sin datos disponibles');
            setGlobalState('connected', 'MeshiVerse conectado');
            return;
        }

        setAutomaticConnection('error', 'No se están recibiendo datos');
        setGlobalState('error', 'Intercambio detenido');
        elements.meshiverseResource.textContent = 'Sin datos';
    } catch (error) {
        elements.meshiverseMessage.textContent = 'ProyectoAPI no pudo completar la comprobación automática.';
        elements.meshiverseResource.textContent = 'Sin datos';
        setAutomaticConnection('error', 'No se están recibiendo datos');
        setGlobalState('error', 'Intercambio detenido');
    }
}

function setAutomaticConnection(state, title) {
    elements.meshiverseConnection.className = `automatic-connection ${state}`;
    elements.meshiverseTitle.textContent = title;
}

function setGlobalState(state, message) {
    elements.globalStatus.className = `connection-state ${state}`;
    const indicator = document.createElement('i');
    indicator.setAttribute('aria-hidden', 'true');
    elements.globalStatus.replaceChildren(indicator, document.createTextNode(message));
}

function normalizeToken(value) {
    return String(value || '').trim().replace(/^Bearer\s+/i, '');
}

function expiryCopy(seconds) {
    if (!Number.isFinite(Number(seconds))) {
        return 'Token verificado y listo para compartir';
    }

    const expiration = new Date(Date.now() + Number(seconds) * 1000);
    return `Válido hasta ${formatDate(expiration.toISOString())}`;
}

function formatDate(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return 'fecha no informada';
    }

    return new Intl.DateTimeFormat('es-GT', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

function setButtonBusy(button, busy, busyLabel = 'Procesando…') {
    if (busy) {
        button.dataset.originalLabel = button.textContent.trim();
        button.textContent = busyLabel;
        button.disabled = true;
        return;
    }

    button.textContent = button.dataset.originalLabel || button.textContent;
    button.disabled = false;
}

async function copyText(value) {
    if (!value) return;

    try {
        await navigator.clipboard.writeText(value);
    } catch (error) {
        const helper = document.createElement('textarea');
        helper.value = value;
        helper.style.position = 'fixed';
        helper.style.opacity = '0';
        document.body.append(helper);
        helper.select();
        document.execCommand('copy');
        helper.remove();
    }

    showToast('Token copiado. Ya puedes entregarlo al otro proyecto.');
}

function showToast(message, isError = false) {
    window.clearTimeout(toastTimer);
    elements.toast.textContent = message;
    elements.toast.classList.toggle('error', isError);
    elements.toast.classList.add('show');
    toastTimer = window.setTimeout(() => elements.toast.classList.remove('show'), 3200);
}

elements.generatorForm.addEventListener('submit', generateToken);
elements.copyToken.addEventListener('click', () => copyText(activeToken));
elements.useToken.addEventListener('click', () => {
    if (!activeToken) return;
    sessionStorage.setItem(TOKEN_STORAGE_KEY, activeToken);
    showToast('Token habilitado en esta sesión y listo para el CRUD.');
});

loadMeshiverseStatus();
