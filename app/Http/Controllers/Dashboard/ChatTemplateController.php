<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\ChatTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChatTemplateController
{
    public function index(): View
    {
        $templates = ChatTemplate::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.chat-templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('admin.chat-templates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'command' => 'required|string|max:50|unique:chat_templates,command|regex:/^[a-zа-я0-9_-]+$/iu',
            'title' => 'required|string|max:150',
            'text' => 'required|string|max:4000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        ChatTemplate::create([
            'command' => mb_strtolower($data['command']),
            'title' => $data['title'],
            'text' => $data['text'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('chat-templates.index')
            ->with('success', 'Шаблон добавлен');
    }

    public function edit(ChatTemplate $chatTemplate): View
    {
        return view('admin.chat-templates.edit', ['template' => $chatTemplate]);
    }

    public function update(Request $request, ChatTemplate $chatTemplate): RedirectResponse
    {
        $data = $request->validate([
            'command' => 'required|string|max:50|regex:/^[a-zа-я0-9_-]+$/iu|unique:chat_templates,command,' . $chatTemplate->id,
            'title' => 'required|string|max:150',
            'text' => 'required|string|max:4000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $chatTemplate->update([
            'command' => mb_strtolower($data['command']),
            'title' => $data['title'],
            'text' => $data['text'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('chat-templates.index')
            ->with('success', 'Шаблон обновлён');
    }

    public function destroy(ChatTemplate $chatTemplate): RedirectResponse
    {
        $chatTemplate->delete();

        return redirect()->route('chat-templates.index')
            ->with('success', 'Шаблон удалён');
    }

    /**
     * JSON API для подгрузки шаблонов в чат-форму.
     */
    public function api(): JsonResponse
    {
        $templates = ChatTemplate::active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'command', 'title', 'text']);

        return response()->json(['templates' => $templates]);
    }
}
