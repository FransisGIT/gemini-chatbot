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
        Schema::table('maut_criteria', function (Blueprint $table) {
            $table->decimal('weight', 12, 4)->nullable()->default(null)->change();
            $table->enum('type', ['benefit', 'cost'])->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maut_criteria', function (Blueprint $table) {
            $table->decimal('weight', 12, 4)->default(0.1)->nullable(false)->change();
            $table->enum('type', ['benefit', 'cost'])->default('benefit')->nullable(false)->change();
        });
    }
};
