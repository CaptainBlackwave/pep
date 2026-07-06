<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_vendor_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('vendor_id')->constrained('community_vendors')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('file_path', 500);
            $table->string('file_type', 40)->default('pdf');
            $table->string('category', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 40)->default('published');
            $table->timestamps();
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_vendor_documents');
    }
};
