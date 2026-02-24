<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AbsenceController;
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

    // Attendance Management Routes
    Route::resource('attendances', AttendanceController::class);
    Route::post('/attendances/bulk-destroy', [AttendanceController::class, 'bulkDestroy'])->name('attendances.bulkDestroy');
    Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
    Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
    Route::get('/attendance-stats', [AttendanceController::class, 'getStats'])->name('attendance.stats');

    // Absence Management Routes
    Route::resource('absences', AbsenceController::class);
    Route::post('/absences/bulk-destroy', [AbsenceController::class, 'bulkDestroy'])->name('absences.bulkDestroy');
    Route::post('/absences/{absence}/approve', [AbsenceController::class, 'approve'])->name('absences.approve');
    Route::post('/absences/{absence}/reject', [AbsenceController::class, 'reject'])->name('absences.reject');
    
    // Single canonical settings route (GET shows form, PUT updates)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'updateAppearance'])->name('settings.update');
    
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
});
