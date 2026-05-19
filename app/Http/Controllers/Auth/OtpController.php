<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class OtpController extends Controller
{
   public function showVerifyForm(Request $request)
{
    $userId = $request->cookie('otp_user_id');
    $user = \App\Models\User::find($userId);

    if (!$user) return redirect()->route('login');

    // Calculate remaining seconds based on the DB timestamp
    $secondsRemaining = now()->diffInSeconds($user->otp_expires_at, false);

    return view('auth.otpVerify', [
        'expiresIn' => $secondsRemaining > 0 ? $secondsRemaining : 0
    ]);
}
   public function verify(Request $request)
{
    $request->validate(['otp' => 'required|numeric|digits:6']);

    // 1. Get the user ID from the cookie
    $userId = $request->cookie('otp_user_id');
    
    // 2. CRITICAL FIX: Use find() right here to get the LATEST data from DB
    // This ensures we get the 456555 and NOT the old 600794
    $user = \App\Models\User::where('id', $userId)->first();

    if (!$user) {
        return redirect()->route('login')->withErrors(['otp' => 'Session expired.']);
    }

    // 3. NIST: Check Expiration
    if ($user->otp_expires_at && now()->gt($user->otp_expires_at)) {
        $user->update(['otp_code' => null, 'otp_expires_at' => null]);
        return back()->withErrors(['otp' => 'OTP has expired.']);
    }

    // 4. Compare with the LATEST code in the database
    if ($user->otp_code === $request->otp) {
        // SUCCESS: Clear immediately (NIST Single-Use)
        $user->update(['otp_code' => null, 'otp_expires_at' => null]);

        \Auth::login($user);
        return redirect('/home')->withoutCookie('otp_user_id');
    }

    return back()->withErrors(['otp' => 'Invalid code.']);
}

// Update Resend to also follow 5-minute rule
public function resend(Request $request)
{
    $userId = $request->cookie('otp_user_id');
    $user = \App\Models\User::find($userId);

    if ($user) {
        $newOtp = rand(100000, 999999);

        // Explicitly update and force a save to the DB
        $user->otp_code = $newOtp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save(); // Force write to database

        \Mail::to($user->email)->send(new \App\Mail\SendOtpMail($newOtp));

        return back()->with('message', 'A new OTP has been sent!');
    }

    return redirect()->route('login');
}
}