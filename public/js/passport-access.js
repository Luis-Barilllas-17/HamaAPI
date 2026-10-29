(function () {
    const storageKey = 'proyectoapi.passport_access_token';

    function getToken() {
        return window.sessionStorage.getItem(storageKey)?.trim() || '';
    }

    function saveToken(value) {
        const token = value.trim().replace(/^Bearer\s+/i, '');

        if (token) {
            window.sessionStorage.setItem(storageKey, token);
        } else {
            window.sessionStorage.removeItem(storageKey);
        }

        updateStatus();
        window.dispatchEvent(new CustomEvent('passport:token-changed'));
    }

    function authorizationHeaders(headers = {}) {
        const token = getToken();

        return token
            ? { ...headers, Authorization: `Bearer ${token}` }
            : headers;
    }

    function openDialog() {
        const dialog = document.querySelector('#passport-dialog');
        const input = document.querySelector('#passport-token');

        if (!dialog) {
            return;
        }

        input.value = getToken();

        if (!dialog.open) {
            dialog.showModal();
        }

        window.setTimeout(() => input.focus(), 30);
    }

    function updateStatus() {
        const status = document.querySelector('#passport-token-status');

        if (!status) {
            return;
        }

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
            dialog.close();
        });

        clearButton?.addEventListener('click', () => {
            saveToken('');
            document.querySelector('#passport-token').value = '';
        });

        updateStatus();

        if (!getToken()) {
            openDialog();
        }
    }

    window.PassportAccess = {
        authorizationHeaders,
        getToken,
        openDialog,
    };

    setup();
}());
