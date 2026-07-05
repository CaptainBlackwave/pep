<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('community_vendors', 'owner_user_id')) {
                $table->foreignId('owner_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('community_vendors', 'claim_status')) {
                $table->string('claim_status', 40)->default('unclaimed')->after('owner_user_id');
            }

            if (!Schema::hasColumn('community_vendors', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('website_url');
            }

            if (!Schema::hasColumn('community_vendors', 'contact_telegram')) {
                $table->string('contact_telegram', 120)->nullable()->after('contact_email');
            }

            if (!Schema::hasColumn('community_vendors', 'contact_signal')) {
                $table->string('contact_signal', 120)->nullable()->after('contact_telegram');
            }

            if (!Schema::hasColumn('community_vendors', 'contact_discord')) {
                $table->string('contact_discord', 120)->nullable()->after('contact_signal');
            }

            if (!Schema::hasColumn('community_vendors', 'support_url')) {
                $table->string('support_url')->nullable()->after('contact_discord');
            }

            if (!Schema::hasColumn('community_vendors', 'response_policy')) {
                $table->text('response_policy')->nullable()->after('support_url');
            }

            if (!Schema::hasColumn('community_vendors', 'public_contact_notes')) {
                $table->text('public_contact_notes')->nullable()->after('response_policy');
            }

            if (!Schema::hasColumn('community_vendors', 'profile_submitted_at')) {
                $table->timestamp('profile_submitted_at')->nullable()->after('public_contact_notes');
            }

            $table->index(['owner_user_id', 'claim_status']);
        });

        Schema::create('community_vendor_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('community_vendors')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 40)->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'user_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_vendor_claims');

        Schema::table('community_vendors', function (Blueprint $table) {
            if (Schema::hasColumn('community_vendors', 'owner_user_id') && Schema::hasColumn('community_vendors', 'claim_status')) {
                $table->dropIndex(['owner_user_id', 'claim_status']);
            }

            if (Schema::hasColumn('community_vendors', 'owner_user_id')) {
                $table->dropConstrainedForeignId('owner_user_id');
            }

            foreach ([
                'claim_status',
                'contact_email',
                'contact_telegram',
                'contact_signal',
                'contact_discord',
                'support_url',
                'response_policy',
                'public_contact_notes',
                'profile_submitted_at',
            ] as $column) {
                if (Schema::hasColumn('community_vendors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
