{{--
    Подключает выпадающий список шаблонов для textarea.
    Параметры:
        $textareaId — id textarea, в который вставлять шаблон
        $btnTitle (опционально) — заголовок для кнопки шаблонов
--}}
@php $btnTitle = $btnTitle ?? 'Шаблоны ответов'; @endphp

<style>
    .chat-templates-wrap { position: relative; display: inline-block; }
    .chat-templates-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 44px; height: 44px;
        border-radius: 8px;
        background: #f1f5f9; color: #475569;
        cursor: pointer;
        border: none;
        transition: background 0.15s;
    }
    .chat-templates-btn:hover { background: #e2e8f0; }
    .chat-templates-dropdown {
        position: absolute;
        bottom: calc(100% + 8px);
        left: 0;
        min-width: 320px;
        max-width: 420px;
        max-height: 400px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        z-index: 50;
        padding: 6px;
        display: none;
    }
    .chat-templates-dropdown.is-open { display: block; }
    .chat-templates-search {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        margin-bottom: 6px;
        outline: none;
    }
    .chat-templates-search:focus { border-color: #3b82f6; }
    .chat-template-item {
        padding: 8px 10px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
    }
    .chat-template-item:hover { background: #f1f5f9; }
    .chat-template-item__cmd {
        display: inline-block;
        padding: 1px 6px;
        background: #eff6ff;
        color: #2563eb;
        border-radius: 4px;
        font-family: monospace;
        font-size: 11px;
        margin-right: 6px;
    }
    .chat-template-item__title { font-weight: 500; color: #0f172a; }
    .chat-template-item__preview {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .chat-templates-empty {
        padding: 16px;
        text-align: center;
        font-size: 13px;
        color: #94a3b8;
    }
</style>

<div class="chat-templates-wrap" data-template-target="{{ $textareaId }}">
    <button type="button" class="chat-templates-btn js-chat-templates-toggle" title="{{ $btnTitle }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            <line x1="8" y1="9" x2="16" y2="9"/>
            <line x1="8" y1="13" x2="14" y2="13"/>
        </svg>
    </button>
    <div class="chat-templates-dropdown js-chat-templates-dropdown">
        <input type="text" class="chat-templates-search js-chat-templates-search" placeholder="Поиск шаблона..." autocomplete="off">
        <div class="js-chat-templates-list">
            <div class="chat-templates-empty">Загрузка...</div>
        </div>
    </div>
</div>

<script>
(function () {
    if (window.__chatTemplatesInitDone) return; // запускать инициализацию один раз
    window.__chatTemplatesInitDone = true;
    window.__chatTemplatesCache = null;

    async function loadTemplates() {
        if (window.__chatTemplatesCache) return window.__chatTemplatesCache;
        try {
            const res = await fetch("{{ route('chat-templates.api') }}", {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) return [];
            const data = await res.json();
            window.__chatTemplatesCache = data.templates || [];
            return window.__chatTemplatesCache;
        } catch (e) {
            return [];
        }
    }

    function renderList(listEl, templates, filter) {
        const q = (filter || '').toLowerCase().trim();
        const filtered = q
            ? templates.filter(t =>
                t.command.toLowerCase().includes(q) ||
                t.title.toLowerCase().includes(q) ||
                t.text.toLowerCase().includes(q)
              )
            : templates;

        if (filtered.length === 0) {
            listEl.innerHTML = '<div class="chat-templates-empty">Шаблоны не найдены</div>';
            return;
        }

        listEl.innerHTML = '';
        filtered.forEach(t => {
            const item = document.createElement('div');
            item.className = 'chat-template-item';
            item.dataset.templateId = t.id;
            item.innerHTML = `
                <div>
                    <span class="chat-template-item__cmd">/${escapeHtml(t.command)}</span>
                    <span class="chat-template-item__title">${escapeHtml(t.title)}</span>
                </div>
                <div class="chat-template-item__preview">${escapeHtml(t.text.substring(0, 80))}${t.text.length > 80 ? '...' : ''}</div>
            `;
            item.addEventListener('click', () => {
                insertTemplateIntoTextarea(item.closest('[data-template-target]'), t.text);
            });
            listEl.appendChild(item);
        });
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        })[c]);
    }

    function insertTemplateIntoTextarea(wrapper, text) {
        const targetId = wrapper.dataset.templateTarget;
        const textarea = document.getElementById(targetId);
        if (!textarea) return;

        // Если в начале есть /команда — заменяем её. Иначе — заменяем всё.
        const current = textarea.value;
        const slashMatch = current.match(/^\/[a-zа-я0-9_-]*/iu);
        if (slashMatch) {
            textarea.value = text + current.substring(slashMatch[0].length).replace(/^\s*/, '');
        } else {
            textarea.value = text;
        }

        // Триггерим input event чтобы автоматическое расширение textarea сработало
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        textarea.focus();
        // Курсор в конец
        textarea.setSelectionRange(textarea.value.length, textarea.value.length);
        closeAllDropdowns();
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.chat-templates-dropdown.is-open').forEach(d => d.classList.remove('is-open'));
    }

    // Делегирование событий: один обработчик на весь документ
    document.addEventListener('click', async function (e) {
        // Закрытие при клике вне
        if (!e.target.closest('.chat-templates-wrap')) {
            closeAllDropdowns();
            return;
        }

        // Открытие dropdown при клике на кнопку
        const toggleBtn = e.target.closest('.js-chat-templates-toggle');
        if (toggleBtn) {
            const wrap = toggleBtn.closest('.chat-templates-wrap');
            const dropdown = wrap.querySelector('.js-chat-templates-dropdown');
            const search = wrap.querySelector('.js-chat-templates-search');
            const list = wrap.querySelector('.js-chat-templates-list');
            const wasOpen = dropdown.classList.contains('is-open');

            closeAllDropdowns();
            if (wasOpen) return;

            dropdown.classList.add('is-open');
            const templates = await loadTemplates();
            renderList(list, templates, search.value);
            search.focus();
        }
    });

    // Поиск внутри dropdown
    document.addEventListener('input', async function (e) {
        const search = e.target.closest('.js-chat-templates-search');
        if (!search) return;
        const wrap = search.closest('.chat-templates-wrap');
        const list = wrap.querySelector('.js-chat-templates-list');
        const templates = await loadTemplates();
        renderList(list, templates, search.value);
    });

    // Слушаем ввод в textarea — если начинается со слэша, открываем dropdown с фильтром
    document.addEventListener('input', async function (e) {
        const ta = e.target;
        if (!ta.matches('textarea[data-templates-trigger]')) return;
        const value = ta.value;
        const slashMatch = value.match(/^\/([a-zа-я0-9_-]*)$/iu);
        if (!slashMatch) return; // не команда

        // Найти соответствующий picker
        const targetId = ta.id;
        const wrap = document.querySelector('.chat-templates-wrap[data-template-target="' + CSS.escape(targetId) + '"]');
        if (!wrap) return;
        const dropdown = wrap.querySelector('.js-chat-templates-dropdown');
        const list = wrap.querySelector('.js-chat-templates-list');
        const search = wrap.querySelector('.js-chat-templates-search');

        dropdown.classList.add('is-open');
        const templates = await loadTemplates();
        const query = slashMatch[1];
        search.value = query;
        renderList(list, templates, query);
    });
})();
</script>
