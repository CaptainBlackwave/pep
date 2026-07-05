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
            if (!Schema::hasColumn('community_message_threads', 'owner_unread_count')) {
                $table->unsignedInteger('owner_unread_count')->default(0)->after('unread_count');
            }

            if (!Schema::hasColumn('community_message_threads', 'participant_unread_count')) {
                $table->unsignedInteger('participant_unread_count')->default(0)->after('owner_unread_count');
            }
        });

        if (Schema::hasColumn('community_message_threads', 'unread_count')) {
            DB::table('community_message_threads')
                ->where('unread_count', '>', 0)
                ->where('participant_unread_count', 0)
                ->update(['participant_unread_count' => DB::raw('unread_count')]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('community_message_threads')) {
            return;
        }

        Schema::table('community_message_threads', function (Blueprint $table) {
            if (Schema::hasColumn('community_message_threads', 'participant_unread_count')) {
                $table->dropColumn('participant_unread_count');
            }

            if (Schema::hasColumn('community_message_threads', 'owner_unread_count')) {
                $table->dropColumn('owner_unread_count');
            }
        });
    }
};
