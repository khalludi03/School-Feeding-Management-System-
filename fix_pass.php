$admin = \App\Models\User::where('username', 'admin')->first();
$admin->password = \Illuminate\Support\Facades\Hash::make('admin123');
$admin->must_change_password = false;
$admin->save();

$demo = \App\Models\User::where('username', 'demo')->first();
if ($demo) {
    $demo->password = \Illuminate\Support\Facades\Hash::make('demo1234');
    $demo->must_change_password = false;
    $demo->save();
}

$staff = \App\Models\User::where('username', 'staff')->first();
if ($staff) {
    $staff->password = \Illuminate\Support\Facades\Hash::make('staff123');
    $staff->must_change_password = false;
    $staff->save();
}

echo "Passwords fixed!\n";
