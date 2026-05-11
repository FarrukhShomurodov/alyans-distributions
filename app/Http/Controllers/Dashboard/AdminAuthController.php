<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AdminAuthController
{
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => ['required', 'string', Password::min(8)],
        ]);

        return Auth::guard('admin')->attempt($credentials) ? redirect()->intended(route('dashboard')) : back()->withErrors(['login' => __('validation.incorrect_login_data')]);
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();

        return redirect()->route('dashboard.login');
    }
}
