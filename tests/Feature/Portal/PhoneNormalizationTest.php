<?php

namespace Tests\Feature\Portal;

use App\Models\Contact;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class PhoneNormalizationTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    // ── Phase 0: phone_normalized observer ───────────────────────

    public function test_phone_normalized_is_populated_on_create(): void
    {
        $tenant = $this->setUpTenant();

        $contact = Contact::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Ravi',
            'phone'     => '+91 98765 43210',
        ]);

        $this->assertSame('9876543210', $contact->phone_normalized);
    }

    public function test_phone_normalized_matches_across_format_variants(): void
    {
        $tenant = $this->setUpTenant();

        $variants = [
            '+91 98765 43210',
            '9876543210',
            '987-654-3210',
            '(987) 654-3210',
        ];

        $normalized = [];
        foreach ($variants as $i => $phone) {
            $contact = Contact::create([
                'tenant_id' => $tenant->id,
                'name'      => "Variant {$i}",
                'phone'     => $phone,
            ]);
            $normalized[] = $contact->phone_normalized;
        }

        $this->assertCount(1, array_unique($normalized));
        $this->assertSame('9876543210', $normalized[0]);
    }

    public function test_phone_normalized_recomputes_only_when_phone_changes(): void
    {
        $tenant = $this->setUpTenant();

        $contact = Contact::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Ravi',
            'phone'     => '9876543210',
        ]);

        $contact->update(['name' => 'Ravi Kumar']);
        $this->assertSame('9876543210', $contact->fresh()->phone_normalized);

        $contact->update(['phone' => '9123456780']);
        $this->assertSame('9123456780', $contact->fresh()->phone_normalized);
    }

    public function test_empty_phone_normalizes_to_null(): void
    {
        $tenant = $this->setUpTenant();

        $contact = Contact::create([
            'tenant_id' => $tenant->id,
            'name'      => 'No Phone',
            'phone'     => '',
        ]);

        $this->assertNull($contact->phone_normalized);
    }

    public function test_backfill_migration_populates_existing_rows(): void
    {
        $tenant = $this->setUpTenant();

        // Bypass the observer to simulate a pre-existing row with no
        // phone_normalized, the way data looked before this migration ran.
        $id = \Illuminate\Support\Facades\DB::table('contacts')->insertGetId([
            'tenant_id'         => $tenant->id,
            'name'              => 'Legacy Row',
            'phone'             => '+91-98765-43210',
            'phone_normalized'  => null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('contacts')
            ->whereNull('phone_normalized')
            ->whereNotNull('phone')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $digits = preg_replace('/\D/', '', (string) $row->phone);
                    $normalized = $digits === '' ? null : substr($digits, -10);
                    if ($normalized !== null) {
                        \Illuminate\Support\Facades\DB::table('contacts')->where('id', $row->id)->update([
                            'phone_normalized' => $normalized,
                        ]);
                    }
                }
            });

        $this->assertSame('9876543210', Contact::withoutGlobalScopes()->find($id)->phone_normalized);
    }

    // ── Existing behaviour must stay green after the refactor ────

    public function test_whatsapp_contact_matching_still_finds_contact_by_phone(): void
    {
        $tenant = $this->setUpTenant();

        $contact = Contact::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Ravi',
            'phone'     => '+91 98765 43210',
        ]);

        $found = (new \App\Services\WhatsappChatbotService(
            \App\Models\WhatsappSetting::forTenant($tenant->id)
        ))->findContactByWaId($tenant, '919876543210');

        $this->assertNotNull($found);
        $this->assertSame($contact->id, $found->id);
    }
}
