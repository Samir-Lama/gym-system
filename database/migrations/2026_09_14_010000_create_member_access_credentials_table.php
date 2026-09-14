<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_access_credentials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type');
            $table->string('credential_hash')->unique();
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index(['member_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_access_credentials');
    }
};
