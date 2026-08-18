<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_method_province', function (Blueprint $table) {
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained()->cascadeOnDelete();
            $table->decimal('fee', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['shipping_method_id', 'province_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_method_province');
    }
};
