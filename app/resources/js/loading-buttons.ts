const SPINNER_SVG = `<svg class="size-4 animate-spin mr-2 inline-block" role="status" aria-label="Loading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>`;

document.addEventListener('submit', (e) => {
    const form = e.target as HTMLFormElement;
    if (!form || form.target === '_blank' || form.hasAttribute('data-no-loading')) {
        return;
    }

    // Find the submit button that was clicked.
    // If not determinable via submitter (e.g. older Safari), fallback to the first submit button.
    const submitter = (e as SubmitEvent).submitter as HTMLButtonElement | null;
    const button = submitter || form.querySelector('button[type="submit"]') as HTMLButtonElement;

    if (button) {
        if (!button.hasAttribute('data-original-html')) {
            button.setAttribute('data-original-html', button.innerHTML);
        }

        const loadingText = button.getAttribute('data-loading-text');
        
        // Use a slight delay to allow the form submission to proceed before manipulating the DOM/disabling
        setTimeout(() => {
            if (loadingText) {
                button.innerHTML = SPINNER_SVG + `<span>${loadingText}</span>`;
            } else {
                button.innerHTML = SPINNER_SVG + `<span>${button.innerHTML}</span>`;
            }
            button.setAttribute('aria-busy', 'true');
            button.classList.add('opacity-80', 'cursor-not-allowed');
            // Disable it to prevent duplicate submissions
            button.disabled = true;
        }, 10);
    }
});

// Restore buttons when navigating back using bfcache
window.addEventListener('pageshow', (e) => {
    if (e.persisted) {
        document.querySelectorAll('button[data-original-html]').forEach((btn) => {
            const button = btn as HTMLButtonElement;
            button.innerHTML = button.getAttribute('data-original-html') || '';
            button.removeAttribute('aria-busy');
            button.classList.remove('opacity-80', 'cursor-not-allowed');
            button.disabled = false;
        });
    }
});
