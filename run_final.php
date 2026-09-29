try { DB::statement('ALTER TABLE schools ADD COLUMN emis_is_provisional TINYINT(1) DEFAULT 0'); } catch(\Exception $e) {}
Artisan::call('migrate', ['--force' => true]);
echo Artisan::output();
