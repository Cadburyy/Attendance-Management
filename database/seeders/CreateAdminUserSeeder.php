<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::create([
            'name' => 'Admin', 
            'email' => 'admin@gmail.com',
            'password' => Hash::make('adminit'),
            'salt' => bin2hex(random_bytes(16)),
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'AdminIT']);
        $permissions = Permission::pluck('id','id')->all();
        $adminRole->syncPermissions($permissions);
        $adminUser->assignRole([$adminRole->id]);

        Permission::firstOrCreate(['name' => 'attendance']);
        Permission::firstOrCreate(['name' => 'override']);
        Permission::firstOrCreate(['name' => 'user']);

        $employeeUser = User::create([
            'name' => 'test', 
            'email' => 'test@gmail.com',
            'password' => Hash::make('123123'),
            'salt' => bin2hex(random_bytes(16)),
        ]);

        $employeeRole = Role::firstOrCreate(['name' => 'Employee']);
        $employeeRole->syncPermissions(['attendance']);
        $employeeUser->assignRole([$employeeRole->id]);

        $hrUser = User::create([
            'name' => 'hr', 
            'email' => 'hr@gmail.com',
            'password' => Hash::make('123123'),
            'salt' => bin2hex(random_bytes(16)),
        ]);

        $hrRole = Role::firstOrCreate(['name' => 'HR']);
        $hrRole->syncPermissions(['attendance', 'override', 'user']);
        $hrUser->assignRole([$hrRole->id]);
    }
}