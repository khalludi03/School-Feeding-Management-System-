<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SchoolCodeService
{
    public function nextCode(): string
    {
        return DB::transaction(function (): string {
            $sequence = DB::table('school_code_sequences')->where('prefix', 'AN')->lockForUpdate()->first();
            if ($sequence === null) {
                throw new RuntimeException('School code sequence is missing.');
            }

            $number = (int) $sequence->next_number;
            do {
                $code = 'AN-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
                $number++;
            } while (School::where('code', $code)->exists());

            DB::table('school_code_sequences')->where('prefix', 'AN')->update(['next_number' => $number]);

            return $code;
        });
    }
}
