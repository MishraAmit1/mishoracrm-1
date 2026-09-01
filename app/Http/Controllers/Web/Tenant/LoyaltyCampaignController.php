<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\LoyaltyCampaign;
use App\Services\LoyaltyCampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoyaltyCampaignController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function find(int|string $id): LoyaltyCampaign
    {
        return LoyaltyCampaign::where('tenant_id', $this->tenantId())
            ->withCount('recipients')
            ->findOrFail($id);
    }

    public function index(): View
    {
        $campaigns = LoyaltyCampaign::where('tenant_id', $this->tenantId())
            ->withCount('recipients')
            ->latest()
            ->paginate(20);

        return view('tenant.loyalty.campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        $contacts = Contact::where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('tenant.loyalty.campaigns.create', compact('contacts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                     => ['required', 'string', 'max:150'],
            'segment_type'             => ['required', 'in:' . implode(',', LoyaltyCampaign::SEGMENTS)],
            'min_spend'                => ['nullable', 'numeric', 'min:0'],
            'min_visits'               => ['nullable', 'integer', 'min:1'],
            'spend_within_days'        => ['nullable', 'integer', 'min:1', 'max:3650'],
            'visits_within_days'       => ['nullable', 'integer', 'min:1', 'max:3650'],
            'inactive_days'            => ['nullable', 'integer', 'min:1', 'max:3650'],
            'tier'                     => ['nullable', 'in:bronze,silver,gold'],
            'category'                 => ['required_if:segment_type,category', 'nullable', 'string', 'max:80'],
            'category_min_spend'       => ['nullable', 'numeric', 'min:0'],
            'category_within_days'     => ['nullable', 'integer', 'min:1', 'max:3650'],
            'contact_ids'              => ['nullable', 'array'],
            'contact_ids.*'            => ['integer'],
            'reward_type'              => ['required', 'in:' . implode(',', LoyaltyCampaign::REWARDS)],
            'reward_value'             => ['required_unless:reward_type,free_item', 'nullable', 'numeric', 'min:0'],
            'reward_item'              => ['required_if:reward_type,free_item', 'nullable', 'string', 'max:120'],
            'max_discount'             => ['nullable', 'numeric', 'min:0'],
            'code_mode'                => ['required', 'in:unique,shared'],
            'shared_code'              => ['required_if:code_mode,shared', 'nullable', 'string', 'max:20'],
            'expires_at'               => ['nullable', 'date', 'after:today'],
            'usage_limit_per_customer' => ['required', 'integer', 'min:1', 'max:100'],
            'total_redemption_cap'     => ['nullable', 'integer', 'min:1'],
            'delivery'                 => ['required', 'in:none,whatsapp,email,both'],
        ]);

        $config = match ($data['segment_type']) {
            'spend'    => ['min_spend' => (float) ($data['min_spend'] ?? 0), 'within_days' => $data['spend_within_days'] ?? null],
            'visits'   => ['min_visits' => (int) ($data['min_visits'] ?? 1), 'within_days' => $data['visits_within_days'] ?? null],
            'inactive' => ['inactive_days' => (int) ($data['inactive_days'] ?? 60)],
            'tier'     => ['tier' => $data['tier'] ?? 'gold'],
            'category' => [
                'category'    => trim($data['category']),
                'min_spend'   => (float) ($data['category_min_spend'] ?? 0),
                'within_days' => $data['category_within_days'] ?? null,
            ],
            'manual'   => ['contact_ids' => array_values($data['contact_ids'] ?? [])],
        };

        $campaign = LoyaltyCampaign::create([
            'tenant_id'                => $this->tenantId(),
            'created_by'               => auth()->id(),
            'name'                     => $data['name'],
            'segment_type'             => $data['segment_type'],
            'segment_config'           => $config,
            'reward_type'              => $data['reward_type'],
            'reward_value'             => $data['reward_type'] === 'free_item' ? 0 : ($data['reward_value'] ?? 0),
            'reward_item'              => $data['reward_item'] ?? null,
            'max_discount'             => $data['reward_type'] === 'percent' ? ($data['max_discount'] ?? null) : null,
            'code_mode'                => $data['code_mode'],
            'shared_code'              => $data['code_mode'] === 'shared'
                ? strtoupper($data['shared_code'])
                : null,
            'expires_at'               => $data['expires_at'] ?? null,
            'usage_limit_per_customer' => $data['usage_limit_per_customer'],
            'total_redemption_cap'     => $data['total_redemption_cap'] ?? null,
            'delivery'                 => $data['delivery'],
            'status'                   => 'draft',
        ]);

        return redirect()->route('tenant.loyalty.campaigns.show', $campaign->id)
            ->with('success', 'Campaign saved as a draft. Review the audience, then launch.');
    }

    public function show(int|string $id): View
    {
        $campaign = $this->find($id);

        $previewCount = null;
        $recipients   = collect();

        if ($campaign->status === 'draft') {
            $previewCount = app(LoyaltyCampaignService::class)->previewCount($campaign);
        } else {
            $recipients = $campaign->recipients()->with('contact:id,name,phone')->latest('id')->paginate(30);
        }

        return view('tenant.loyalty.campaigns.show', compact('campaign', 'previewCount', 'recipients'));
    }

    public function launch(int|string $id): RedirectResponse
    {
        $campaign = $this->find($id);

        if ($campaign->status !== 'draft') {
            return back()->with('error', 'This campaign has already been launched.');
        }

        $count = app(LoyaltyCampaignService::class)->launch($campaign);

        return back()->with('success', "Campaign launched to {$count} customer(s).");
    }

    public function end(int|string $id): RedirectResponse
    {
        $campaign = $this->find($id);
        app(LoyaltyCampaignService::class)->end($campaign);

        return back()->with('success', 'Campaign ended — its codes can no longer be redeemed.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $campaign = $this->find($id);

        if ($campaign->status === 'active') {
            return back()->with('error', 'End the campaign before deleting it.');
        }

        $campaign->recipients()->delete();
        $campaign->delete();

        return redirect()->route('tenant.loyalty.campaigns.index')
            ->with('success', 'Campaign deleted.');
    }
}
