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
    testGeneratedToken: document.querySelector('#test-generated-token'),
    externalForm: document.querySelector('#external-api-form'),
    externalUrl: document.querySelector('#external-handshake-url'),
    externalToken: document.querySelector('#external-token'),
    externalResult: document.querySelector('#external-result'),
    globalStatus: document.querySelector('#global-connection-status'),
    proofStatus: document.querySelector('#proof-status'),
    proofMessage: document.querySelector('#proof-message'),
    proofDetails: document.querySelector('#proof-details'),
    proofProvider: document.querySelector('#proof-provider'),
    proofConsumer: document.querySelector('#proof-consumer'),
    proofExpiry: document.querySelector('#proof-expiry'),
    checkToken: document.querySelector('#check-token'),
    checkHandshake: document.querySelector('#check-handshake'),
    checkRead: document.querySelector('#check-read'),
    checkWrite: document.querySelector('#check-write'),
    sharedCount: document.querySelector('#shared-hama-count'),
    toast: document.querySelector('#viewer-toast'),
};

let activeToken = '';
let activeProject = '';
let toastTimer;

function normalizeToken(value) {
    return String(value || '').trim().replace(/^Bearer\s+/i, '');
}

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
        const validationMessage = data.errors
            ? Object.values(data.errors).flat().join(' ')
            : '';
        const message = data.error_description || validationMessage || data.message || `Solicitud rechazada (${response.status}).`;
        const requestError = new Error(message);
        requestError.status = response.status;
        requestError.data = data;
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
            body: JSON.stringify({
                project_name: projectName,
                scopes,
            }),
        });

        activeToken = normalizeToken(data.access_token);
        activeProject = projectName;
        elements.generatedToken.value = activeToken;
        elements.generatedExpiry.textContent = expiryCopy(data.expires_in);
        elements.generatedResult.hidden = false;
        showToast(`Token listo para ${projectName}.`);
        elements.generatedResult.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } catch (error) {
        showToast(`No se pudo generar: ${error.message}`, true);
    } finally {
        setButtonBusy(button, false);
    }
}

function expiryCopy(seconds) {
    if (!Number.isFinite(Number(seconds))) {
        return 'Revisa su vigencia al probar la conexión';
    }

    const expiration = new Date(Date.now() + Number(seconds) * 1000);
    return `Válido hasta ${formatDate(expiration.toISOString())}`;
}

async function testLocalToken(token, projectName, button) {
    const cleanToken = normalizeToken(token);
    const cleanProject = String(projectName || '').trim();

    if (!cleanToken || !cleanProject) {
        showToast('Indica el proyecto y un token para realizar la prueba.', true);
        return;
    }

    if (button) {
        setButtonBusy(button, true, 'Verificando…');
    }
    setGlobalState('waiting', 'Comprobando conexión');

    try {
        const data = await requestJson('/api/v1/connections/handshake', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                Authorization: `Bearer ${cleanToken}`,
            },
            body: JSON.stringify({ project_name: cleanProject }),
        });

        renderHandshake(data);

        const canRead = capabilityAllowed(data, 'hamas.list');
        if (canRead) {
            await verifyHamaList(cleanToken);
        }

        showToast('Conexión autenticada correctamente.');
    } catch (error) {
        renderConnectionError(error);
        showToast('La conexión no pudo autenticarse.', true);
    } finally {
        if (button) {
            setButtonBusy(button, false);
        }
    }
}

async function verifyHamaList(token) {
    try {
        const data = await requestJson('/api/v1/hamas', {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });
        const count = Array.isArray(data.data) ? data.data.length : data.meta?.total;
        if (Number.isFinite(Number(count))) {
            elements.sharedCount.textContent = String(count);
        }
        setProofCheck(elements.checkRead, 'success', '✓');
    } catch (error) {
        setProofCheck(elements.checkRead, 'error', '×');
        elements.checkRead.querySelector('small').textContent = 'La consulta GET falló';
    }
}

