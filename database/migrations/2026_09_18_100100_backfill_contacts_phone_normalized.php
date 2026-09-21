<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One-time backfill of contacts.phone_normalized for rows created before
     * the column existed. Uses raw DB::table (not Eloquent) so it doesn't
     * trigger Contact's saving observer and stays fast/portable at scale.
     */
    public function up(): void
    {
        DB::table('contacts')
            ->whereNull('phone_normalized')
            ->whereNotNull('phone')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $digits = preg_replace('/\D/', '', (string) $row->phone);
                    $normalized = $digits === '' ? null : substr($digits, -10);

                    if ($normalized !== null) {
                        DB::table('contacts')->where('id', $row->id)->update([
                            'phone_normalized' => $normalized,
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Data-only migration — not reversible.
    }
};
