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
        if (Schema::hasColumn('maut_indicators', 'alias')) {
            Schema::table('maut_indicators', function (Blueprint $table) {
                $table->dropColumn('alias');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('maut_indicators', 'alias')) {
            Schema::table('maut_indicators', function (Blueprint $table) {
                $table->decimal('alias', 12, 4)->default(0)->after('score');
            });
        }
    }
};
