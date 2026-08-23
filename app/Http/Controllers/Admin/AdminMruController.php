<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mru;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMruController extends Controller
{
    /**
     * Display a listing of all MRUs (Villages / Areas).
     */
    public function index(Request $request): View
    {
        $search = trim($request->get('search', ''));

        $query = Mru::withCount(['consumerAccounts', 'billRecords']);

        if (!empty($search)) {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('code', 'like', "%{$escaped}%")
                  ->orWhere('name', 'like', "%{$escaped}%")
                  ->orWhere('full_identifier', 'like', "%{$escaped}%");
            });
        }

        $mrus = $query->orderBy('code')->paginate(25);

        return view('admin.mrus.index', compact('mrus', 'search'));
    }

    /**
     * Update MRU details (name, status).
     */
    public function update(Request $request, Mru $mru): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|string|in:active,inactive',
        ]);

        $mru->update([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return back()->with('success', "MRU '{$mru->code}' updated successfully.");
    }
}
