<?php

namespace Tests\Feature\Public;

use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_show_page_loads_for_valid_token(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'sent']);

        $this->get($quotation->publicUrl())
            ->assertOk()
            ->assertSee($quotation->number);
    }

    public function test_invalid_token_returns_404(): void
    {
        $this->get(route('public.quotations.show', 'this-token-does-not-exist'))
            ->assertNotFound();
    }

    public function test_soft_deleted_quotation_link_returns_404(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'sent']);
        $url       = $quotation->publicUrl();
        $quotation->delete();

        $this->get($url)->assertNotFound();
    }

    public function test_accept_requires_signed_name(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'sent']);

        $this->post(route('public.quotations.accept', $quotation->ensurePublicToken()))
            ->assertSessionHasErrors('signed_name');

        $this->assertSame('sent', $quotation->fresh()->status);
    }

    public function test_accept_marks_quotation_accepted_and_creates_invoice(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'sent']);
        $token     = $quotation->ensurePublicToken();

        $this->post(route('public.quotations.accept', $token), [
            'signed_name' => 'Jane Customer',
        ])->assertRedirect();

        $quotation->refresh();
        $this->assertSame('accepted', $quotation->status);
        $this->assertSame('Jane Customer', $quotation->signed_name);
        $this->assertNotNull($quotation->customer_responded_at);
        $this->assertNotNull($quotation->customer_response_ip);
        $this->assertTrue(Invoice::where('quotation_id', $quotation->id)->exists());
    }

    public function test_reject_marks_quotation_rejected_with_reason(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'sent']);
        $token     = $quotation->ensurePublicToken();

        $this->post(route('public.quotations.reject', $token), [
            'reason' => 'Price too high',
        ])->assertRedirect();

        $quotation->refresh();
        $this->assertSame('rejected', $quotation->status);
        $this->assertSame('Price too high', $quotation->rejected_reason);
        $this->assertNotNull($quotation->customer_responded_at);
    }

    public function test_already_responded_quotation_cannot_be_re_accepted(): void
    {
        $quotation = Quotation::factory()->create(['status' => 'sent']);
        $token     = $quotation->ensurePublicToken();

        $this->post(route('public.quotations.accept', $token), ['signed_name' => 'First Signer']);
        $this->post(route('public.quotations.accept', $token), ['signed_name' => 'Second Signer'])
            ->assertSessionHas('info');

        $this->assertSame('First Signer', $quotation->fresh()->signed_name);
        $this->assertSame(1, Invoice::where('quotation_id', $quotation->id)->count());
    }

    public function test_expired_quotation_cannot_be_accepted(): void
    {
        $quotation = Quotation::factory()->create([
            'status'      => 'sent',
            'valid_until' => now()->subDay()->toDateString(),
        ]);
        $token = $quotation->ensurePublicToken();

        $this->post(route('public.quotations.accept', $token), ['signed_name' => 'Late Signer'])
            ->assertSessionHas('error');

        $this->assertSame('sent', $quotation->fresh()->status);
    }
}
