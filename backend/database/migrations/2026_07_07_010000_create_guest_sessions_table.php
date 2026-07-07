<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('guest_sessions')) {
            return;
        }

        Schema::create('guest_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('guest_id', 80)->unique();
            $table->string('current_path')->nullable();
            $table->string('current_label', 120)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('last_active')->nullable()->index();
            $table->timestamps();

            $table->index(['current_label', 'current_path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_sessions');
    }
};
