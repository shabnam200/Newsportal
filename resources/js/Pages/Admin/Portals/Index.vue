<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '../../AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps({
  portals: { type: Object, required: true },
  stats: { type: Object, required: true },
  q: { type: String, default: '' },
  status: { type: String, default: '' },
})

const statCards = [
  ['Total portals', 'total', 'total'],
  ['Active', 'active', 'active'],
  ['Suspended', 'suspended', 'suspended'],
  ['DB not ready', 'unprovisioned', 'unready'],
]
const planTone = (state) => ({ active: 'success', grace: 'warning', expired: 'danger' }[state] || 'secondary')
</script>

<template>
  <div class="row g-3 mb-4">
    <div v-for="[label, key, tone] in statCards" :key="key" class="col-6 col-xl-3">
      <div class="card portal-stat-card" :class="`portal-stat-${tone}`"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center"><span class="portal-stat-label">{{ label }}</span><span class="portal-stat-mark" aria-hidden="true"></span></div>
        <div class="portal-stat-value">{{ stats[key] }}</div>
      </div></div>
    </div>
  </div>

  <form class="card portal-filters" method="GET" role="search">
    <div class="card-body">
      <div class="portal-search"><label class="visually-hidden" for="portal-search">Search portals</label><input class="form-control" id="portal-search" name="q" :value="q" placeholder="Search by portal, editor, email, mobile, or reg. no."></div>
      <div class="portal-status-filter"><label class="visually-hidden" for="portal-status">Filter by status</label><select class="form-select" id="portal-status" name="status" :value="status" @change="$event.target.form.requestSubmit()"><option value="">All statuses</option><option value="active">Active</option><option value="suspended">Suspended</option></select></div>
      <button class="btn btn-primary px-4" type="submit">Filter</button>
      <a v-if="q || status" class="btn btn-phoenix-secondary" :href="$page.props.adminLayout.urls.portals">Clear</a>
    </div>
  </form>

  <div class="card portal-table-card"><div class="table-responsive">
    <table class="table table-hover mb-0 align-middle portal-table">
      <thead><tr><th class="portal-id-heading">ID</th><th>Portal</th><th>Editor</th><th>Mobile</th><th>Database</th><th>Plan</th><th>Status</th><th>Last login</th><th></th></tr></thead>
      <tbody>
        <tr v-for="portal in portals.data" :key="portal.id">
          <td class="portal-id-cell"><span class="portal-id" :aria-label="`Portal ID ${portal.id}`">{{ portal.id }}</span></td>
          <td><div class="portal-name">{{ portal.portal_name }}</div><span v-if="portal.portal_reg_no" class="portal-secondary">Reg. no. {{ portal.portal_reg_no }}</span></td>
          <td><div class="fw-semibold">{{ portal.editor_name || 'Not provided' }}</div><span v-if="portal.editor_email" class="portal-secondary">{{ portal.editor_email }}</span></td>
          <td>{{ portal.editor_mobile }}</td>
          <td><code v-if="portal.provisioned">{{ portal.database_name }}</code><span v-else class="badge badge-phoenix badge-phoenix-warning">not ready</span></td>
          <td v-if="portal.subscription"><div>{{ portal.subscription.package_name }}</div><span class="badge badge-phoenix" :class="`badge-phoenix-${planTone(portal.subscription.state)}`">{{ portal.subscription.state }}</span> <span class="plan-until">until {{ portal.subscription.ends_at }}</span></td>
          <td v-else><span class="text-body-tertiary">No plan</span></td>
          <td><span class="badge badge-phoenix" :class="`badge-phoenix-${portal.status === 'active' ? 'success' : 'danger'}`">{{ portal.status.charAt(0).toUpperCase() + portal.status.slice(1) }}</span></td>
          <td class="last-login">{{ portal.last_login_at }}</td>
          <td class="text-end"><a class="btn btn-sm btn-phoenix-primary manage-btn" :href="portal.show_url">Manage</a></td>
        </tr>
        <tr v-if="portals.data.length === 0"><td colspan="9" class="text-center text-body-tertiary py-5">No portals match these filters.</td></tr>
      </tbody>
    </table>
  </div>
    <nav v-if="portals.last_page > 1" class="portal-pagination" aria-label="Portal pages"><ul class="pagination mb-0">
      <li v-for="(link, index) in portals.links" :key="index" class="page-item" :class="{ active: link.active, disabled: !link.url }"><Link class="page-link" :href="link.url || '#'"><span v-html="link.label"></span></Link></li>
    </ul></nav>
  </div>
</template>
