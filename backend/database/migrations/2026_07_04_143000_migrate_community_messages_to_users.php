<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('community_message_threads')) {
            return;
        }

        Schema::table('community_message_threads', function (Blueprint $table) {
            if (!Schema::hasColumn('community_message_threads', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('community_message_threads', 'participant_user_id')) {
                $table->foreignId('participant_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
        });

        if (Schema::hasTable('community_member_profiles') && Schema::hasColumn('community_message_threads', 'participant_profile_id')) {
            DB::table('community_message_threads')
                ->join('community_member_profiles', 'community_message_threads.participant_profile_id', '=', 'community_member_profiles.id')
                ->whereNull('community_message_threads.participant_user_id')
                ->whereNotNull('community_member_profiles.user_id')
                ->update([
                    'community_message_threads.participant_user_id' => DB::raw('community_member_profiles.user_id'),
                ]);
        }

        if (!Schema::hasTable('community_messages')) {
            return;
        }

        Schema::table('community_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('community_messages', 'sender_user_id')) {
                $table->foreignId('sender_user_id')->nullable()->after('thread_id')->constrained('users')->nullOnDelete();
            }
        });

        if (Schema::hasTable('community_member_profiles') && Schema::hasColumn('community_messages', 'sender_profile_id')) {
            DB::table('community_messages')
                ->join('community_member_profiles', 'community_messages.sender_profile_id', '=', 'community_member_profiles.id')
                ->whereNull('community_messages.sender_user_id')
                ->whereNotNull('community_member_profiles.user_id')
                ->update([
                    'community_messages.sender_user_id' => DB::raw('community_member_profiles.user_id'),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('community_messages') || !Schema::hasTable('community_message_threads')) {
            return;
        }

        Schema::table('community_messages', function (Blueprint $table) {
            if (Schema::hasColumn('community_messages', 'sender_user_id')) {
                $table->dropConstrainedForeignId('sender_user_id');
            }
        });

        Schema::table('community_message_threads', function (Blueprint $table) {
            if (Schema::hasColumn('community_message_threads', 'participant_user_id')) {
                $table->dropConstrainedForeignId('participant_user_id');
            }

            if (Schema::hasColumn('community_message_threads', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
