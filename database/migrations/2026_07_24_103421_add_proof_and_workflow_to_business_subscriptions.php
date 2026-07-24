<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_subscriptions', function (Blueprint $table) {
            $table->string('proof_image')->nullable()->after('notes');         // uploaded screenshot/photo path
            $table->enum('submitted_by', ['business', 'admin'])->default('admin')->after('proof_image');
            $table->text('rejection_reason')->nullable()->after('submitted_by');
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('business_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['proof_image', 'submitted_by', 'rejection_reason', 'submitted_by_user_id']);
        });
    }
};
