(function () {
    const storageKey = 'proyectoapi.passport_access_token_v2';
    const automaticMode = document.body.dataset.autoPassport === 'true';
    let automaticTokenRequest = null;

    function getToken() {
        return window.sessionStorage.getItem(storageKey)?.trim() || '';
    }

    function saveToken(value) {
        const token = String(value || '').trim().replace(/^Bearer\s+/i, '');

        if (token) window.sessionStorage.setItem(storageKey, token);
        else window.sessionStorage.removeItem(storageKey);

        updateStatus();
        window.dispatchEvent(new CustomEvent('passport:token-changed'));
    }

    function authorizationHeaders(headers = {}) {
        const result = new Headers(headers);
        const token = getToken();
        if (token) result.set('Authorization', `Bearer ${token}`);
        return result;
    }

    async function ensureToken(force = false) {
        if (force) saveToken('');
        if (getToken()) return getToken();
        if (automaticTokenRequest) return automaticTokenRequest;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        automaticTokenRequest = fetch('/session/api-token', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            body: JSON.stringify({}),
        })
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.access_token) {
                    throw new Error(data.message || 'No se pudo preparar el acceso seguro de la interfaz.');
                }
                saveToken(data.access_token);
                return data.access_token;
            })
            .finally(() => { automaticTokenRequest = null; });

        return automaticTokenRequest;
    }

    async function authorizedFetch(url, options = {}) {
        await ready;

        const send = () => fetch(url, {
            ...options,
            headers: authorizationHeaders(options.headers || {}),
        });

        let response = await send();
        if (response.status === 401 && automaticMode) {
            await ensureToken(true);
            response = await send();
        }
        return response;
    }

    function openDialog() {
        const dialog = document.querySelector('#passport-dialog');
        const input = document.querySelector('#passport-token');
        if (!dialog || !input) return;

        input.value = getToken();
        if (!dialog.open) dialog.showModal();
        window.setTimeout(() => input.focus(), 30);
    }

    function updateStatus() {
        const status = document.querySelector('#passport-token-status');
        if (!status) return;

        const connected = Boolean(getToken());
        status.textContent = connected ? 'Con token' : 'Sin token';
        status.classList.toggle('connected', connected);
    }

    function setup() {
        const dialog = document.querySelector('#passport-dialog');
        const form = document.querySelector('#passport-form');
        const openButton = document.querySelector('#open-passport-token');
        const clearButton = document.querySelector('#clear-passport-token');

        openButton?.addEventListener('click', openDialog);
        form?.addEventListener('submit', (event) => {
            event.preventDefault();
            saveToken(document.querySelector('#passport-token').value);
            dialog?.close();
        });
        clearButton?.addEventListener('click', () => {
            saveToken('');
            const input = document.querySelector('#passport-token');
            if (input) input.value = '';
        });
        updateStatus();
    }

    const ready = automaticMode
        ? ensureToken().catch(() => '')
        : Promise.resolve(getToken());

    window.PassportAccess = {
        authorizationHeaders,
        authorizedFetch,
        ensureToken,
        getToken,
        openDialog,
        ready,
    };

    setup();
}());
