<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Mail\SendOtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * FEATURE: Logic to determine if OTP is required
     */
    private function isOutsideWorkingHours()
    {
        // RETURN TRUE FOR TESTING (Always triggers OTP)
        /*
        return true; 
         */
         // UNCOMMENT THIS FOR REAL PRODUCTION LOGIC:
         
        $now = now()->timezone('Asia/Jakarta');
        $start = Carbon::createFromTime(8, 0, 0, 'Asia/Jakarta'); // 08:00 AM
        $end = Carbon::createFromTime(17, 0, 0, 'Asia/Jakarta');  // 05:00 PM

        // Returns true if it's the weekend OR outside 8am-5pm
        return $now->isWeekend() || !$now->between($start, $end);
       
    }

    /**
     * ADJUSTED: Logic to handle OTP after successful password check
     */
    protected function authenticated(Request $request, $user)
    {
        // 1. Only AdminIT and HR are subject to OTP check
        if (in_array($user->role, ['AdminIT', 'HR'])) {
            
            // 2. Check the Working Hours feature
            if ($this->isOutsideWorkingHours()) {
                
                // 3. Generate & Save OTP to Database
               // Inside LoginController.php -> authenticated method
$otp = rand(100000, 999999);
$user->otp_code = $otp;

// NIST REQUIREMENT: 5 Minutes max
$user->otp_expires_at = now()->addMinutes(5); 
$user->save();

Mail::to($user->email)->send(new SendOtpMail($otp));

$userId = $user->id;
Auth::logout();

// Set the cookie to also expire in 5 minutes to match
return redirect()->route('otp.verify')
    ->withCookie(cookie('otp_user_id', $userId, 5));
            }
        }

        // Standard login for other roles or inside working hours
        return redirect()->intended($this->redirectTo);
    }

    /**
     * FEATURE: Support for Name login
     */
    public function username() 
    { 
        return 'name'; 
    }

    /**
     * FEATURE: Multi-login (Email or Name)
     */
    protected function credentials(Request $request)
    {
        $login = $request->input($this->username());
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        return [
            $fieldType => $login,
            'password' => $request->input('password')
        ];
    }
}