<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-navigation-type="default" data-navbar-horizontal-shape="default" class="{{ in_array(($module ?? null), ['categories', 'news-entry', 'news-management'], true) ? 'portal-module-page' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Phoenix Project Management</title>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('media/assets/img/favicons/apple-touch-icon.png?size=180') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('media/assets/img/favicons/favicon-32x32.png?size=32') }}">
    {{-- Show a light shell (sidebar + header + content blocks) at once and reveal the real page in one go,
         instead of content first and the sidebar / header popping in later. --}}
    <script>
      document.documentElement.classList.add('app-booting');
      (function () {
        var tasks = [], obs = null, queued = false;
        function run() {
          queued = false;
          tasks = tasks.filter(function (t) { try { return !t(); } catch (e) { return false; } });
          if (!tasks.length && obs) { obs.disconnect(); obs = null; }
        }
        // ONE shared observer for every "place this into the Phoenix page once Vue has drawn it" job.
        window.portalWhenReady = function (fn) {
          try { if (fn()) return; } catch (e) { return; }
          tasks.push(fn);
          if (obs) return;
          obs = new MutationObserver(function () { if (!queued) { queued = true; requestAnimationFrame(run); } });
          obs.observe(document.documentElement, { childList: true, subtree: true });
        };
        function finish() {
          document.documentElement.classList.remove('app-booting');
          var sk = document.getElementById('app-skeleton');
          if (sk) sk.remove();
        }
        window.portalWhenReady(function () {
          var vertical = document.documentElement.getAttribute('data-navigation-type') === 'default' || document.documentElement.getAttribute('data-navigation-type') === 'vertical';
          if (!document.querySelector('#navbarDefault .navbar-logo') || !document.querySelector('#app .content')) return false;
          if (vertical && !document.querySelector('#navbarVerticalNav')) return false;
          finish();
          return true;
        });
        window.setTimeout(finish, 4000); // never leave the page covered if something fails
      })();
    </script>
    <style>
      html.app-booting #app { opacity: 0; }
      #app-skeleton { position: fixed; inset: 0; z-index: 2000; display: flex; background: var(--phoenix-body-bg, #fff); pointer-events: none; }
      html:not(.app-booting) #app-skeleton { display: none; }
      #app-skeleton .sk-side { flex: none; width: var(--phoenix-navbar-vertical-width, 15.875rem); padding: 1.25rem 1rem; border-right: 1px solid var(--phoenix-border-color, #e3e6ed); background: var(--phoenix-body-bg, #fff); }
      #app-skeleton .sk-main { flex: 1; min-width: 0; }
      #app-skeleton .sk-top { height: var(--phoenix-navbar-top-height, 3.5rem); display: flex; align-items: center; justify-content: space-between; padding: 0 1.5rem; border-bottom: 1px solid var(--phoenix-border-color, #e3e6ed); }
      #app-skeleton .sk-body { padding: 1.5rem; display: grid; gap: 1rem; grid-template-columns: repeat(3, 1fr); }
      #app-skeleton .sk-bar, #app-skeleton .sk-card { background: var(--phoenix-secondary-bg, #eff2f6); border-radius: .5rem; animation: sk-pulse 1.2s ease-in-out infinite; }
      #app-skeleton .sk-bar { height: .75rem; margin-bottom: .9rem; }
      #app-skeleton .sk-card { height: 7rem; }
      #app-skeleton .sk-card.wide { grid-column: 1 / -1; height: 14rem; }
      @keyframes sk-pulse { 50% { opacity: .45; } }
      @media (max-width: 991.98px) { #app-skeleton .sk-side { display: none; } #app-skeleton .sk-body { grid-template-columns: 1fr; } }
      @media (prefers-reduced-motion: reduce) { #app-skeleton .sk-bar, #app-skeleton .sk-card { animation: none; } }
    </style>
    <script src="{{ asset('vendors/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <link href="{{ asset('vendors/choices/choices.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/dhtmlx-gantt/dhtmlxgantt.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&amp;display=swap" rel="stylesheet" media="print" onload="this.media='all'"><noscript><link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&amp;display=swap" rel="stylesheet"></noscript>
    <link href="{{ asset('vendors/simplebar/simplebar.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/theme-rtl.min.css') }}" rel="stylesheet" id="style-rtl">
    <link href="{{ asset('assets/css/theme.min.css') }}" rel="stylesheet" id="style-default">
    <link href="{{ asset('assets/css/user-rtl.min.css') }}" rel="stylesheet" id="user-style-rtl">
    <link href="{{ asset('assets/css/user.min.css') }}" rel="stylesheet" id="user-style-default">
    <script>
      var phoenixIsRTL = window.config.config.phoenixIsRTL;
      if (phoenixIsRTL) {
        document.getElementById('style-default').disabled = true;
        document.getElementById('user-style-default').disabled = true;
        document.documentElement.setAttribute('dir', 'rtl');
      } else {
        document.getElementById('style-rtl').disabled = true;
        document.getElementById('user-style-rtl').disabled = true;
      }
    </script>
    <script>window.portalAssetBase = @json(rtrim(asset(''), '/'));</script>
    @vite('v1.23.0/src/project-management.js')
    @vite('resources/js/app.js')
    @if (($inertiaPage ?? false) || in_array(($module ?? null), ['categories', 'news-entry'], true))
        @inertiaHead
    @endif
</head>
<body>
<div id="app-skeleton" aria-hidden="true">
    <div class="sk-side"><div class="sk-bar" style="width:55%;height:1.5rem;margin-bottom:2rem"></div><div class="sk-bar" style="width:80%"></div><div class="sk-bar" style="width:65%"></div><div class="sk-bar" style="width:75%"></div><div class="sk-bar" style="width:60%"></div><div class="sk-bar" style="width:70%"></div></div>
    <div class="sk-main"><div class="sk-top"><div class="sk-bar" style="width:12rem;margin:0"></div><div class="sk-bar" style="width:8rem;margin:0"></div></div><div class="sk-body"><div class="sk-card"></div><div class="sk-card"></div><div class="sk-card"></div><div class="sk-card wide"></div></div></div>
</div>
@php
    $money = fn ($n) => '৳' . rtrim(rtrim(number_format($n, 2, '.', ','), '0'), '.');
    $b = $billing ?? null;
    $methods = config('billing.methods');
    $reopen = old('_form');
    $isTrialSubscription = (bool) data_get($subscriptionInfo ?? [], 'subscription.package.is_trial', true);
    $subscriptionAction = $isTrialSubscription ? 'Purchase' : 'Renew';
@endphp
<style>
    /* Keep the Phoenix shell, but hide its demo dashboard while an Inertia module is active. */
    html.portal-module-page #app .content > *:not(.footer):not(.cat-module):not(.news-module):not(#portal-inertia):not(#subscription-banner) { display: none !important; }
    /* Keep Phoenix's own footer behaviour (absolute at the bottom of .content, so it follows the sidebar offset
       on desktop and spans the full width on phones). A fixed footer with a hard-coded left offset covered the
       page content and broke the layout on small screens. */
    html.portal-module-page #app .content { min-width: 0; }
    html.portal-module-page #app .content > .cat-module, html.portal-module-page #app .content > .news-module, html.portal-module-page #app .content > #portal-inertia { min-width: 0; max-width: 100%; }
</style>
<style>
    .sub-toast { position: fixed; top: 5rem; left: 50%; transform: translateX(-50%); z-index: 2100; width: min(28rem, 92vw); padding: .5rem 2.5rem .5rem .75rem; font-size: .85rem; }
    .portal-support-notice { position: fixed; top: 5rem; right: 1rem; z-index: 2200; width: min(36rem, calc(100vw - 2rem)); }
    .plan-card { cursor: pointer; display: block; border: 1px solid var(--phoenix-border-color); border-radius: .5rem; padding: .75rem 1rem; margin-bottom: .6rem; }
    .plan-card:hover { border-color: var(--phoenix-primary); }
    .plan-card input:checked ~ .plan-body { color: var(--phoenix-primary); }
    .plan-card:has(input:checked) { border-color: var(--phoenix-primary); background: var(--phoenix-primary-bg-subtle); }
    .plan-price { font-size: 1.15rem; font-weight: 800; }
    .plan-price small { font-weight: 400; font-size: .8rem; color: var(--phoenix-tertiary-color); }

    /* ---- "expiring soon" banner ---- */
    .sub-banner { position: relative; flex-wrap: wrap; padding: 1rem 3rem 1rem 1.1rem; border: 1px solid var(--sb-border); border-left: 4px solid var(--sb-accent); border-radius: .75rem; background: var(--sb-bg); box-shadow: 0 1px 2px rgba(0, 0, 0, .04); }
    .sub-banner-warning { --sb-accent: var(--phoenix-warning, #e5780b); --sb-bg: var(--phoenix-warning-bg-subtle, #fff4e0); --sb-border: var(--phoenix-warning-border-subtle, #ffd9a8); }
    .sub-banner-danger { --sb-accent: var(--phoenix-danger, #d9322f); --sb-bg: var(--phoenix-danger-bg-subtle, #fde8e8); --sb-border: var(--phoenix-danger-border-subtle, #f7c1c0); }
    .sub-banner-icon { flex: none; width: 2.6rem; height: 2.6rem; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 1.05rem; color: var(--sb-accent); background: var(--phoenix-card-bg, #fff); box-shadow: 0 0 0 1px var(--sb-border); }
    .sub-banner-body { flex: 1 1 18rem; min-width: 0; }
    .sub-banner-title { font-weight: 800; line-height: 1.3; color: var(--phoenix-emphasis-color, inherit); }
    .sub-banner-text { margin-top: .1rem; font-size: .875rem; color: var(--phoenix-body-color); }
    .sub-banner-meta { display: inline-block; margin-left: .35rem; color: var(--phoenix-tertiary-color); }
    .sub-banner-bar { max-width: 22rem; height: 4px; margin-top: .6rem; overflow: hidden; border-radius: 4px; background: color-mix(in srgb, var(--sb-accent) 20%, transparent); }
    .sub-banner-bar span { display: block; height: 100%; border-radius: 4px; background: var(--sb-accent); }
    .sub-banner-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
    .sub-banner-close { position: absolute; top: .65rem; right: .65rem; width: 1.8rem; height: 1.8rem; padding: 0; border: 0; border-radius: 50%; background: transparent; color: var(--phoenix-tertiary-color); font-size: .95rem; line-height: 1; }
    .sub-banner-close:hover { background: color-mix(in srgb, var(--sb-accent) 15%, transparent); color: var(--phoenix-emphasis-color, inherit); }
    @media (max-width: 575.98px) {
        .sub-banner { padding-right: 2.75rem; }
        .sub-banner-actions { width: 100%; }
        .sub-banner-actions .btn { flex: 1 1 auto; }
    }

    /* ---- grace period popup ---- */
    .grace-icon { flex: none; width: 2.75rem; height: 2.75rem; display: flex; align-items: center; justify-content: center; border-radius: .75rem; font-size: 1.1rem; color: var(--phoenix-primary); background: var(--phoenix-primary-bg-subtle); }
    .grace-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(7.5rem, 1fr)); gap: .5rem; }
    .grace-tile { padding: .55rem .75rem; border: 1px solid var(--phoenix-border-color); border-radius: .6rem; }
    .grace-tile small { display: block; font-size: .68rem; letter-spacing: .04em; text-transform: uppercase; color: var(--phoenix-tertiary-color); }
    .grace-tile strong { font-size: .875rem; color: var(--phoenix-emphasis-color, inherit); }
    .grace-chip { padding: .2rem .8rem; border: 1px solid var(--phoenix-border-color); border-radius: 999px; background: var(--phoenix-card-bg, transparent); color: var(--phoenix-body-color); font-size: .78rem; line-height: 1.5; cursor: pointer; }
    .grace-chip:hover, .grace-chip.active { border-color: var(--phoenix-primary); background: var(--phoenix-primary-bg-subtle); color: var(--phoenix-primary); }
    .grace-until-note { min-height: 1.25rem; margin-top: .4rem; font-size: .8rem; font-weight: 600; color: var(--phoenix-primary); }
    .grace-status { display: flex; gap: .75rem; padding: .9rem 1rem; border: 1px solid var(--gs-border); border-radius: .75rem; background: var(--gs-bg); }
    .grace-status-icon { flex: none; margin-top: .15rem; color: var(--gs-accent); }
    .grace-status-info { --gs-accent: var(--phoenix-info, #0097eb); --gs-bg: var(--phoenix-info-bg-subtle, #e5f4fd); --gs-border: var(--phoenix-info-border-subtle, #b3e0f9); }
    .grace-status-warning { --gs-accent: var(--phoenix-warning, #e5780b); --gs-bg: var(--phoenix-warning-bg-subtle, #fff4e0); --gs-border: var(--phoenix-warning-border-subtle, #ffd9a8); }
    .grace-status-success { --gs-accent: var(--phoenix-success, #25b003); --gs-bg: var(--phoenix-success-bg-subtle, #e8f7e4); --gs-border: var(--phoenix-success-border-subtle, #b9e5ad); }
    .grace-status-danger { --gs-accent: var(--phoenix-danger, #d9322f); --gs-bg: var(--phoenix-danger-bg-subtle, #fde8e8); --gs-border: var(--phoenix-danger-border-subtle, #f7c1c0); }
</style>

{{-- These two blocks are moved into the Phoenix top navbar by the script at the bottom. --}}
<div id="sub-nav-store" style="display:none"
     data-portal-name="{{ $currentPortal->portal_name ?? '' }}"
     data-portal-sub="{{ trim(($currentPortal->editor_name ?? '') . ' ' . (($currentPortal->editor_name ?? '') ? '· ' : '') . ($currentPortal->editor_mobile ?? '')) }}">
    @php
        $subState = $subscriptionInfo['state'] ?? null;
        $subDays = $subscriptionInfo['days_left'] ?? null;
        $subTone = $subState === 'grace' ? 'danger' : (($subDays !== null && $subDays <= (int) ($subscriptionInfo['window_days'] ?? config('billing.warn_days'))) ? 'warning' : 'primary');
    @endphp
    <div id="sub-nav-left" class="d-none d-lg-flex align-items-center flex-nowrap ms-1">
        @if ($subState === 'grace')
            <span class="badge badge-phoenix badge-phoenix-danger fs-10 text-nowrap py-2 px-3 rounded-pill">
                <span class="fa-regular fa-clock me-1"></span>Overdue · {{ $subscriptionInfo['grace_days_left'] }} day(s) of grace left
            </span>
        @elseif ($subState === 'active')
            <span class="badge badge-phoenix badge-phoenix-{{ $subTone }} fs-10 text-nowrap py-2 px-3 rounded-pill">
                <span class="fa-regular fa-clock me-1"></span>{{ $subscriptionInfo['subscription']->package->name }} · {{ $subDays }} day(s) left
            </span>
        @endif
    </div>

    <div id="sub-nav-right" class="d-flex align-items-center gap-2 me-2">
        @if (!empty($adminSupportSession))
            <span class="badge badge-phoenix badge-phoenix-warning text-nowrap d-none d-sm-inline-flex"
                  title="Support for {{ $currentPortal->portal_name }} by {{ $supportAdmin->name }}. Billing requests require approval and are audited.">
                <span class="fa-solid fa-headset me-1" aria-hidden="true"></span>Support · Write
            </span>
        @endif
        <button type="button" class="btn btn-sm btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#renewModal">
            <span class="fa-regular fa-credit-card me-1" aria-hidden="true"></span>{{ $isTrialSubscription ? 'Purchase' : 'Renew now' }}
        </button>
    </div>

    <form id="sub-logout-form" method="POST" action="{{ ($adminSupport ?? false) ? route('admin.support.end', $adminSupportSession) : route('portal.logout') }}">@csrf</form>
</div>

@if (session('billing_success'))
    <div id="billing-success-toast" class="sub-toast auto-dismiss-toast alert alert-subtle-success alert-dismissible fade show" role="alert">
        {{ session('billing_success') }}
        <button type="button" class="btn-close" aria-label="Close" onclick="this.parentElement.remove()"></button>
    </div>
@endif

@if (session('warning'))
    <div id="session-warning-toast" class="sub-toast auto-dismiss-toast alert alert-subtle-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}
        <button type="button" class="btn-close" aria-label="Close" onclick="this.parentElement.remove()"></button>
    </div>
@endif

@if (session('billing_success') || session('warning'))
    <script>
      (function () {
        document.querySelectorAll('.auto-dismiss-toast').forEach(function (toast) {
          window.setTimeout(function () {
            if (!toast.isConnected) return;
            toast.classList.remove('show');
            window.setTimeout(function () { toast.remove(); }, 200);
          }, 5000);
        });
      })();
    </script>
@endif

@if (!empty($adminSupportSession))
    <span class="visually-hidden" role="status">
        Write-enabled admin support session for {{ $currentPortal->portal_name }} opened by {{ $supportAdmin->name }}. Purchase and grace-period requests require approval and are audited.
    </span>
@endif



@if ($b && $b['banner']['show'] && !$b['pendingGrace'])
    @php
        $bInfo = $b['info'];
        $bSub = $bInfo['subscription'];
        $bGrace = $b['banner']['is_grace'];
        $bTone = $bGrace ? 'danger' : 'warning';
        // how much of the warning period is still left (the bar shrinks day by day)
        $bTotal = max(1, $bGrace ? (int) $bSub->grace_days : (int) $bInfo['window_days']);
        $bLeft = (int) ($bGrace ? $bInfo['grace_days_left'] : $bInfo['days_left']);
        $bPct = max(5, min(100, (int) round($bLeft / $bTotal * 100)));
    @endphp
    <div id="subscription-banner" class="sub-banner sub-banner-{{ $bTone }} align-items-center gap-3 mb-4 d-none" role="alert">
        <div class="sub-banner-icon"><span class="fa-solid fa-{{ $bGrace ? 'circle-exclamation' : 'hourglass-half' }}"></span></div>

        <div class="sub-banner-body">
            <div class="sub-banner-title">{{ $bGrace ? 'Subscription expired' : 'Subscription expiring soon' }}</div>
            <div class="sub-banner-text">
                {{ $b['banner']['message'] }}
                <span class="sub-banner-meta">
                    {{ $bSub->package->name }} &middot;
                    {{ $bGrace ? 'access until ' . $bInfo['grace_ends_at']->format('d M Y') : 'ends ' . $bSub->ends_at->format('d M Y') }}
                </span>
            </div>
            <div class="sub-banner-bar" aria-hidden="true"><span style="width: {{ $bPct }}%"></span></div>
        </div>

        <div class="sub-banner-actions">
            @if ($b['grace']['can_request'] && !$b['pendingGrace'])
                <button type="button" class="btn btn-sm btn-phoenix-{{ $bTone }} px-3 text-nowrap" data-bs-toggle="modal" data-bs-target="#graceModal">
                    <span class="fa-regular fa-clock me-1"></span>Request grace period
                </button>
            @endif
        </div>

        <button type="button" class="sub-banner-close" id="subscription-banner-close" aria-label="Dismiss" title="Dismiss">
            <span class="fa-solid fa-xmark"></span>
        </button>
    </div>
@endif

<div id="app"></div>
@if (($inertiaPage ?? false) || in_array(($module ?? null), ['categories', 'news-entry'], true))
    @inertia('portal-inertia')
@endif

@if ($b)
{{-- ============ Grace period popup ============ --}}
<div class="modal fade" id="graceModal" tabindex="-1" aria-labelledby="graceModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ ($adminSupport ?? false) ? route('admin.support.grace.request', $adminSupportSession) : route('portal.grace.request') }}">
                @csrf
                <input type="hidden" name="_form" value="grace">
                <div class="modal-header border-0 pb-0 align-items-start">
                    <div class="d-flex align-items-center gap-3">
                        <div class="grace-icon"><span class="fa-regular fa-clock"></span></div>
                        <div>
                            <h5 class="modal-title mb-0" id="graceModalTitle">Request a grace period</h5>
                            <p class="fs-9 text-body-tertiary mb-0">Keep using the portal a little longer while you arrange payment.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if ($b['pendingGrace'])
                        <div class="grace-status grace-status-info">
                            <span class="fa-solid fa-hourglass-half grace-status-icon"></span>
                            <div>
                                <div class="fw-bold">Waiting for approval</div>
                                <div class="fs-9">You asked to keep access until <strong>{{ $b['pendingGrace']->until_date->format('d F Y') }}</strong>. You can keep using the portal as soon as the administrator approves it.</div>
                            </div>
                        </div>
                    @elseif (!$b['grace']['can_request'])
                        <div class="grace-status grace-status-warning">
                            <span class="fa-solid fa-circle-exclamation grace-status-icon"></span>
                            <div>
                                <div class="fw-bold">No more grace available</div>
                                <div class="fs-9">The maximum grace period is already in place for your subscription. Please renew to keep using the portal.</div>
                            </div>
                        </div>
                    @else
                        @php
                            $gMin = $b['grace']['min'];
                            $gMax = $b['grace']['max'];
                            $gBase = $gMin->copy()->subDay(); // last day you can use the portal today
                            $gChips = [];
                            foreach ([3, 7, 15] as $n) {
                                $d = $gBase->copy()->addDays($n);
                                if ($d->lte($gMax)) {
                                    $gChips[$d->toDateString()] = "+{$n} days";
                                }
                            }
                            if (!isset($gChips[$gMax->toDateString()])) {
                                $gChips[$gMax->toDateString()] = 'Max';
                            }
                        @endphp

                        @if ($b['lastGrace'])
                            <div class="grace-status grace-status-{{ $b['lastGrace']->status === 'approved' ? 'success' : 'danger' }} mb-3">
                                <span class="fa-solid fa-{{ $b['lastGrace']->status === 'approved' ? 'circle-check' : 'circle-xmark' }} grace-status-icon"></span>
                                <div class="fs-9">
                                    Your last request (until {{ $b['lastGrace']->until_date->format('d M Y') }}) was <strong>{{ $b['lastGrace']->status }}</strong>@if ($b['lastGrace']->admin_note): {{ $b['lastGrace']->admin_note }}@endif.
                                </div>
                            </div>
                        @endif

                        <div class="grace-tiles mb-4">
                            <div class="grace-tile">
                                <small>{{ $b['info']['state'] === 'grace' ? 'Plan ended' : 'Plan ends' }}</small>
                                <strong>{{ $b['info']['subscription']->ends_at->format('d M Y') }}</strong>
                            </div>
                            <div class="grace-tile">
                                <small>Access until</small>
                                <strong>{{ $gBase->format('d M Y') }}</strong>
                            </div>
                            <div class="grace-tile">
                                <small>Latest you can ask</small>
                                <strong>{{ $gMax->format('d M Y') }}</strong>
                            </div>
                        </div>

                        <label class="form-label fw-semibold" for="until_date">Keep my access until</label>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            @foreach ($gChips as $date => $label)
                                <button type="button" class="grace-chip" data-date="{{ $date }}">{{ $label }}</button>
                            @endforeach
                        </div>
                        <input class="form-control @error('until_date') is-invalid @enderror" type="date" id="until_date" name="until_date"
                               min="{{ $gMin->format('Y-m-d') }}" max="{{ $gMax->format('Y-m-d') }}" data-base="{{ $gBase->format('Y-m-d') }}"
                               value="{{ old('until_date') }}" required>
                        @error('until_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="grace-until-note" id="grace-until-note" aria-live="polite"></div>
                        <div class="form-text mb-3">Pick a date between {{ $gMin->format('d M Y') }} and {{ $gMax->format('d M Y') }}, or tap a shortcut above.</div>

                        <label class="form-label fw-semibold" for="grace_reason">Reason <span class="fw-normal text-body-tertiary">(optional)</span></label>
                        <textarea class="form-control @error('reason') is-invalid @enderror" id="grace_reason" name="reason" rows="2" maxlength="500" placeholder="e.g. Payment is on the way, need a few more days">{{ old('reason') }}</textarea>
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Close</button>
                    @if (!$b['pendingGrace'] && $b['grace']['can_request'])
                        <button type="submit" class="btn btn-primary"><span class="fa-solid fa-paper-plane me-2"></span>Send request</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ Renew / purchase popup ============ --}}
<div class="modal fade" id="renewModal" tabindex="-1" aria-labelledby="renewModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ ($adminSupport ?? false) ? route('admin.support.purchase.request', $adminSupportSession) : route('portal.purchase.request') }}" id="renewForm">
                @csrf
                <input type="hidden" name="_form" value="renew">
                <div class="modal-header">
                    <h5 class="modal-title" id="renewModalTitle">{{ $subscriptionAction }} Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($b['pendingPurchase'])
                        <div class="alert alert-subtle-info mb-0">
                            Your {{ strtolower($subscriptionAction) }} request is waiting for the administrator's approval:<br>
                            <strong>{{ $b['pendingPurchase']->package->name }}</strong> · {{ $b['pendingPurchase']->months }} month(s) · {{ $money($b['pendingPurchase']->amount) }}
                            via {{ $methods[$b['pendingPurchase']->method] ?? ucfirst($b['pendingPurchase']->method) }}@if ($b['pendingPurchase']->trx_id) (Trx {{ $b['pendingPurchase']->trx_id }})@endif.
                        </div>
                    @elseif ($b['packages']->isEmpty())
                        <div class="alert alert-subtle-warning mb-0">No plans are available right now. Please contact the administrator.</div>
                    @else
                        @if ($b['lastPurchase'])
                            <div class="alert alert-subtle-{{ $b['lastPurchase']->status === 'approved' ? 'success' : 'danger' }} fs-9 py-2">
                                Your last {{ strtolower($subscriptionAction) }} request ({{ $b['lastPurchase']->package->name }}, {{ $b['lastPurchase']->months }} month(s)) was <strong>{{ $b['lastPurchase']->status }}</strong>@if ($b['lastPurchase']->admin_note): {{ $b['lastPurchase']->admin_note }}@endif.
                            </div>
                        @endif

                        <div class="border border-primary rounded-2 overflow-hidden mb-4">
                            <div class="bg-primary text-white px-3 py-2"><h5 class="mb-0 text-white">Choose Your Plan</h5></div>
                            <div class="p-3">
                                <div class="fw-bold fs-9 mb-2">Choose Duration:</div>
                                @foreach ($b['packages'] as $p)
                                    <label class="plan-card">
                                        <div class="d-flex align-items-start gap-2">
                                            <input class="form-check-input mt-1" type="radio" name="package_id" value="{{ $p['id'] }}"
                                                   @checked((string) old('package_id', $b['packages']->first()['id']) === (string) $p['id'])>
                                            <div class="plan-body">
                                                <div class="plan-price">{{ $money($p['price']) }}<small>/month</small></div>
                                                <div class="fs-9">
                                                    {{ $p['name'] }}@if ($p['term_months'] > 1) · For a {{ $p['term_months'] }}-months term.@endif
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                                @error('package_id')<div class="text-danger fs-9">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="renew_months">Months to pay</label>
                            <input class="form-control @error('months') is-invalid @enderror" type="number" id="renew_months" name="months" min="1" value="{{ old('months', 1) }}" required>
                            @error('months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" id="renew_hint"></div>
                        </div>

                        <div class="alert alert-subtle-info py-2 d-flex justify-content-between align-items-center">
                            <span>Total to pay</span><strong class="fs-8" id="renew_total">৳0</strong>
                        </div>
                        @if ($b['extras']['one_time'] > 0 || $b['extras']['cycle'] > 0)
                            <div class="form-text mb-3">Includes additional charges ({{ $money($b['extras']['one_time']) }} one-time + {{ $money($b['extras']['cycle']) }}/month).</div>
                        @endif

                        <div class="alert alert-subtle-secondary fs-9 py-2">
                            @if (config('billing.payment_info'))
                                {!! nl2br(e(config('billing.payment_info'))) !!}
                            @else
                                Send the amount to the administrator (bKash, Nagad, Rocket, bank or cash), then enter the payment details below.
                            @endif
                            Your plan is extended once the administrator confirms the payment.
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-5">
                                <label class="form-label" for="renew_method">Payment method</label>
                                <select class="form-select @error('method') is-invalid @enderror" id="renew_method" name="method">
                                    @foreach ($methods as $k => $v)
                                        <option value="{{ $k }}" @selected(old('method', 'bkash') === $k)>{{ $v }}</option>
                                    @endforeach
                                </select>
                                @error('method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-7">
                                <label class="form-label" for="renew_trx">Transaction ID <span class="text-body-tertiary" id="renew_trx_hint">(required)</span></label>
                                <input class="form-control @error('trx_id') is-invalid @enderror" id="renew_trx" name="trx_id" maxlength="100" value="{{ old('trx_id') }}">
                                @error('trx_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="renew_note">Note <span class="text-body-tertiary">(optional)</span></label>
                                <input class="form-control @error('note') is-invalid @enderror" id="renew_note" name="note" maxlength="255" value="{{ old('note') }}">
                                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    @if (!$b['pendingPurchase'] && $b['packages']->isNotEmpty())
                        <button type="submit" class="btn btn-primary">{{ $subscriptionAction }}</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    // ---- put the banner at the top of the Phoenix page content once Vue has drawn it ----
    var banner = document.getElementById('subscription-banner');
    if (banner) {
        var closed = false;
        var closedKey = 'subBannerClosed:' + @json(session('portal_login_key', 'legacy')) + ':' + @json(($b['info']['state'] ?? '') . ':' . ($b['info']['days_left'] ?? $b['info']['grace_days_left'] ?? ''));
        try { closed = sessionStorage.getItem(closedKey) === '1'; } catch (e) {}
        var closeBtn = document.getElementById('subscription-banner-close');
        if (closeBtn) closeBtn.addEventListener('click', function () {
            banner.remove();
            try { sessionStorage.setItem(closedKey, '1'); } catch (e) {}
        });
        if (closed) {
            banner.remove();
        } else {
            var place = function () {
                var content = document.querySelector('.content');
                if (!content) return false;
                content.insertBefore(banner, content.firstChild);
                banner.classList.remove('d-none');
                banner.classList.add('d-flex');
                return true;
            };
            window.portalWhenReady(place);
        }
    }

    // ---- grace popup: quick-pick chips + live "access until" note ----
    var graceInput = document.getElementById('until_date');
    var graceNote = document.getElementById('grace-until-note');
    if (graceInput && graceNote) {
        var graceChips = document.querySelectorAll('.grace-chip');
        var toDate = function (v) { return new Date(v + 'T00:00:00'); };
        var refreshGrace = function () {
            var v = graceInput.value;
            graceChips.forEach(function (c) { c.classList.toggle('active', c.dataset.date === v); });
            graceNote.textContent = '';
            if (!v) return;
            var days = Math.round((toDate(v) - toDate(graceInput.dataset.base)) / 86400000);
            if (days < 1) return;
            graceNote.textContent = 'You will keep access until ' +
                toDate(v).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) +
                ' (' + days + ' extra day' + (days === 1 ? '' : 's') + ').';
        };
        graceChips.forEach(function (c) {
            c.addEventListener('click', function () { graceInput.value = c.dataset.date; refreshGrace(); });
        });
        graceInput.addEventListener('input', refreshGrace);
        graceInput.addEventListener('change', refreshGrace);
        refreshGrace();
    }

    // ---- renew popup: live months / total ----
    var form = document.getElementById('renewForm');
    var packages = @json($b['packages']->values());
    var extras = @json($b['extras']);
    var months = document.getElementById('renew_months');

    if (form && months && packages.length) {
        var hint = document.getElementById('renew_hint');
        var total = document.getElementById('renew_total');
        var method = document.getElementById('renew_method');
        var trx = document.getElementById('renew_trx');
        var trxHint = document.getElementById('renew_trx_hint');

        var current = function () {
            var r = form.querySelector('input[name=package_id]:checked');
            if (!r) return null;
            for (var i = 0; i < packages.length; i++) if (String(packages[i].id) === r.value) return packages[i];
            return null;
        };
        var fmt = function (n) { return '৳' + (Math.round(n * 100) / 100).toLocaleString('en-US', { maximumFractionDigits: 2 }); };

        var update = function (reset) {
            var p = current();
            if (!p) return;
            months.min = p.min_months;
            months.max = p.max_months;
            var m = parseInt(months.value, 10);
            if (reset || isNaN(m) || m < p.min_months || m > p.max_months) { m = p.min_months; months.value = m; }
            hint.textContent = (p.due > 0 ? 'Due now: ' + p.due + ' month(s). ' : '') + 'You can pay ' + p.min_months + ' to ' + p.max_months + ' month(s).';
            total.textContent = fmt(p.price * m + extras.one_time + extras.cycle * m);
        };

        form.querySelectorAll('input[name=package_id]').forEach(function (r) {
            r.addEventListener('change', function () { update(true); });
        });
        months.addEventListener('input', function () { update(false); });

        var toggleTrx = function () {
            var cash = method.value === 'cash';
            trxHint.textContent = cash ? '(optional for cash)' : '(required)';
        };
        method.addEventListener('change', toggleTrx);

        update(false);
        toggleTrx();
    }

    // ---- if the last submit had errors, open the popup it came from ----
    var reopen = @json($reopen);
    @if ($errors->any() && $reopen)
    var target = document.getElementById(reopen === 'grace' ? 'graceModal' : 'renewModal');
    var tries = 0;
    var timer = setInterval(function () {
        tries++;
        if (window.bootstrap && window.bootstrap.Modal && target) {
            clearInterval(timer);
            window.bootstrap.Modal.getOrCreateInstance(target).show();
        } else if (tries > 100) {
            clearInterval(timer);
        }
    }, 100);
    @endif
})();
</script>
@endif
{{-- Keep the portal sidebar focused on the three requested destinations. --}}
<script>
(function () {
    var activeModule = @json($module ?? null);
    var urls = {
        dashboard: @json(route('dashboard')),
        categories: @json(route('categories.index')),
        news: @json(route('news.create')),
        newsManagement: @json(route('news.index'))
    };

    var place = function () {
        var list = document.querySelector('#navbarVerticalNav');
        if (!list) return false;
        var onDashboard = activeModule !== 'categories' && activeModule !== 'news-entry' && activeModule !== 'news-management';
        var basicOpen = activeModule === 'categories';
        list.innerHTML = '';
        list.insertAdjacentHTML('beforeend',
            '<li class="nav-item"><a class="nav-link' + (onDashboard ? ' active' : '') + '" href="' + urls.dashboard + '">' +
                '<div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="pie-chart"></span></span><span class="nav-link-text">Dashboard</span></div></a></li>' +
            '<li class="nav-item"><a class="nav-link' + (basicOpen ? ' active' : '') + '" href="#portal-basic-setup" role="button" data-bs-toggle="collapse" aria-expanded="' + (basicOpen ? 'true' : 'false') + '" aria-controls="portal-basic-setup">' +
                '<div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="settings"></span></span><span class="nav-link-text">Basic Setup</span></div></a>' +
                '<div class="parent-wrapper"><ul class="nav collapse parent' + (basicOpen ? ' show' : '') + '" id="portal-basic-setup"><li class="nav-item"><a class="nav-link' + (basicOpen ? ' active' : '') + '" href="' + urls.categories + '"><div class="d-flex align-items-center"><span class="nav-link-text">Categories</span></div></a></li></ul></div></li>' +
            '<li class="nav-item"><a class="nav-link' + (activeModule === 'news-entry' ? ' active' : '') + '" href="' + urls.news + '">' +
                '<div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="file-text"></span></span><span class="nav-link-text">News Entry</span></div></a></li>' +
            '<li class="nav-item"><a class="nav-link' + (activeModule === 'news-management' ? ' active' : '') + '" href="' + urls.newsManagement + '">' +
                '<div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="clipboard"></span></span><span class="nav-link-text">News Management</span></div></a></li>'
        );
        if (window.feather && typeof window.feather.replace === 'function') window.feather.replace({ width: '16px', height: '16px', 'aria-hidden': 'true' });
        return true;
    };

    window.portalWhenReady(place);
})();
</script>

<script>
(function () {
    var store = document.getElementById('sub-nav-store');
    var left = document.getElementById('sub-nav-left');
    var right = document.getElementById('sub-nav-right');
    var logoutForm = document.getElementById('sub-logout-form');

    // "Sign out" must work with every Phoenix navbar layout (default, slim, horizontal, combo, dual).
    // Phoenix keeps one navbar and removes the others, so a listener attached to one particular navbar
    // disappears with it. Listen on the document instead (capture phase, before the dropdown handles it).
    if (logoutForm) {
        document.addEventListener('click', function (e) {
            var link = e.target.closest ? e.target.closest('a') : null;
            if (!link) return;
            var inProfile = link.closest('.dropdown-profile') && /sign\s*out/i.test(link.textContent);
            var signOutPage = /sign-out\.html(?:$|[?#])/.test(link.getAttribute('href') || '');
            if (!inProfile && !signOutPage) return;
            e.preventDefault();
            logoutForm.submit();
        }, true);
    }

    if (!store || !left || !right) return;

    var place = function () {
        var nav = document.querySelector('#navbarDefault');
        if (!nav) return false;
        var logo = nav.querySelector('.navbar-logo');
        var icons = nav.querySelector('.navbar-nav-icons');
        var profile = nav.querySelector('.dropdown-profile');
        if (!logo || !icons || !profile) return false;

        // plan badge: next to the logo
        logo.appendChild(left);

        // Grace / Purchase buttons: before the theme toggle
        var li = document.createElement('li');
        li.className = 'nav-item d-flex align-items-center';
        li.appendChild(right);
        icons.insertBefore(li, icons.firstChild);

        // user dropdown: show this portal instead of the demo user
        var name = profile.querySelector('h6');
        if (name) {
            name.textContent = store.dataset.portalName || name.textContent;
            var sub = (store.dataset.portalSub || '').trim();
            if (sub) {
                var p = document.createElement('p');
                p.className = 'fs-9 text-body-tertiary mb-0';
                p.textContent = sub;
                name.parentNode.insertBefore(p, name.nextSibling);
            }
        }
        return true;
    };

    window.portalWhenReady(place);
})();
</script>
</body>
</html>
