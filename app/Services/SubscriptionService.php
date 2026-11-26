<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Portal;
use App\Models\PortalCharge;
use App\Models\Subscription;
use Illuminate\Support\Carbon;

/**
 * A subscription is a "term" made of monthly installments (term_months, 1 = monthly plan,
 * 12 = yearly plan). Installment k covers days [start + (k-1)*period, start + k*period - 1].
 * Installments are always paid in order, so a skipped month simply stays unpaid and is
 * collected together with the next payment ("due carries over").
 * Access is allowed until ends_at (= end of the last paid installment) + grace days.
 */
class SubscriptionService
{
    /** Give a brand-new portal its one-time trial. Does nothing if it ever had a subscription. */
    public function ensureTrial(Portal $portal): void
    {
        if ($portal->subscriptions()->exists()) {
            return;
        }

        $trial = Package::where('is_trial', true)->where('active', true)->first();

        if ($trial) {
            $this->startTerm($portal, $trial, 1);
        }
    }

    /** New term; starts today, or the day after the currently paid period ends (no lost days). */
    public function startTerm(Portal $portal, Package $package, int $monthsPaid): Subscription
    {
        $latest = $portal->subscriptions()->orderByDesc('ends_at')->first();
        $start = today();

        if ($latest && $latest->ends_at->gte($start)) {
            $start = $latest->ends_at->copy()->addDay();
        }

        return Subscription::create([
            'portal_id' => $portal->id,
            'package_id' => $package->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addDays($package->duration_days * $monthsPaid - 1),
            'grace_days' => $package->grace_days,
            'price' => $package->price,
            'term_months' => $package->term_months,
            'paid_months' => $monthsPaid,
            'period_days' => $package->duration_days,
        ]);
    }

    public function termEnd(Subscription $sub): Carbon
    {
        return $sub->starts_at->copy()->addDays($sub->period_days * $sub->term_months - 1);
    }

    /** How many monthly installments have already started (capped at the term length). */
    public function periodsStarted(Subscription $sub): int
    {
        $today = today();

        if ($today->lt($sub->starts_at)) {
            return 0;
        }

        $n = (int) floor($sub->starts_at->diffInDays($today) / max(1, $sub->period_days)) + 1;

        return min($n, $sub->term_months);
    }

    /** Installments that should already be paid but are not (includes skipped months). */
    public function dueMonths(Subscription $sub): int
    {
        if ($sub->paid_months >= $sub->term_months) {
            return 0;
        }

        if (today()->gt($this->termEnd($sub))) {
            return $sub->term_months - $sub->paid_months;
        }

        return max(0, $this->periodsStarted($sub) - $sub->paid_months);
    }

    /**
     * What paying for $package means right now for this portal.
     *  mode      continue = pay installments of the running term | new = start a new term
     *  due       months that must be paid at minimum
     *  remaining max months that can be paid now (due + advance)
     *  arrears   unpaid months of an old, already finished term (only with mode = new)
     */
    public function quote(Portal $portal, Package $package): array
    {
        $sub = $portal->subscriptions()
            ->where('package_id', $package->id)
            ->orderByDesc('starts_at')
            ->first();

        if ($sub && $sub->paid_months < $sub->term_months) {
            if (today()->lte($this->termEnd($sub))) {
                $due = $this->dueMonths($sub);
                return [
                    'mode' => 'continue', 'subscription' => $sub, 'arrears' => 0,
                    'due' => $due, 'remaining' => $sub->term_months - $sub->paid_months,
                    'price' => (float) $sub->price,
                ];
            }

            // old term fully over but not fully paid: those months are still owed, plus a new term
            $arrears = $sub->term_months - $sub->paid_months;
            return [
                'mode' => 'new', 'subscription' => $sub, 'arrears' => $arrears,
                'due' => $arrears + 1, 'remaining' => $arrears + $package->term_months,
                'price' => (float) $package->price,
            ];
        }

        return [
            'mode' => 'new', 'subscription' => null, 'arrears' => 0,
            'due' => 1, 'remaining' => $package->term_months,
            'price' => (float) $package->price,
        ];
    }

    /** Records that $advance months (on top of the due ones) are paid. Returns [Subscription, months]. */
    public function applyPayment(Portal $portal, Package $package, int $advance): array
    {
        $q = $this->quote($portal, $package);
        $months = $q['due'] + $advance;

        if ($months < 1 || $months > $q['remaining']) {
            throw new \InvalidArgumentException('Invalid number of months.');
        }

        if ($q['mode'] === 'continue') {
            $sub = $q['subscription'];
            $sub->paid_months += $months;
            $sub->ends_at = $sub->starts_at->copy()->addDays($sub->period_days * $sub->paid_months - 1);
            $sub->save();
        } else {
            if ($q['arrears'] > 0) {
                $old = $q['subscription'];
                $old->paid_months = $old->term_months;
                $old->ends_at = $this->termEnd($old);
                $old->save();
            }
            $sub = $this->startTerm($portal, $package, $months - $q['arrears']);
        }

        return [$sub, $months];
    }

    /**
     * state: none | active | grace | expired
     * active  = within paid period
     * grace   = period ended but still inside grace days (portal works, warning shown)
     * expired = grace over, login blocked
     */
    public function info(Portal $portal): array
    {
        $sub = $portal->subscriptions()->with('package')->orderByDesc('ends_at')->first();

        if (!$sub) {
            return ['state' => 'none', 'subscription' => null, 'days_left' => null, 'grace_days_left' => null,
                    'grace_ends_at' => null, 'due_months' => 0, 'window_days' => null];
        }

        $today = today();
        $ends = $sub->ends_at->copy()->startOfDay();
        $graceEnds = $ends->copy()->addDays($sub->grace_days);

        $state = 'expired';
        $daysLeft = null;
        $graceLeft = null;

        if ($today->lte($ends)) {
            $state = 'active';
            $daysLeft = (int) $today->diffInDays($ends) + 1;
        } elseif ($today->lte($graceEnds)) {
            $state = 'grace';
            $graceLeft = (int) $today->diffInDays($graceEnds) + 1;
        }

        return [
            'state' => $state,
            'subscription' => $sub,
            'days_left' => $daysLeft,
            'grace_days_left' => $graceLeft,
            'grace_ends_at' => $graceEnds,
            'due_months' => $this->dueMonths($sub),
            // The "expiring soon" banner and the Grace Period option open when this many days (or fewer) are left.
            'window_days' => max(1, (int) ($sub->package?->grace_window_days ?? config('billing.warn_days'))),
        ];
    }

    public function isUsable(Portal $portal): bool
    {
        return in_array($this->info($portal)['state'], ['active', 'grace'], true);
    }

    /** one_time = unpaid one-time charges (added once); cycle = amount added for every month paid. */
    public function extrasBreakdown(Portal $portal): array
    {
        return [
            'one_time' => (float) $portal->charges()->where('type', 'one_time')->whereNull('paid_at')->sum('amount'),
            'cycle' => (float) $portal->charges()->where('type', 'cycle')->sum('amount'),
        ];
    }

    public function markOneTimeChargesPaid(Portal $portal): void
    {
        PortalCharge::where('portal_id', $portal->id)
            ->where('type', 'one_time')
            ->whereNull('paid_at')
            ->update(['paid_at' => now()]);
    }
}
