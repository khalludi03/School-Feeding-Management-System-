<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only view of the allocation and delivery records owned by later stories.
 *
 * Neither table exists yet, so every count resolves to zero today. The contract is what matters:
 * a dated enrolment change must surface downstream drift without ever rewriting those records.
 */
class DownstreamImpactService
{
    private const ALLOCATION_TABLE = 'allocations';

    private const RECEIPT_TABLE = 'delivery_receipts';

    /**
     * @return array{allocations: int, receipts: int, stale: bool}
     */
    public function summaryFor(School $school): array
    {
        $allocations = $this->countFor(self::ALLOCATION_TABLE, $school);
        $receipts = $this->countFor(self::RECEIPT_TABLE, $school);

        return [
            'allocations' => $allocations,
            'receipts' => $receipts,
            'stale' => ($allocations + $receipts) > 0,
        ];
    }

    private function countFor(string $table, School $school): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'school_id')) {
            return 0;
        }

        return DB::table($table)->where('school_id', $school->id)->count();
    }
}
