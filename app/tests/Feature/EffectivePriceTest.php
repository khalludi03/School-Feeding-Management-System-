<?php

namespace Tests\Feature;

use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\FeedingItemPrice;
use App\Models\User;
use App\Services\ItemPriceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EffectivePriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_september_receipt_uses_september_price_even_after_october_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-10-31',
            'ration_factor' => 0.900,
        ]);
        $item = FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana_bread',
            'name' => 'Bread',
            'unit' => 'packet',
            'unit_price' => 10.000,
        ]);
        FeedingItemPrice::create([
            'feeding_cycle_id' => $cycle->id,
            'feeding_item_id' => $item->id,
            'unit_price' => 12.000,
            'effective_on' => '2026-10-01',
            'created_by' => $admin->id,
        ]);

        $septemberPrice = app(ItemPriceService::class)->priceFor($item, Carbon::parse('2026-09-15'));
        $octoberPrice = app(ItemPriceService::class)->priceFor($item, Carbon::parse('2026-10-15'));

        $this->assertSame(10.0, $septemberPrice['price']);
        $this->assertSame($cycle->starts_on->toDateString(), $septemberPrice['effective_on']);
        $this->assertSame('item_default', $septemberPrice['source']);

        $this->assertSame(12.0, $octoberPrice['price']);
        $this->assertSame('2026-10-01', $octoberPrice['effective_on']);
        $this->assertSame('dated', $octoberPrice['source']);
    }

    public function test_price_change_does_not_alter_earlier_receipt_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-10-31',
            'ration_factor' => 0.900,
        ]);
        $item = FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana_bread',
            'name' => 'Bread',
            'unit' => 'packet',
            'unit_price' => 10.000,
        ]);

        $originalPrice = app(ItemPriceService::class)->priceFor($item, Carbon::parse('2026-09-15'));

        FeedingItemPrice::create([
            'feeding_cycle_id' => $cycle->id,
            'feeding_item_id' => $item->id,
            'unit_price' => 12.000,
            'effective_on' => '2026-10-01',
            'created_by' => $admin->id,
        ]);

        $unchangedPrice = app(ItemPriceService::class)->priceFor($item, Carbon::parse('2026-09-15'));

        $this->assertSame($originalPrice['price'], $unchangedPrice['price']);
        $this->assertSame('item_default', $unchangedPrice['source']);
    }

    public function test_three_decimals_are_retained(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        $item = FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'boiled_egg',
            'name' => 'Egg',
            'unit' => 'piece',
            'unit_price' => 5.000,
        ]);

        $today = today()->toDateString();

        $reviewResponse = $this->actingAs($admin)->post(route('prices.review', $cycle), [
            'feeding_item_id' => $item->id,
            'unit_price' => '5.555',
            'effective_on' => $today,
            'reason' => 'Test price with three decimals',
        ]);

        $reviewResponse->assertRedirect();
        $location = $reviewResponse->headers->get('Location');
        $this->assertStringContainsString('/prices/review/', $location);

        $token = trim($location, '/');
        $token = (string) last(explode('/', $token));

        $storeResponse = $this->actingAs($admin)->post(route('prices.store', $cycle), [
            'token' => $token,
            'feeding_item_id' => $item->id,
            'unit_price' => '5.555',
            'effective_on' => $today,
            'reason' => 'Test price with three decimals',
        ]);

        $storeResponse->assertSessionHas('status');

        $price = FeedingItemPrice::query()
            ->where('feeding_cycle_id', $cycle->id)
            ->where('feeding_item_id', $item->id)
            ->whereDate('effective_on', $today)
            ->firstOrFail();

        $this->assertSame('5.555', number_format((float) $price->unit_price, 3));
    }
}
