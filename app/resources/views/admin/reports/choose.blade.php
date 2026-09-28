@extends('layouts.app')
@section('title', 'Report Generator')
@section('content')
<a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a>

<div class="mt-6 max-w-2xl mx-auto">
    <div class="card-glass rounded-2xl p-6 shadow-sm">
        <p class="mb-2 text-sm font-semibold text-muted-foreground">Official forms</p>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Report Generator</h1>
        <p class="mt-2 text-sm text-muted-foreground">Select a form type, period, and school to generate the programme statement.</p>

        <form method="POST" action="{{ route('admin.reports.generate') }}" id="report-form" class="mt-7 space-y-6" data-no-loading>
            @csrf

            {{-- ===== FORM TYPE ===== --}}
            <fieldset>
                <legend class="mb-3 block text-sm font-semibold text-foreground">Form Type</legend>
                <div class="grid grid-cols-1 gap-3">
                    @foreach($formOptions as $value => $option)
                        <label class="form-type-option relative flex items-start gap-3 rounded-xl border-2 border-border bg-card/80 p-4 cursor-pointer hover:border-primary/50 transition-colors">
                            <input type="radio" name="form_type" value="{{ $value }}"
                                   class="mt-1 radio radio-primary radio-sm"
                                   {{ old('form_type') == $value ? 'checked' : '' }}>
                            <div class="flex-1">
                                <div class="font-semibold text-foreground">{{ $option['label'] }}</div>
                                <div class="mt-0.5 text-xs text-muted-foreground">{{ $option['desc'] }}</div>
                            </div>
                            @if($option['per_school'])
                                <span class="absolute top-2 right-3 text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full">Per-school</span>
                            @endif
                        </label>
                    @endforeach
                </div>
                @error('form_type')
                    <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
                @enderror
            </fieldset>

            {{-- ===== SCHOOL (for forms 4 and 12) ===== --}}
            <div id="school-section" class="{{ old('form_type') == '4' || old('form_type') == '12' ? '' : 'hidden' }}">
                <label for="school-select" class="mb-2 block text-sm font-semibold text-foreground">School</label>
                <select name="school_id" id="school-select"
                        class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    <option value="">— All schools (generate bundle) —</option>
                    @foreach($participatingSchools as $id => $name)
                        <option value="{{ $id }}" @selected(old('school_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('school_id')
                    <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>

            {{-- ===== PERIOD MODE ===== --}}
            <fieldset>
                <legend class="mb-3 block text-sm font-semibold text-foreground">Period</legend>
                <div class="flex gap-4 mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="period_mode" value="month" class="radio radio-primary radio-sm"
                               {{ old('period_mode', 'month') === 'month' ? 'checked' : '' }}>
                        <span class="text-sm font-medium">Month</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="period_mode" value="range" class="radio radio-primary radio-sm"
                               {{ old('period_mode') === 'range' ? 'checked' : '' }}>
                        <span class="text-sm font-medium">Custom Range</span>
                    </label>
                </div>

                {{-- Month picker --}}
                <div id="month-section" class="{{ old('period_mode', 'month') === 'month' ? '' : 'hidden' }}">
                    <label for="month-input" class="mb-2 block text-sm font-semibold text-foreground">Month</label>
                    <input id="month-input" name="month" type="month"
                           value="{{ old('month', now()->format('Y-m')) }}"
                           max="{{ now()->format('Y-m') }}"
                           class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    @error('month')
                        <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Date range --}}
                <div id="range-section" class="space-y-4 {{ old('period_mode') === 'range' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="start-date" class="mb-2 block text-sm font-semibold text-foreground">Start Date</label>
                            <input id="start-date" name="start_date" type="date"
                                   value="{{ old('start_date') }}"
                                   max="{{ now()->format('Y-m-d') }}"
                                   class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                            @error('start_date')
                                <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="end-date" class="mb-2 block text-sm font-semibold text-foreground">End Date</label>
                            <input id="end-date" name="end_date" type="date"
                                   value="{{ old('end_date') }}"
                                   max="{{ now()->format('Y-m-d') }}"
                                   class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                            @error('end_date')
                                <p class="mt-2 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </fieldset>

            {{-- ===== SUBMIT ===== --}}
            <div class="flex flex-col gap-3 pt-2">
                <x-ui.button type="submit" id="generate-btn" class="w-full">Generate Report</x-ui.button>
                <p id="bundle-note" class="hidden text-center text-xs text-muted-foreground">
                    No school selected — a zip bundle of all school PDFs will be generated.
                </p>
            </div>
        </form>
    </div>
</div>

<script>
    const formTypeRadios = document.querySelectorAll('input[name="form_type"]');
    const schoolSection = document.getElementById('school-section');
    const schoolSelect = document.getElementById('school-select');
    const bundleNote = document.getElementById('bundle-note');
    const periodModeRadios = document.querySelectorAll('input[name="period_mode"]');
    const monthSection = document.getElementById('month-section');
    const rangeSection = document.getElementById('range-section');

    const perSchoolForms = ['4', '12'];

    function updateSchoolVisibility() {
        const selectedForm = document.querySelector('input[name="form_type"]:checked')?.value;
        if (perSchoolForms.includes(selectedForm)) {
            schoolSection.classList.remove('hidden');
            updateBundleNote();
        } else {
            schoolSection.classList.add('hidden');
            bundleNote.classList.add('hidden');
        }
    }

    function updateBundleNote() {
        const selectedForm = document.querySelector('input[name="form_type"]:checked')?.value;
        if (perSchoolForms.includes(selectedForm) && !schoolSelect.value) {
            bundleNote.classList.remove('hidden');
        } else {
            bundleNote.classList.add('hidden');
        }
    }

    function updatePeriodVisibility() {
        const selectedMode = document.querySelector('input[name="period_mode"]:checked')?.value;
        if (selectedMode === 'range') {
            rangeSection.classList.remove('hidden');
            monthSection.classList.add('hidden');
        } else {
            monthSection.classList.remove('hidden');
            rangeSection.classList.add('hidden');
        }
    }

    formTypeRadios.forEach(radio => {
        radio.addEventListener('change', updateSchoolVisibility);
    });

    schoolSelect.addEventListener('change', updateBundleNote);

    periodModeRadios.forEach(radio => {
        radio.addEventListener('change', updatePeriodVisibility);
    });

    updateSchoolVisibility();
    updatePeriodVisibility();

    document.addEventListener('DOMContentLoaded', () => {
        if (window.setupDownloadButton) {
            window.setupDownloadButton('generate-btn', '{{ route("admin.reports.generate") }}', 'POST', 'report-form');
        }
    });
</script>
@endsection
