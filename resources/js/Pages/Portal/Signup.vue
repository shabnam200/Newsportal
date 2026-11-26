<script setup>
import { Head } from '@inertiajs/vue3'
import { nextTick, ref } from 'vue'

defineProps({
  csrfToken: { type: String, required: true },
  logoUrl: { type: String, required: true },
  submitUrl: { type: String, required: true },
  loginUrl: { type: String, required: true },
  oldInput: { type: Object, required: true },
})

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const passwordInput = ref(null)
const passwordConfirmationInput = ref(null)

async function focusInput(input) {
  await nextTick()
  input.value?.focus()
}

async function togglePassword() {
  showPassword.value = !showPassword.value
  await focusInput(passwordInput)
}

async function togglePasswordConfirmation() {
  showPasswordConfirmation.value = !showPasswordConfirmation.value
  await focusInput(passwordConfirmationInput)
}
</script>

<template>
  <Head title="Portal Sign Up" />

  <main class="main">
    <div class="container">
      <div class="row flex-center min-vh-100 py-5">
        <div class="col-sm-10 col-md-8 col-lg-5 col-xl-5">
          <div class="text-center mb-7">
            <img :src="logoUrl" alt="" width="48" @error="$event.target.style.display = 'none'">
            <h3 class="text-body-highlight mt-3">Portal Sign Up</h3>
            <p class="text-body-tertiary">Create your portal account. You will log in with your mobile number and password.</p>
          </div>

          <form method="POST" :action="submitUrl" novalidate>
            <input type="hidden" name="_token" :value="csrfToken">

            <div class="mb-3">
              <label class="form-label" for="portal_name">Portal name <span class="text-danger">*</span></label>
              <input class="form-control" :class="{ 'is-invalid': $page.props.errors?.portal_name }" id="portal_name" name="portal_name" type="text" :value="oldInput.portal_name" required autofocus>
              <div v-if="$page.props.errors?.portal_name" class="invalid-feedback">{{ $page.props.errors.portal_name }}</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="portal_reg_no">Portal reg. no <span class="text-body-tertiary">(optional)</span></label>
              <input class="form-control" :class="{ 'is-invalid': $page.props.errors?.portal_reg_no }" id="portal_reg_no" name="portal_reg_no" type="text" :value="oldInput.portal_reg_no">
              <div v-if="$page.props.errors?.portal_reg_no" class="invalid-feedback">{{ $page.props.errors.portal_reg_no }}</div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label" for="editor_name">Editor name <span class="text-body-tertiary">(optional)</span></label>
                <input class="form-control" :class="{ 'is-invalid': $page.props.errors?.editor_name }" id="editor_name" name="editor_name" type="text" :value="oldInput.editor_name">
                <div v-if="$page.props.errors?.editor_name" class="invalid-feedback">{{ $page.props.errors.editor_name }}</div>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="editor_email">Editor email <span class="text-body-tertiary">(optional)</span></label>
                <input class="form-control" :class="{ 'is-invalid': $page.props.errors?.editor_email }" id="editor_email" name="editor_email" type="email" :value="oldInput.editor_email">
                <div v-if="$page.props.errors?.editor_email" class="invalid-feedback">{{ $page.props.errors.editor_email }}</div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="editor_mobile">Editor mobile <span class="text-danger">*</span></label>
              <input class="form-control" :class="{ 'is-invalid': $page.props.errors?.editor_mobile }" id="editor_mobile" name="editor_mobile" type="tel" placeholder="01XXXXXXXXX" :value="oldInput.editor_mobile" autocomplete="tel" required>
              <div v-if="$page.props.errors?.editor_mobile" class="invalid-feedback">{{ $page.props.errors.editor_mobile }}</div>
              <div class="form-text">This number is your login id.</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
              <div class="pw-wrap">
                <input ref="passwordInput" class="form-control" :class="{ 'is-invalid': $page.props.errors?.password }" id="password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" required>
                <button class="pw-toggle" type="button" :aria-label="showPassword ? 'Hide password' : 'Show password'" :aria-pressed="showPassword" :title="showPassword ? 'Hide password' : 'Show password'" @click="togglePassword">
                  <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
              <div v-if="$page.props.errors?.password" class="invalid-feedback">{{ $page.props.errors.password }}</div>
              <div class="form-text">At least 6 characters.</div>
            </div>

            <div class="mb-4">
              <label class="form-label" for="password_confirmation">Confirm password <span class="text-danger">*</span></label>
              <div class="pw-wrap">
                <input ref="passwordConfirmationInput" class="form-control" id="password_confirmation" name="password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'" autocomplete="new-password" required>
                <button class="pw-toggle" type="button" :aria-label="showPasswordConfirmation ? 'Hide password' : 'Show password'" :aria-pressed="showPasswordConfirmation" :title="showPasswordConfirmation ? 'Hide password' : 'Show password'" @click="togglePasswordConfirmation">
                  <svg v-if="!showPasswordConfirmation" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
            </div>

            <button class="btn btn-primary w-100 mb-3" type="submit">Sign up</button>

            <div class="text-center">
              <span class="text-body-tertiary">Already have an account?</span>
              <a :href="loginUrl" class="fw-semibold">Login</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>
</template>
