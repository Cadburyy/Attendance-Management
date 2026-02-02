<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\WeightController;
use App\Http\Controllers\MutationController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;


Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('home');
    }
    return redirect()->route('login');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {

    Route::resource('items', ItemController::class);
    Route::post('/items/bulk-destroy', [ItemController::class, 'bulkDestroy'])->name('items.bulkDestroy');
    Route::post('/items/download-csv', [ItemController::class, 'downloadCsv'])->name('items.downloadCsv');
    Route::post('/items/download-popup-csv', [ItemController::class, 'downloadPopupCsv'])->name('items.downloadPopupCsv');

    Route::resource('mutations', MutationController::class);
    Route::post('/mutations/bulk-destroy', [MutationController::class, 'bulkDestroy'])->name('mutations.bulkDestroy');
    Route::post('/mutations/download-csv', [MutationController::class, 'downloadCsv'])->name('mutations.downloadCsv');
    Route::post('/mutations/download-popup-csv', [MutationController::class, 'downloadPopupCsv'])->name('mutations.downloadPopupCsv');

    Route::resource('sales', SaleController::class);
    Route::post('/sales/bulk-destroy', [SaleController::class, 'bulkDestroy'])->name('sales.bulkDestroy');
    Route::post('/sales/download-csv', [SaleController::class, 'downloadCsv'])->name('sales.downloadCsv');
    Route::post('/sales/download-popup-csv', [SaleController::class, 'downloadPopupCsv'])->name('sales.downloadPopupCsv');

    Route::resource('weights', WeightController::class);
    Route::post('/weights/bulk-destroy', [WeightController::class, 'bulkDestroy'])->name('weights.bulkDestroy');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/appearance', [SettingsController::class, 'editAppearance'])->name('settings.appearance');
    Route::put('/settings/appearance', [SettingsController::class, 'updateAppearance'])->name('settings.appearance.update');
    
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
});