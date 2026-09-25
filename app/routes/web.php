<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::view('/forgot-password', 'auth.forgot')->name('password.forgot');
});

Route::middleware(['auth', 'account'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/change-temporary-password', [PasswordController::class, 'forceEdit'])->name('password.force.edit');
    Route::post('/change-temporary-password', [PasswordController::class, 'forceUpdate'])->name('password.force.update');

    Route::get('/', function () {
        return redirect()->route(auth()->user()->role === 'admin' ? 'admin.dashboard' : 'staff.home');
    })->name('home');

    Route::get('/password', [PasswordController::class, 'profileEdit'])->name('password.profile.edit');
    Route::put('/password', [PasswordController::class, 'profileUpdate'])->name('password.profile.update');

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::view('/dashboard', 'admin.dashboard')->name('admin.dashboard');
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
        Route::get('/staff/{staff}/deactivate/confirm', [StaffController::class, 'confirmDeactivate'])->name('staff.deactivate.confirm');
        Route::post('/staff/{staff}/deactivate', [StaffController::class, 'deactivate'])->name('staff.deactivate');
        Route::get('/staff/{staff}/reactivate/confirm', [StaffController::class, 'confirmReactivate'])->name('staff.reactivate.confirm');
        Route::post('/staff/{staff}/reactivate', [StaffController::class, 'reactivate'])->name('staff.reactivate');
        Route::get('/staff/{staff}/reset-password/confirm', [StaffController::class, 'confirmResetPassword'])->name('staff.reset.confirm');
        Route::post('/staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset');
    });

    Route::middleware('role:field_staff')->prefix('field')->group(function () {
        Route::view('/home', 'field.home')->name('staff.home');
        Route::view('/enter-delivery', 'field.pending', ['title' => 'Enter Delivery'])->name('field.delivery.create');
        Route::view('/my-entries', 'field.pending', ['title' => 'My Entries'])->name('field.entries');
        Route::view('/daily-report', 'field.pending', ['title' => 'Daily Delivery Report'])->name('field.report');
    });
});
