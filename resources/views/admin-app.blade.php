<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-navigation-type="default" data-navbar-horizontal-shape="default">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>Admin · News Portal</title>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('media/assets/img/favicons/apple-touch-icon.png?size=180') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('media/assets/img/favicons/favicon-32x32.png?size=32') }}">
    <script src="{{ asset('vendors/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&amp;display=swap" rel="stylesheet">
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
    <style>
      .path-cell { max-width: 340px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
      .content .table { font-size: .875rem; margin-bottom: 0; }
      .content .table > :not(caption) > * > * { padding: .75rem 1rem; vertical-align: middle; }
      .content .table > :not(caption) > * > *:first-child { padding-left: 1.25rem; }
      .content .table > :not(caption) > * > *:last-child { padding-right: 1.25rem; }
      .content .table thead th { font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--phoenix-tertiary-color); white-space: nowrap; }
      .content .table td, .content .table td.path-cell { white-space: nowrap; }
      .content .table code { color: var(--phoenix-body-color); font-size: .8rem; background: transparent; }
      .content .card > .table-responsive { border-radius: inherit; }
      .stat-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: var(--phoenix-tertiary-color); font-weight: 700; }
      .stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1.2; color: var(--phoenix-emphasis-color); }
      .content .card { color: var(--phoenix-body-color); }
      .content .card .fs-3, .content .card .fs-2, .stat-value { color: var(--phoenix-emphasis-color); }
      .content .table { --phoenix-table-color: var(--phoenix-body-color); --phoenix-table-bg: transparent; color: var(--phoenix-body-color); }
      .content .table > :not(caption) > * > * { color: var(--phoenix-body-color) !important; }
      .content .table .fw-semibold, .content .table .fw-bold { color: var(--phoenix-emphasis-color) !important; }
      .content .table > thead > tr > th { color: var(--phoenix-tertiary-color) !important; background-color: var(--phoenix-card-bg) !important; border-bottom: 1px solid var(--phoenix-border-color); }
      .portal-filters { margin-bottom: 1rem; }
      .portal-filters .card-body { display: grid; grid-template-columns: minmax(0, 1fr) minmax(190px, 230px) auto auto; align-items: center; gap: 10px; padding: 14px; }
      .portal-search, .portal-status-filter { min-width: 0; }
      .portal-filters .form-control, .portal-filters .form-select, .portal-filters .btn { min-height: 44px; }
      .portal-filters .form-control, .portal-filters .form-select { width: 100%; }
      .portal-filters .btn { white-space: nowrap; }
      @media (max-width: 575.98px) {
        .portal-filters .card-body { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
        .portal-search, .portal-status-filter { grid-column: 1 / -1; }
        .portal-filters .btn { width: 100%; }
      }
      @media (min-width: 992px) {
        .main > .navbar-vertical { position: fixed !important; top: var(--phoenix-navbar-top-height); bottom: 0; height: calc(100vh - var(--phoenix-navbar-top-height)); overflow: hidden; }
        .main > .navbar-vertical .navbar-vertical-content { height: calc(100vh - var(--phoenix-navbar-top-height) - 4rem); overflow-y: auto; overscroll-behavior: contain; }
      }
    </style>
    @vite('resources/js/app.js')
    @inertiaHead
</head>
<body>
    @inertia
    <script src="{{ asset('vendors/popper/popper.min.js') }}"></script>
    <script src="{{ asset('vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vendors/anchorjs/anchor.min.js') }}"></script>
    <script src="{{ asset('vendors/is/is.min.js') }}"></script>
    <script src="{{ asset('vendors/fontawesome/all.min.js') }}"></script>
    <script src="{{ asset('vendors/lodash/lodash.min.js') }}"></script>
    <script src="{{ asset('vendors/list.js/list.min.js') }}"></script>
    <script src="{{ asset('vendors/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('vendors/dayjs/dayjs.min.js') }}"></script>
    <script src="{{ asset('assets/js/phoenix.js') }}"></script>
</body>
</html>
