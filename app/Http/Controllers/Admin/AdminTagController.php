<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BillTagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTagController extends Controller
{
    protected BillTagService $billTagService;

    public function __construct(BillTagService $billTagService)
    {
        $this->billTagService = $billTagService;
    }

    /**
     * Display the Admin Tags configuration console.
     */
    public function index(): View
    {
        $tags = $this->billTagService->getAllTags();
        $defaultTag = $this->billTagService->getDefaultTag();

        return view('admin.tags.index', compact('tags', 'defaultTag'));
    }

    /**
     * Store a new custom tag or update all tags.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'tags' => 'required|array',
            'tags.*.code' => 'required|string|max:64',
            'tags.*.label' => 'required|string|max:100',
            'tags.*.short_label' => 'required|string|max:50',
            'tags.*.color' => 'required|string|in:emerald,blue,purple,amber,rose,cyan,indigo,slate',
            'tags.*.order' => 'required|integer|min:1|max:100',
            'tags.*.is_active' => 'nullable|boolean',
            'default_tag_code' => 'required|string',
        ]);

        $tags = $request->input('tags', []);
        $defaultCode = trim($request->input('default_tag_code'));

        foreach ($tags as &$t) {
            $t['is_default'] = (strtoupper(trim($t['code'])) === strtoupper($defaultCode));
            $t['is_active'] = !empty($t['is_active']);
        }

        $this->billTagService->saveTagConfig($tags);

        return redirect()->route('admin.tags.index')
            ->with('success', 'Bill review tags updated successfully.');
    }

    /**
     * Add a single new tag to the list.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:64',
            'label' => 'required|string|max:100',
            'short_label' => 'required|string|max:50',
            'color' => 'required|string|in:emerald,blue,purple,amber,rose,cyan,indigo,slate',
            'order' => 'nullable|integer|min:1|max:100',
        ]);

        $tags = $this->billTagService->getAllTags();
        $code = strtoupper(trim(preg_replace('/[^A-Za-z0-9_]/', '_', $request->code)));

        // Check duplicate code
        foreach ($tags as $existing) {
            if (strtoupper($existing['code']) === $code) {
                return redirect()->route('admin.tags.index')
                    ->withErrors(['code' => "Tag code '{$code}' already exists."]);
            }
        }

        $tags[] = [
            'code' => $code,
            'label' => trim($request->label),
            'short_label' => trim($request->short_label),
            'color' => $request->color,
            'is_default' => false,
            'is_active' => true,
            'order' => (int)($request->order ?: (count($tags) + 1)),
        ];

        $this->billTagService->saveTagConfig($tags);

        return redirect()->route('admin.tags.index')
            ->with('success', "New tag '{$request->label}' created successfully.");
    }

    /**
     * Delete a tag.
     */
    public function destroy(string $code): RedirectResponse
    {
        $defaultTag = $this->billTagService->getDefaultTag();
        $code = strtoupper(trim($code));

        if ($code === strtoupper($defaultTag)) {
            return redirect()->route('admin.tags.index')
                ->withErrors(['delete' => "Cannot delete the active default tag '{$code}'. Please designate another tag as default first."]);
        }

        $deleted = $this->billTagService->deleteTag($code);

        if (!$deleted) {
            return redirect()->route('admin.tags.index')
                ->withErrors(['delete' => "Tag '{$code}' not found."]);
        }

        return redirect()->route('admin.tags.index')
            ->with('success', "Tag '{$code}' deleted successfully.");
    }

    /**
     * Reset tags to factory defaults.
     */
    public function resetToFactory(): RedirectResponse
    {
        $this->billTagService->resetToFactory();

        return redirect()->route('admin.tags.index')
            ->with('success', 'Tags reset to factory defaults.');
    }
}
