<?php

namespace App\Services;

class GstService
{
    /**
     * Split a total GST amount into CGST/SGST (intra-state) or IGST
     * (inter-state), based on the supplier's and recipient's state.
     *
     * When either state is unknown we assume an intra-state supply
     * (CGST+SGST) — the common case for a small single-state business —
     * and surface the breakdown so the user can correct it.
     *
     * @return array{place_of_supply: ?string, is_inter_state: bool, cgst_amount: float, sgst_amount: float, igst_amount: float}
     */
    public static function breakdown(float $taxAmount, ?string $supplierState, ?string $recipientState): array
    {
        $tax        = round(max(0.0, $taxAmount), 2);
        $interState = static::isInterState($supplierState, $recipientState);
        $pos        = static::stateCode($recipientState);

        if ($interState) {
            return [
                'place_of_supply' => $pos,
                'is_inter_state'  => true,
                'cgst_amount'     => 0.0,
                'sgst_amount'     => 0.0,
                'igst_amount'     => $tax,
            ];
        }

        $half = round($tax / 2, 2);

        return [
            'place_of_supply' => $pos,
            'is_inter_state'  => false,
            'cgst_amount'     => $half,
            'sgst_amount'     => round($tax - $half, 2),
            'igst_amount'     => 0.0,
        ];
    }

    // A party's state hint — its `state` field, falling back to a GSTIN.
    public static function partyState($party): ?string
    {
        if (!$party) {
            return null;
        }

        return $party->state
            ?: ($party->gst_number ?? $party->gst_no ?? null);
    }

    // Given the tax total and the two party states, produce the 5 DB
    // columns to merge into a document.
    public static function documentColumns(float $taxAmount, ?string $supplierState, ?string $recipientState): array
    {
        $b = static::breakdown($taxAmount, $supplierState, $recipientState);

        return [
            'place_of_supply' => $b['place_of_supply'],
            'is_inter_state'  => $b['is_inter_state'],
            'cgst_amount'     => $b['cgst_amount'],
            'sgst_amount'     => $b['sgst_amount'],
            'igst_amount'     => $b['igst_amount'],
        ];
    }

    // True only when both states are known and different.
    public static function isInterState(?string $a, ?string $b): bool
    {
        $ca = static::stateCode($a);
        $cb = static::stateCode($b);

        if ($ca === null || $cb === null) {
            return false;
        }

        return $ca !== $cb;
    }

    /**
     * Normalise a code ("KA"), a name ("Karnataka") or a 15-char GSTIN
     * (state = first two digits) to our 2-letter state code, or null.
     */
    public static function stateCode(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value  = trim($value);
        if ($value === '') {
            return null;
        }

        $states = config('crm.states', []);

        // GSTIN — first two digits are the numeric state code.
        if (preg_match('/^(\d{2})[0-9A-Za-z]{13}$/', $value, $m)) {
            return static::numericStateCodes()[$m[1]] ?? null;
        }

        $upper = strtoupper($value);
        if (array_key_exists($upper, $states)) {
            return $upper;
        }

        foreach ($states as $code => $name) {
            if (strcasecmp($name, $value) === 0) {
                return $code;
            }
        }

        return null;
    }

    public static function stateName(?string $value): ?string
    {
        $code = static::stateCode($value);

        return $code ? (config('crm.states')[$code] ?? $code) : null;
    }

    // Official GST numeric state codes → our config('crm.states') keys.
    private static function numericStateCodes(): array
    {
        return [
            '01' => 'JK', '02' => 'HP', '03' => 'PB', '04' => 'CH', '05' => 'UK',
            '06' => 'HR', '07' => 'DL', '08' => 'RJ', '09' => 'UP', '10' => 'BR',
            '11' => 'SK', '12' => 'AR', '13' => 'NL', '14' => 'MN', '15' => 'MZ',
            '16' => 'TR', '17' => 'ML', '18' => 'AS', '19' => 'WB', '20' => 'JH',
            '21' => 'OD', '22' => 'CG', '23' => 'MP', '24' => 'GJ', '26' => 'DN',
            '27' => 'MH', '29' => 'KA', '30' => 'GA', '31' => 'LD', '32' => 'KL',
            '33' => 'TN', '34' => 'PY', '35' => 'AN', '36' => 'TS', '37' => 'AP',
            '38' => 'LA',
        ];
    }
}
