<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shipping_methods')) {
            Schema::create('shipping_methods', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('cost', 10, 2);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
