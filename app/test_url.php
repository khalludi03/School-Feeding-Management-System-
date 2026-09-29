<?php
$path = App\Models\DeliveryReceipt::latest()->first()->chalan_photo_path;
echo Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(5));
