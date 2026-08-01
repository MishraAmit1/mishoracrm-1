<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Lead;

// Shared phone/email matching used by both bulk import (skip-if-duplicate)
// and the Duplicates review/merge screens (group-if-duplicate).
class DuplicateMatcher
{
    // Keeps only digits and the last 10 — good enough for Indian mobile
    // numbers regardless of +91 / 0 / spaces / dashes formatting.
    public static function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;

        $digits = preg_replace('/\D+/', '', $phone);
        if (!$digits) return null;

        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    public static function normalizeEmail(?string $email): ?string
    {
        if (!$email) return null;

        $email = strtolower(trim($email));
        return $email === '' ? null : $email;
    }

    public static function findExistingLead(int $tenantId, ?string $phone, ?string $email, ?int $exceptId = null): ?Lead
    {
        return static::findExisting(Lead::class, $tenantId, $phone, $email, $exceptId);
    }

    public static function findExistingContact(int $tenantId, ?string $phone, ?string $email, ?int $exceptId = null): ?Contact
    {
        return static::findExisting(Contact::class, $tenantId, $phone, $email, $exceptId);
    }

    private static function findExisting(string $modelClass, int $tenantId, ?string $phone, ?string $email, ?int $exceptId = null)
    {
        $normPhone = static::normalizePhone($phone);
        $normEmail = static::normalizeEmail($email);

        if (!$normPhone && !$normEmail) return null;

        $candidates = $modelClass::where('tenant_id', $tenantId)
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->where(function ($q) use ($normPhone, $normEmail) {
                if ($normPhone) $q->orWhere('phone', 'like', '%' . $normPhone);
                if ($normEmail) $q->orWhereRaw('LOWER(email) = ?', [$normEmail]);
            })
            ->get(['id', 'phone', 'email']);

        foreach ($candidates as $candidate) {
            if ($normPhone && static::normalizePhone($candidate->phone) === $normPhone) {
                return $modelClass::find($candidate->id);
            }
            if ($normEmail && static::normalizeEmail($candidate->email) === $normEmail) {
                return $modelClass::find($candidate->id);
            }
        }

        return null;
    }
}
