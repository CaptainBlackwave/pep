<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_message_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('participant_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('unread_count')->default(0);
            $table->string('status', 40)->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'participant_user_id']);
            $table->index(['status', 'last_message_at']);
        });

        Schema::create('community_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('community_message_threads')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->string('attachment_name')->nullable();
            $table->json('attachment_meta')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['thread_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_messages');
        Schema::dropIfExists('community_message_threads');
    }
};
