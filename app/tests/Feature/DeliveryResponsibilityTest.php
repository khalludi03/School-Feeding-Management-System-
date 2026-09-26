<?php

namespace Tests\Feature;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryResponsibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reassign_correction_responsibility(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'field_staff']);
        $newOwner = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $receipt = $this->receipt($school, $owner);

        $this->actingAs($admin)->put(route('admin.receipts.assign.update', $receipt), [
            'responsible_by' => $newOwner->id,
            'reason' => 'Original owner is on leave.',
        ])->assertRedirect();

        $this->assertSame($newOwner->id, $receipt->fresh()->responsible_by);
        $this->assertSame($owner->id, $receipt->fresh()->entered_by);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'action' => 'delivery_responsibility_reassigned',
        ]);
    }

    public function test_reassignment_rejects_inactive_or_non_field_staff_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'field_staff']);
        $inactive = User::factory()->create(['role' => 'field_staff', 'is_active' => false]);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $receipt = $this->receipt($school, $owner);

        $this->actingAs($admin)->put(route('admin.receipts.assign.update', $receipt), [
            'responsible_by' => $inactive->id,
            'reason' => 'Should fail.',
        ])->assertSessionHasErrors('responsible_by');

        $this->assertSame($owner->id, $receipt->fresh()->responsible_by);
    }

    public function test_former_owner_cannot_edit_after_reassignment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'field_staff']);
        $newOwner = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $item = $this->item($cycle);
        $receipt = $this->receipt($school, $owner);
        $this->line($receipt, $item, 90);

        $this->actingAs($admin)->put(route('admin.receipts.assign.update', $receipt), [
            'responsible_by' => $newOwner->id,
            'reason' => 'Owner changed.',
        ])->assertRedirect();

        $this->actingAs($owner)->get(route('field.delivery.edit', $receipt))->assertNotFound();
        $this->actingAs($newOwner)->get(route('field.delivery.edit', $receipt))->assertOk();
    }

    public function test_my_entries_includes_records_assigned_to_the_user(): void
    {
        $owner = User::factory()->create(['role' => 'field_staff']);
        $assignee = User::factory()->create(['role' => 'field_staff']);
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $receipt = $this->receipt($school, $owner);

        $receipt->responsible_by = $assignee->id;
        $receipt->save();

        $this->actingAs($assignee)->get(route('field.entries'))
            ->assertOk()
            ->assertSee($school->code);
    }

    private function openCycle(): FeedingCycle
    {
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => today()->subDays(5)->toDateString(),
            'ends_on' => today()->addDays(20)->toDateString(),
        ]);
        $cycle->forceFill(['ration_factor' => 0.9])->save();

        return $cycle->refresh();
    }

    private function participatingSchool(FeedingCycle $cycle): School
    {
        $school = School::factory()->create();
        $school->participationPeriods()->create(['starts_on' => $cycle->starts_on->toDateString()]);
        $school->enrolments()->create([
            'effective_on' => $cycle->starts_on->toDateString(),
            'pupil_count' => 100,
            'source' => 'test',
        ]);

        return $school->refresh();
    }

    private function receipt(School $school, User $owner): DeliveryReceipt
    {
        $receipt = new DeliveryReceipt;
        $receipt->school_id = $school->id;
        $receipt->delivery_date = today()->toDateString();
        $receipt->entered_by = $owner->id;
        $receipt->responsible_by = $owner->id;
        $receipt->save();

        return $receipt;
    }

    private function line(DeliveryReceipt $receipt, FeedingItem $item, int $quantity): void
    {
        $line = new DeliveryReceiptItem;
        $line->delivery_receipt_id = $receipt->id;
        $line->feeding_item_id = $item->id;
        $line->delivered_quantity = $quantity;
        $line->save();
    }

    private function item(FeedingCycle $cycle): FeedingItem
    {
        return FeedingItem::factory()
            ->for($cycle)
            ->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'bread', 'name' => 'Bread', 'sort_order' => 1, 'supply_days' => 16]);
    }
}
