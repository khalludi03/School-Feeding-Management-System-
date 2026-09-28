@extends('layouts.app')
@section('title', 'Form 4 – School Receipt Register')
@section('content')
@php
    $allSchools = \App\Models\School::where('is_active', true)->orderBy('bangla_name')->get(['id', 'code', 'bangla_name', 'emis_code']);
@endphp
<div class="mx-auto max-w-2xl">
    <div class="rounded-xl border bg-card text-card-foreground shadow mt-8">
        <div class="flex flex-col space-y-1.5 p-6 pb-4">
            <p class="text-sm font-semibold text-muted-foreground">Official forms</p>
            <h1 class="text-2xl font-bold tracking-tight">Form 4 – Receipt Register</h1>
            <p class="text-sm text-muted-foreground">Select a school and period to generate the dated receipt register (<span lang="bn">ফরম-০৪</span>) for tracing received quantities to their chalans.</p>
        </div>
        <div class="p-6 pt-0">
            <form method="GET" action="{{ route('admin.form4.show', ['school' => '__SCHOOL_ID__']) }}" id="form4-form" class="space-y-6" novalidate>
                @csrf
                <script type="application/json" id="school-combobox-data">
                    {!! json_encode([
                        'name' => 'school_id',
                        'defaultValue' => $selectedSchool ?? '',
                        'schools' => $allSchools
                    ]) !!}
                </script>
                <div id="school-combobox-root"></div>

                <script type="application/json" id="month-picker-data">
                    {!! json_encode([
                        'name' => 'month',
                        'defaultValue' => ($selectedYear && $selectedMonth) ? sprintf('%04d-%02d', $selectedYear, $selectedMonth) : ''
                    ]) !!}
                </script>
                <div id="month-picker-root"></div>

                <x-forms.actions 
                    form-id="form4-form" 
                    pdf-route="{{ route('admin.form4.pdf', ['school' => '__SCHOOL_ID__']) }}"
                    excel-route="{{ route('admin.form4.export', ['school' => '__SCHOOL_ID__']) }}"
                />
            </form>
        </div>
    </div>
</div>
<script>
    const form = document.getElementById('form4-form');
    form.addEventListener('submit', (e) => {
        const schoolInput = document.getElementById('school-select');
        const schoolId = schoolInput ? schoolInput.value : '';
        const monthInput = document.getElementById('month-select');
        const month = monthInput ? monthInput.value : '';
        
        if (!form.checkValidity()) {
            e.preventDefault();
            form.reportValidity();
            return;
        }

        const base = '{{ route('admin.form4.show', ['school' => '__SCHOOL_ID__']) }}'.replace('__SCHOOL_ID__', schoolId);
        form.action = base + (month ? '?month=' + month : '');
    });
</script>
@endsection
