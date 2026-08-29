<?php

use App\Services\GstService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CGST/SGST/IGST split for every tax-bearing document. `tax_amount`
     * stays as the grand total tax = cgst + sgst + igst. `place_of_supply`
     * is the recipient's state code; `is_inter_state` picks IGST vs
     * CGST+SGST.
     */
    private array $tables = ['quotations', 'invoices', 'purchase_orders', 'vendor_bills'];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->string('place_of_supply', 4)->nullable()->after('tax_amount');
                $table->boolean('is_inter_state')->default(false)->after('place_of_supply');
                $table->decimal('cgst_amount', 12, 2)->default(0)->after('is_inter_state');
                $table->decimal('sgst_amount', 12, 2)->default(0)->after('cgst_amount');
                $table->decimal('igst_amount', 12, 2)->default(0)->after('sgst_amount');
            });
        }

        // Normalise existing free-text state values ("Karnataka") to our
        // 2-letter codes ("KA") so GST place-of-supply resolves cleanly.
        foreach (['vendors', 'contacts'] as $party) {
            DB::table($party)->whereNotNull('state')->where('state', '!=', '')
                ->orderBy('id')
                ->each(function ($row) use ($party) {
                    $code = GstService::stateCode($row->state);
                    if ($code && $code !== $row->state) {
                        DB::table($party)->where('id', $row->id)->update(['state' => $code]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropColumn(['place_of_supply', 'is_inter_state', 'cgst_amount', 'sgst_amount', 'igst_amount']);
            });
        }
    }
};
