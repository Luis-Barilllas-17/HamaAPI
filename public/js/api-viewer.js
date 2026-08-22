const elements = {
    refresh: document.querySelector('#refresh-response'),
    copy: document.querySelector('#copy-response'),
    status: document.querySelector('#status-badge'),
    type: document.querySelector('#response-type'),
    time: document.querySelector('#response-time'),
    records: document.querySelector('#response-records'),
    output: document.querySelector('#json-response'),
    toast: document.querySelector('#viewer-toast'),
};

let formattedResponse = '';
let toastTimer;

async function loadResponse() {
    setLoading(true);
    const startedAt = performance.now();

    try {
        const response = await fetch('/api/hamas', {
            headers: { Accept: 'application/json' },
        });
        const elapsed = Math.round(performance.now() - startedAt);
        const data = await response.json();

        formattedResponse = JSON.stringify(data, null, 2);
        elements.output.textContent = formattedResponse;
        elements.status.textContent = `${response.status} ${response.statusText || 'OK'}`;
        elements.status.className = `response-status${response.ok ? '' : ' error'}`;
        elements.type.textContent = 'JSON';
        elements.time.textContent = `${elapsed} ms`;
        elements.records.textContent = Array.isArray(data.data) ? data.data.length : '—';
        elements.copy.disabled = false;
    } catch (error) {
        formattedResponse = '';
        elements.output.textContent = JSON.stringify({
            success: false,
            message: 'No fue posible obtener la respuesta de la API.',
        }, null, 2);
        elements.status.textContent = 'Error de conexión';
        elements.status.className = 'response-status error';
        elements.type.textContent = '—';
        elements.time.textContent = '—';
        elements.records.textContent = '—';
        elements.copy.disabled = true;
    } finally {
        setLoading(false);
    }
}

function setLoading(isLoading) {
    elements.refresh.disabled = isLoading;

    if (isLoading) {
        elements.status.textContent = 'Consultando...';
        elements.status.className = 'response-status loading';
        elements.output.textContent = 'Cargando respuesta...';
        elements.copy.disabled = true;
    }
}

async function copyResponse() {
    if (!formattedResponse) {
        return;
    }

    try {
        await navigator.clipboard.writeText(formattedResponse);
        showToast('Respuesta JSON copiada.');
    } catch (error) {
        const textArea = document.createElement('textarea');
        textArea.value = formattedResponse;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.append(textArea);
        textArea.select();
        document.execCommand('copy');
        textArea.remove();
        showToast('Respuesta JSON copiada.');
    }
}

function showToast(message) {
    window.clearTimeout(toastTimer);
    elements.toast.textContent = message;
    elements.toast.classList.add('show');

    toastTimer = window.setTimeout(() => {
        elements.toast.classList.remove('show');
    }, 2600);
}

elements.refresh.addEventListener('click', loadResponse);
elements.copy.addEventListener('click', copyResponse);

loadResponse();
