<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_discussion_categories', function (Blueprint $table) {
            $table->boolean('premium_only')->default(false)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('community_discussion_categories', function (Blueprint $table) {
            $table->dropColumn('premium_only');
        });
    }
};
