<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\User;
use App\Services\CredentialService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $staff = User::where('role', 'field_staff')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('whatsapp_number', 'like', "%{$search}%")))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('staff.index', compact('staff', 'search'));
    }

    public function create(): View
    {
        return view('staff.form', ['staff' => null]);
    }

    public function store(Request $request, CredentialService $credentials): Response
    {
        $data = $this->validated($request);
        try {
            [$staff, $password] = DB::transaction(function () use ($data, $credentials): array {
                $staff = User::create([
                    ...$data,
                    'role' => 'field_staff',
                    'is_active' => true,
                    'password' => Str::random(40),
                ]);
                AuditEvent::record('staff_created', $staff);
                $password = $credentials->issueTemporaryPassword($staff, 'temporary_password_issued');

                return [$staff, $password];
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['username' => 'This username is already in use.']);
        }

        return $this->handoff($staff, $password);
    }

    public function edit(User $staff): View
    {
        $this->editable($staff);

        return view('staff.form', compact('staff'));
    }

    public function update(Request $request, User $staff, CredentialService $credentials): RedirectResponse|Response
    {
        $this->editable($staff);
        $data = $this->validated($request, $staff);
        $requiresNewHandoff = $staff->is_active && $staff->must_change_password
            && ($data['username'] !== $staff->username || $data['whatsapp_number'] !== $staff->whatsapp_number);

        try {
            $password = DB::transaction(function () use ($staff, $data, $requiresNewHandoff, $credentials): ?string {
                $staff->update($data);
                AuditEvent::record('staff_updated', $staff);

                return $requiresNewHandoff
                    ? $credentials->issueTemporaryPassword($staff, 'temporary_password_issued')
                    : null;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['username' => 'This username is already in use.']);
        }

        if ($password !== null) {
            return $this->handoff($staff, $password);
        }

        return redirect()->route('staff.index')->with('status', 'Staff account updated.');
    }

    public function deactivate(User $staff): RedirectResponse
    {
        $this->editable($staff);
        DB::transaction(function () use ($staff): void {
            $staff->forceFill([
                'is_active' => false,
                'auth_version' => $staff->auth_version + 1,
                'remember_token' => Str::random(60),
            ])->save();
            DB::table('sessions')->where('user_id', $staff->id)->delete();
            AuditEvent::record('staff_deactivated', $staff);
        });

        return redirect()->route('staff.index')->with('status', 'Staff account deactivated.');
    }

    public function reactivate(User $staff, CredentialService $credentials): Response
    {
        $this->editable($staff);
        $password = DB::transaction(function () use ($staff, $credentials): string {
            $staff->update(['is_active' => true]);
            AuditEvent::record('staff_reactivated', $staff);

            return $credentials->issueTemporaryPassword($staff, 'temporary_password_issued');
        });

        return $this->handoff($staff, $password);
    }

    public function resetPassword(User $staff, CredentialService $credentials): Response
    {
        $this->editable($staff);
        abort_unless($staff->is_active, 422);
        $password = $credentials->issueTemporaryPassword($staff, 'temporary_password_issued');

        return $this->handoff($staff, $password);
    }

    private function validated(Request $request, ?User $staff = null): array
    {
        $request->merge(['username' => Str::lower(trim((string) $request->input('username')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users')->ignore($staff?->id)],
            'whatsapp_number' => ['required', 'regex:/^(?:\+?880|0)1[3-9]\d{8}$/'],
        ]);
        $digits = preg_replace('/\D/', '', $data['whatsapp_number']);
        $data['whatsapp_number'] = str_starts_with($digits, '0') ? '88'.$digits : $digits;

        return $data;
    }

    private function editable(User $staff): void
    {
        abort_unless($staff->role === 'field_staff', 404);
        abort_if($staff->is_demo, 403, 'Demo accounts are protected.');
    }

    private function handoff(User $staff, string $password): Response
    {
        $expiresAt = $staff->temporary_password_expires_at->format('j M Y, g:i A');
        $message = implode("\n", [
            'School Feeding Management sign-in',
            'Site: '.rtrim(config('app.url'), '/').'/login',
            'Username: '.$staff->username,
            'Temporary password: '.$password,
            'Expires: '.$expiresAt.' (Bangladesh time)',
            'Change this password when you first sign in.',
        ]);
        $handoff = [
            'name' => $staff->name,
            'username' => $staff->username,
            'whatsapp_number' => $staff->whatsapp_number,
            'password' => $password,
            'expires_at' => $expiresAt,
            'message' => $message,
            'whatsapp_url' => 'https://wa.me/'.$staff->whatsapp_number.'?text='.rawurlencode($message),
        ];

        return response()->view('staff.handoff', compact('handoff'))
            ->header('Cache-Control', 'no-store, private');
    }
}
