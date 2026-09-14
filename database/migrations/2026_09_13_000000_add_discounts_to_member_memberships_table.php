<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Support databases that already received the initial discount columns.
        Schema::table('member_memberships', function (Blueprint $table) {
            if (! Schema::hasColumn('member_memberships', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('member_memberships', 'final_price')) {
                $table->decimal('final_price', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('member_memberships', 'discount_reason')) {
                $table->string('discount_reason')->nullable();
            }
        });

        DB::table('member_memberships')->whereNull('final_price')->update([
            'final_price' => DB::raw('price - discount_amount'),
        ]);

        Schema::table('member_memberships', function (Blueprint $table) {
            $table->decimal('final_price', 10, 2)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('member_memberships', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'final_price', 'discount_reason']);
        });
    }
};
