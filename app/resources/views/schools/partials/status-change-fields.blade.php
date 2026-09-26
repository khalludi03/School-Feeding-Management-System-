<div>
    <label for="reason" class="mb-2 block text-sm font-semibold text-base-content">Reason</label>
    <textarea id="reason" name="reason" required minlength="3" maxlength="500" rows="3" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">{{ old('reason') }}</textarea>
    @error('reason')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
</div>
<div>
    <label for="current_password" class="mb-2 block text-sm font-semibold text-base-content">Your Admin password</label>
    <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
    @error('current_password')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
</div>
<div class="flex flex-wrap items-center gap-4 pt-2">
    <button class="rounded-xl bg-error px-5 py-3 font-semibold text-primary-content hover:bg-error/90">{{ $submitLabel }}</button>
    <a href="{{ route('schools.show', $school) }}" class="font-semibold text-secondary-content hover:underline">Cancel</a>
</div>
