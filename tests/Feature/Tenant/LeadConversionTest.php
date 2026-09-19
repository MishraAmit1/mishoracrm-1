<?php

namespace Tests\Feature\Tenant;

use App\Models\Contact;
use App\Models\ApiKey;
use App\Models\Deal;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class LeadConversionTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    public function test_convert_creates_a_linked_contact_and_deal(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $lead   = Lead::factory()->create([
            'tenant_id'  => $tenant->id,
            'lead_value' => 5000,
        ]);

        $this->actingAs($admin)->post(route('tenant.leads.convert', $lead->id))
            ->assertRedirect();

        $lead->refresh();
        $this->assertSame('converted', $lead->status);
        $this->assertNotNull($lead->converted_at);

        $contact = Contact::where('lead_id', $lead->id)->first();
        $this->assertNotNull($contact);
        $this->assertSame($lead->name, $contact->name);
        $this->assertSame($lead->phone, $contact->phone);

        $deal = Deal::where('lead_id', $lead->id)->first();
        $this->assertNotNull($deal);
        $this->assertSame($contact->id, $deal->contact_id);
        $this->assertSame('new', $deal->stage);
        $this->assertEquals(5000, $deal->value);
    }

    public function test_converting_an_already_converted_lead_is_idempotent(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id]);

        $contact1 = $lead->convertToContact();
        $lead->refresh();
        $contact2 = $lead->convertToContact();

        $this->assertSame($contact1->id, $contact2->id);
        $this->assertSame(1, Contact::where('lead_id', $lead->id)->count());
        $this->assertSame(1, Deal::where('lead_id', $lead->id)->count());
    }

    public function test_convert_web_route_redirects_to_existing_contact_if_already_converted(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id]);
        $contact = $lead->convertToContact();
        $lead->refresh();

        $this->actingAs($admin)->post(route('tenant.leads.convert', $lead->id))
            ->assertRedirect(route('tenant.contacts.show', $contact->id));
    }

    public function test_api_convert_has_parity_with_web(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $admin->givePermissionTo('leads.convert');
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $admin->id]);

        $this->giveActiveSubscription($tenant);
        $apiKey = ApiKey::generate($tenant->id, $admin->id, 'test-key');

        $response = $this->withHeader('X-API-Key', $apiKey->key)
            ->postJson("/api/v1/tenant/leads/{$lead->id}/convert");

        $response->assertOk()->assertJson(['success' => true]);

        $lead->refresh();
        $this->assertSame('converted', $lead->status);
        $this->assertSame(1, Contact::where('lead_id', $lead->id)->count());
    }
}
