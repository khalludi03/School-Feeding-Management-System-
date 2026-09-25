<div>
    <label for="reason" class="mb-2 block text-sm font-semibold">Reason</label>
    <textarea id="reason" name="reason" required minlength="3" maxlength="500" rows="3" class="w-full rounded-xl border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">{{ old('reason') }}</textarea>
    @error('reason')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
</div>
<div>
    <label for="current_password" class="mb-2 block text-sm font-semibold">Your Admin password</label>
    <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="w-full rounded-xl border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
    @error('current_password')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
</div>
<div class="flex flex-wrap items-center gap-4 pt-2">
    <button class="rounded-xl bg-rose-700 px-5 py-3 font-semibold text-white hover:bg-rose-800">{{ $submitLabel }}</button>
    <a href="{{ route('schools.show', $school) }}" class="font-semibold text-slate-600 hover:underline">Cancel</a>
</div>
