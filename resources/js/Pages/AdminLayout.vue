<script setup>
import { computed, onMounted } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'

const page = usePage()
const admin = computed(() => page.props.adminLayout?.admin ?? {})
const urls = computed(() => page.props.adminLayout?.urls ?? {})
const pendingRequestCount = computed(() => page.props.adminLayout?.pendingRequestCount ?? 0)
const flash = computed(() => page.props.flash ?? {})
const errors = computed(() => page.props.errors ?? {})
const initials = computed(() => (admin.value.name || '?').trim().charAt(0).toUpperCase())

function isActive(key) {
  const path = page.url.split('?')[0]
  const activePrefixes = {
    dashboard: ['/admin'],
    portals: ['/admin/portals'],
    requests: ['/admin/requests', '/admin/support-sessions'],
    activity: ['/admin/activity'],
    packages: ['/admin/packages'],
  }

  if (key === 'dashboard') return path === '/admin'
  return (activePrefixes[key] || []).some((prefix) => path.startsWith(prefix))
}

function installConfirmDialog() {
  const modalElement = document.getElementById('admin-confirm-modal')
  const modal = modalElement && window.bootstrap?.Modal
    ? window.bootstrap.Modal.getOrCreateInstance(modalElement)
    : null
  const title = document.getElementById('admin-confirm-title')
  const message = document.getElementById('admin-confirm-message')
  const confirmButton = document.getElementById('admin-confirm-submit')
  let pendingForm = null
  let pendingSubmitter = null

  if (!modalElement || !modal || !title || !message || !confirmButton) return

  document.addEventListener('submit', (event) => {
    const form = event.target
    if (!(form instanceof HTMLFormElement)) return
    if (form.dataset.confirmed === 'true') {
      delete form.dataset.confirmed
      return
    }

    const submitter = event.submitter || null
    const prompt = (submitter && submitter.getAttribute('data-confirm')) || form.getAttribute('data-confirm')
    if (!prompt) return

    event.preventDefault()
    pendingForm = form
    pendingSubmitter = submitter
    title.textContent = (submitter && submitter.getAttribute('data-confirm-title'))
      || form.getAttribute('data-confirm-title')
      || 'Confirm action'
    message.textContent = prompt
    modal.show()
  })

  confirmButton.addEventListener('click', () => {
    if (!pendingForm) return

    const form = pendingForm
    const submitter = pendingSubmitter
    pendingForm = null
    pendingSubmitter = null
    form.dataset.confirmed = 'true'
    modal.hide()

    if (typeof form.requestSubmit === 'function') {
      if (submitter) form.requestSubmit(submitter)
      else form.requestSubmit()
    } else {
      form.submit()
    }
  })

  modalElement.addEventListener('hidden.bs.modal', () => {
    pendingForm = null
    pendingSubmitter = null
  })
}

onMounted(() => {
  window.feather?.replace({ width: '16px', height: '16px' })
  installConfirmDialog()
})
</script>

