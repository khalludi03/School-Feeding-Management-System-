<?php
$file = 'app/Http/Controllers/DeliveryReceiptController.php';
$content = file_get_contents($file);

// Replace create signature to inject DailyDemandService
$content = str_replace(
    'public function create(Request $request, WorkingDayCalendar $calendar): View',
    'public function create(Request $request, WorkingDayCalendar $calendar, \App\Services\DailyDemandService $demands): View',
    $content
);

// We need a helper to map schools with demands
$helper = <<<PHP
    private function mapSchoolsWithDemands(\$schools, \$cycle, \$date, \$demandsService)
    {
        if (\$cycle === null) return \$schools;
        return \$schools->map(function (\$s) use (\$cycle, \$date, \$demandsService) {
            \$demandData = \$demandsService->forSchool(\$cycle, \$s, \$date);
            return [
                'id' => \$s->id,
                'code' => \$s->code,
                'bangla_name' => \$s->bangla_name,
                'emis_code' => \$s->emis_code,
                'demands' => \$demandData['items'] ?? [],
            ];
        });
    }
PHP;

// Insert helper method
$content = preg_replace('/(private const MAX_PHOTO_KILOBYTES = 4096;)/', "$1\n\n$helper\n", $content);

// Replace schools => ... in create
$content = str_replace(
    "'schools' => \$this->schoolsFor(\$cycle, \$date, \$calendar),",
    "'schools' => \$this->mapSchoolsWithDemands(\$this->schoolsFor(\$cycle, \$date, \$calendar), \$cycle, \$date, \$demands),",
    $content
);

// Replace schools => ... in edit
$content = str_replace(
    "'schools' => collect([\$receipt->school]),",
    "'schools' => \$this->mapSchoolsWithDemands(collect([\$receipt->school]), \$cycle, \$receipt->delivery_date, \$demands),",
    $content
);

// Fallback logic for base64 photo in store & update
$storePhotoLogic = <<<PHP
        \$photoPath = null;
        if (\$request->hasFile('chalan_photo')) {
            \$photoPath = \$request->file('chalan_photo')->store('chalans', 's3');
        } elseif (\$request->filled('chalan_photo_base64')) {
            \$base64 = \$request->input('chalan_photo_base64');
            if (preg_match('/^data:image\/(\w+);base64,/', \$base64, \$type)) {
                \$base64 = substr(\$base64, strpos(\$base64, ',') + 1);
                \$type = strtolower(\$type[1]);
                if (in_array(\$type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    \$base64 = base64_decode(\$base64);
                    if (\$base64 !== false) {
                        \$photoPath = 'chalans/' . \Illuminate\Support\Str::uuid() . '.' . \$type;
                        \Illuminate\Support\Facades\Storage::disk('s3')->put(\$photoPath, \$base64);
                    }
                }
            }
        }
PHP;

$updatePhotoLogic = <<<PHP
        \$photoPath = \$receipt->chalan_photo_path;
        if (\$request->hasFile('chalan_photo')) {
            \$photoPath = \$request->file('chalan_photo')->store('chalans', 's3');
        } elseif (\$request->filled('chalan_photo_base64')) {
            \$base64 = \$request->input('chalan_photo_base64');
            if (preg_match('/^data:image\/(\w+);base64,/', \$base64, \$type)) {
                \$base64 = substr(\$base64, strpos(\$base64, ',') + 1);
                \$type = strtolower(\$type[1]);
                if (in_array(\$type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    \$base64 = base64_decode(\$base64);
                    if (\$base64 !== false) {
                        \$photoPath = 'chalans/' . \Illuminate\Support\Str::uuid() . '.' . \$type;
                        \Illuminate\Support\Facades\Storage::disk('s3')->put(\$photoPath, \$base64);
                    }
                }
            }
        }
PHP;

$content = preg_replace(
    '/\$photoPath = \$request->hasFile\(\'chalan_photo\'\)\s*\?\s*\$request->file\(\'chalan_photo\'\)->store\(\'chalans\', \'s3\'\)\s*:\s*null;/',
    $storePhotoLogic,
    $content
);

$content = preg_replace(
    '/\$photoPath = \$request->hasFile\(\'chalan_photo\'\)\s*\?\s*\$request->file\(\'chalan_photo\'\)->store\(\'chalans\', \'s3\'\)\s*:\s*\$receipt->chalan_photo_path;/',
    $updatePhotoLogic,
    $content
);

file_put_contents($file, $content);
echo "Patched DeliveryReceiptController\n";
