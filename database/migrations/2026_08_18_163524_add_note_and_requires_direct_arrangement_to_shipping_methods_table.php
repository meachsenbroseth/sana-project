<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('shipping_methods', 'note') && Schema::hasColumn('shipping_methods', 'requires_direct_arrangement')) {
            return;
        }

        Schema::table('shipping_methods', function (Blueprint $table) {
            if (! Schema::hasColumn('shipping_methods', 'note')) {
                $table->text('note')->nullable();
            }

            if (! Schema::hasColumn('shipping_methods', 'requires_direct_arrangement')) {
                $table->boolean('requires_direct_arrangement')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropColumn(['note', 'requires_direct_arrangement']);
        });
    }
};
