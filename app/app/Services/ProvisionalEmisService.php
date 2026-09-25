<?php

namespace App\Services;

use App\Models\School;
use RuntimeException;

class ProvisionalEmisService
{
    public function generate(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = (string) random_int(10_000_000_000, 99_999_999_999);

            if (! School::query()->where('emis_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Unable to generate a unique provisional EMIS.');
    }
}
