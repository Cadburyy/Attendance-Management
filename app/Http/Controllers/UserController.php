<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    function __construct() {
        $this->middleware('permission:user');
    }

    private function protectSettingUser(User $targetUser): void {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->can('setting') && $targetUser->can('setting')) {
            throw ValidationException::withMessages(['error' => ["Can't edit this user."]]);
        }
    }

    private function checkAdminITProtection(User $targetUser): ?RedirectResponse {
        /** @var User $authUser */
        $authUser = Auth::user();
        if ($authUser->hasRole('User') && $targetUser->hasRole('AdminIT')) {
            return redirect()->route('users.index')->with('error', 'The Admin role is not permitted to modify or delete users with the AdminIT role.');
        }
        return null;
    }

    public function index(Request $request): View {
        $roles = Role::pluck('name', 'name')->all();
        $data = User::query()
            ->when($request->name, function($query, $name) { return $query->where('name', 'like', "%{$name}%"); })
            ->when($request->role, function($query, $role) { return $query->role($role); })
            ->latest()->paginate(5)->appends($request->query());

        return view('users.index', compact('data', 'roles'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function create(): View {
        $roles = Role::pluck('name', 'name')->all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|same:confirm-password|min:6|max:18',
            'roles' => 'required'
        ]);

        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->can('setting')) {
            $assignedRoles = Role::whereIn('name', Arr::wrap($request->input('roles')))->get();
            foreach ($assignedRoles as $role) {
                if ($role->hasPermissionTo('setting')) {
                    throw ValidationException::withMessages(['roles' => ["You don't have permission to assign roles with 'setting' access."]]);
                }
            }
        }

        $input = $request->all();
        $salt = random_bytes(16);
        $hash = hash_pbkdf2("sha256", $input['password'], $salt, 600000, 32);
        
        $input['salt'] = bin2hex($salt);
        $input['password'] = bin2hex($hash);

        $user = User::create($input);
        $user->assignRole($request->input('roles'));

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    public function show($id): View {
        $user = User::findOrFail($id);
        return view('users.show', compact('user'));
    }

    public function edit($id): View|RedirectResponse {
        $user = User::findOrFail($id);
        $this->protectSettingUser($user);
        if ($response = $this->checkAdminITProtection($user)) return $response;
        $roles = Role::pluck('name', 'name')->all();
        $userRole = $user->roles->pluck('name', 'name')->all();
        return view('users.edit', compact('user', 'roles', 'userRole'));
    }

    public function update(Request $request, $id): RedirectResponse {
        $user = User::findOrFail($id);
        $this->protectSettingUser($user);
        if ($response = $this->checkAdminITProtection($user)) return $response;
        
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|same:confirm-password|min:6|max:18',
            'roles' => 'required'
        ]);

        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->can('setting')) {
            $assignedRoles = Role::whereIn('name', Arr::wrap($request->input('roles')))->get();
            foreach ($assignedRoles as $role) {
                if ($role->hasPermissionTo('setting')) {
                    throw ValidationException::withMessages(['roles' => ["You don't have permission to assign roles with 'setting' access."]]);
                }
            }
        }

        $input = $request->all();
        if (!empty($input['password'])) {
            $salt = random_bytes(16);
            $hash = hash_pbkdf2("sha256", $input['password'], $salt, 600000, 32);
            $input['salt'] = bin2hex($salt);
            $input['password'] = bin2hex($hash);
        } else {
            $input = Arr::except($input, array('password', 'salt'));
        }

        $user->update($input);
        $user->syncRoles($request->input('roles'));

        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }

    public function destroy($id): RedirectResponse {
        $user = User::findOrFail($id);
        $this->protectSettingUser($user);
        if ($response = $this->checkAdminITProtection($user)) return $response;
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }
}