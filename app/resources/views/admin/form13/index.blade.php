@extends('layouts.app')
@section('title', 'Form 13 – Consolidated Stock')
@section('content')
<div class="mx-auto max-w-2xl">
    <div class="rounded-xl border bg-card text-card-foreground shadow mt-8">
        <div class="flex flex-col space-y-1.5 p-6 pb-4">
            <p class="text-sm font-semibold text-muted-foreground">Official forms</p>
            <h1 class="text-2xl font-bold tracking-tight">Form 13 – Consolidated Stock</h1>
            <p class="text-sm text-muted-foreground">Select a period to view the upazila consolidated stock (<span lang="bn">ফরম-১৩</span>).</p>
        </div>
        <div class="p-6 pt-0">
            <form method="GET" action="{{ route('admin.form13.show') }}" id="form13-form" class="space-y-6" novalidate>
                <script type="application/json" id="month-picker-data">
                    {!! json_encode([
                        'name' => 'month',
                        'defaultValue' => ''
                    ]) !!}
                </script>
                <div id="month-picker-root"></div>

                <x-forms.actions 
                    form-id="form13-form" 
                    pdf-route="{{ route('admin.form13.pdf') }}"
                    excel-route="{{ route('admin.form13.export') }}"
                />
            </form>
        </div>
    </div>
</div>
@endsection