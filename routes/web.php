<?php

use App\Http\Controllers\ClientPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RetentionReleaseController;
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

    // Module shells — replaced with real controllers in later steps.
    $stubs = [
        'money-out' => ['Money Out', 'Step 6–8'],
        'workers'   => ['Workers', 'Step 7'],
        'vendors'   => ['Vendors', 'Step 6'],
        'reports'   => ['Reports', 'Step 10'],
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
