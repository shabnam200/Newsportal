<script setup>
import { Head } from '@inertiajs/vue3'
import { nextTick, ref } from 'vue'

defineProps({
  csrfToken: { type: String, required: true },
  logoUrl: { type: String, required: true },
  submitUrl: { type: String, required: true },
  oldEmail: { type: String, default: '' },
})

const showPassword = ref(false)
const passwordInput = ref(null)

async function togglePassword() {
  showPassword.value = !showPassword.value
  await nextTick()
  passwordInput.value?.focus()
}
</script>

<template>
  <Head title="Admin Login" />

  <main class="main">
    <div class="container">
      <div class="row flex-center min-vh-100 py-5">
        <div class="col-sm-10 col-md-8 col-lg-5 col-xl-5">
          <div class="text-center mb-7">
            <img :src="logoUrl" alt="" width="48" @error="$event.target.style.display = 'none'">
            <h3 class="text-body-highlight mt-3">Admin Login</h3>
            <p class="text-body-tertiary">Sign in to manage portals</p>
          </div>

          <form method="POST" :action="submitUrl" novalidate>
            <input type="hidden" name="_token" :value="csrfToken">

            <div class="mb-3">
              <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
              <input class="form-control" :class="{ 'is-invalid': $page.props.errors?.email }" id="email" name="email" type="email" :value="oldEmail" required autofocus>
              <div v-if="$page.props.errors?.email" class="invalid-feedback">{{ $page.props.errors.email }}</div>
            </div>

            <div class="mb-4">
              <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
              <div class="pw-wrap">
                <input ref="passwordInput" class="form-control" :class="{ 'is-invalid': $page.props.errors?.password }" id="password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required>
                <button class="pw-toggle" type="button" :aria-label="showPassword ? 'Hide password' : 'Show password'" :aria-pressed="showPassword" :title="showPassword ? 'Hide password' : 'Show password'" @click="togglePassword">
                  <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
              <div v-if="$page.props.errors?.password" class="invalid-feedback">{{ $page.props.errors.password }}</div>
            </div>

            <button class="btn btn-primary w-100" type="submit">Login</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</template>
