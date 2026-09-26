@extends('layouts.app')
@section('title', 'Form 4 – School Receipt Register')
@section('content')
<div class="mx-auto max-w-md">
    <div class="mt-8 rounded-2xl card-glass p-6 shadow-sm">
        <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-primary">Official forms</p>
        <h1 class="text-2xl font-bold tracking-tight text-base-content">Form 4 – Receipt Register</h1>
        <p class="mt-2 text-sm text-secondary-content">Select a school and period to generate the dated receipt register (ফরম-০৪) for tracing received quantities to their chalans.</p>

        <form method="GET" action="{{ route('admin.form4.show', ['school' => '__SCHOOL_ID__']) }}" id="form4-form" class="mt-7 space-y-5">
            @csrf
            <div>
                <label for="school-select" class="mb-2 block text-sm font-semibold text-base-content">School</label>
                <select id="school-select" name="school_id" required class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    <option value="">— Select school —</option>
                    @foreach($schools as $id => $name)
                        <option value="{{ $id }}" @selected($selectedSchool == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-4">
                <div class="flex-1">
                    <label for="month-select" class="mb-2 block text-sm font-semibold text-base-content">Month</label>
                    <input id="month-select" name="month" type="month"
                           value="{{ $selectedYear && $selectedMonth ? sprintf('%04d-%02d', $selectedYear, $selectedMonth) : '' }}"
                           max="{{ now()->format('Y-m') }}"
                           class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20"
                           required>
                </div>
            </div>

            <div class="flex flex-col gap-3 pt-2">
                <button type="submit" class="w-full rounded-xl bg-primary px-4 py-3 font-semibold text-primary-content hover:bg-primary">View Register</button>
                <button type="button" id="export-pdf-btn" class="w-full rounded-xl border border-base-300 bg-white px-4 py-3 font-semibold text-base-content hover:bg-base-200" disabled>Export PDF</button>
                <button type="button" id="export-excel-btn" class="w-full rounded-xl border border-base-300 bg-white px-4 py-3 font-semibold text-base-content hover:bg-base-200" disabled>Export Excel</button>
            </div>
        </form>
    </div>
</div>

<script>
    const form = document.getElementById('form4-form');
    const schoolSelect = document.getElementById('school-select');
    const monthInput = document.getElementById('month-select');
    const pdfBtn = document.getElementById('export-pdf-btn');
    const excelBtn = document.getElementById('export-excel-btn');

    function updateUrls() {
        const schoolId = schoolSelect.value;
        const month = monthInput.value;

        if (!schoolId || !month) {
            pdfBtn.disabled = true;
            excelBtn.disabled = true;
            return;
        }

        const base = '{{ route('admin.form4.show', ['school' => '__SCHOOL_ID__']) }}'.replace('__SCHOOL_ID__', schoolId);
        form.action = base + '?month=' + month;

        pdfBtn.disabled = false;
        excelBtn.disabled = false;

        pdfBtn.onclick = () => {
            window.location.href = '{{ route('admin.form4.pdf', ['school' => '__SCHOOL_ID__']) }}'.replace('__SCHOOL_ID__', schoolId) + '?month=' + month;
        };
        excelBtn.onclick = () => {
            window.location.href = '{{ route('admin.form4.export', ['school' => '__SCHOOL_ID__']) }}'.replace('__SCHOOL_ID__', schoolId) + '?month=' + month;
        };
    }

    schoolSelect.addEventListener('change', updateUrls);
    monthInput.addEventListener('change', updateUrls);
</script>
@endsection
