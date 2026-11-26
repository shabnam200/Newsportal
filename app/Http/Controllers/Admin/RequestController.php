<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GraceRequest;
use App\Models\Payment;
use App\Models\PurchaseRequest;
use App\Services\SubscriptionService;
use App\Support\AdminInertiaPage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Admin side of the portal "grace period" and "renew / purchase" requests. */
class RequestController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $renewalStatus = in_array($request->query('renewal_status'), ['approved', 'rejected'], true)
            ? $request->query('renewal_status')
            : '';
        $graceStatus = in_array($request->query('grace_status'), ['approved', 'rejected'], true)
            ? $request->query('grace_status')
            : '';
        $filterByPortal = static function ($query) use ($q) {
            $query->where(function ($portal) use ($q) {
                $portal->where('portal_name', 'like', "%{$q}%")
                    ->orWhere('editor_mobile', 'like', "%{$q}%")
                    ->orWhere('editor_email', 'like', "%{$q}%");
            });
        };

        $pendingPurchases = PurchaseRequest::with(['portal', 'package'])
            ->where('status', 'pending')
            ->when($q !== '', fn ($query) => $query->whereHas('portal', $filterByPortal))
            ->oldest()
            ->paginate(8, ['*'], 'renewals_page')
            ->withQueryString();
        $pendingGraces = GraceRequest::with('portal')
            ->where('status', 'pending')
            ->when($q !== '', fn ($query) => $query->whereHas('portal', $filterByPortal))
            ->oldest()
            ->paginate(8, ['*'], 'grace_page')
            ->withQueryString();
        $pendingGraces->through(function (GraceRequest $request) {
            $subscription = $request->portal->subscriptions()->orderByDesc('ends_at')->first();
            $request->setAttribute('current_subscription', $subscription ? [
                'ends_at' => $subscription->ends_at?->format('d M Y'),
                'grace_days' => $subscription->grace_days,
                'grace_until' => $subscription->ends_at?->copy()->addDays($subscription->grace_days)->format('d M Y'),
            ] : null);

            return $request;
        });

        return AdminInertiaPage::render('Admin/Requests/Index', [
            'title' => 'Requests · Admin', 'pageTitle' => 'Requests',
            'pageSubtitle' => 'Grace period and renewal requests sent by portals',
            'q' => $q,
            'renewalStatus' => $renewalStatus,
            'graceStatus' => $graceStatus,
            'pendingPurchases' => $pendingPurchases,
            'pendingGraces' => $pendingGraces,
            'historyPurchases' => PurchaseRequest::with(['portal', 'package'])
                ->whereIn('status', ['approved', 'rejected'])
                ->when($renewalStatus !== '', fn ($query) => $query->where('status', $renewalStatus))
                ->latest('decided_at')
                ->limit(20)
                ->get(),
            'historyGraces' => GraceRequest::with('portal')
                ->whereIn('status', ['approved', 'rejected'])
                ->when($graceStatus !== '', fn ($query) => $query->where('status', $graceStatus))
                ->latest('decided_at')
                ->limit(20)
                ->get(),
            'methods' => config('billing.methods'),
            'requestUrls' => [
                'index' => route('admin.requests.index'),
                'purchaseApproveBase' => url('/admin/requests/purchase'),
                'graceApproveBase' => url('/admin/requests/grace'),
                'portalBase' => route('admin.portals.index'),
            ],
        ]);
    }

    // ---------- Grace period ----------

    public function approveGrace(Request $request, GraceRequest $graceRequest, SubscriptionService $subs)
    {
        if (!$graceRequest->isPending()) {
            return back()->withErrors(['request' => 'This request was already handled.']);
        }

        $data = $request->validate([
            'until_date' => ['required', 'date_format:Y-m-d'],
            'admin_note' => ['nullable', 'string', 'max:255'],
        ]);

        $sub = $graceRequest->portal->subscriptions()->orderByDesc('ends_at')->first();

        if (!$sub) {
            return back()->withErrors(['request' => 'This portal has no subscription to extend.']);
        }

        $until = Carbon::parse($data['until_date'])->startOfDay();
        $ends = $sub->ends_at->copy()->startOfDay();

        if ($until->lt(today())) {
            return back()->withErrors(['request' => 'The grace end date is already in the past. Pick today or a later date.']);
        }

        if ($until->lte($ends)) {
            return back()->withErrors(['request' => 'The grace end date must be after the paid period ends (' . $ends->format('d M Y') . ').']);
        }

        // Grace is stored as "days after ends_at"; never shrink a grace that is already longer.
        $days = (int) round($ends->diffInDays($until));

        DB::transaction(function () use ($request, $graceRequest, $sub, $days, $until, $data) {
            if ($days > $sub->grace_days) {
                $sub->grace_days = $days;
                $sub->save();
            }

            $graceRequest->update([
                'status' => 'approved',
                'approved_until' => $until,
                'admin_note' => $data['admin_note'] ?? null,
                'decided_at' => now(),
                'decided_by' => $request->session()->get('admin_id'),
            ]);
        });

        return back()->with('success', "Grace period approved for {$graceRequest->portal->portal_name} until " . $until->format('d M Y') . '.');
    }

    public function rejectGrace(Request $request, GraceRequest $graceRequest)
    {
        if (!$graceRequest->isPending()) {
            return back()->withErrors(['request' => 'This request was already handled.']);
        }

        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:255']]);

        $graceRequest->update([
            'status' => 'rejected',
            'admin_note' => $data['admin_note'] ?? null,
            'decided_at' => now(),
            'decided_by' => $request->session()->get('admin_id'),
        ]);

        return back()->with('success', 'Grace period request rejected.');
    }

    // ---------- Renew / purchase ----------

    public function approvePurchase(Request $request, PurchaseRequest $purchaseRequest, SubscriptionService $subs)
    {
        if (!$purchaseRequest->isPending()) {
            return back()->withErrors(['request' => 'This request was already handled.']);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'admin_note' => ['nullable', 'string', 'max:255'],
        ]);

        $portal = $purchaseRequest->portal;
        $package = $purchaseRequest->package;

        // The portal's situation may have changed since it sent the request: check again.
        $quote = $subs->quote($portal, $package);
        $advance = $purchaseRequest->months - $quote['due'];

        if ($advance < 0 || $purchaseRequest->months > $quote['remaining']) {
            return back()->withErrors(['request' => "The plan state of {$portal->portal_name} changed after this request was sent "
                . "(now due: {$quote['due']}, max {$quote['remaining']} month(s)). Reject it, or record the payment from the portal's Billing tab."]);
        }

        DB::transaction(function () use ($request, $purchaseRequest, $portal, $package, $subs, $advance, $data) {
            [$subscription, $months] = $subs->applyPayment($portal, $package, $advance);

            $payment = Payment::create([
                'portal_id' => $portal->id,
                'package_id' => $package->id,
                'subscription_id' => $subscription->id,
                'amount' => $data['amount'],
                'months' => $months,
                'method' => $purchaseRequest->method,
                'trx_id' => $purchaseRequest->trx_id,
                'note' => trim('Portal request #' . $purchaseRequest->id . ($purchaseRequest->note ? ' – ' . $purchaseRequest->note : '')),
                'paid_at' => now(),
                'recorded_by' => $request->session()->get('admin_id'),
            ]);

            $subs->markOneTimeChargesPaid($portal);

            $purchaseRequest->update([
                'status' => 'approved',
                'payment_id' => $payment->id,
                'admin_note' => $data['admin_note'] ?? null,
                'decided_at' => now(),
                'decided_by' => $request->session()->get('admin_id'),
            ]);
        });

        return back()->with('success', "Approved: {$purchaseRequest->months} month(s) of {$package->name} added for {$portal->portal_name}.");
    }

    public function rejectPurchase(Request $request, PurchaseRequest $purchaseRequest)
    {
        if (!$purchaseRequest->isPending()) {
            return back()->withErrors(['request' => 'This request was already handled.']);
        }

        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:255']]);

        $purchaseRequest->update([
            'status' => 'rejected',
            'admin_note' => $data['admin_note'] ?? null,
            'decided_at' => now(),
            'decided_by' => $request->session()->get('admin_id'),
        ]);

        return back()->with('success', 'Renewal request rejected.');
    }
}
