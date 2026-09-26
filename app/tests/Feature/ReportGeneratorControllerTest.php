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

class ReportGeneratorControllerTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_index_page_loads_successfully(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get('/admin/reports/choose');

        $response->assertStatus(200);
        $response->assertSee('Form 4');
        $response->assertSee('Form 7');
        $response->assertSee('Form 10');
        $response->assertSee('Form 12');
        $response->assertSee('Form 13');
    }

    public function test_end_date_before_start_date_returns_validation_error(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post('/admin/reports/generate', [
            'form_type' => '7',
            'period_mode' => 'range',
            'start_date' => '2025-03-15',
            'end_date' => '2025-03-10',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('end_date');
    }

    public function test_invalid_month_format_returns_validation_error(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post('/admin/reports/generate', [
            'form_type' => '7',
            'period_mode' => 'month',
            'month' => '2025-13',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('month');
    }

    public function test_invalid_form_type_returns_validation_error(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post('/admin/reports/generate', [
            'form_type' => '99',
            'period_mode' => 'month',
            'month' => '2025-03',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('form_type');
    }

    public function test_per_period_form_redirects_to_pdf_route(): void
    {
        $admin = $this->adminUser();
        $cycle = $this->openCycle();
        $this->items($cycle);

        $response = $this->actingAs($admin)->post('/admin/reports/generate', [
            'form_type' => '7',
            'period_mode' => 'month',
            'month' => now()->format('Y-m'),
        ]);

        $response->assertRedirect();
        $response->assertRedirectContains(route('admin.form7.pdf', absolute: false));
    }

    public function test_per_school_form_with_school_selected_redirects_to_pdf_route(): void
    {
        $admin = $this->adminUser();
        $cycle = $this->openCycle();
        $items = $this->items($cycle);
        $school = $this->participatingSchool($cycle);

        $response = $this->actingAs($admin)->post('/admin/reports/generate', [
            'form_type' => '4',
            'period_mode' => 'month',
            'month' => now()->format('Y-m'),
            'school_id' => $school->id,
        ]);

        $response->assertRedirect();
        $response->assertRedirectContains(route('admin.form4.pdf', ['school' => $school->id], false));
    }

    public function test_per_school_form_without_school_returns_zip_bundle(): void
    {
        $admin = $this->adminUser();
        $cycle = $this->openCycle();
        $items = $this->items($cycle);
        $school = $this->participatingSchool($cycle);
        $this->seedReceiptData($school, $cycle, $items);

        $response = $this->actingAs($admin)->post('/admin/reports/generate', [
            'form_type' => '4',
            'period_mode' => 'month',
            'month' => now()->format('Y-m'),
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
        $response->assertHeader('Content-Disposition');
    }

    public function test_zip_bundle_contains_pdf_files(): void
    {
        $admin = $this->adminUser();
        $cycle = $this->openCycle();
        $items = $this->items($cycle);
        $school = $this->participatingSchool($cycle);
        $this->seedReceiptData($school, $cycle, $items);

        $response = $this->actingAs($admin)->post('/admin/reports/generate', [
            'form_type' => '4',
            'period_mode' => 'month',
            'month' => now()->format('Y-m'),
        ]);

        $content = $response->getContent();
        $tempFile = tempnam(sys_get_temp_dir(), 'zip_test');
        file_put_contents($tempFile, $content);

        $zip = new \ZipArchive;
        $zip->open($tempFile);
        $this->assertGreaterThan(0, $zip->numFiles);
        $zip->close();
        unlink($tempFile);
    }

    public function test_per_school_form_without_school_generates_zip(): void
    {
        $admin = $this->adminUser();
        $cycle = $this->openCycle();
        $items = $this->items($cycle);
        $this->participatingSchool($cycle);

        $response = $this->actingAs($admin)->post('/admin/reports/generate', [
            'form_type' => '4',
            'period_mode' => 'month',
            'month' => now()->format('Y-m'),
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
    }

    public function test_date_range_mode_redirects_to_pdf_with_date_params(): void
    {
        $admin = $this->adminUser();
        $cycle = $this->openCycle();
        $items = $this->items($cycle);
        $school = $this->participatingSchool($cycle);

        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();

        $response = $this->actingAs($admin)->post('/admin/reports/generate', [
            'form_type' => '4',
            'period_mode' => 'range',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'school_id' => $school->id,
        ]);

        $response->assertRedirect();
        $redirectUrl = $response->getTargetUrl();
        $this->assertStringContainsString('start=', $redirectUrl);
        $this->assertStringContainsString('end=', $redirectUrl);
    }

    public function test_date_range_mode_zip_bundle(): void
    {
        $admin = $this->adminUser();
        $cycle = $this->openCycle();
        $items = $this->items($cycle);
        $school = $this->participatingSchool($cycle);
        $this->seedReceiptData($school, $cycle, $items);

        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();

        $response = $this->actingAs($admin)->post('/admin/reports/generate', [
            'form_type' => '12',
            'period_mode' => 'range',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
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

    private function items(FeedingCycle $cycle): array
    {
        $bread = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'bread', 'name' => 'Bread', 'sort_order' => 1, 'supply_days' => 16]);
        $egg = FeedingItem::factory()->for($cycle)->withoutSupplyPattern()
            ->create(['item_key' => 'egg', 'name' => 'Egg', 'sort_order' => 2, 'supply_days' => 12]);
        $banana = FeedingItem::factory()->for($cycle)->withoutSupplyPattern()
            ->create(['item_key' => 'banana', 'name' => 'Banana', 'sort_order' => 3, 'supply_days' => 5]);

        return [$bread, $egg, $banana];
    }

    private function participatingSchool(FeedingCycle $cycle, int $pupils = 100): School
    {
        $school = School::factory()->create();
        $school->participationPeriods()->create(['starts_on' => $cycle->starts_on->toDateString()]);
        $school->enrolments()->create([
            'effective_on' => $cycle->starts_on->toDateString(),
            'pupil_count' => $pupils,
            'source' => 'test',
        ]);

        return $school->refresh();
    }

    private function seedReceiptData(School $school, FeedingCycle $cycle, array $items): void
    {
        $receipt = DeliveryReceipt::factory()
            ->for($school)
            ->create([
                'delivery_date' => now()->toDateString(),
                'chalan_date' => now()->subDays(2)->toDateString(),
            ]);

        foreach ($items as $item) {
            DeliveryReceiptItem::factory()
                ->create([
                    'delivery_receipt_id' => $receipt->id,
                    'feeding_item_id' => $item->id,
                    'delivered_quantity' => 10,
                ]);
        }
    }
}
