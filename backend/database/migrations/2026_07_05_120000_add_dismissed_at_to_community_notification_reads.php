<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_notification_reads', function (Blueprint $table) {
            $table->timestamp('dismissed_at')->nullable()->after('read_at');
            $table->index(['user_id', 'dismissed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('community_notification_reads', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'dismissed_at']);
            $table->dropColumn('dismissed_at');
        });
    }
};
