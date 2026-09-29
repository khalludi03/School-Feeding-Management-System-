<?php
$file = 'app/Http/Controllers/DeliveryReceiptController.php';
$content = file_get_contents($file);

// Replace the validation logic to accept either chalan_photo OR chalan_photo_base64
$content = str_replace(
    "if (! \$hasExistingPhoto && ! \$request->hasFile('chalan_photo')) {",
    "if (! \$hasExistingPhoto && ! \$request->hasFile('chalan_photo') && ! \$request->filled('chalan_photo_base64')) {",
    $content
);

// Replace the storing logic
$storeLogic = <<<PHP
        if (\$request->hasFile('chalan_photo')) {
            \$data['chalan_photo'] = \$request->file('chalan_photo')->store('chalan', config('filesystems.default'));
        } elseif (\$request->filled('chalan_photo_base64')) {
            \$base64 = \$request->input('chalan_photo_base64');
            if (preg_match('/^data:image\/(\w+);base64,/', \$base64, \$matches)) {
                \$type = \$matches[1];
                \$base64 = substr(\$base64, strpos(\$base64, ',') + 1);
                \$image = base64_decode(\$base64);
                \$filename = 'chalan/' . uniqid() . '.' . \$type;
                \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->put(\$filename, \$image);
                \$data['chalan_photo'] = \$filename;
            }
        }
PHP;

$content = str_replace(
    "        if (\$request->hasFile('chalan_photo')) {\n            \$data['chalan_photo'] = \$request->file('chalan_photo')->store('chalan', config('filesystems.default'));\n        }",
    $storeLogic,
    $content
);

file_put_contents($file, $content);
