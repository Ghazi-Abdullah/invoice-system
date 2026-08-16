<?php

use App\Http\Controllers\Admin\BranchController;
use Illuminate\Support\Facades\Route;

// NOTE: /active, /my-branches and /assign must stay ABOVE /{id}, otherwise
// the {id} wildcard swallows them and they never reach the intended method.
Route::prefix('branches')->name('admin.branches.')->group(function () {
    Route::get('/', [BranchController::class, 'index'])->name('index');
    Route::get('/active', [BranchController::class, 'active'])->name('active');
    Route::get('/my-branches', [BranchController::class, 'myBranches'])->name('my-branches');
    Route::post('/assign', [BranchController::class, 'assignBranches'])->name('assign');
    Route::post('/', [BranchController::class, 'store'])->name('store');
    Route::get('/{id}', [BranchController::class, 'show'])->name('show');
    Route::put('/{id}', [BranchController::class, 'update'])->name('update');
    Route::delete('/{id}', [BranchController::class, 'destroy'])->name('destroy');
});