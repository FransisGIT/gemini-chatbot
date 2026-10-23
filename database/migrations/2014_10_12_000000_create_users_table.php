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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable()->default('default-avatar.png');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password_changed_at')->nullable();
            $table->boolean('is_otp')->default(false);
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->enum('permission', ['superadmin', 'admin', 'user'])->default('user');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_email_notification_enabled')->default(true);
            $table->boolean('is_whatsapp_notification_enabled')->default(true);
            $table->string('active_device')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