function renderHandshake(data) {
    const canRead = capabilityAllowed(data, 'hamas.list');
    const canWrite = capabilityAllowed(data, 'hamas.create');
    const count = data.shared_data?.saved_hamas?.records;

    setGlobalState('connected', 'Conexión confirmada');
    elements.proofStatus.className = 'proof-status success';
    elements.proofStatus.textContent = 'Conectado';
    elements.proofMessage.textContent = data.message || 'El intercambio entre proyectos está autenticado.';
    setProofCheck(elements.checkToken, 'success', '✓');
    setProofCheck(elements.checkHandshake, 'success', '✓');
    setProofCheck(elements.checkRead, canRead ? 'success' : 'denied', canRead ? '✓' : '×');
    setProofCheck(elements.checkWrite, canWrite ? 'success' : 'denied', canWrite ? '✓' : '×');
    elements.checkRead.querySelector('small').textContent = canRead ? 'Permiso hamas.read concedido' : 'Permiso no concedido';
    elements.checkWrite.querySelector('small').textContent = canWrite ? 'Permiso hamas.write concedido' : 'Permiso no concedido';

    if (count !== null && count !== undefined) {
        elements.sharedCount.textContent = String(count);
    } else {
        elements.sharedCount.textContent = 'Sin acceso';
    }

    elements.proofProvider.textContent = data.connection?.provider || 'ProyectoAPI';
    elements.proofConsumer.textContent = data.connection?.consumer || 'Proyecto externo';
    elements.proofExpiry.textContent = formatDate(data.token?.expires_at);
    elements.proofDetails.hidden = false;
    document.querySelector('#connection-proof').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function renderConnectionError(error) {
    setGlobalState('error', 'Conexión rechazada');
    elements.proofStatus.className = 'proof-status error';
    elements.proofStatus.textContent = 'No conectado';
    elements.proofMessage.textContent = error.status === 401
        ? 'El token fue rechazado por Laravel Passport.'
        : error.message;
    setProofCheck(elements.checkToken, 'error', '×');
    setProofCheck(elements.checkHandshake, 'error', '×');
    setProofCheck(elements.checkRead, 'pending', '3');
    setProofCheck(elements.checkWrite, 'pending', '4');
    elements.proofDetails.hidden = true;
    elements.sharedCount.textContent = '—';
}

function capabilityAllowed(data, key) {
    return Array.isArray(data.capabilities)
        && data.capabilities.some((capability) => capability.key === key && capability.allowed === true);
}

function setProofCheck(element, state, icon) {
    element.className = `proof-check ${state}`;
    element.querySelector('i').textContent = icon;
}

function setGlobalState(state, message) {
    elements.globalStatus.className = `connection-state ${state}`;
    const indicator = document.createElement('i');
    indicator.setAttribute('aria-hidden', 'true');
    elements.globalStatus.replaceChildren(indicator, document.createTextNode(message));
}

function renderInlineResult(element, state, message) {
    element.className = `inline-result ${state}`;
    const icon = document.createElement('span');
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = state === 'success' ? '✓' : state === 'error' ? '×' : 'i';
    const copy = document.createElement('p');
    copy.textContent = message;
    element.replaceChildren(icon, copy);
}

async function testExternalApi(event) {
    event.preventDefault();
    const token = normalizeToken(elements.externalToken.value);
    const urlValue = elements.externalUrl.value.trim();
    const button = elements.externalForm.querySelector('button[type="submit"]');
    let url;

    try {
        url = new URL(urlValue);
        if (!['http:', 'https:'].includes(url.protocol)) {
            throw new Error('Protocolo no permitido');
        }
    } catch (error) {
        renderInlineResult(elements.externalResult, 'error', 'Escribe una URL http o https válida.');
        return;
    }

    if (!token) {
        renderInlineResult(elements.externalResult, 'error', 'Pega el token emitido por la API externa.');
        return;
    }

    setButtonBusy(button, true, 'Conectando…');
    renderInlineResult(elements.externalResult, 'neutral', 'Contactando la API externa…');

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const data = await requestJson('/conexiones/probar-externa', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                base_url: url.toString().replace(/\/$/, ''),
                token,
            }),
        });
        const provider = data.connection?.provider || url.host;
        renderInlineResult(elements.externalResult, 'success', `Conexión externa confirmada con ${provider}.`);
        setGlobalState('connected', `Conectado con ${provider}`);
        showToast('La otra API respondió correctamente.');
    } catch (error) {
        const copy = error instanceof TypeError
            ? 'No fue posible comunicarse con ProyectoAPI para realizar la prueba.'
            : error.message;
        renderInlineResult(elements.externalResult, 'error', copy);
        setGlobalState('error', 'Conexión externa rechazada');
    } finally {
        setButtonBusy(button, false);
    }
}

function setButtonBusy(button, busy, busyLabel = 'Procesando…') {
    if (!button) {
        return;
    }

    if (busy) {
        button.dataset.originalLabel = button.textContent.trim();
        button.textContent = busyLabel;
        button.disabled = true;
        return;
    }

    button.textContent = button.dataset.originalLabel || button.textContent;
    button.disabled = false;
}

function formatDate(value) {
    if (!value) {
        return 'No informado';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return 'No informado';
    }

    return new Intl.DateTimeFormat('es-GT', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

async function copyText(value) {
    if (!value) {
        return;
    }

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
    showToast('Token copiado. Compártelo sin enviar el Client Secret.');
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
    if (!activeToken) {
        return;
    }
    sessionStorage.setItem(TOKEN_STORAGE_KEY, activeToken);
    showToast('Token habilitado en esta sesión y listo para el CRUD.');
});
elements.testGeneratedToken.addEventListener('click', (event) => testLocalToken(activeToken, activeProject, event.currentTarget));
elements.externalForm.addEventListener('submit', testExternalApi);
