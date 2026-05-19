<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request; // Essential for the redirect logic

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        // 1. YOUR EXISTING SPATIE FEATURES
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // 2. THE OTP REDIRECT FIX
        // This tells the built-in Laravel guard to let guests see the OTP page
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('otp-verify*')) {
                return null; // Stop the redirect loop
            }
            return route('login');
        });

        // 3. THE COOKIE FIX
        // Laravel 11 encrypts all cookies. We must exclude this one so the 
        // OtpController can read the User ID correctly.
       $middleware->encryptCookies(except: [
    'otp_user_id',
    'otp_user_email', 
]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function ($schedule) {
        // 4. YOUR EXISTING SCHEDULE FEATURE
        $schedule->command('attendance:cleanup')->dailyAt('00:01');
    })->create();