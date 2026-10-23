<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maut_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->json('criteria_snapshot');
            $table->json('alternatives_snapshot');
            $table->json('scores_snapshot');
            $table->json('normalized_matrix');
            $table->json('ranking');
            $table->decimal('total_weight', 10, 4)->default(0);
            $table->boolean('weights_are_normalized')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maut_results');
    }
};
