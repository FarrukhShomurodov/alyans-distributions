<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Requests\Admin\StorageRequest;
use App\Http\Requests\Admin\UpdateRequest;
use App\Models\Admin;
use App\Services\AdminService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class AdminController
{
    protected AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function index(): View
    {
        $admins = Admin::query()->select(['id', 'login'])
            ->orderBy('id')
            ->paginate(50);

        return view('admin.admins.index', compact('admins'));
    }

    public function create(): View
    {
        $roles = Role::where('guard_name', 'admin')->pluck('name', 'id');

        return view('admin.admins.create', compact('roles'));
    }

    public function store(StorageRequest $request): RedirectResponse
    {
        $this->adminService->store((array) $request->validated());

        return redirect()->route('admins.index')->with('success', 'Админ успешно добавлен!');
    }

    public function edit(Admin $admin): View
    {
        $roles = Role::where('guard_name', 'admin')->pluck('name', 'id');

        return view('admin.admins.edit', compact('admin', 'roles'));
    }

    public function update(UpdateRequest $request, Admin $admin): RedirectResponse
    {
        $this->adminService->update($admin, (array) $request->validated());

        return redirect()->route('admins.index')->with('success', 'Админ успешно обнавлен!');
    }

    public function destroy(Admin $admin): RedirectResponse
    {
        if ($admin->email === 'test@gmail.com') {
            return redirect()->route('admins.index')->withErrors('Вы неможете удалить админа.');
        }

        $this->adminService->destroy($admin);

        return redirect()->route('admins.index')->with('success', 'Админ успешно удален!');
    }
}
