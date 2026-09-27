@extends('layouts.app')
@section('title', 'Form 7 – School Chalan Totals')
@section('content')
<div class="mx-auto max-w-md">
    <div class="mt-8 rounded-2xl card-glass p-6 shadow-sm">
        <p class="mb-2 text-sm font-semibold text-muted-foreground">Official forms</p>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Form 7 – Chalan Totals</h1>
        <p class="mt-2 text-sm text-muted-foreground">Select a period to generate the school-wise chalan totals (ফরম-০৭) for reconciling upazila supply.</p>

        <form method="GET" action="{{ route('admin.form7.show') }}" class="mt-7 space-y-5">
            <div>
                <label for="month-select" class="mb-2 block text-sm font-semibold text-foreground">Month</label>
                <input id="month-select" name="month" type="month"
                       max="{{ now()->format('Y-m') }}"
                       class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20"
                       required>
            </div>

            <div class="flex flex-col gap-3 pt-2">
                <x-ui.button class="w-full">View Register</x-ui.button>
                <a id="export-pdf-btn" href="#" class="w-full rounded-xl border border-border bg-white px-4 py-3 font-semibold text-foreground hover:bg-muted text-center">Download PDF</a>
                <a id="export-excel-btn" href="#" class="w-full rounded-xl border border-border bg-white px-4 py-3 font-semibold text-foreground hover:bg-muted text-center">Export Excel</a>
            </div>
        </form>
    </div>
</div>

<script>
    const monthInput = document.getElementById('month-select');
    const pdfBtn = document.getElementById('export-pdf-btn');
    const excelBtn = document.getElementById('export-excel-btn');

    function updateUrls() {
        const month = monthInput.value;
        const monthParam = month ? '?month=' + month : '';

        pdfBtn.href = '{{ route('admin.form7.pdf') }}' + monthParam;
        excelBtn.href = '{{ route('admin.form7.export') }}' + monthParam;

        if (!month) {
            pdfBtn.href = '#';
            excelBtn.href = '#';
        }
    }

    monthInput.addEventListener('change', updateUrls);
</script>
@endsection
