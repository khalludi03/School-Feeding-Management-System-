<?php

use App\Http\Controllers\Admin\DeliveryReceiptAssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarConflictController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\DeliveryReceiptController;
use App\Http\Controllers\DeliveryZeroConfirmationController;
use App\Http\Controllers\DemandExplanationController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\RationController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolEnrolmentController;
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
        Route::get('/schools', [SchoolController::class, 'index'])->name('schools.index');
        Route::get('/schools/create', [SchoolController::class, 'create'])->name('schools.create');
        Route::post('/schools', [SchoolController::class, 'store'])->name('schools.store');
        Route::get('/schools/{school}', [SchoolController::class, 'show'])->name('schools.show');
        Route::get('/schools/{school}/edit', [SchoolController::class, 'edit'])->name('schools.edit');
        Route::put('/schools/{school}', [SchoolController::class, 'update'])->name('schools.update');
        Route::scopeBindings()->group(function (): void {
            Route::get('/schools/{school}/enrolments/create', [SchoolEnrolmentController::class, 'create'])->name('schools.enrolments.create');
            Route::post('/schools/{school}/enrolments/review', [SchoolEnrolmentController::class, 'review'])->name('schools.enrolments.review');
            Route::get('/schools/{school}/enrolments/review/{token}', [SchoolEnrolmentController::class, 'showReview'])->name('schools.enrolments.review.show');
            Route::post('/schools/{school}/enrolments', [SchoolEnrolmentController::class, 'store'])->name('schools.enrolments.store');
            Route::get('/schools/{school}/enrolments/{enrolment}/cancel', [SchoolEnrolmentController::class, 'confirmCancel'])->name('schools.enrolments.cancel.confirm');
            Route::post('/schools/{school}/enrolments/{enrolment}/cancel', [SchoolEnrolmentController::class, 'cancel'])->name('schools.enrolments.cancel');
        });
        Route::get('/schools/{school}/deactivate/confirm', [SchoolController::class, 'confirmDeactivate'])->name('schools.deactivate.confirm');
        Route::post('/schools/{school}/deactivate', [SchoolController::class, 'deactivate'])->name('schools.deactivate');
        Route::get('/schools/{school}/reactivate/confirm', [SchoolController::class, 'confirmReactivate'])->name('schools.reactivate.confirm');
        Route::post('/schools/{school}/reactivate', [SchoolController::class, 'reactivate'])->name('schools.reactivate');
        Route::view('/settings', 'admin.pending', ['title' => 'Programme settings'])->name('admin.settings');
        Route::get('/calendar', [CalendarController::class, 'index'])->name('admin.calendar');
        Route::get('/calendar/configure/{cycle}', [CalendarController::class, 'monthConfig'])->name('admin.calendar.month-config');
        Route::post('/calendar/configure/{cycle}', [CalendarController::class, 'updateMonthConfig'])->name('admin.calendar.month-config.update');
        Route::post('/calendar', [CalendarController::class, 'store'])->name('admin.calendar.store');
        Route::get('/calendar/conflicts', [CalendarConflictController::class, 'show'])->name('admin.calendar.conflicts');
        Route::delete('/calendar/{nonWorkingDay}', [CalendarController::class, 'destroy'])->name('admin.calendar.destroy');
        Route::view('/report-generator', 'admin.pending', ['title' => 'Official Report Generator'])->name('admin.report-generator');
        Route::get('/reports/daily', [DailyReportController::class, 'index'])->name('admin.reports.daily');
        Route::get('/reports/daily/export', [DailyReportController::class, 'export'])->name('admin.reports.daily.export');
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
        Route::get('/schools/{school}/demand', [DemandExplanationController::class, 'show'])->name('schools.demand.explain');
        Route::get('/receipts/{receipt}/assign', [DeliveryReceiptAssignmentController::class, 'edit'])->name('admin.receipts.assign');
        Route::put('/receipts/{receipt}/assign', [DeliveryReceiptAssignmentController::class, 'update'])->name('admin.receipts.assign.update');
        Route::scopeBindings()->group(function (): void {
            Route::get('/cycles/{cycle}/rations', [RationController::class, 'index'])->name('rations.index');
            Route::get('/cycles/{cycle}/rations/create', [RationController::class, 'create'])->name('rations.create');
            Route::post('/cycles/{cycle}/rations/review', [RationController::class, 'review'])->name('rations.review');
            Route::get('/cycles/{cycle}/rations/review/{token}', [RationController::class, 'showReview'])->name('rations.review.show');
            Route::post('/cycles/{cycle}/rations', [RationController::class, 'store'])->name('rations.store');
            Route::get('/cycles/{cycle}/prices', [PriceController::class, 'index'])->name('prices.index');
            Route::get('/cycles/{cycle}/prices/create', [PriceController::class, 'create'])->name('prices.create');
            Route::post('/cycles/{cycle}/prices/review', [PriceController::class, 'review'])->name('prices.review');
            Route::get('/cycles/{cycle}/prices/review/{token}', [PriceController::class, 'showReview'])->name('prices.review.show');
            Route::post('/cycles/{cycle}/prices', [PriceController::class, 'store'])->name('prices.store');
        });
    });

    Route::middleware('role:field_staff')->prefix('field')->group(function () {
        Route::view('/home', 'field.home')->name('staff.home');
        Route::get('/enter-delivery', [DeliveryReceiptController::class, 'create'])->name('field.delivery.create');
        Route::post('/enter-delivery', [DeliveryReceiptController::class, 'store'])->name('field.delivery.store');
        Route::get('/my-entries', [DeliveryReceiptController::class, 'index'])->name('field.entries');
        Route::get('/enter-delivery/{receipt}/edit', [DeliveryReceiptController::class, 'edit'])->name('field.delivery.edit');
        Route::put('/enter-delivery/{receipt}', [DeliveryReceiptController::class, 'update'])->name('field.delivery.update');
        Route::get('/daily-report', [DailyReportController::class, 'index'])->name('field.report');
        Route::get('/daily-report/export', [DailyReportController::class, 'export'])->name('field.report.export');
        Route::get('/schools/{school}/demand', [DemandExplanationController::class, 'show'])->name('field.demand.explain');
        Route::get('/zero-confirmations/create', [DeliveryZeroConfirmationController::class, 'create'])->name('field.zero-confirmation.create');
        Route::post('/zero-confirmations', [DeliveryZeroConfirmationController::class, 'store'])->name('field.zero-confirmation.store');
    });
});
