<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:role');
    }

    private function protectSettingRole(Role $role): void
    {
        if (Gate::denies('setting') && $role->hasPermissionTo('setting')) {
            throw ValidationException::withMessages([
                'error' => ["Can't edit this role."]
            ]);
        }
    }

    public function index(Request $request): View
    {
        $roles = Role::orderBy('id', 'DESC')->paginate(5);
        return view('roles.index', compact('roles'))
            ->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function create(): View
    {
        $permission = Permission::get();
        return view('roles.create', compact('permission'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ]);

        $permissionsID = array_map(fn($value) => (int)$value, $request->input('permission'));

        $settingPermission = Permission::where('name', 'setting')->first();
        if ($settingPermission && in_array($settingPermission->id, $permissionsID)) {
            if (Gate::denies('setting')) {
                throw ValidationException::withMessages([
                    'permission' => ["Can't assign setting permission."]
                ]);
            }
        }

        $role = Role::create(['name' => $request->input('name')]);
        $role->syncPermissions($permissionsID);

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully');
    }

    public function edit($id): View
    {
        $role = Role::findOrFail($id);

        $this->protectSettingRole($role);

        $permission = Permission::get();
        $rolePermissions = DB::table("role_has_permissions")
            ->where("role_id", $id)
            ->pluck('permission_id', 'permission_id')
            ->all();

        return view('roles.edit', compact('role', 'permission', 'rolePermissions'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $this->validate($request, [
            'name' => 'required',
            'permission' => 'required',
        ]);

        $role = Role::findOrFail($id);

        $this->protectSettingRole($role);

        $permissionsID = array_map(fn($value) => (int)$value, $request->input('permission'));

        $settingPermission = Permission::where('name', 'setting')->first();
        if ($settingPermission && in_array($settingPermission->id, $permissionsID)) {
            if (Gate::denies('setting')) {
                throw ValidationException::withMessages([
                    'permission' => ["Can't assign setting permission."]
                ]);
            }
        }

        $role->name = $request->input('name');
        $role->save();
        $role->syncPermissions($permissionsID);

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully');
    }

    public function destroy($id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        $this->protectSettingRole($role);

        $role->delete();
        return redirect()->back()
            ->with('success', 'Role deleted successfully');
    }
}