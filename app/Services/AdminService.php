<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminService
{
    public function store(array $validated): Admin
    {
        return DB::transaction(function () use ($validated) {
            $validated['password'] = isset($validated['password'])
                ? Hash::make((string) $validated['password'])
                : null;

            $roleId = $validated['role_id'] ?? null;
            unset($validated['role_id']);

            $admin = Admin::query()->create($validated);

            if ($roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $admin->syncRoles([$role->name]);
                }
            }

            return $admin;
        });
    }

    public function update(Admin $admin, array $validated): Admin
    {
        return DB::transaction(function () use ($admin, $validated) {
            if (isset($validated['password']) && $validated['password'] !== '') {
                $validated['password'] = Hash::make((string) $validated['password']);
            } else {
                unset($validated['password']);
            }

            $roleId = $validated['role_id'] ?? null;
            unset($validated['role_id']);

            $admin->update($validated);

            if ($roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $admin->syncRoles([$role->name]);
                }
            }

            return $admin->refresh();
        });
    }

    public function destroy(Admin $admin): void
    {
        DB::transaction(function () use ($admin) {
            $admin->syncRoles([]);
            $admin->delete();
        });
    }
}

