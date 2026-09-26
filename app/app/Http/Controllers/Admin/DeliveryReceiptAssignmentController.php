<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\DeliveryReceipt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryReceiptAssignmentController extends Controller
{
    public function edit(DeliveryReceipt $receipt): View
    {
        $receipt->load(['school', 'enteredBy', 'responsibleBy']);

        $staff = User::query()
            ->where('role', 'field_staff')
            ->where('is_active', true)
            ->whereKeyNot($receipt->responsible_by)
            ->orderBy('name')
            ->get();

        return view('admin.receipts.assign', [
            'receipt' => $receipt,
            'staff' => $staff,
        ]);
    }

    public function update(Request $request, DeliveryReceipt $receipt): RedirectResponse
    {
        $data = $request->validate([
            'responsible_by' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $user = User::query()->find((int) $value);

                    if ($user === null) {
                        return;
                    }

                    if ($user->role !== 'field_staff') {
                        $fail('Correction responsibility can only be assigned to a Field Staff member.');
                    }

                    if (! $user->is_active) {
                        $fail('The selected user is not active.');
                    }
                },
            ],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $previousOwnerId = $receipt->responsible_by;
        $newOwnerId = (int) $data['responsible_by'];

        if ($previousOwnerId === $newOwnerId) {
            return redirect()->route('schools.show', $receipt->school)
                ->with('status', 'The selected staff member is already responsible for this record.');
        }

        $receipt->responsible_by = $newOwnerId;
        $receipt->save();

        AuditEvent::recordSchool('delivery_responsibility_reassigned', $receipt->school, [
            'delivery_receipt_id' => $receipt->id,
            'original_author_id' => $receipt->entered_by,
            'previous_owner_id' => $previousOwnerId,
            'new_owner_id' => $newOwnerId,
            'reason' => $data['reason'],
        ]);

        return redirect()->route('schools.show', $receipt->school)
            ->with('status', 'Correction responsibility reassigned.');
    }
}
