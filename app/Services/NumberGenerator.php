<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

/**
 * NumberGenerator — Centralized, concurrency-safe document number service.
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │  This is the ONLY place in the application that may generate document   │
 * │  numbers for sales, invoices, purchases, and stock transfers.           │
 * │  Controllers MUST call this service — never generate numbers inline.    │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * HOW IT WORKS
 * ─────────────
 * 1. Opens a DB transaction (or participates in an existing one).
 * 2. Tries to SELECT … FOR UPDATE the sequence row for the
 *    (business_id, document_type, today's date).
 *    • The FOR UPDATE acquires a row-level lock so that concurrent requests
 *      block until the first one finishes — eliminating race conditions.
 * 3. If no row exists yet (first document of the day), inserts one with
 *    last_number = 0 using INSERT IGNORE, then re-selects with FOR UPDATE.
 * 4. Increments last_number by 1 atomically and saves.
 * 5. Formats and returns the document number string.
 *
 * FORMATS
 * ────────
 *   Sales     → SALE-YYYYMMDD-00001   (5-digit, daily sequence)
 *   Invoices  → INV-YYYYMMDD-00001
 *   Purchases → PO-YYYYMMDD-00001
 *   Transfers → TRF-YYYYMMDD-00001
 *
 * FUTURE BRANCH SUPPORT
 * ──────────────────────
 * All public methods accept an optional $branchId parameter. Pass it when
 * branch-specific numbering is required. The unique constraint on the
 * document_sequences table will need to be updated at that point to include
 * branch_id — the data model is already ready for it.
 */
class NumberGenerator
{
    // ── Document type constants ───────────────────────────────────────────

    public const TYPE_SALE     = 'sale';
    public const TYPE_INVOICE  = 'invoice';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_TRANSFER = 'transfer';

    // ── Prefix map ───────────────────────────────────────────────────────

    private const PREFIXES = [
        self::TYPE_SALE     => 'SALE',
        self::TYPE_INVOICE  => 'INV',
        self::TYPE_PURCHASE => 'PO',
        self::TYPE_TRANSFER => 'TRF',
    ];

    // ── Padding width (digits) ───────────────────────────────────────────

    private const PAD_WIDTH = 5;

    // ── Public API ────────────────────────────────────────────────────────

    /**
     * Generate the next sale number for the given business.
     *
     * @param  int       $businessId
     * @param  int|null  $branchId   Reserved for future branch-level numbering
     * @return string    e.g. "SALE-20260803-00001"
     */
    public function nextSaleNumber(int $businessId, ?int $branchId = null): string
    {
        return $this->next($businessId, self::TYPE_SALE, $branchId);
    }

    /**
     * Generate the next invoice number for the given business.
     *
     * @return string  e.g. "INV-20260803-00001"
     */
    public function nextInvoiceNumber(int $businessId, ?int $branchId = null): string
    {
        return $this->next($businessId, self::TYPE_INVOICE, $branchId);
    }

    /**
     * Generate the next purchase order number for the given business.
     *
     * @return string  e.g. "PO-20260803-00001"
     */
    public function nextPurchaseNumber(int $businessId, ?int $branchId = null): string
    {
        return $this->next($businessId, self::TYPE_PURCHASE, $branchId);
    }

    /**
     * Generate the next stock transfer number for the given business.
     *
     * @return string  e.g. "TRF-20260803-00001"
     */
    public function nextTransferNumber(int $businessId, ?int $branchId = null): string
    {
        return $this->next($businessId, self::TYPE_TRANSFER, $branchId);
    }

    // ── Core engine ───────────────────────────────────────────────────────

    /**
     * Atomically increment the sequence counter and return the formatted number.
     *
     * This method is concurrency-safe via SELECT … FOR UPDATE:
     *   - Two simultaneous calls for the same (business, type, date) will
     *     serialize at the DB level — the second call blocks until the first
     *     commits, then reads the freshly incremented last_number.
     *   - Wraps in its own transaction if none is already active, so callers
     *     that run within their own DB::transaction() share the same lock scope.
     *
     * @param  int         $businessId
     * @param  string      $type        One of the TYPE_* constants
     * @param  int|null    $branchId
     * @return string
     *
     * @throws \RuntimeException  If the document type is not recognised
     * @throws \Exception         Re-throws any DB exception after rollback
     */
    private function next(int $businessId, string $type, ?int $branchId = null): string
    {
        if (!array_key_exists($type, self::PREFIXES)) {
            throw new \RuntimeException("NumberGenerator: unknown document type '{$type}'.");
        }

        $today = now()->toDateString(); // YYYY-MM-DD

        // We need an active transaction for the FOR UPDATE lock to be meaningful.
        // DB::transaction() is re-entrant-safe via savepoints on MySQL/MariaDB.
        return DB::transaction(function () use ($businessId, $type, $branchId, $today) {

            // Ensure the row exists before we try to lock it.
            // INSERT IGNORE means concurrent threads won't get a duplicate-key
            // error — only one insert wins, the rest are silently discarded.
            DB::table('document_sequences')->insertOrIgnore([
                'business_id'   => $businessId,
                'document_type' => $type,
                'sequence_date' => $today,
                'last_number'   => 0,
                'branch_id'     => $branchId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Lock the row for the duration of this transaction.
            // Any concurrent call hitting the same row will wait here.
            /** @var DocumentSequence $sequence */
            $sequence = DocumentSequence::where('business_id', $businessId)
                ->where('document_type', $type)
                ->where('sequence_date', $today)
                ->lockForUpdate()
                ->firstOrFail();

            // Atomic increment
            $nextNumber = $sequence->last_number + 1;

            $sequence->last_number = $nextNumber;
            $sequence->updated_at  = now();
            $sequence->save();

            return $this->format($type, $today, $nextNumber);
        });
    }

    /**
     * Format a document number from its components.
     *
     * @param  string $type      Document type (maps to prefix)
     * @param  string $date      YYYY-MM-DD date string
     * @param  int    $sequence  The sequence counter
     * @return string            e.g. "SALE-20260803-00001"
     */
    private function format(string $type, string $date, int $sequence): string
    {
        $prefix    = self::PREFIXES[$type];
        $datePart  = str_replace('-', '', $date); // YYYYMMDD
        $seqPart   = str_pad($sequence, self::PAD_WIDTH, '0', STR_PAD_LEFT);

        return "{$prefix}-{$datePart}-{$seqPart}";
    }
}
