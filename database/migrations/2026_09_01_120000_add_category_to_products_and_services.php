<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Free-text category tag on catalog items — powers the "customers who
     * spend on category X" loyalty campaign segment (docs/customer-loyalty.txt
     * §5). Nullable; existing items keep working untagged.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type');
            $table->index(['tenant_id', 'category']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('category')->nullable()->after('unit');
            $table->index(['tenant_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'category']);
            $table->dropColumn('category');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'category']);
            $table->dropColumn('category');
        });
    }
};
