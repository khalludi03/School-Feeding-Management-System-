const form = document.querySelector<HTMLFormElement>('form[data-clear-credentials="true"]');

if (form) {
    let userEdited = false;
    const username = form.elements.namedItem('username');
    const password = form.elements.namedItem('password');

    const clearCredentials = () => {
        if (userEdited) {
            return;
        }

        if (username instanceof HTMLInputElement) {
            username.value = '';
        }

        if (password instanceof HTMLInputElement) {
            password.value = '';
        }
    };

    form.addEventListener('keydown', () => {
        userEdited = true;
    });
    form.addEventListener('pointerdown', () => {
        userEdited = true;
    });

    window.addEventListener('pageshow', clearCredentials);
    window.addEventListener('load', clearCredentials);
    clearCredentials();

    const clearUntilUserEdit = window.setInterval(clearCredentials, 100);
    window.setTimeout(() => window.clearInterval(clearUntilUserEdit), 2000);
}
