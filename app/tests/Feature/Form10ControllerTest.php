<?php

namespace Tests\Feature;

use App\Models\Form10Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Form10ControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_does_not_increment_serial(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)->get('/admin/form10/report?month=2026-09&contract_number=C-123');
        $response->assertStatus(200);

        $this->assertEquals(0, Form10Invoice::count());
    }

    public function test_pdf_generation_saves_invoice_and_increments_serial(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $this->assertEquals(0, Form10Invoice::count());

        $response = $this->actingAs($admin)->post('/admin/form10/report/pdf', [
            'month' => '2026-09',
            'contract_number' => 'C-123',
            'bank_account_name' => 'Test Bank',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, Form10Invoice::count());

        $invoice = Form10Invoice::first();
        $this->assertEquals('AN-00001', $invoice->invoice_no);
        $this->assertEquals('C-123', $invoice->contract_number);
        $this->assertEquals('Test Bank', $invoice->bank_account_name);

        // Next one
        $response2 = $this->actingAs($admin)->post('/admin/form10/report/export', [
            'month' => '2026-10',
            'contract_number' => 'C-124',
        ]);
        
        $response2->assertStatus(200);
        $this->assertEquals(2, Form10Invoice::count());
        $invoice2 = Form10Invoice::latest('id')->first();
        $this->assertEquals('AN-00002', $invoice2->invoice_no);
    }
}