<template>
  <Head :title="page.props.title || 'Admin · News Portal'" />
  <main class="main" id="top">
    <nav class="navbar navbar-vertical navbar-expand-lg">
      <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <div class="navbar-vertical-content">
          <ul class="navbar-nav flex-column" id="navbarVerticalNav">
            <li class="nav-item">
              <p class="navbar-vertical-label">Admin</p>
              <hr class="navbar-vertical-line">
              <div class="nav-item-wrapper"><a class="nav-link label-1" :class="{ active: isActive('dashboard') }" :href="urls.dashboard" role="button" aria-expanded="false"><div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="pie-chart"></span></span><span class="nav-link-text-wrapper"><span class="nav-link-text">Dashboard</span></span></div></a></div>
              <div class="nav-item-wrapper"><a class="nav-link label-1" :class="{ active: isActive('portals') }" :href="urls.portals" role="button" aria-expanded="false"><div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="globe"></span></span><span class="nav-link-text-wrapper"><span class="nav-link-text">Portals</span></span></div></a></div>
              <div class="nav-item-wrapper"><a class="nav-link label-1" :class="{ active: isActive('requests') }" :href="urls.requests" role="button" aria-expanded="false"><div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="inbox"></span></span><span class="nav-link-text-wrapper"><span class="nav-link-text">Requests</span><span v-if="pendingRequestCount > 0" class="badge ms-2 badge badge-phoenix badge-phoenix-warning">{{ pendingRequestCount }}</span></span></div></a></div>
              <div class="nav-item-wrapper"><a class="nav-link label-1" :class="{ active: isActive('activity') }" :href="urls.activity" role="button" aria-expanded="false"><div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="activity"></span></span><span class="nav-link-text-wrapper"><span class="nav-link-text">Activity monitor</span></span></div></a></div>
              <div class="nav-item-wrapper"><a class="nav-link label-1" :class="{ active: isActive('packages') }" :href="urls.packages" role="button" aria-expanded="false"><div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="package"></span></span><span class="nav-link-text-wrapper"><span class="nav-link-text">Packages</span></span></div></a></div>
            </li>
          </ul>
        </div>
      </div>
      <div class="navbar-vertical-footer"><button class="btn navbar-vertical-toggle border-0 fw-semibold w-100 white-space-nowrap d-flex align-items-center"><span class="uil uil-left-arrow-to-left fs-8"></span><span class="uil uil-arrow-from-right fs-8"></span><span class="navbar-vertical-footer-text ms-2">Collapsed View</span></button></div>
    </nav>

    <nav class="navbar navbar-top fixed-top navbar-expand" id="navbarDefault">
      <div class="collapse navbar-collapse justify-content-between">
        <div class="navbar-logo">
          <button class="btn navbar-toggler navbar-toggler-humburger-icon hover-bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse" aria-expanded="false" aria-label="Toggle Navigation"><span class="navbar-toggle-icon"><span class="toggle-line"></span></span></button>
          <a class="navbar-brand me-1 me-sm-3" :href="urls.dashboard"><div class="d-flex align-items-center"><div class="d-flex align-items-center"><img :src="urls.logo" alt="phoenix" width="27"><h5 class="logo-text ms-2 d-none d-sm-block">phoenix</h5></div></div></a>
        </div>
        <ul class="navbar-nav navbar-nav-icons flex-row">
          <li class="nav-item"><div class="theme-control-toggle px-2"><input class="form-check-input ms-0 theme-control-toggle-input" type="checkbox" data-theme-control="phoenixTheme" value="dark" id="themeControlToggle"><label class="mb-0 theme-control-toggle-label theme-control-toggle-light" for="themeControlToggle" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Switch theme" style="height:32px;width:32px;"><span class="icon" data-feather="moon"></span></label><label class="mb-0 theme-control-toggle-label theme-control-toggle-dark" for="themeControlToggle" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Switch theme" style="height:32px;width:32px;"><span class="icon" data-feather="sun"></span></label></div></li>
          <li class="nav-item dropdown"><a class="nav-link lh-1 pe-0" id="navbarDropdownUser" href="#!" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false"><div class="avatar avatar-l"><div class="avatar-name rounded-circle"><span>{{ initials }}</span></div></div></a>
            <div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-0 dropdown-profile shadow border" aria-labelledby="navbarDropdownUser"><div class="card position-relative border-0"><div class="card-body p-0"><div class="text-center pt-4 pb-3"><div class="avatar avatar-xl"><div class="avatar-name rounded-circle"><span>{{ initials }}</span></div></div><h6 class="mt-2 text-body-emphasis">{{ admin.name }}</h6><p class="fs-9 text-body-tertiary mb-0">{{ admin.email }}</p></div></div><div class="card-footer p-0 border-top border-translucent"><div class="px-3 py-3"><form method="POST" :action="urls.logout"><input type="hidden" name="_token" :value="page.props.adminLayout.csrfToken"><button class="btn btn-phoenix-secondary d-flex flex-center w-100" type="submit"><span class="me-2" data-feather="log-out"></span>Sign out</button></form></div></div></div></div>
          </li>
        </ul>
      </div>
    </nav>

    <div class="content">
      <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><h2 class="mb-1 lh-sm">{{ page.props.pageTitle || 'Admin' }}</h2><h5 v-if="page.props.pageSubtitle" class="text-body-tertiary fw-semibold mb-0">{{ page.props.pageSubtitle }}</h5></div><div></div></div>
      <div v-if="flash.success" class="alert alert-subtle-success" role="alert">{{ flash.success }}</div>
      <div v-if="flash.warning" class="alert alert-subtle-warning" role="alert">{{ flash.warning }}</div>
      <div v-if="Object.keys(errors).length" class="alert alert-subtle-danger" role="alert"><div v-for="(message, field) in errors" :key="field">{{ message }}</div></div>
      <slot />
      <footer class="footer position-absolute"><div class="row g-0 justify-content-between align-items-center h-100"><div class="col-12 col-sm-auto text-center"><p class="mb-0 mt-2 mt-sm-0 text-body">News Portal Admin<span class="d-none d-sm-inline-block mx-1">|</span><br class="d-sm-none">{{ page.props.currentYear }} &copy;</p></div><div class="col-12 col-sm-auto text-center"><p class="mb-0 text-body-tertiary text-opacity-85">v1.23.0</p></div></div></footer>
    </div>
  </main>

  <div class="modal fade" id="admin-confirm-modal" tabindex="-1" aria-labelledby="admin-confirm-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="admin-confirm-title">Confirm action</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div class="d-flex align-items-start gap-3"><span class="fa-solid fa-triangle-exclamation text-warning mt-1" aria-hidden="true"></span><p class="mb-0" id="admin-confirm-message"></p></div></div><div class="modal-footer"><button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger" id="admin-confirm-submit">Continue</button></div></div></div></div>
</template>
