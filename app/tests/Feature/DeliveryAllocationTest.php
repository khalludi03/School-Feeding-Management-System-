<?php

namespace Tests\Feature;

use App\Models\DeliveryReceipt;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\NonWorkingDay;
use App\Models\School;
use App\Models\User;
use App\Services\DailyReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeliveryAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_allocations_can_split_a_receipt_across_scheduled_dates(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $item = $this->item($cycle);
        $tomorrow = today()->addDay();

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $item->id => 180],
            'chalan_number' => 'CH-001',
            'chalan_date' => today()->toDateString(),
            'chalan_photo' => UploadedFile::fake()->image('chalan.jpg'),
            'allocations' => [
                (string) $item->id => [
                    ['date' => today()->toDateString(), 'quantity' => 90],
                    ['date' => $tomorrow->toDateString(), 'quantity' => 90],
                ],
            ],
            'variance_explanation' => 'Split across two days.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $receipt = DeliveryReceipt::query()->sole();
        $todayRow = app(DailyReportService::class)->forDate(today())['rows'][0];
        $tomorrowRow = app(DailyReportService::class)->forDate($tomorrow)['rows'][0];

        $this->assertSame(90, $todayRow['delivered']['bread']);
        $this->assertSame(90, $tomorrowRow['delivered']['bread']);
        $this->assertSame(0, $todayRow['shortfall']['bread']);
        $this->assertSame(0, $tomorrowRow['shortfall']['bread']);
    }

    public function test_allocation_total_must_equal_received_quantity(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $item = $this->item($cycle);

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $item->id => 200],
            'chalan_number' => 'CH-001',
            'chalan_date' => today()->toDateString(),
            'chalan_photo' => UploadedFile::fake()->image('chalan.jpg'),
            'allocations' => [
                (string) $item->id => [
                    ['date' => today()->toDateString(), 'quantity' => 150],
                ],
            ],
            'variance_explanation' => 'Mismatch.',
        ])->assertSessionHasErrors('allocations.'.$item->id.'.total');

        $this->assertSame(0, DeliveryReceipt::query()->count());
    }

    public function test_allocation_date_cannot_be_before_receipt_date(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $item = $this->item($cycle);

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $item->id => 90],
            'chalan_number' => 'CH-001',
            'chalan_date' => today()->toDateString(),
            'chalan_photo' => UploadedFile::fake()->image('chalan.jpg'),
            'allocations' => [
                (string) $item->id => [
                    ['date' => today()->subDay()->toDateString(), 'quantity' => 90],
                ],
            ],
            'variance_explanation' => 'Backdated.',
        ])->assertSessionHasErrors('allocations.'.$item->id.'.0.date');

        $this->assertSame(0, DeliveryReceipt::query()->count());
    }

    public function test_allocation_date_must_be_a_working_day(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $item = $this->item($cycle);
        NonWorkingDay::factory()->create(['holiday_on' => today()->toDateString()]);

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $item->id => 90],
            'chalan_number' => 'CH-001',
            'chalan_date' => today()->toDateString(),
            'chalan_photo' => UploadedFile::fake()->image('chalan.jpg'),
            'allocations' => [
                (string) $item->id => [
                    ['date' => today()->toDateString(), 'quantity' => 90],
                ],
            ],
            'variance_explanation' => 'Holiday.',
        ])->assertSessionHasErrors('delivery_date');

        $this->assertSame(0, DeliveryReceipt::query()->count());
    }

    public function test_unscheduled_allocation_date_is_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $unscheduledDate = today()->next(Carbon::WEDNESDAY);
        $item = FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'bread',
            'name' => 'Bread',
            'sort_order' => 1,
            'supply_days' => 16,
            'supply_weekdays' => [Carbon::SUNDAY, Carbon::MONDAY],
        ]);

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $item->id => 90],
            'chalan_number' => 'CH-001',
            'chalan_date' => today()->toDateString(),
            'chalan_photo' => UploadedFile::fake()->image('chalan.jpg'),
            'allocations' => [
                (string) $item->id => [
                    ['date' => $unscheduledDate->toDateString(), 'quantity' => 90],
                ],
            ],
            'variance_explanation' => 'Unscheduled date.',
        ])->assertSessionHasErrors('allocations.'.$item->id.'.0.date');

        $this->assertSame(0, DeliveryReceipt::query()->count());
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

    private function item(FeedingCycle $cycle): FeedingItem
    {
        return FeedingItem::factory()
            ->for($cycle)
            ->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'bread', 'name' => 'Bread', 'sort_order' => 1, 'supply_days' => 16]);
    }
}
