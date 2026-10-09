<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl">
    <h2 class="text-sm font-black text-white flex items-center gap-2 mb-4">
        <span>📂</span> Existing Migration Bundles on this Host (storage/app/migrations)
    </h2>

    @if(empty($bundles))
        <div class="p-8 text-center text-slate-500 text-xs">
            No migration bundles stored locally. Click "Generate & Download" above or run <code class="font-mono text-indigo-400">php artisan app:migration-pack</code>.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 font-bold">
                        <th class="pb-3 px-4">Filename</th>
                        <th class="pb-3 px-4">Size</th>
                        <th class="pb-3 px-4">Created</th>
                        <th class="pb-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @foreach($bundles as $b)
                        <tr class="hover:bg-slate-900/40 transition">
                            <td class="py-3 px-4 font-mono text-indigo-400 font-semibold">{{ $b['filename'] }}</td>
                            <td class="py-3 px-4 text-slate-300 font-bold">{{ $b['size'] }}</td>
                            <td class="py-3 px-4 text-slate-400">{{ $b['timestamp'] }}</td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <a href="{{ route('admin.server_migration.download', ['filename' => $b['filename']]) }}" class="px-2.5 py-1 text-[11px] font-bold text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/20 rounded-lg transition inline-flex items-center gap-1">
                                    <span>⬇️</span> Download
                                </a>
                                <form action="{{ route('admin.server_migration.destroy', ['filename' => $b['filename']]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this migration bundle file?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 text-[11px] font-bold text-rose-400 hover:text-rose-300 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 rounded-lg transition">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
