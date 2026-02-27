<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $plainPassword = 'adminit';
        $salt = random_bytes(16);
        $iterations = 600000;
        $hash = hash_pbkdf2("sha256", $plainPassword, $salt, $iterations, 32);

        $user = User::create([
            'name' => 'Admin', 
            'email' => 'admin@gmail.com',
            'password' => bin2hex($hash),
            'salt' => bin2hex($salt),
        ]);

        $role = Role::create(['name' => 'AdminIT']);

        $permissions = Permission::pluck('id','id')->all();
        $role->syncPermissions($permissions);

        $user->assignRole([$role->id]);
    }
}