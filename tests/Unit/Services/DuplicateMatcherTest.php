<?php

namespace Tests\Unit\Services;

use App\Models\Lead;
use App\Models\Tenant;
use App\Services\DuplicateMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalizes_phone_by_keeping_last_ten_digits(): void
    {
        $this->assertSame('9876543210', DuplicateMatcher::normalizePhone('+91 98765-43210'));
        $this->assertSame('9876543210', DuplicateMatcher::normalizePhone('09876543210'));
        $this->assertSame('9876543210', DuplicateMatcher::normalizePhone('9876543210'));
        $this->assertNull(DuplicateMatcher::normalizePhone(null));
        $this->assertNull(DuplicateMatcher::normalizePhone(''));
    }

    public function test_normalizes_email_case_and_whitespace(): void
    {
        $this->assertSame('a@b.com', DuplicateMatcher::normalizeEmail(' A@B.COM '));
        $this->assertNull(DuplicateMatcher::normalizeEmail(null));
        $this->assertNull(DuplicateMatcher::normalizeEmail('  '));
    }

    public function test_finds_existing_lead_by_phone_regardless_of_formatting(): void
    {
        $tenant = Tenant::factory()->create();
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id, 'phone' => '9876543210', 'email' => null]);

        $match = DuplicateMatcher::findExistingLead($tenant->id, '+91-98765 43210', null);

        $this->assertNotNull($match);
        $this->assertSame($lead->id, $match->id);
    }

    public function test_finds_existing_lead_by_email_case_insensitively(): void
    {
        $tenant = Tenant::factory()->create();
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id, 'phone' => '9111111111', 'email' => 'someone@example.com']);

        $match = DuplicateMatcher::findExistingLead($tenant->id, null, 'SomeOne@Example.com');

        $this->assertNotNull($match);
        $this->assertSame($lead->id, $match->id);
    }

    public function test_returns_null_when_no_match(): void
    {
        $tenant = Tenant::factory()->create();
        Lead::factory()->create(['tenant_id' => $tenant->id, 'phone' => '9111111111', 'email' => 'a@example.com']);

        $match = DuplicateMatcher::findExistingLead($tenant->id, '9222222222', 'b@example.com');

        $this->assertNull($match);
    }

    public function test_except_id_excludes_the_given_lead_from_matching(): void
    {
        $tenant = Tenant::factory()->create();
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id, 'phone' => '9333333333', 'email' => null]);

        $match = DuplicateMatcher::findExistingLead($tenant->id, '9333333333', null, $lead->id);

        $this->assertNull($match);
    }

    public function test_does_not_match_leads_from_a_different_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        Lead::factory()->create(['tenant_id' => $tenantA->id, 'phone' => '9444444444', 'email' => null]);

        $match = DuplicateMatcher::findExistingLead($tenantB->id, '9444444444', null);

        $this->assertNull($match);
    }
}
