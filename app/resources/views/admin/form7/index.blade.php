@extends('layouts.app')
@section('title', 'Form 7 – School Chalan Totals')
@section('content')
<div class="mx-auto max-w-2xl">
    <div class="rounded-xl border bg-card text-card-foreground shadow mt-8">
        <div class="flex flex-col space-y-1.5 p-6 pb-4">
            <p class="text-sm font-semibold text-muted-foreground">Official forms</p>
            <h1 class="text-2xl font-bold tracking-tight">Form 7 – Chalan Totals</h1>
            <p class="text-sm text-muted-foreground">Select a period to generate the school-wise chalan totals (<span lang="bn">ফরম-০৭</span>) for reconciling upazila supply.</p>
        </div>
        <div class="p-6 pt-0">
            <form method="GET" action="{{ route('admin.form7.show') }}" id="form7-form" class="space-y-6" novalidate>
                <script type="application/json" id="month-picker-data">
                    {!! json_encode([
                        'name' => 'month',
                        'defaultValue' => ''
                    ]) !!}
                </script>
                <div id="month-picker-root"></div>

                <x-forms.actions 
                    form-id="form7-form" 
                    pdf-route="{{ route('admin.form7.pdf') }}"
                    excel-route="{{ route('admin.form7.export') }}"
                />
            </form>
        </div>
    </div>
</div>
@endsection