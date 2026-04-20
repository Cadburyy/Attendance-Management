<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
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
    Route::get('/attendances/approvals', [AttendanceController::class, 'approvals'])->name('attendances.approvals');
    Route::post('/attendances/bulk-approve', [AttendanceController::class, 'bulkApprove'])->name('attendances.bulkApprove');
    Route::post('/attendances/bulk-reject', [AttendanceController::class, 'bulkReject'])->name('attendances.bulkReject');
    Route::post('/attendances/{attendance}/request-override', [AttendanceController::class, 'requestOverride'])->name('attendances.requestOverride');
    Route::post('/attendances/{attendance}/approve', [AttendanceController::class, 'approveOverride'])->name('attendances.approve');
    Route::post('/attendances/{attendance}/reject', [AttendanceController::class, 'rejectOverride'])->name('attendances.reject');
    
    Route::resource('attendances', AttendanceController::class);
    Route::post('/attendances/bulk-destroy', [AttendanceController::class, 'bulkDestroy'])->name('attendances.bulkDestroy');
    Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
    Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
    Route::get('/attendance-stats', [AttendanceController::class, 'getStats'])->name('attendance.stats');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'updateAppearance'])->name('settings.update');
    
    Route::get('/users/{id}/picture', [UserController::class, 'showPicture'])->name('users.picture');
    Route::get('/users/{id}/decrypt', [UserController::class, 'decryptImage'])->name('users.decrypt');
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
});