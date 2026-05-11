<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Создаём роли (firstOrCreate — безопасно при повторном запуске)
       $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
       $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'admin']);

        $commodityExpertRole = Role::firstOrCreate(['name' => 'commodity_expert', 'guard_name' => 'admin']);


        // Назначаем первому админу роль
       $admin = Admin::query()->first();
       if ($admin && !$admin->hasRole('admin')) {
           $admin->assignRole('admin');
       }
    }
}
