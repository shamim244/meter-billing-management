<!-- Manual Upload Dropzone Modal -->
<div x-show="showUploadModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div @click.outside="showUploadModal = false" class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-brand-100 dark:bg-brand-950 text-brand-600 dark:text-brand-400 flex items-center justify-center text-xl font-bold">
                    📤
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Upload Electricity Bill PDFs</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Import single .pdf files or a bulk .zip package</p>
                </div>
            </div>
            <button type="button" @click="showUploadModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
        </div>

        <form @submit.prevent="submitUpload()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Target MRU</label>
                    <select x-model="uploadMruId" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                        <option value="">General (No MRU)</option>
                        @foreach($mrus as $m)
                            <option value="{{ $m->id }}">{{ $m->code }} - {{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Month</label>
                    <select x-model="uploadMonth" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Year</label>
                    <select x-model="uploadYear" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                        @php
                            $cYear = (int)date('Y');
                            $modalYearsList = range(max(2020, $cYear - 4), $cYear + 2);
                            rsort($modalYearsList);
                        @endphp
                        @foreach($modalYearsList as $y)
                            <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Dropzone area -->
            <div class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-brand-500 rounded-2xl p-6 text-center transition">
                <input type="file" id="pdfUploadInput" multiple accept=".pdf,.zip" @change="handleFileSelect($event)" class="hidden">
                <label for="pdfUploadInput" class="cursor-pointer block space-y-2">
                    <div class="text-3xl">📂</div>
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                        Click to select or drag & drop files
                    </div>
                    <div class="text-[11px] text-slate-500">
                        Supports PDF files named with CA (e.g. <span class="font-mono">102300783538.pdf</span>) or ZIP archives
                    </div>
                </label>
            </div>

            <div x-show="uploadFiles.length > 0" class="space-y-1 max-h-36 overflow-y-auto">
                <template x-for="(f, idx) in uploadFiles" :key="idx">
                    <div class="text-xs font-mono text-slate-600 dark:text-slate-400 flex items-center justify-between p-2 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                        <span class="truncate" x-text="f.name"></span>
                        <span class="text-[10px] text-slate-500 font-bold" x-text="(f.size / 1024).toFixed(1) + ' KB'"></span>
                    </div>
                </template>
            </div>

            <div class="pt-3 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="showUploadModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition text-center">
                    Cancel
                </button>
                <button type="submit" 
                        :disabled="uploadFiles.length === 0 || uploadRunning"
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 disabled:opacity-50 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-md shadow-brand-500/20">
                    <span x-show="!uploadRunning">🚀 Upload & Parse Files</span>
                    <span x-show="uploadRunning">Uploading...</span>
                </button>
            </div>
        </form>
    </div>
</div>
