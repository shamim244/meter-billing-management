<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CouponCode;
use App\Models\Plan;
use App\Services\Coupon\CouponService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCouponController extends Controller
{
    public function __construct(
        protected CouponService $couponService
    ) {}

    /**
     * Display a listing of all coupon campaigns and metrics.
     */
    public function index(Request $request): View
    {
        $search = trim($request->get('search', ''));
        $typeFilter = $request->get('type', 'all');
        $statusFilter = $request->get('status', 'all');

        $query = CouponCode::with(['restrictedPlan', 'creator', 'slabs'])
            ->withCount('redemptions');

        if (!empty($search)) {
            $escaped = addcslashes($search, '%_\\');
            $query->where('code', 'like', "%{$escaped}%");
        }

        if ($typeFilter !== 'all') {
            $query->where('type', $typeFilter);
        }

        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $coupons = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'total_coupons' => CouponCode::count(),
            'active_campaigns' => CouponCode::where('is_active', true)->count(),
            'total_redemptions' => \App\Models\CouponRedemption::count(),
            'total_discount_given' => (float)\App\Models\CouponRedemption::sum('discount_or_bonus_amount'),
        ];

        return view('admin.coupons.index', compact('coupons', 'search', 'typeFilter', 'statusFilter', 'stats'));
    }

    /**
     * Show the form for creating a new coupon code.
     */
    public function create(): View
    {
        $plans = Plan::where('is_active', true)->get();
        return view('admin.coupons.create', compact('plans'));
    }

    /**
     * Store a newly created coupon code in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:coupon_codes,code',
            'type' => 'required|string|in:subscription_discount,topup_bonus',
            'discount_kind' => 'required_if:type,subscription_discount|nullable|string|in:percentage,flat',
            'discount_value' => 'required_if:type,subscription_discount|nullable|numeric|min:0.01',
            'plan_restriction_id' => 'nullable|exists:plans,id',
            'minimum_amount' => 'nullable|numeric|min:0',
            'usage_limit_per_user' => 'required|integer|min:1|max:1000',
            'usage_limit_total' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
            'slabs' => 'required_if:type,topup_bonus|nullable|array',
            'slabs.*.min_amount' => 'required_with:slabs|numeric|min:0',
            'slabs.*.max_amount' => 'nullable|numeric|min:0',
            'slabs.*.bonus_percent' => 'required_with:slabs|numeric|min:0.01|max:100',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['created_by_admin_id'] = auth()->id();

        $coupon = $this->couponService->createCoupon($data);

        return redirect()->route('admin.coupons.index')
            ->with('success', "Coupon '{$coupon->code}' created successfully!");
    }

    /**
     * Display a specific coupon's details, analytics, and redemption logs.
     */
    public function show(CouponCode $coupon): View
    {
        $coupon->load(['restrictedPlan', 'slabs', 'creator']);
        $analytics = $this->couponService->getAnalytics($coupon);

        $redemptions = $coupon->redemptions()
            ->with('user')
            ->orderBy('redeemed_at', 'desc')
            ->paginate(25);

        return view('admin.coupons.show', compact('coupon', 'analytics', 'redemptions'));
    }

    /**
     * Show the form for editing an existing coupon.
     */
    public function edit(CouponCode $coupon): View
    {
        $coupon->load(['restrictedPlan', 'slabs']);
        $plans = Plan::where('is_active', true)->get();

        return view('admin.coupons.edit', compact('coupon', 'plans'));
    }

    /**
     * Update an existing coupon.
     */
    public function update(Request $request, CouponCode $coupon): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:coupon_codes,code,' . $coupon->id,
            'discount_kind' => 'required_if:type,subscription_discount|nullable|string|in:percentage,flat',
            'discount_value' => 'required_if:type,subscription_discount|nullable|numeric|min:0.01',
            'plan_restriction_id' => 'nullable|exists:plans,id',
            'minimum_amount' => 'nullable|numeric|min:0',
            'usage_limit_per_user' => 'required|integer|min:1|max:1000',
            'usage_limit_total' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
            'slabs' => 'nullable|array',
            'slabs.*.min_amount' => 'required_with:slabs|numeric|min:0',
            'slabs.*.max_amount' => 'nullable|numeric|min:0',
            'slabs.*.bonus_percent' => 'required_with:slabs|numeric|min:0.01|max:100',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->boolean('is_active', false);

        $this->couponService->updateCoupon($coupon, $data);

        return redirect()->route('admin.coupons.show', $coupon)
            ->with('success', "Coupon '{$coupon->code}' updated successfully!");
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggle(CouponCode $coupon): RedirectResponse
    {
        $newState = $this->couponService->toggleActive($coupon);
        $statusStr = $newState ? 'activated' : 'deactivated';

        return back()->with('success', "Coupon '{$coupon->code}' {$statusStr} successfully.");
    }

    /**
     * Bulk deactivate selected coupons.
     */
    public function bulkDeactivate(Request $request): RedirectResponse
    {
        $request->validate([
            'coupon_ids' => 'required|array|min:1',
            'coupon_ids.*' => 'integer|exists:coupon_codes,id',
        ]);

        $count = CouponCode::whereIn('id', $request->coupon_ids)->update(['is_active' => false]);

        return back()->with('success', "Deactivated {$count} coupon code(s) successfully.");
    }

    /**
     * Remove the specified coupon from storage.
     */
    public function destroy(CouponCode $coupon): RedirectResponse
    {
        $code = $coupon->code;
        $this->couponService->deleteCoupon($coupon);

        return redirect()->route('admin.coupons.index')
            ->with('success', "Coupon '{$code}' was successfully deleted.");
    }
}
