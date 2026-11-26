<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>News Portal</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('media/assets/img/favicons/favicon-32x32.png?size=32') }}">
    <script>
      (function () {
        var theme = 'light';
        try { theme = localStorage.getItem('phoenixTheme') || 'light'; } catch (e) {}
        if (theme === 'auto') {
          theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.setAttribute('data-bs-theme', theme === 'dark' ? 'dark' : 'light');
      })();
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/theme.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/user.min.css') }}" rel="stylesheet">
    <style>
      .theme-switch {
        position: fixed; top: 1rem; right: 1rem; z-index: 10;
        width: 2.5rem; height: 2.5rem; padding: 0; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--phoenix-card-bg); color: var(--phoenix-body-color);
        border: 1px solid var(--phoenix-border-color); cursor: pointer; font-size: 1.1rem; line-height: 1;
      }
      .theme-switch:hover { border-color: var(--phoenix-primary); color: var(--phoenix-primary); }
      .main { color: var(--phoenix-body-color); }
      .form-text { color: var(--phoenix-tertiary-color); }
      .pw-wrap { position: relative; }
      .pw-wrap > .form-control, .pw-wrap > .form-control.is-invalid { padding-right: 2.9rem; background-image: none; }
      .pw-wrap:has(> .form-control.is-invalid) ~ .invalid-feedback { display: block; }
      .pw-toggle { position: absolute; top: 0; right: 0; width: 2.9rem; height: 100%; padding: 0; display: flex; align-items: center; justify-content: center; border: 0; background: transparent; color: var(--phoenix-tertiary-color); cursor: pointer; }
      .pw-toggle:hover, .pw-toggle:focus-visible { color: var(--phoenix-primary); outline: none; }
      .pw-toggle svg { width: 1.15rem; height: 1.15rem; }
      input[type="password"]::-ms-reveal, input[type="password"]::-ms-clear { display: none; }
    </style>
    @vite('resources/js/app.js')
    @inertiaHead
</head>
<body>
    <button type="button" class="theme-switch" id="themeSwitch" aria-label="Switch light / dark theme" title="Switch theme">
        <span id="themeSwitchIcon">&#9790;</span>
    </button>

    @if (session('warning'))
        <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 20; width: min(38rem, 100%);">
            <div class="alert alert-subtle-warning alert-dismissible fade show mb-0" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" aria-label="Close" onclick="this.parentElement.remove()"></button>
            </div>
        </div>
    @endif

    @inertia

    <script>
      (function () {
        var root = document.documentElement;
        var icon = document.getElementById('themeSwitchIcon');
        var paint = function () { icon.innerHTML = root.getAttribute('data-bs-theme') === 'dark' ? '&#9728;' : '&#9790;'; };
        paint();
        document.getElementById('themeSwitch').addEventListener('click', function () {
          var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
          root.setAttribute('data-bs-theme', next);
          try { localStorage.setItem('phoenixTheme', next); } catch (e) {}
          paint();
        });
      })();
    </script>
</body>
</html>
