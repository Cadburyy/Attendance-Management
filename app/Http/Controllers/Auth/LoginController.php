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
     * Helper to verify geolocation during login
     */
    private function verifyLoginGeolocation(Request $request, $user)
    {
        // AdminIT is ALWAYS exempt from geolocation checks to prevent lockouts
        if ($user->hasRole('AdminIT') || $user->role === 'AdminIT') {
            return true;
        }

        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        // If geolocation is disabled, allow
        if (empty($settings['geolocation_enabled']) || $settings['geolocation_enabled'] == '0') {
            return true;
        }

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        if (!$latitude || !$longitude) {
            // Geolocation is enabled but coordinates weren't supplied/blocked
            return false;
        }

        $officeLat = (float)($settings['office_latitude'] ?? 0);
        $officeLng = (float)($settings['office_longitude'] ?? 0);
        $maxRadius = (int)($settings['office_radius'] ?? 100);

        // Haversine formula to calculate distance in meters
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($latitude - $officeLat);
        $dLng = deg2rad($longitude - $officeLng);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($officeLat)) * cos(deg2rad($latitude)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return ($distance <= $maxRadius);
    }

    /**
     * ADJUSTED: Logic to handle OTP after successful password check
     */
    protected function authenticated(Request $request, $user)
    {
        // 1. Verify Geolocation (Exempt AdminIT)
        if (!$this->verifyLoginGeolocation($request, $user)) {
            Auth::logout();
            return redirect()->back()
                ->withInput($request->only('name', 'remember'))
                ->withErrors(['name' => 'Login ditolak: Anda berada di luar jangkauan lokasi kantor.']);
        }

        // 2. Only AdminIT and HR are subject to OTP check
        if (in_array($user->role, ['AdminIT', 'HR']) || $user->hasRole('AdminIT') || $user->hasRole('HR')) {
            
            // 3. Check the Working Hours feature
            if ($this->isOutsideWorkingHours()) {
                
                // 4. Generate & Save OTP to Database
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