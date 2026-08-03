<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the document_sequences table.
 *
 * This is the single source of truth for all document number generation.
 * Each row tracks the last sequence number issued for a given
 * (business, document_type, date) combination, enabling:
 *   - Daily resets (sequence restarts at 1 each new day)
 *   - Per-business isolation (multi-tenant safe)
 *   - Constant-time generation (no full-table scans)
 *   - Future branch-specific numbering (branch_id column ready)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();

            // Which business owns this sequence
            $table->unsignedBigInteger('business_id');

            // Document type: 'sale', 'invoice', 'purchase', 'transfer'
            $table->string('document_type', 30);

            // The calendar date this sequence belongs to (enables daily resets)
            $table->date('sequence_date');

            // The last sequence number issued on this date for this business+type
            $table->unsignedInteger('last_number')->default(0);

            // Reserved for future branch-specific numbering — nullable so it
            // doesn't require a schema change later, just populate it.
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->timestamps();

            // Guarantees uniqueness and is the lock target for SELECT FOR UPDATE
            $table->unique(
                ['business_id', 'document_type', 'sequence_date'],
                'uq_sequences_business_type_date'
            );

            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
