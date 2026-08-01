<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\CustomFieldValue;
use App\Models\Deal;
use App\Models\Followup;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadCallLog;
use App\Models\Quotation;
use App\Models\Reminder;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

// Merges duplicate Leads/Contacts found by the Duplicates review screen.
// The "primary" record survives; "losers" are soft-deleted after every
// relation pointing at them is repointed to the primary.
class MergeService
{
    // Returns a list of human-readable warnings (e.g. "a deal was left
    // linked to the archived lead") — never throws for normal conflicts.
    public function mergeLeads(int $tenantId, int $primaryId, array $loserIds): array
    {
        $loserIds = array_values(array_diff(array_unique($loserIds), [$primaryId]));
        $warnings = [];

        DB::transaction(function () use ($tenantId, $primaryId, $loserIds, &$warnings) {
            $primary = Lead::where('tenant_id', $tenantId)->lockForUpdate()->findOrFail($primaryId);

            foreach ($loserIds as $loserId) {
                $loser = Lead::where('tenant_id', $tenantId)->lockForUpdate()->find($loserId);
                if (!$loser) continue;

                Followup::where('tenant_id', $tenantId)->where('lead_id', $loserId)->update(['lead_id' => $primaryId]);
                LeadCallLog::where('tenant_id', $tenantId)->where('lead_id', $loserId)->update(['lead_id' => $primaryId]);
                Task::where('tenant_id', $tenantId)
                    ->where('taskable_type', Lead::class)->where('taskable_id', $loserId)
                    ->update(['taskable_id' => $primaryId]);
                Reminder::where('tenant_id', $tenantId)
                    ->where('remindable_type', Lead::class)->where('remindable_id', $loserId)
                    ->update(['remindable_id' => $primaryId]);

                // Deal is ~1:1 off a Lead — only repoint if the primary has none yet.
                $loserDeal = Deal::where('tenant_id', $tenantId)->where('lead_id', $loserId)->first();
                if ($loserDeal) {
                    $primaryHasDeal = Deal::where('tenant_id', $tenantId)->where('lead_id', $primaryId)->exists();
                    if (!$primaryHasDeal) {
                        $loserDeal->update(['lead_id' => $primaryId]);
                    } else {
                        $warnings[] = "Lead #{$loserId} had its own deal — left linked to the archived lead, not moved.";
                    }
                }

                // Contact is ~1:1 off a converted Lead — same conflict handling.
                $loserContact = Contact::where('tenant_id', $tenantId)->where('lead_id', $loserId)->first();
                if ($loserContact) {
                    $primaryHasContact = Contact::where('tenant_id', $tenantId)->where('lead_id', $primaryId)->exists();
                    if (!$primaryHasContact) {
                        $loserContact->update(['lead_id' => $primaryId]);
                    } else {
                        $warnings[] = "Lead #{$loserId} had its own converted contact — left linked to the archived lead, not moved.";
                    }
                }

                $this->mergeCustomFieldValues($tenantId, Lead::class, $loserId, $primaryId);

                $loser->delete();
            }
        });

        return $warnings;
    }

    public function mergeContacts(int $tenantId, int $primaryId, array $loserIds): array
    {
        $loserIds = array_values(array_diff(array_unique($loserIds), [$primaryId]));
        $warnings = [];

        DB::transaction(function () use ($tenantId, $primaryId, $loserIds) {
            Contact::where('tenant_id', $tenantId)->lockForUpdate()->findOrFail($primaryId);

            foreach ($loserIds as $loserId) {
                $loser = Contact::where('tenant_id', $tenantId)->lockForUpdate()->find($loserId);
                if (!$loser) continue;

                Followup::where('tenant_id', $tenantId)->where('contact_id', $loserId)->update(['contact_id' => $primaryId]);
                Task::where('tenant_id', $tenantId)
                    ->where('taskable_type', Contact::class)->where('taskable_id', $loserId)
                    ->update(['taskable_id' => $primaryId]);

                // Contact -> Deal/Quotation/Invoice are all hasMany — safe to reassign all.
                Deal::where('tenant_id', $tenantId)->where('contact_id', $loserId)->update(['contact_id' => $primaryId]);
                Quotation::where('tenant_id', $tenantId)->where('contact_id', $loserId)->update(['contact_id' => $primaryId]);
                Invoice::where('tenant_id', $tenantId)->where('contact_id', $loserId)->update(['contact_id' => $primaryId]);

                $loser->delete();
            }
        });

        return $warnings;
    }

    private function mergeCustomFieldValues(int $tenantId, string $modelClass, int $loserId, int $primaryId): void
    {
        $loserValues = CustomFieldValue::where('tenant_id', $tenantId)
            ->where('model_type', $modelClass)
            ->where('model_id', $loserId)
            ->get();

        foreach ($loserValues as $value) {
            $primaryHasValue = CustomFieldValue::where('tenant_id', $tenantId)
                ->where('model_type', $modelClass)
                ->where('model_id', $primaryId)
                ->where('field_key', $value->field_key)
                ->exists();

            if ($primaryHasValue) {
                $value->delete();
            } else {
                $value->update(['model_id' => $primaryId]);
            }
        }
    }
}
