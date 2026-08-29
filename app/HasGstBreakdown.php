<?php

namespace App;

trait HasGstBreakdown
{
    /**
     * Tax lines for display — either [CGST, SGST] or [IGST], falling back
     * to a single combined "GST" line for older rows saved before the
     * split existed.
     *
     * @return array<int, array{label: string, amount: float}>
     */
    public function gstLines(): array
    {
        $cgst = (float) ($this->cgst_amount ?? 0);
        $sgst = (float) ($this->sgst_amount ?? 0);
        $igst = (float) ($this->igst_amount ?? 0);

        if ($igst > 0) {
            return [['label' => 'IGST', 'amount' => $igst]];
        }

        if ($cgst > 0 || $sgst > 0) {
            return [
                ['label' => 'CGST', 'amount' => $cgst],
                ['label' => 'SGST', 'amount' => $sgst],
            ];
        }

        return [['label' => 'GST', 'amount' => (float) ($this->tax_amount ?? 0)]];
    }

    public function placeOfSupplyName(): ?string
    {
        return $this->place_of_supply
            ? (config('crm.states')[$this->place_of_supply] ?? $this->place_of_supply)
            : null;
    }
}
