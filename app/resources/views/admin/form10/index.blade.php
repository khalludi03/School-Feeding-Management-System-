@extends('layouts.app')
@section('title', 'Form 10 – Invoice')
@section('content')
<div class="mx-auto max-w-3xl">
    <div class="rounded-xl border bg-card text-card-foreground shadow mt-8">
        <div class="flex flex-col space-y-1.5 p-6 pb-4">
            <p class="text-sm font-semibold text-muted-foreground">Official forms</p>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Form 10 – Invoice</h1>
            <p class="text-sm text-muted-foreground">Fill out the details to generate the official Form-10 invoice.</p>
        </div>

        <div class="p-6 pt-0">
            @if($errors->any())
                <div role="alert" class="mb-6 rounded-xl border border-error bg-destructive/10 p-4 text-sm text-destructive">
                    Please correct the errors below.
                </div>
            @endif

            <form id="form10-form" method="POST" action="{{ route('admin.form10.pdf') }}" class="space-y-6" autocomplete="off" novalidate>
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
                            <script type="application/json" id="month-picker-data">
                                {!! json_encode([
                                    'name' => 'month',
                                    'defaultValue' => request('month') ?? now()->format('Y-m')
                                ]) !!}
                            </script>
                            <div id="month-picker-root"></div>
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

                <x-forms.actions 
                    form-id="form10-form" 
                    method="POST"
                    pdf-route="{{ route('admin.form10.pdf') }}"
                    excel-route="{{ route('admin.form10.export') }}"
                />
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('form10-form');
        document.getElementById('btn-view-register').addEventListener('click', function(e) {
            e.preventDefault();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            form.method = 'GET';
            form.action = '{{ route("admin.form10.show") }}';
            form.requestSubmit(document.getElementById('btn-view-register'));
        });
    });
</script>
@endsection