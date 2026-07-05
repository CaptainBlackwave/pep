<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_discussion_replies', function (Blueprint $table) {
            if (!Schema::hasColumn('community_discussion_replies', 'attachment_url')) {
                $table->string('attachment_url')->nullable()->after('attachment_name');
            }

            if (!Schema::hasColumn('community_discussion_replies', 'attachment_meta')) {
                $table->json('attachment_meta')->nullable()->after('attachment_url');
            }
        });

        Schema::create('community_discussion_reactions', function (Blueprint $table) {
            $table->id();
            $table->string('target_type', 40);
            $table->unsignedBigInteger('target_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('emoji', 24);
            $table->timestamps();

            $table->unique(['target_type', 'target_id', 'user_id', 'emoji'], 'community_discussion_reaction_unique');
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('community_discussion_reports', function (Blueprint $table) {
            $table->id();
            $table->string('target_type', 40);
            $table->unsignedBigInteger('target_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 80);
            $table->text('details')->nullable();
            $table->string('status', 40)->default('open');
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_discussion_reports');
        Schema::dropIfExists('community_discussion_reactions');

        Schema::table('community_discussion_replies', function (Blueprint $table) {
            if (Schema::hasColumn('community_discussion_replies', 'attachment_meta')) {
                $table->dropColumn('attachment_meta');
            }

            if (Schema::hasColumn('community_discussion_replies', 'attachment_url')) {
                $table->dropColumn('attachment_url');
            }
        });
    }
};
