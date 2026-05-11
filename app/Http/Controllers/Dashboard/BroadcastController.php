<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Broadcast;
use App\Models\BotUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;

class BroadcastController
{
    public function index(): View
    {
        $broadcasts = Broadcast::orderByDesc('created_at')->paginate(20);

        return view('admin.broadcasts.index', compact('broadcasts'));
    }

    public function create(): View
    {
        $usersCount = BotUser::where('is_active', true)->count();

        return view('admin.broadcasts.create', compact('usersCount'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:4000',
            'photo' => 'nullable|image|mimes:jpg,png,jpeg|max:5120',
        ]);

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = $request->file('photo')->store('broadcasts', 'public');
        }

        $broadcast = Broadcast::create([
            'title' => $data['title'],
            'message' => $data['message'],
            'photo_url' => $photoUrl,
            'status' => Broadcast::STATUS_DRAFT,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('broadcasts.index')->with('success', 'Рассылка создана как черновик');
    }

    public function send(Broadcast $broadcast): RedirectResponse
    {
        if ($broadcast->status === Broadcast::STATUS_SENT) {
            return redirect()->route('broadcasts.index')->with('error', 'Рассылка уже отправлена');
        }

        $users = BotUser::where('is_active', true)->whereNotNull('chat_id')->get();
        $broadcast->update([
            'status' => Broadcast::STATUS_SENDING,
            'total_recipients' => $users->count(),
        ]);

        $sentCount = 0;
        $failedCount = 0;

        try {
            $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

            foreach ($users as $user) {
                try {
                    if ($broadcast->photo_url) {
                        $photoPath = Storage::disk('public')->path($broadcast->photo_url);
                        $telegram->sendPhoto([
                            'chat_id' => $user->chat_id,
                            'photo' => fopen($photoPath, 'r'),
                            'caption' => $broadcast->message,
                            'parse_mode' => 'HTML',
                        ]);
                    } else {
                        $telegram->sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => $broadcast->message,
                            'parse_mode' => 'HTML',
                        ]);
                    }
                    $sentCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    Log::warning("Broadcast #{$broadcast->id}: failed to send to user {$user->id}: " . $e->getMessage());
                }

                usleep(50000); // 50ms задержка между отправками
            }

            $broadcast->update([
                'status' => Broadcast::STATUS_SENT,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'sent_at' => now(),
            ]);

            return redirect()->route('broadcasts.index')
                ->with('success', "Рассылка отправлена: {$sentCount} успешно, {$failedCount} ошибок");

        } catch (\Throwable $e) {
            $broadcast->update([
                'status' => Broadcast::STATUS_FAILED,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ]);

            Log::error("Broadcast #{$broadcast->id} failed: " . $e->getMessage());

            return redirect()->route('broadcasts.index')
                ->with('error', 'Ошибка при отправке рассылки: ' . $e->getMessage());
        }
    }

    public function destroy(Broadcast $broadcast): RedirectResponse
    {
        if ($broadcast->photo_url && Storage::disk('public')->exists($broadcast->photo_url)) {
            Storage::disk('public')->delete($broadcast->photo_url);
        }

        $broadcast->delete();

        return redirect()->route('broadcasts.index')->with('success', 'Рассылка удалена');
    }
}
