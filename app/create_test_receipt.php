DB::table('delivery_receipts')->insert([
    'school_id' => 2,
    'delivery_date' => '2026-09-02',
    'chalan_number' => 'AUTH-TEST',
    'chalan_date' => '2026-09-02',
    'entered_by' => 2,
    'responsible_by' => 2,
    'chalan_photo_path' => 'chalan/6abb43ac3c73c.jpeg',
    'created_at' => now(),
    'updated_at' => now(),
]);
$id = DB::getPdo()->lastInsertId();
echo "Inserted receipt ID: $id\n";
