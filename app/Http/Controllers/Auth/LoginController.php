<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

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
     * Define the input field name used in the login form.
     */
    public function username()
    {
        return 'name';
    }

    /**
     * Override the credentials method to allow login via Email OR Name.
     * Laravel will automatically use Hash::check() against the Bcrypt password in the DB.
     */
    protected function credentials(Request $request)
    {
        $login = $request->input($this->username());
        
        // Check if the input is a valid email format, otherwise assume it's a name
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        return [
            $fieldType => $login,
            'password' => $request->input('password')
        ];
    }
}