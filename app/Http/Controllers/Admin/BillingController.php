<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Portal;
use App\Models\Payment;
use App\Models\PortalCharge;
use App\Services\SubscriptionService;
use App\Support\AdminInertiaPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    /** Manual payment: admin received money, now record the paid month(s). */
    public function recordPayment(Request $request, Portal $portal, SubscriptionService $subs)
    {
        $data = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
            'advance_months' => ['nullable', 'integer', 'min:0', 'max:60'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'in:cash,bkash,nagad,rocket,bank,other'],
            'trx_id' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $package = Package::findOrFail($data['package_id']);
        $advance = (int) ($data['advance_months'] ?? 0);

        if ($package->is_trial && $portal->subscriptions()->whereHas('package', fn ($q) => $q->where('is_trial', true))->exists()) {
            return back()->withInput()->withErrors(['package_id' => 'This portal has already used its one-time trial.']);
        }

        $quote = $subs->quote($portal, $package);

        if ($advance > $quote['remaining'] - $quote['due']) {
            return back()->withInput()->withErrors(['advance_months' => 'Too many advance months. At most ' . ($quote['remaining'] - $quote['due']) . ' allowed for this plan.']);
        }

        if ($quote['due'] + $advance < 1) {
            return back()->withInput()->withErrors(['advance_months' => 'Nothing is due right now. Enter how many months are being paid in advance.']);
        }

        $months = 0;

        DB::transaction(function () use ($request, $portal, $package, $data, $subs, $advance, &$months) {
            [$subscription, $months] = $subs->applyPayment($portal, $package, $advance);

            Payment::create([
                'portal_id' => $portal->id,
                'package_id' => $package->id,
                'subscription_id' => $subscription->id,
                'amount' => $data['amount'],
                'months' => $months,
                'method' => $data['method'],
                'trx_id' => $data['trx_id'] ?? null,
                'note' => $data['note'] ?? null,
                'paid_at' => now(),
                'recorded_by' => $request->session()->get('admin_id'),
            ]);

            $subs->markOneTimeChargesPaid($portal);
        });

        return back()->with('success', "Payment recorded: {$months} month(s) of {$package->name}.");
    }

    public function addCharge(Request $request, Portal $portal)
    {
        $data = $request->validate([
            'purpose' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:1'],
            'type' => ['required', 'in:one_time,cycle'],
        ]);

        $portal->charges()->create($data);

        return back()->with('success', 'Additional charge added. It will be included in the next payment.');
    }

    public function removeCharge(Portal $portal, PortalCharge $charge)
    {
        abort_unless($charge->portal_id === $portal->id, 404);
        $charge->delete();

        return back()->with('success', 'Charge removed.');
    }

    // ---------- Package settings ----------

    public function packages()
    {
        return AdminInertiaPage::render('Admin/Packages/Index', [
            'title' => 'Packages · Admin', 'pageTitle' => 'Packages', 'pageSubtitle' => null,
            'packages' => Package::orderBy('is_trial', 'desc')->orderBy('price')->get(),
            'packageStoreUrl' => route('admin.packages.store'),
        ]);
    }

    public function storePackage(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'term_months' => ['required', 'integer', 'min:1', 'max:60'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:365'],
            'grace_window_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $slug = Str::slug($data['name']) ?: 'package';
        while (Package::where('slug', $slug)->exists()) {
            $slug .= '-' . Str::lower(Str::random(3));
        }

        Package::create($data + ['slug' => $slug, 'is_trial' => false, 'active' => true]);

        return back()->with('success', "Package \"{$data['name']}\" added.");
    }

    public function updatePackage(Request $request, Package $package)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'term_months' => ['sometimes', 'integer', 'min:1', 'max:60'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:365'],
            'grace_window_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);
        $data['active'] = $request->boolean('active');

        $package->update($data);

        return back()->with('success', "{$package->name} updated. Running subscriptions keep their old terms (the grace option window applies right away).");
    }
}
