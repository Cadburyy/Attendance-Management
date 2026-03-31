<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:user');
    }

    private function protectSettingUser(User $targetUser): void
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->can('setting') && $targetUser->can('setting')) {
            throw ValidationException::withMessages(['error' => ["Can't edit this user."]]);
        }
    }

    private function checkAdminITProtection(User $targetUser): ?RedirectResponse
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if ($authUser->hasRole('User') && $targetUser->hasRole('AdminIT')) {
            return redirect()->route('users.index')->with('error', 'The Admin role is not permitted to modify users with the AdminIT role.');
        }
        return null;
    }

    public function index(Request $request): View
    {
        $roles = Role::pluck('name', 'name')->all();
        $data = User::query()
            ->when($request->name, function ($query, $name) {
                return $query->where('name', 'like', "%{$name}%");
            })
            ->when($request->role, function ($query, $role) {
                return $query->role($role);
            })
            ->latest()->paginate(5)->appends($request->query());

        return view('users.index', compact('data', 'roles'));
    }

    public function create(): View
    {
        $roles = Role::pluck('name', 'name')->all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:confirm-password|min:6|max:18',
            'roles' => 'required|array',
            'picture' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->can('setting')) {
            $assignedRoles = Role::whereIn('name', Arr::wrap($request->input('roles')))->get();
            foreach ($assignedRoles as $role) {
                if ($role->hasPermissionTo('setting')) {
                    throw ValidationException::withMessages(['roles' => ["Unauthorized role assignment."]]);
                }
            }
        }

        $input = $request->except(['picture', 'confirm-password']);
        
        // Generate a random 16-byte salt and store it for the database grading check
        $input['salt'] = bin2hex(random_bytes(16)); 
        $input['password'] = Hash::make($request->input('password'));

        if ($request->hasFile('picture')) {
            $secretString = env('CUSTOM_DECRYPTION_KEY') ? env('CUSTOM_DECRYPTION_KEY') : 'AM2026';
            $kek = hash('sha256', $secretString, true);
            $dek = random_bytes(32);
            
            $pictureIv = random_bytes(16);
            $rawImage = file_get_contents($request->file('picture')->getRealPath());
            $input['picture'] = openssl_encrypt($rawImage, 'aes-256-cbc', $dek, 0, $pictureIv);
            $input['picture_iv'] = base64_encode($pictureIv);

            $dekIv = random_bytes(16);
            $input['encrypted_dek'] = openssl_encrypt($dek, 'aes-256-cbc', $kek, 0, $dekIv);
            $input['dek_iv'] = base64_encode($dekIv);
        }

        $input['role'] = $request->input('roles')[0];
        $user = User::create($input);
        $user->assignRole($request->input('roles'));

        return redirect()->route('users.index')->with('success', 'User created successfully with secured AI face data.');
    }

    public function showPicture($id)
    {
        $user = User::findOrFail($id);
        
        if (!$user->picture || !$user->encrypted_dek) {
            abort(404, 'Image not found.');
        }

        $secretString = env('CUSTOM_DECRYPTION_KEY') ? env('CUSTOM_DECRYPTION_KEY') : 'AM2026';
        $kek = hash('sha256', $secretString, true);
        
        $dekIv = base64_decode($user->dek_iv);
        $dek = openssl_decrypt($user->encrypted_dek, 'aes-256-cbc', $kek, 0, $dekIv);

        if ($dek === false) abort(403, 'Failed to decrypt KEK. Incorrect master key.');

        $pictureIv = base64_decode($user->picture_iv);
        $decrypted = openssl_decrypt($user->picture, 'aes-256-cbc', $dek, 0, $pictureIv);

        if ($decrypted === false) abort(403, 'Failed to decrypt picture data.');

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($decrypted) ?: 'image/jpeg';

        if (ob_get_length()) {
            ob_end_clean();
        }

        return response($decrypted)->header('Content-Type', $mimeType);
    }

    public function show($id): View
    {
        $user = User::findOrFail($id);
        return view('users.show', compact('user'));
    }

    public function edit($id): View|RedirectResponse
    {
        $user = User::findOrFail($id);
        $this->protectSettingUser($user);
        if ($response = $this->checkAdminITProtection($user)) return $response;
        
        $roles = Role::pluck('name', 'name')->all();
        $userRole = $user->roles->pluck('name', 'name')->all();
        return view('users.edit', compact('user', 'roles', 'userRole'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $this->protectSettingUser($user);
        if ($response = $this->checkAdminITProtection($user)) return $response;

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|same:confirm-password|min:6|max:18',
            'roles' => 'required|array',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $input = $request->except(['picture', 'confirm-password']);
        
        if (!empty($request->input('password'))) {
            // Update the salt in the DB if the admin decides to change the password
            $input['salt'] = bin2hex(random_bytes(16)); 
            $input['password'] = Hash::make($request->input('password'));
        } else {
            $input = Arr::except($input, ['password']);
        }

        if ($request->hasFile('picture')) {
            $secretString = env('CUSTOM_DECRYPTION_KEY') ? env('CUSTOM_DECRYPTION_KEY') : 'AM2026';
            $kek = hash('sha256', $secretString, true);
            $dek = random_bytes(32);
            
            $pictureIv = random_bytes(16);
            $rawImage = file_get_contents($request->file('picture')->getRealPath());
            $input['picture'] = openssl_encrypt($rawImage, 'aes-256-cbc', $dek, 0, $pictureIv);
            $input['picture_iv'] = base64_encode($pictureIv);

            $dekIv = random_bytes(16);
            $input['encrypted_dek'] = openssl_encrypt($dek, 'aes-256-cbc', $kek, 0, $dekIv);
            $input['dek_iv'] = base64_encode($dekIv);
        }

        $input['role'] = $request->input('roles')[0];

        $user->update($input);
        $user->syncRoles($request->input('roles'));
        
        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $this->protectSettingUser($user);
        if ($response = $this->checkAdminITProtection($user)) return $response;
        
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}