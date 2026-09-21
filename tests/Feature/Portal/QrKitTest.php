<?php

namespace Tests\Feature\Portal;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class QrKitTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function shop(array $rules = [], bool $portal = true, array $attrs = []): Tenant
    {
        $tenant   = $this->setUpTenant();
        $settings = $tenant->settings ?? [];
        $settings['modules']['loyalty']         = true;
        $settings['modules']['customer_portal'] = $portal;
        $settings['loyalty'] = array_replace(Tenant::LOYALTY_DEFAULTS, $rules);
        $tenant->update(array_merge(['settings' => $settings, 'name' => 'Sharma Salon'], $attrs));

        return $tenant->fresh();
    }

    private function admin(Tenant $tenant)
    {
        return $this->makeUser($tenant, 'tenant_admin');
    }

    private const JOIN = ['welcome_bonus_points' => 50, 'welcome_wa_number' => '919876543210', 'welcome_keyword' => 'JOIN'];

    // ── Access ─────────────────────────────────────────────────

    public function test_qr_kit_needs_the_portal_and_manage_permission(): void
    {
        $off = $this->shop([], false);
        $this->actingAs($this->admin($off))->get(route('tenant.loyalty.qr-kit.index'))->assertForbidden();
        $this->actingAs($this->admin($off))->get(route('tenant.loyalty.qr-kit.show', 'poster'))->assertForbidden();

        $on    = $this->shop();
        $staff = $this->makeUser($on, 'staff');
        $this->actingAs($staff)->get(route('tenant.loyalty.qr-kit.index'))->assertForbidden();

        $manager = $this->makeUser($on, 'staff', ['loyalty.manage']);
        $this->actingAs($manager)->get(route('tenant.loyalty.qr-kit.index'))->assertOk();
    }

    // ── Picker ─────────────────────────────────────────────────

    public function test_picker_offers_the_three_layouts(): void
    {
        $tenant = $this->shop();

        $this->actingAs($this->admin($tenant))->get(route('tenant.loyalty.qr-kit.index'))
            ->assertOk()
            ->assertSee('Window poster')
            ->assertSee('Counter stand')
            ->assertSee('Table tent')
            ->assertSee(route('tenant.loyalty.qr-kit.show', 'poster'), false)
            ->assertSee(route('tenant.loyalty.qr-kit.show', 'stand'), false)
            ->assertSee(route('tenant.loyalty.qr-kit.show', 'tent'), false);
    }

    public function test_picker_disables_join_until_the_whatsapp_welcome_is_set_up(): void
    {
        $bare = $this->shop();
        $this->actingAs($this->admin($bare))->get(route('tenant.loyalty.qr-kit.index'))
            ->assertSee('to enable this')
            ->assertSee('value="join"', false);
        $this->assertMatchesRegularExpression('/value="join"\s+disabled/', $this->actingAs($this->admin($bare))->get(route('tenant.loyalty.qr-kit.index'))->getContent());

        $ready = $this->shop(self::JOIN);
        $this->actingAs($this->admin($ready))->get(route('tenant.loyalty.qr-kit.index'))
            ->assertSee('https://wa.me/919876543210?text=JOIN', false)
            ->assertDontSee('to enable this');
    }

    public function test_picker_prefills_a_headline_for_the_shops_card_type(): void
    {
        $stamps = $this->shop(['mode' => 'stamps', 'stamps_required' => 6, 'stamp_reward' => 'Free coffee']);
        $this->actingAs($this->admin($stamps))->get(route('tenant.loyalty.qr-kit.index'))
            ->assertSee('Collect 6 stamps, get Free coffee!');

        $both = $this->shop(['mode' => 'both']);
        $this->actingAs($this->admin($both))->get(route('tenant.loyalty.qr-kit.index'))
            ->assertSee('Earn points &amp; collect stamps on every visit', false);

        $points = $this->shop();
        $this->actingAs($this->admin($points))->get(route('tenant.loyalty.qr-kit.index'))
            ->assertSee('Earn rewards every time you visit');
    }

    // ── Printouts ──────────────────────────────────────────────

    public function test_each_layout_sets_its_own_paper_size(): void
    {
        $tenant = $this->shop();
        $admin  = $this->admin($tenant);

        $this->actingAs($admin)->get(route('tenant.loyalty.qr-kit.show', 'poster'))->assertOk()->assertSee('size: A4 portrait', false)->assertSee('tpl-poster', false);
        $this->actingAs($admin)->get(route('tenant.loyalty.qr-kit.show', 'stand'))->assertOk()->assertSee('size: A5 portrait', false)->assertSee('tpl-stand', false);
        $this->actingAs($admin)->get(route('tenant.loyalty.qr-kit.show', 'tent'))->assertOk()->assertSee('size: A4 landscape', false)->assertSee('tpl-tent', false);
    }

    public function test_the_poster_carries_the_shop_headline_and_a_single_qr(): void
    {
        $tenant = $this->shop(['mode' => 'stamps', 'stamps_required' => 5, 'stamp_reward' => 'Free coffee']);

        $page = $this->actingAs($this->admin($tenant))->get(route('tenant.loyalty.qr-kit.show', 'poster'))->assertOk();

        $page->assertSee('Sharma Salon')
            ->assertSee('Collect 5 stamps, get Free coffee!')
            ->assertSee('js/qr-kit.js', false)
            ->assertSee('js/qrcode-generator.js', false);
        $this->assertSame(1, substr_count($page->getContent(), 'data-qr='));
    }

    public function test_the_table_tent_prints_two_panels_one_flipped(): void
    {
        $page = $this->actingAs($this->admin($this->shop()))->get(route('tenant.loyalty.qr-kit.show', 'tent'))->assertOk();

        $this->assertSame(2, substr_count($page->getContent(), 'data-qr='));
        $this->assertSame(1, substr_count($page->getContent(), 'class="half flip"'));
    }

    public function test_qr_targets_the_join_link_when_available_and_the_wallet_otherwise(): void
    {
        config(['app.base_domain' => 'saas-crm.test']);

        $ready = $this->shop(self::JOIN);
        $admin = $this->admin($ready);

        $this->actingAs($admin)->get(route('tenant.loyalty.qr-kit.show', 'poster'))
            ->assertSee('data-qr="https://wa.me/919876543210?text=JOIN"', false)
            ->assertSee('Scan to join our rewards club on WhatsApp');

        $wallet = $this->actingAs($admin)->get(route('tenant.loyalty.qr-kit.show', ['poster', 'qr' => 'wallet']))
            ->assertSee('Scan to open your rewards wallet');
        $this->assertMatchesRegularExpression('#data-qr="https?://saas-crm\.test/wallet/login"#', $wallet->getContent());
    }

    public function test_join_falls_back_to_the_wallet_when_it_is_not_configured(): void
    {
        config(['app.base_domain' => 'saas-crm.test']);

        $page = $this->actingAs($this->admin($this->shop()))->get(route('tenant.loyalty.qr-kit.show', ['stand', 'qr' => 'join']))->assertOk();
        $this->assertMatchesRegularExpression('#data-qr="https?://saas-crm\.test/wallet/login"#', $page->getContent());
    }

    public function test_the_wallet_link_always_uses_the_base_domain_never_the_shop_subdomain(): void
    {
        config(['app.base_domain' => 'saas-crm.test']);
        $tenant = $this->shop([], true, ['subdomain' => 'sharma']);

        $content = $this->actingAs($this->admin($tenant))->get(route('tenant.loyalty.qr-kit.show', ['poster', 'qr' => 'wallet']))->getContent();

        $this->assertStringContainsString('saas-crm.test/wallet/login', $content);
        $this->assertStringNotContainsString('sharma.saas-crm.test', $content);
    }

    public function test_a_custom_headline_is_used_and_escaped(): void
    {
        $tenant = $this->shop();

        $page = $this->actingAs($this->admin($tenant))
            ->get(route('tenant.loyalty.qr-kit.show', ['poster', 'headline' => '<script>alert(1)</script> Big Sale']))
            ->assertOk();

        $page->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; Big Sale', false);
    }

    public function test_bad_input_is_rejected(): void
    {
        $tenant = $this->shop();
        $admin  = $this->admin($tenant);

        $this->actingAs($admin)->get(route('tenant.loyalty.qr-kit.show', 'billboard'))->assertNotFound();
        $this->actingAs($admin)->get(route('tenant.loyalty.qr-kit.show', ['poster', 'qr' => 'nope']))->assertSessionHasErrors('qr');
        $this->actingAs($admin)->get(route('tenant.loyalty.qr-kit.show', ['poster', 'headline' => str_repeat('x', 91)]))->assertSessionHasErrors('headline');
    }

    public function test_a_shop_without_a_logo_gets_an_initial_and_one_with_a_logo_gets_the_image(): void
    {
        $plain = $this->shop();
        $this->actingAs($this->admin($plain))->get(route('tenant.loyalty.qr-kit.show', 'poster'))
            ->assertSee('class="mark">S<', false);

        $branded = $this->shop([], true, ['logo' => 'logos/sharma.png']);
        $this->actingAs($this->admin($branded))->get(route('tenant.loyalty.qr-kit.show', 'poster'))
            ->assertSee('storage/logos/sharma.png', false);
    }

    public function test_the_loyalty_rules_page_links_to_the_kit(): void
    {
        $tenant = $this->shop();

        $this->actingAs($this->admin($tenant))->get(route('tenant.loyalty.settings'))
            ->assertOk()
            ->assertSee('Print QR Kit')
            ->assertSee(route('tenant.loyalty.qr-kit.index'), false);
    }
}
