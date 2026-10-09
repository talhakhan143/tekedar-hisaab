<?php

use App\Http\Controllers\ClientPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeveloperUserController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\MaterialPurchaseController;
use App\Http\Controllers\MoneyOutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResetController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\WorkLedgerController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('projects', ProjectController::class);

    // In-project quick-add (project hub)
    Route::post('/projects/{project}/payments', [ProjectController::class, 'storePayment'])->name('projects.payments.store');
    Route::post('/projects/{project}/materials', [ProjectController::class, 'storeMaterial'])->name('projects.materials.store');
    Route::post('/projects/{project}/expenses', [ProjectController::class, 'storeExpense'])->name('projects.expenses.store');
    Route::post('/projects/{project}/attendance-bulk', [ProjectController::class, 'storeBulkAttendance'])->name('projects.attendance.bulk');
    Route::post('/projects/{project}/adjustment', [ProjectController::class, 'storeAdjustment'])->name('projects.adjustment.store');
    Route::post('/projects/{project}/subcontractor', [ProjectController::class, 'storeSubcontractor'])->name('projects.subcontractor.store');

    // Estimates (nested under project for index/store; flat for update/destroy)
    Route::get('projects/{project}/estimates', [EstimateController::class, 'index'])->name('estimates.index');
    Route::post('projects/{project}/estimates', [EstimateController::class, 'store'])->name('estimates.store');
    Route::put('estimates/{estimate}', [EstimateController::class, 'update'])->name('estimates.update');
    Route::delete('estimates/{estimate}', [EstimateController::class, 'destroy'])->name('estimates.destroy');

    // Money In — client payments
    Route::get('/money-in', [ClientPaymentController::class, 'index'])->name('money-in');
    Route::get('/client-payments/create', [ClientPaymentController::class, 'create'])->name('client-payments.create');
    Route::post('/client-payments', [ClientPaymentController::class, 'store'])->name('client-payments.store');
    Route::delete('/client-payments/{clientPayment}', [ClientPaymentController::class, 'destroy'])->name('client-payments.destroy');

    // Vendors + material purchases (payables)
    Route::resource('vendors', VendorController::class);
    Route::get('/materials', [MaterialPurchaseController::class, 'index'])->name('materials.index');
    Route::get('/materials/create', [MaterialPurchaseController::class, 'create'])->name('materials.create');
    Route::post('/materials', [MaterialPurchaseController::class, 'store'])->name('materials.store');
    Route::get('/materials/{materialPurchase}/edit', [MaterialPurchaseController::class, 'edit'])->name('materials.edit');
    Route::put('/materials/{materialPurchase}', [MaterialPurchaseController::class, 'update'])->name('materials.update');
    Route::delete('/materials/{materialPurchase}', [MaterialPurchaseController::class, 'destroy'])->name('materials.destroy');
    Route::post('/materials/{materialPurchase}/pay', [MaterialPurchaseController::class, 'pay'])->name('materials.pay');
    // alias used by project show page
    Route::get('/material-purchases/create', [MaterialPurchaseController::class, 'create'])->name('material-purchases.create');

    // Pre-contract estimate calculator (POS-style, client-side only)
    Route::view('/calculator', 'calculator')->name('calculator');

    // Daily attendance (bulk single-day or range/month)
    Route::get('/attendance', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance');
    Route::get('/attendance/register', [\App\Http\Controllers\AttendanceController::class, 'register'])->name('attendance.register');
    Route::post('/attendance/register', [\App\Http\Controllers\AttendanceController::class, 'saveRegister'])->name('attendance.register.save');
    Route::post('/attendance', [\App\Http\Controllers\AttendanceController::class, 'store'])->name('attendance.store');
    Route::post('/attendance/quick-worker', [\App\Http\Controllers\AttendanceController::class, 'quickWorker'])->name('attendance.quick-worker');

    // Project-scoped quick worker entry (from project page)
    Route::post('/projects/{project}/work-entries', [WorkLedgerController::class, 'storeProjectWork'])->name('projects.work-entries.store');
    Route::post('/projects/{project}/wage-payments', [WorkLedgerController::class, 'storeProjectWage'])->name('projects.wage-payments.store');

    // Workers + sub-ledgers (attendance, advances, wage payments)
    Route::resource('workers', WorkerController::class);
    Route::patch('/workers/{worker}/wage', [WorkerController::class, 'updateWage'])->name('workers.wage.update');
    Route::post('/workers/{worker}/work-entries', [WorkLedgerController::class, 'storeWork'])->name('work-entries.store');
    Route::delete('/work-entries/{workEntry}', [WorkLedgerController::class, 'destroyWork'])->name('work-entries.destroy');
    Route::post('/workers/{worker}/advances', [WorkLedgerController::class, 'storeAdvance'])->name('worker-advances.store');
    Route::delete('/worker-advances/{workerAdvance}', [WorkLedgerController::class, 'destroyAdvance'])->name('worker-advances.destroy');
    Route::post('/workers/{worker}/wage-payments', [WorkLedgerController::class, 'storePayment'])->name('wage-payments.store');
    Route::delete('/wage-payments/{wagePayment}', [WorkLedgerController::class, 'destroyPayment'])->name('wage-payments.destroy');

    // Money Out hub — other expenses + general overheads
    Route::get('/money-out', [MoneyOutController::class, 'index'])->name('money-out');
    Route::post('/expenses', [MoneyOutController::class, 'storeExpense'])->name('expenses.store');
    Route::delete('/expenses/{otherExpense}', [MoneyOutController::class, 'destroyExpense'])->name('expenses.destroy');
    Route::post('/overheads', [MoneyOutController::class, 'storeOverhead'])->name('overheads.store');
    Route::delete('/overheads/{generalOverhead}', [MoneyOutController::class, 'destroyOverhead'])->name('overheads.destroy');

    // Reports + exports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('/reports/outstanding', [ReportController::class, 'outstanding'])->name('reports.outstanding');
    Route::get('/reports/closeout/{project}', [ReportController::class, 'closeout'])->name('reports.closeout');

    // Settings
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Danger zone — hard reset of all business data (keeps users + settings)
    Route::post('/hard-reset', [ResetController::class, 'reset'])->name('hard-reset');

    // Printable vouchers / invoices (browser print → save PDF)
    Route::get('/vouchers/material/{materialPurchase}', [VoucherController::class, 'material'])->name('vouchers.material');
    Route::get('/vouchers/wage/{wagePayment}', [VoucherController::class, 'wage'])->name('vouchers.wage');
    Route::get('/vouchers/advance/{workerAdvance}', [VoucherController::class, 'advance'])->name('vouchers.advance');
    Route::get('/vouchers/client-payment/{clientPayment}', [VoucherController::class, 'clientPayment'])->name('vouchers.client-payment');
    Route::get('/vouchers/expense/{otherExpense}', [VoucherController::class, 'expense'])->name('vouchers.expense');
    Route::get('/vouchers/worker-statement/{worker}', [VoucherController::class, 'workerStatement'])->name('vouchers.worker-statement');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Hidden developer-only user management (change any user's email/password).
Route::middleware(['auth', 'developer'])->group(function () {
    Route::get('/developer/users', [DeveloperUserController::class, 'index'])->name('developer.users');
    Route::put('/developer/users/{user}', [DeveloperUserController::class, 'update'])->name('developer.users.update');
});

require __DIR__.'/auth.php';
