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
</div>
