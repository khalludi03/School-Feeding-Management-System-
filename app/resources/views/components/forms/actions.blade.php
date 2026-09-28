@props([
    'formId',
    'pdfRoute',
    'excelRoute',
    'method' => 'GET',
    'pdfId' => 'btn-export-pdf',
    'excelId' => 'btn-export-excel',
    'submitId' => 'btn-view-register',
])

<div class="mt-8 flex flex-col gap-3 sm:flex-row">
    <button type="submit" id="{{ $submitId }}" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-primary px-8 text-sm font-semibold text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
        View Register
    </button>
    <button type="button" id="{{ $pdfId }}" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-input bg-background px-8 text-sm font-semibold shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" data-turbo="false">
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
        Export PDF
    </button>
    <button type="button" id="{{ $excelId }}" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-input bg-background px-8 text-sm font-semibold shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" data-turbo="false">
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h2"/><path d="M8 17h2"/><path d="M14 13h2"/><path d="M14 17h2"/></svg>
        Export Excel
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.setupDownloadButton) {
        window.setupDownloadButton('{{ $pdfId }}', () => {
            if ('{{ $method }}' === 'POST') return '{{ $pdfRoute }}';
            
            const form = document.getElementById('{{ $formId }}');
            if (form && !form.checkValidity()) {
                form.reportValidity();
                return null;
            }
            const schoolInput = document.getElementById('school-select');
            const schoolId = schoolInput ? schoolInput.value : '';
            const monthInput = document.getElementById('month-select');
            const month = monthInput ? monthInput.value : '';
            
            let url = '{{ $pdfRoute }}';
            if (schoolId) url = url.replace('__SCHOOL_ID__', schoolId);
            return url + (month ? '?month=' + month : '');
        }, '{{ $method }}', '{{ $formId }}');
        
        window.setupDownloadButton('{{ $excelId }}', () => {
            if ('{{ $method }}' === 'POST') return '{{ $excelRoute }}';

            const form = document.getElementById('{{ $formId }}');
            if (form && !form.checkValidity()) {
                form.reportValidity();
                return null;
            }
            const schoolInput = document.getElementById('school-select');
            const schoolId = schoolInput ? schoolInput.value : '';
            const monthInput = document.getElementById('month-select');
            const month = monthInput ? monthInput.value : '';

            let url = '{{ $excelRoute }}';
            if (schoolId) url = url.replace('__SCHOOL_ID__', schoolId);
            return url + (month ? '?month=' + month : '');
        }, '{{ $method }}', '{{ $formId }}');
    }
});
</script>
