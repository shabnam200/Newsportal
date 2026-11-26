@extends('layouts.base')
@section('title', 'Portal Login')
@section('body')
<main class="main">
  <div class="container">
    <div class="row flex-center min-vh-100 py-5">
      <div class="col-sm-10 col-md-8 col-lg-5 col-xl-4">
        <div class="text-center mb-7">
          <img src="{{ url('media/assets/img/icons/logo.png?size=128') }}" alt="" width="48" onerror="this.style.display='none'">
          <h3 class="text-body-highlight mt-3">Portal Login</h3>
          <p class="text-body-tertiary">Enter your mobile number and password to continue</p>
        </div>

        @if (session('status'))
          <div class="alert alert-subtle-success" role="alert">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('portal.login.submit') }}" novalidate>
          @csrf

          <div class="mb-3">
            <label class="form-label" for="editor_mobile">Mobile number</label>
            <input class="form-control @error('editor_mobile') is-invalid @enderror" id="editor_mobile" name="editor_mobile" type="tel" placeholder="01XXXXXXXXX" value="{{ old('editor_mobile') }}" autocomplete="tel" required autofocus>
            @error('editor_mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mb-4">
            <label class="form-label" for="password">Password</label>
            <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" autocomplete="current-password" required>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <button class="btn btn-primary w-100 mb-3" type="submit">Login</button>

          <div class="text-center">
            <span class="text-body-tertiary">Don't have an account?</span>
            <a href="{{ route('portal.signup') }}" class="fw-semibold">Sign up</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>
@endsection
