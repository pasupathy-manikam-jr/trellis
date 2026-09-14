<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // The gateway's handle for this checkout. Unique, so a replayed
            // confirmation lands on the same order rather than a second one.
            $table->string('reference')->nullable()->unique()->after('coupon_id');
            $table->string('failure_reason')->nullable()->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['reference', 'failure_reason']);
        });
    }
};
