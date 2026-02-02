<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
           'role',
           'user',
           'setting',
           'appearance',
           'item',
           'mutation',
           'sale',
           'weight',
        ];

        foreach ($permissions as $permission) {
             if (Permission::where('name', $permission)->doesntExist()) {
                 Permission::create(['name' => $permission]);
             }
        }
    }
}