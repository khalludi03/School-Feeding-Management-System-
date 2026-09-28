import { toast } from 'sonner';

export function setupDownloadButton(buttonId: string, actionUrlOrFn: string | (() => string), method: string = 'GET', formId?: string) {
    const btn = document.getElementById(buttonId) as HTMLButtonElement | null;
    if (!btn) return;

    btn.addEventListener('click', async (e) => {
        e.preventDefault();
        
        let form: HTMLFormElement | null = null;
        if (formId) {
            form = document.getElementById(formId) as HTMLFormElement | null;
            if (form && !form.reportValidity()) return;
        }

        const actionUrl = typeof actionUrlOrFn === 'function' ? actionUrlOrFn() : actionUrlOrFn;
        if (!actionUrl) return;

        // Visual loading state
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('opacity-80', 'cursor-not-allowed');
        btn.innerHTML = `<svg class="size-4 animate-spin mr-2 inline-block" role="status" aria-label="Loading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg><span>${originalHtml}</span>`;

        try {
            const fetchOptions: RequestInit = { method };
            
            if (form && method.toUpperCase() === 'POST') {
                const formData = new FormData(form);
                const csrfToken = (document.querySelector('input[name="_token"]') as HTMLInputElement)?.value;
                if (csrfToken && !formData.has('_token')) {
                    formData.append('_token', csrfToken);
                }
                fetchOptions.body = formData;
            }

            const response = await fetch(actionUrl, fetchOptions);

            if (!response.ok) {
                throw new Error('Server returned ' + response.status);
            }

            let filename = 'download';
            const disposition = response.headers.get('Content-Disposition');
            if (disposition && disposition.indexOf('attachment') !== -1) {
                const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                const matches = filenameRegex.exec(disposition);
                if (matches != null && matches[1]) {
                    filename = matches[1].replace(/['"]/g, '');
                }
            } else {
                const contentType = response.headers.get('Content-Type');
                if (contentType && contentType.includes('text/html')) {
                     toast.error('An error occurred while generating the report.');
                     return;
                }
            }

            const blob = await response.blob();
            const blobUrl = window.URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = blobUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(blobUrl);
            document.body.removeChild(a);

        } catch (error) {
            console.error('Download failed:', error);
            toast.error('Failed to generate file. Please try again.');
        } finally {
            // Restore button
            btn.innerHTML = originalHtml;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
            btn.disabled = false;
        }
    });
}
