<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Lead;
use App\Services\DuplicateMatcher;
use App\Services\MergeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DuplicateController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // ── Leads ─────────────────────────────────────────────────────
    public function leadsIndex(): View
    {
        $groups = $this->findDuplicateGroups(Lead::class, ['name', 'phone', 'email', 'company', 'status', 'created_at']);

        return view('tenant.duplicates.leads', ['groups' => $groups]);
    }

    public function mergeLeads(Request $request): RedirectResponse
    {
        $request->validate([
            'primary_id' => ['required', 'integer'],
            'loser_ids'  => ['required', 'array', 'min:1'],
            'loser_ids.*' => ['integer'],
        ]);

        if (in_array($request->primary_id, $request->loser_ids)) {
            return back()->with('error', 'A record cannot be merged into itself.');
        }

        $warnings = (new MergeService())->mergeLeads($this->tenantId(), (int) $request->primary_id, $request->loser_ids);

        return redirect()->route('tenant.leads.duplicates')
            ->with('success', 'Leads merged successfully.' . (count($warnings) ? ' ' . implode(' ', $warnings) : ''));
    }

    // ── Contacts ──────────────────────────────────────────────────
    public function contactsIndex(): View
    {
        $groups = $this->findDuplicateGroups(Contact::class, ['name', 'phone', 'email', 'company', 'city', 'created_at']);

        return view('tenant.duplicates.contacts', ['groups' => $groups]);
    }

    public function mergeContacts(Request $request): RedirectResponse
    {
        $request->validate([
            'primary_id' => ['required', 'integer'],
            'loser_ids'  => ['required', 'array', 'min:1'],
            'loser_ids.*' => ['integer'],
        ]);

        if (in_array($request->primary_id, $request->loser_ids)) {
            return back()->with('error', 'A record cannot be merged into itself.');
        }

        $warnings = (new MergeService())->mergeContacts($this->tenantId(), (int) $request->primary_id, $request->loser_ids);

        return redirect()->route('tenant.contacts.duplicates')
            ->with('success', 'Contacts merged successfully.' . (count($warnings) ? ' ' . implode(' ', $warnings) : ''));
    }

    // ── Shared grouping (union-find over normalized phone/email) ───
    private function findDuplicateGroups(string $modelClass, array $columns): \Illuminate\Support\Collection
    {
        $rows = $modelClass::where('tenant_id', $this->tenantId())
            ->orderBy('id')
            ->get(array_unique(array_merge(['id'], $columns)))
            ->values();

        $n      = $rows->count();
        $parent = range(0, max($n - 1, 0));

        $find = function (int $x) use (&$parent, &$find) {
            while ($parent[$x] !== $x) {
                $parent[$x] = $parent[$parent[$x]];
                $x = $parent[$x];
            }
            return $x;
        };
        $union = function (int $a, int $b) use (&$parent, $find) {
            $ra = $find($a);
            $rb = $find($b);
            if ($ra !== $rb) $parent[$rb] = $ra;
        };

        $phoneSeenAt = [];
        $emailSeenAt = [];

        foreach ($rows as $i => $row) {
            $p = DuplicateMatcher::normalizePhone($row->phone);
            $e = DuplicateMatcher::normalizeEmail($row->email);

            if ($p !== null) {
                if (isset($phoneSeenAt[$p])) {
                    $union($phoneSeenAt[$p], $i);
                } else {
                    $phoneSeenAt[$p] = $i;
                }
            }
            if ($e !== null) {
                if (isset($emailSeenAt[$e])) {
                    $union($emailSeenAt[$e], $i);
                } else {
                    $emailSeenAt[$e] = $i;
                }
            }
        }

        $buckets = [];
        foreach ($rows as $i => $row) {
            $root = $find($i);
            $buckets[$root][] = $row;
        }

        return collect($buckets)
            ->filter(fn($bucket) => count($bucket) > 1)
            ->values()
            ->map(fn($bucket) => collect($bucket));
    }
}
