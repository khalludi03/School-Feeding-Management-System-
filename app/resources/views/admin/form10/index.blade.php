@extends('layouts.app')
@section('title', 'Form 10 – Invoice from Actual Receipts')
@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mt-8 rounded-2xl card-glass p-6 shadow-sm">
        <p class="mb-2 text-sm font-semibold text-muted-foreground">Official forms</p>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Form 10 – Invoice</h1>
        <p class="mt-2 text-sm text-muted-foreground">Fill out the details to generate the official Form-10 invoice.</p>

        @if($errors->any())
            <div role="alert" class="mt-6 rounded-xl border border-error bg-destructive/10 p-4 text-sm text-destructive">
                Please correct the errors below.
            </div>
        @endif

        <form id="form10-form" method="GET" action="{{ route('admin.form10.show') }}" class="mt-7 space-y-6" autocomplete="off">
            @csrf
            
            <fieldset class="space-y-4 rounded-xl border border-border p-5">
                <legend class="px-2 text-sm font-semibold text-foreground">Invoice Metadata</legend>
                
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-foreground">Invoice Number</label>
                        <input type="text" autocomplete="off" readonly value="{{ $nextInvoiceNo }}" class="w-full rounded-xl border border-border bg-muted/50 px-4 py-3 text-muted-foreground outline-none">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-foreground">Invoice Date</label>
                        <input type="text" autocomplete="off" readonly value="{{ today()->format('Y-m-d') }}" class="w-full rounded-xl border border-border bg-muted/50 px-4 py-3 text-muted-foreground outline-none">
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="month-select" class="mb-2 block text-sm font-semibold text-foreground">Month</label>
                        <input id="month-select" name="month" type="month"
                               max="{{ now()->format('Y-m') }}"
                               value="{{ request('month') ?? now()->format('Y-m') }}"
                               class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20"
                               required>
                    </div>
                    <div>
                        <label for="contract_number" class="mb-2 block text-sm font-semibold text-foreground">Contract Number <span class="text-destructive">*</span></label>
                        <input id="contract_number" name="contract_number" type="text" autocomplete="off"
                               value="{{ old('contract_number', $latestInvoice?->contract_number) }}"
                               class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20"
                               required>
                        @error('contract_number')<p class="mt-1 text-xs text-destructive">{{ $message }}</p>@enderror
                    </div>
                </div>
            </fieldset>

            <fieldset class="space-y-4 rounded-xl border border-border p-5">
                <legend class="px-2 text-sm font-semibold text-foreground">Supplier Bank Details</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="bank_account_name" class="mb-2 block text-sm font-semibold text-foreground">Account Name</label>
                        <input id="bank_account_name" name="bank_account_name" type="text" autocomplete="off" value="{{ old('bank_account_name', $latestInvoice?->bank_account_name) }}" class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    </div>
                    <div>
                        <label for="bank_account_number" class="mb-2 block text-sm font-semibold text-foreground">Account Number</label>
                        <input id="bank_account_number" name="bank_account_number" type="text" autocomplete="off" value="{{ old('bank_account_number', $latestInvoice?->bank_account_number) }}" class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    </div>
                    <div>
                        <label for="bank_name" class="mb-2 block text-sm font-semibold text-foreground">Bank Name</label>
                        <input id="bank_name" name="bank_name" type="text" autocomplete="off" value="{{ old('bank_name', $latestInvoice?->bank_name) }}" class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    </div>
                    <div>
                        <label for="bank_branch" class="mb-2 block text-sm font-semibold text-foreground">Branch Name</label>
                        <input id="bank_branch" name="bank_branch" type="text" autocomplete="off" value="{{ old('bank_branch', $latestInvoice?->bank_branch) }}" class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    </div>
                    <div>
                        <label for="bank_routing" class="mb-2 block text-sm font-semibold text-foreground">Routing Number</label>
                        <input id="bank_routing" name="bank_routing" type="text" autocomplete="off" value="{{ old('bank_routing', $latestInvoice?->bank_routing) }}" class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    </div>
                </div>
            </fieldset>

            <fieldset class="space-y-4 rounded-xl border border-border p-5">
                <legend class="px-2 text-sm font-semibold text-foreground">Signature Details</legend>
                <div>
                    <label for="upeo_mobile" class="mb-2 block text-sm font-semibold text-foreground">UPEO Mobile Number</label>
                    <input id="upeo_mobile" name="upeo_mobile" type="text" autocomplete="off" value="{{ old('upeo_mobile', $latestInvoice?->upeo_mobile) }}" class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                </div>
            </fieldset>

            <div class="flex flex-col gap-3 pt-2 sm:flex-row">
                <button type="button" id="btn-preview" class="w-full rounded-xl border border-border bg-card px-4 py-3 font-semibold text-foreground hover:bg-muted text-center sm:w-1/3">View Register</button>
                <button type="button" id="btn-pdf" class="w-full rounded-xl border border-transparent bg-primary px-4 py-3 font-semibold text-primary-foreground hover:bg-primary/90 text-center sm:w-1/3">Generate PDF</button>
                <button type="button" id="btn-excel" class="w-full rounded-xl border border-transparent bg-primary px-4 py-3 font-semibold text-primary-foreground hover:bg-primary/90 text-center sm:w-1/3">Generate Excel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form10-form');
    
    document.getElementById('btn-preview').addEventListener('click', function() {
        form.method = 'GET';
        form.action = '{{ route("admin.form10.show") }}';
        form.requestSubmit(this);
    });

    if (window.setupDownloadButton) {
        window.setupDownloadButton('btn-pdf', '{{ route("admin.form10.pdf") }}', 'POST', 'form10-form');
        window.setupDownloadButton('btn-excel', '{{ route("admin.form10.export") }}', 'POST', 'form10-form');
    }
});
</script>
@endsection
