<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use DB;
use Hash;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:user');
    }

    /**
     * Protects users who have 'setting' permission from being modified by those who don't.
     */
    private function protectSettingUser(User $targetUser): void
    {
        if (!Auth::user()->can('setting') && $targetUser->can('setting')) {
            throw ValidationException::withMessages([
                'error' => ["Can't edit this user."]
            ]);
        }
    }

    /**
     * Legacy protection for AdminIT role.
     */
    private function checkAdminITProtection(User $targetUser): ?RedirectResponse
    {
        if (Auth::user()->hasRole('User')) {
            if ($targetUser->hasRole('AdminIT')) {
                return redirect()->route('users.index')
                    ->with('error', 'The Admin role is not permitted to modify or delete users with the AdminIT role.');
            }
        }
        return null;
    }

    public function index(Request $request): View
    {
        $roles = Role::pluck('name', 'name')->all();

        $data = User::query()
            ->when($request->name, function($query, $name) {
                return $query->where('name', 'like', "%{$name}%");
            })
            ->when($request->role, function($query, $role) {
                return $query->role($role);
            })
            ->latest()
            ->paginate(5)
            ->appends($request->query());

        return view('users.index', compact('data', 'roles'))
            ->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function create(): View
    {
        $roles = Role::pluck('name', 'name')->all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:confirm-password',
            'roles' => 'required'
        ]);

        // Check if user is trying to assign a role that has 'setting' permission
        if (!Auth::user()->can('setting')) {
            $assignedRoles = Role::whereIn('name', Arr::wrap($request->input('roles')))->get();
            foreach ($assignedRoles as $role) {
                if ($role->hasPermissionTo('setting')) {
                    throw ValidationException::withMessages([
                        'roles' => ["You don't have permission to assign roles with 'setting' access."]
                    ]);
                }
            }
        }

        $input = $request->all();
        $input['password'] = Hash::make($input['password']);

        $user = User::create($input);
        $user->assignRole($request->input('roles'));

        return redirect()->route('users.index')
            ->with('success', 'User created successfully');
    }

    public function show($id): View
    {
        $user = User::find($id);
        return view('users.show', compact('user'));
    }

    public function edit($id): View|RedirectResponse
    {
        $user = User::find($id);

        $this->protectSettingUser($user);
        
        if ($response = $this->checkAdminITProtection($user)) {
            return $response;
        }

        $roles = Role::pluck('name', 'name')->all();
        $userRole = $user->roles->pluck('name', 'name')->all();
        return view('users.edit', compact('user', 'roles', 'userRole'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $user = User::find($id);

        $this->protectSettingUser($user);

        if ($response = $this->checkAdminITProtection($user)) {
            return $response;
        }
        
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|same:confirm-password',
            'roles' => 'required'
        ]);

        // Prevent unauthorized assignment of 'setting' roles
        if (!Auth::user()->can('setting')) {
            $assignedRoles = Role::whereIn('name', Arr::wrap($request->input('roles')))->get();
            foreach ($assignedRoles as $role) {
                if ($role->hasPermissionTo('setting')) {
                    throw ValidationException::withMessages([
                        'roles' => ["You don't have permission to assign roles with 'setting' access."]
                    ]);
                }
            }
        }

        $input = $request->all();
        if (!empty($input['password'])) {
            $input['password'] = Hash::make($input['password']);
        } else {
            $input = Arr::except($input, array('password'));
        }

        $user->update($input);
        DB::table('model_has_roles')->where('model_id', $id)->delete();

        $user->assignRole($request->input('roles'));

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully');
    }

    public function destroy($id): RedirectResponse
    {
        $user = User::find($id);

        $this->protectSettingUser($user);

        if ($response = $this->checkAdminITProtection($user)) {
            return $response;
        }
        
        $user->delete();
        
        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully');
    }
}