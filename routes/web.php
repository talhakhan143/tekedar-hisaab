<?php

use App\Http\Controllers\ClientPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\MaterialPurchaseController;
use App\Http\Controllers\MoneyOutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RetentionReleaseController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\WorkLedgerController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('projects', ProjectController::class);

    // Estimates (nested under project for index/store; flat for update/destroy)
    Route::get('projects/{project}/estimates', [EstimateController::class, 'index'])->name('estimates.index');
    Route::post('projects/{project}/estimates', [EstimateController::class, 'store'])->name('estimates.store');
    Route::put('estimates/{estimate}', [EstimateController::class, 'update'])->name('estimates.update');
    Route::delete('estimates/{estimate}', [EstimateController::class, 'destroy'])->name('estimates.destroy');

    // Money In — client payments + retention releases
    Route::get('/money-in', [ClientPaymentController::class, 'index'])->name('money-in');
    Route::get('/client-payments/create', [ClientPaymentController::class, 'create'])->name('client-payments.create');
    Route::post('/client-payments', [ClientPaymentController::class, 'store'])->name('client-payments.store');
    Route::delete('/client-payments/{clientPayment}', [ClientPaymentController::class, 'destroy'])->name('client-payments.destroy');
    Route::post('/retention-releases', [RetentionReleaseController::class, 'store'])->name('retention-releases.store');
    Route::delete('/retention-releases/{retentionRelease}', [RetentionReleaseController::class, 'destroy'])->name('retention-releases.destroy');

    // Vendors + material purchases (payables)
    Route::resource('vendors', VendorController::class);
    Route::get('/materials', [MaterialPurchaseController::class, 'index'])->name('materials.index');
    Route::get('/materials/create', [MaterialPurchaseController::class, 'create'])->name('materials.create');
    Route::post('/materials', [MaterialPurchaseController::class, 'store'])->name('materials.store');
    Route::get('/materials/{materialPurchase}/edit', [MaterialPurchaseController::class, 'edit'])->name('materials.edit');
    Route::put('/materials/{materialPurchase}', [MaterialPurchaseController::class, 'update'])->name('materials.update');
    Route::delete('/materials/{materialPurchase}', [MaterialPurchaseController::class, 'destroy'])->name('materials.destroy');
    // alias used by project show page
    Route::get('/material-purchases/create', [MaterialPurchaseController::class, 'create'])->name('material-purchases.create');

    // Workers + sub-ledgers (attendance, advances, wage payments)
    Route::resource('workers', WorkerController::class);
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

    // Module shells — replaced with real controllers in later steps.
    $stubs = [
        'settings'  => ['Settings', 'Step 11'],
    ];
    foreach ($stubs as $slug => [$label, $step]) {
        Route::get('/'.$slug, fn () => view('stub', ['module' => $label, 'step' => $step]))->name($slug);
    }
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
