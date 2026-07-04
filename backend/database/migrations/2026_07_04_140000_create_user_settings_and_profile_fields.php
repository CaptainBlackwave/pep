<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'bio')) {
                    $table->text('bio')->nullable();
                }
                if (!Schema::hasColumn('users', 'timezone')) {
                    $table->string('timezone', 100)->nullable();
                }
                if (!Schema::hasColumn('users', 'locale')) {
                    $table->string('locale', 20)->nullable();
                }
                if (!Schema::hasColumn('users', 'website_url')) {
                    $table->string('website_url', 2048)->nullable();
                }
            });
        }

        if (!Schema::hasTable('user_settings')) {
            Schema::create('user_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->boolean('email_notifications')->default(true);
                $table->boolean('push_notifications')->default(true);
                $table->boolean('sound_enabled')->default(true);
                $table->boolean('show_online')->default(true);
                $table->boolean('public_profile')->default(true);
                $table->string('profile_visibility', 40)->default('everyone');
                $table->string('direct_messages', 40)->default('members_only');
                $table->boolean('show_read_topics')->default(true);
                $table->boolean('show_typing')->default(true);
                $table->boolean('show_recent_activity')->default(true);
                $table->boolean('personalize_experience')->default(true);
                $table->boolean('allow_analytics')->default(false);
                $table->boolean('compact_discussions')->default(false);
                $table->boolean('show_online_members')->default(true);
                $table->boolean('remember_content_filters')->default(true);
                $table->string('theme', 20)->default('dark');
                $table->string('language', 20)->default('en');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                foreach (['bio', 'timezone', 'locale', 'website_url'] as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
