<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\BotUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotUserController
{
    public function index(Request $request): View
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $users = BotUser::query()
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.bot_users.index', compact('users', 'dateFrom', 'dateTo'));
    }

    public function show(BotUser $user): View
    {
        $user->load('orders');

        return view('admin.bot_users.show', compact('user'));
    }

    public function toggleBlock(BotUser $user): JsonResponse
    {
        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $user->is_active,
        ]);
    }
}
