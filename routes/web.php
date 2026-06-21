<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Module shells — replaced with real controllers in later steps.
    $stubs = [
        'projects'  => ['Projects', 'Step 3'],
        'money-in'  => ['Money In', 'Step 5'],
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
