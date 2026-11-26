<script setup>
import AdminLayout from '../../AdminLayout.vue'
import { usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

defineProps({ packages: { type: Array, required: true }, packageStoreUrl: { type: String, required: true } })
const page = usePage()
const csrf = page.props.adminLayout.csrfToken
const updateUrl = (id) => `${page.props.adminLayout.urls.packages}/${id}`
const money = (value) => new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Number(value || 0))
</script>

<template>
  <p class="text-body-tertiary">The charge is <b>per month</b>. Set <b>Term = 12</b> for a yearly plan: the portal commits for 12 months but pays month by month (or several months at once). A skipped month stays due and is collected with the next payment. Changes apply to new terms only; running subscriptions keep their old terms.</p>

  <div class="card mb-4 border-primary"><div class="card-body">
    <h5 class="mb-3">Add new package</h5>
    <form method="POST" :action="packageStoreUrl"><input type="hidden" name="_token" :value="csrf">
      <div class="row g-3">
        <div class="col-lg-2"><label class="form-label">Name</label><input class="form-control" name="name" placeholder="e.g. Yearly Plan" required></div>
        <div class="col-lg-2"><label class="form-label">Charge per month (tk)</label><input class="form-control" type="number" step="0.01" min="0" name="price" required></div>
        <div class="col-lg-2"><label class="form-label">Days in a month cycle</label><input class="form-control" type="number" min="1" name="duration_days" value="30" required></div>
        <div class="col-lg-1"><label class="form-label">Term (months)</label><input class="form-control" type="number" min="1" name="term_months" value="1" required></div>
        <div class="col-lg-2"><label class="form-label">Grace (days)</label><input class="form-control" type="number" min="0" name="grace_days" value="5" required></div>
        <div class="col-lg-2"><label class="form-label">Grace option opens (days left)</label><input class="form-control" type="number" min="1" max="365" name="grace_window_days" value="7" required></div>
        <div class="col-lg-1 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">Add</button></div>
      </div>
      <div class="form-text mt-2">Term 1 = monthly plan, 12 = yearly plan paid monthly. <b>Grace option opens</b>: the portal sees the "Subscription expiring soon" banner with the Grace Period button only when this many days (or fewer) are left.</div>
    </form>
  </div></div>

  <div class="row g-3">
    <div v-for="pkg in packages" :key="pkg.id" class="col-lg-6"><div class="card"><div class="card-body">
      <form method="POST" :action="updateUrl(pkg.id)"><input type="hidden" name="_token" :value="csrf"><input type="hidden" name="_method" value="PUT">
        <div class="d-flex justify-content-between mb-3"><h5 class="mb-0">{{ pkg.name }}</h5><span v-if="pkg.is_trial" class="badge badge-phoenix badge-phoenix-info">one-time trial</span><span v-else-if="pkg.term_months > 1" class="badge badge-phoenix badge-phoenix-primary">{{ pkg.term_months }}-month term · {{ money(pkg.price * pkg.term_months) }} tk total</span><span v-else class="badge badge-phoenix badge-phoenix-secondary">monthly</span></div>
        <div class="row g-3">
          <div class="col-12"><label class="form-label">Name</label><input class="form-control" name="name" :value="pkg.name" required></div>
          <div :class="`col-md-${pkg.is_trial ? 4 : 3}`"><label class="form-label">{{ pkg.is_trial ? 'Charge (tk)' : 'Charge / month' }}</label><input class="form-control" type="number" step="0.01" min="0" name="price" :value="pkg.price" required></div>
          <div :class="`col-md-${pkg.is_trial ? 4 : 3}`"><label class="form-label">{{ pkg.is_trial ? 'Duration (days)' : 'Days / month' }}</label><input class="form-control" type="number" min="1" name="duration_days" :value="pkg.duration_days" required></div>
          <div v-if="!pkg.is_trial" class="col-md-3"><label class="form-label">Term (months)</label><input class="form-control" type="number" min="1" name="term_months" :value="pkg.term_months" required></div>
          <div :class="`col-md-${pkg.is_trial ? 4 : 3}`"><label class="form-label">Grace (days)</label><input class="form-control" type="number" min="0" name="grace_days" :value="pkg.grace_days" required></div>
          <div class="col-md-6"><label class="form-label">Grace option opens (days left)</label><input class="form-control" type="number" min="1" max="365" name="grace_window_days" :value="pkg.grace_window_days" required><div class="form-text">Banner + Grace Period button show when this many days or fewer are left. Applies to running subscriptions too.</div></div>
          <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="active" value="1" :id="`active${pkg.id}`" :checked="pkg.active"><label class="form-check-label" :for="`active${pkg.id}`">Active (available to assign)</label></div></div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">Save</button>
      </form>
    </div></div>
  </div></div>
</template>
