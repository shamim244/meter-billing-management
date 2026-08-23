<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
        showNewTagModal: false,
        newCode: '',
        newLabel: '',
        newShortLabel: '',
        newColor: 'blue'
    }">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>🏷️</span> Bill Review Tags Manager
                </h1>
                <p class="text-sm text-slate-400 mt-1">Configure available tags, display labels, badge colors, and the default tag for consumer review cards.</p>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" @click="showNewTagModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-indigo-600/30">
                    <span>➕</span> Add Custom Tag
                </button>
                <form method="POST" action="{{ route('admin.tags.reset_factory') }}" onsubmit="return confirm('Reset all bill tags to factory defaults?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition border border-slate-700/60">
                        🔄 Factory Reset
                    </button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-300 text-xs font-semibold flex items-center gap-2">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-300 text-xs font-semibold space-y-1">
                @foreach($errors->all() as $err)
                    <div>⚠️ {{ $err }}</div>
                @endforeach
            </div>
        @endif

        <!-- Tags Form Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-5 border-b border-slate-800/80">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider">Active & Configured Tags</h2>
                <p class="text-xs text-slate-400 mt-0.5">Edit full descriptions, compact card pills, badge color themes, and mark the platform default.</p>
            </div>

            <form method="POST" action="{{ route('admin.tags.update') }}" class="p-5 space-y-4">
                @csrf

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                                <th class="py-3 px-3 font-semibold w-16 text-center">Default</th>
                                <th class="py-3 px-3 font-semibold">Tag Code</th>
                                <th class="py-3 px-3 font-semibold">Full Label (Reports/Tooltip)</th>
                                <th class="py-3 px-3 font-semibold">Card Pill Label (Mobile/Desktop)</th>
                                <th class="py-3 px-3 font-semibold">Color Theme</th>
                                <th class="py-3 px-3 font-semibold w-20 text-center">Order</th>
                                <th class="py-3 px-3 font-semibold w-20 text-center">Active</th>
                                <th class="py-3 px-3 font-semibold w-24 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            @foreach($tags as $idx => $tag)
                                <tr class="hover:bg-slate-800/20 transition">
                                    <td class="py-3 px-3 text-center">
                                        <input type="radio" name="default_tag_code" value="{{ $tag['code'] }}" {{ (!empty($tag['is_default']) || $defaultTag === $tag['code']) ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500 bg-slate-950 border-slate-700">
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="text" name="tags[{{ $idx }}][code]" value="{{ $tag['code'] }}" readonly class="w-36 text-xs bg-slate-950/70 border-slate-800 rounded-lg text-slate-400 font-mono py-1 px-2 cursor-not-allowed">
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="text" name="tags[{{ $idx }}][label]" value="{{ $tag['label'] }}" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-lg text-white py-1 px-2.5 focus:ring-indigo-500">
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="text" name="tags[{{ $idx }}][short_label]" value="{{ $tag['short_label'] ?? $tag['label'] }}" required class="w-44 text-xs bg-slate-950 border-slate-800 rounded-lg text-white font-semibold py-1 px-2.5 focus:ring-indigo-500">
                                    </td>
                                    <td class="py-3 px-3">
                                        <select name="tags[{{ $idx }}][color]" class="text-xs bg-slate-950 border-slate-800 rounded-lg text-white py-1 px-2 focus:ring-indigo-500">
                                            <option value="emerald" {{ ($tag['color'] ?? '') === 'emerald' ? 'selected' : '' }}>🟢 Emerald (OK)</option>
                                            <option value="blue" {{ ($tag['color'] ?? '') === 'blue' ? 'selected' : '' }}>🔵 Blue</option>
                                            <option value="purple" {{ ($tag['color'] ?? '') === 'purple' ? 'selected' : '' }}>🟣 Purple</option>
                                            <option value="amber" {{ ($tag['color'] ?? '') === 'amber' ? 'selected' : '' }}>🟠 Amber</option>
                                            <option value="rose" {{ ($tag['color'] ?? '') === 'rose' ? 'selected' : '' }}>🔴 Rose / Red</option>
                                            <option value="cyan" {{ ($tag['color'] ?? '') === 'cyan' ? 'selected' : '' }}>🩵 Cyan</option>
                                            <option value="indigo" {{ ($tag['color'] ?? '') === 'indigo' ? 'selected' : '' }}>🔷 Indigo</option>
                                            <option value="slate" {{ ($tag['color'] ?? '') === 'slate' ? 'selected' : '' }}>⚪ Slate / Grey</option>
                                        </select>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <input type="number" name="tags[{{ $idx }}][order]" min="1" max="99" value="{{ $tag['order'] ?? ($idx + 1) }}" class="w-14 text-center text-xs bg-slate-950 border-slate-800 rounded-lg text-white py-1 px-1 font-mono">
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <input type="checkbox" name="tags[{{ $idx }}][is_active]" value="1" {{ !empty($tag['is_active']) ? 'checked' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500 bg-slate-950 border-slate-700">
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        @if(!empty($tag['is_default']) || $defaultTag === $tag['code'])
                                            <span class="text-[10px] text-slate-500 font-semibold px-2 py-1 rounded bg-slate-950 border border-slate-800" title="Cannot delete active default tag">🔒 Default</span>
                                        @else
                                            <button type="submit" form="delete-tag-{{ $tag['code'] }}" class="px-2.5 py-1 bg-rose-500/10 hover:bg-rose-500/25 text-rose-400 hover:text-rose-300 rounded-lg text-xs font-semibold transition border border-rose-500/20 inline-flex items-center gap-1" title="Delete this Tag">
                                                <span>🗑️</span> Delete
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-end pt-4 border-t border-slate-800">
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30">
                        💾 Save Tag Settings
                    </button>
                </div>
            </form>

            {{-- Hidden Delete Forms --}}
            @foreach($tags as $tag)
                @if(empty($tag['is_default']) && $defaultTag !== $tag['code'])
                    <form id="delete-tag-{{ $tag['code'] }}" method="POST" action="{{ route('admin.tags.destroy', $tag['code']) }}" onsubmit="return confirm('Are you sure you want to permanently delete tag \'{{ $tag['label'] }}\' ({{ $tag['code'] }})?');" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            @endforeach
        </div>

        <!-- Add Custom Tag Modal -->
        <div x-show="showNewTagModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <span>➕</span> Add New Review Tag
                    </h3>
                    <button type="button" @click="showNewTagModal = false" class="text-slate-400 hover:text-white text-lg font-bold">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.tags.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Tag Code <span class="text-rose-400">*</span></label>
                        <input type="text" name="code" x-model="newCode" required placeholder="e.g. 24DAYS / MTR_BURNT" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 font-mono uppercase focus:ring-indigo-500">
                        <span class="text-[10px] text-slate-500 mt-1 block">Unique identifier for database storage.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Full Label (Reports & Tooltip) <span class="text-rose-400">*</span></label>
                        <input type="text" name="label" x-model="newLabel" required placeholder="e.g. Not-approved Previous BQC and RQC" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Card Pill Label (Short Mobile Display) <span class="text-rose-400">*</span></label>
                        <input type="text" name="short_label" x-model="newShortLabel" required placeholder="e.g. Not-Apprv Prev BQC/RQC" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                        <span class="text-[10px] text-slate-500 mt-1 block">Compact text shown on card pills to prevent UI overflow.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Badge Color Theme <span class="text-rose-400">*</span></label>
                        <select name="color" x-model="newColor" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                            <option value="emerald">🟢 Emerald (Green)</option>
                            <option value="blue">🔵 Blue</option>
                            <option value="purple">🟣 Purple</option>
                            <option value="amber">🟠 Amber</option>
                            <option value="rose">🔴 Rose / Red</option>
                            <option value="cyan">🩵 Cyan</option>
                            <option value="indigo">🔷 Indigo</option>
                            <option value="slate">⚪ Slate / Grey</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                        <button type="button" @click="showNewTagModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-semibold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
                            Add Tag
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
