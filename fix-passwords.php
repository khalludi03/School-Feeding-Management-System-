<?php
$admin = App\Models\User::where('username', 'admin')->first();
if ($admin) {
    $admin->password = 'admin123';
    $admin->must_change_password = false;
    $admin->save();
}

$demoAdmin = App\Models\User::where('username', 'demo-admin')->first();
if ($demoAdmin) {
    $demoAdmin->password = 'demo1234';
    $demoAdmin->save();
}

$demoStaff = App\Models\User::where('username', 'demo-staff')->first();
if ($demoStaff) {
    $demoStaff->password = 'staff123';
    $demoStaff->save();
}
echo "Passwords updated.";
