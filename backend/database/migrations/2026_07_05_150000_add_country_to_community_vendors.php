<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_vendors', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->after('status_class');
        });
    }

    public function down(): void
    {
        Schema::table('community_vendors', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
