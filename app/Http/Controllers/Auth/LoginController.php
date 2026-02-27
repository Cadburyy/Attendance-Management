<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        $login = $request->input('name');
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        $user = User::where($fieldType, $login)->first();

        if ($user && $user->salt) {
            $salt = hex2bin($user->salt);
            $hash = hash_pbkdf2("sha256", $request->password, $salt, 600000, 32);

            if (hash_equals($user->password, bin2hex($hash))) {
                Auth::login($user, $request->filled('remember'));
                return $this->sendLoginResponse($request);
            }
        }

        return $this->sendFailedLoginResponse($request);
    }

    public function username()
    {
        return 'name';
    }

    protected function credentials(Request $request)
    {
        $login = $request->input('name');
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        return [
            $fieldType => $login,
            'password' => $request->input('password')
        ];
    }
}