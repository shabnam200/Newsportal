@extends('layouts.base')
@section('title', 'Portal Sign Up')
@section('body')
<main class="main">
  <div class="container">
    <div class="row flex-center min-vh-100 py-5">
      <div class="col-sm-10 col-md-8 col-lg-5 col-xl-5">
        <div class="text-center mb-7">
          <img src="{{ url('media/assets/img/icons/logo.png?size=128') }}" alt="" width="48" onerror="this.style.display='none'">
          <h3 class="text-body-highlight mt-3">Portal Sign Up</h3>
          <p class="text-body-tertiary">Create your portal account. You will log in with your mobile number and password.</p>
        </div>

        <form method="POST" action="{{ route('portal.signup.submit') }}" novalidate>
          @csrf

          <div class="mb-3">
            <label class="form-label" for="portal_name">Portal name <span class="text-danger">*</span></label>
            <input class="form-control @error('portal_name') is-invalid @enderror" id="portal_name" name="portal_name" type="text" value="{{ old('portal_name') }}" required autofocus>
            @error('portal_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label" for="portal_reg_no">Portal reg. no <span class="text-body-tertiary">(optional)</span></label>
            <input class="form-control @error('portal_reg_no') is-invalid @enderror" id="portal_reg_no" name="portal_reg_no" type="text" value="{{ old('portal_reg_no') }}">
            @error('portal_reg_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label" for="editor_name">Editor name <span class="text-body-tertiary">(optional)</span></label>
              <input class="form-control @error('editor_name') is-invalid @enderror" id="editor_name" name="editor_name" type="text" value="{{ old('editor_name') }}">
              @error('editor_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="editor_email">Editor email <span class="text-body-tertiary">(optional)</span></label>
              <input class="form-control @error('editor_email') is-invalid @enderror" id="editor_email" name="editor_email" type="email" value="{{ old('editor_email') }}">
              @error('editor_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="editor_mobile">Editor mobile <span class="text-danger">*</span></label>
            <input class="form-control @error('editor_mobile') is-invalid @enderror" id="editor_mobile" name="editor_mobile" type="tel" placeholder="01XXXXXXXXX" value="{{ old('editor_mobile') }}" autocomplete="tel" required>
            @error('editor_mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">This number is your login id.</div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
            <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" autocomplete="new-password" required>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">At least 6 characters.</div>
          </div>

          <div class="mb-4">
            <label class="form-label" for="password_confirmation">Confirm password <span class="text-danger">*</span></label>
            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
          </div>

          <button class="btn btn-primary w-100 mb-3" type="submit">Sign up</button>

          <div class="text-center">
            <span class="text-body-tertiary">Already have an account?</span>
            <a href="{{ route('portal.login') }}" class="fw-semibold">Login</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>
@endsection
