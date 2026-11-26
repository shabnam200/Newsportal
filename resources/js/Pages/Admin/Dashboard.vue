<script setup>
import AdminLayout from '../AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps({
  stats: { type: Object, required: true },
  flagged: { type: Array, required: true },
  recentPortals: { type: Array, required: true },
  recent: { type: Array, required: true },
  pendingRequestCount: { type: Number, default: 0 },
  csrfToken: { type: String, required: true },
})

const numberFormat = new Intl.NumberFormat('en-US')

function statusTone(status) {
  if (status >= 500) return 'danger'
  if (status >= 400) return 'warning'
  if (status >= 300) return 'info'
  return 'success'
}
</script>

<template>
  <div>
    <div v-if="pendingRequestCount > 0" class="alert alert-subtle-info d-flex flex-wrap justify-content-between align-items-center gap-2" role="alert">
      <div><b>{{ pendingRequestCount }}</b> request(s) from portals are waiting for your approval (grace period / renewal).</div>
      <a class="btn btn-sm btn-phoenix-primary" :href="$page.props.adminLayout.urls.requests">Review requests</a>
    </div>

    <div class="row g-3 mb-4">
      <div v-for="stat in [
        { label: 'Total portals', value: stats.total },
        { label: 'Active', value: stats.active },
        { label: 'Suspended', value: stats.suspended },
        { label: 'Requests (24h)', value: stats.requests24 },
      ]" :key="stat.label" class="col-6 col-xl-3">
        <div class="card"><div class="card-body"><div class="stat-label">{{ stat.label }}</div><div class="stat-value">{{ numberFormat.format(stat.value) }}</div></div></div>
      </div>
    </div>

    <div class="card mb-4"><div class="card-body">
      <h5 class="mb-3">Needs attention</h5>
      <div v-for="item in flagged" :key="item.url" class="d-flex flex-wrap justify-content-between align-items-start gap-3 py-3 border-top">
        <div>
          <a class="fw-bold" :href="item.url">{{ item.name }}</a>
          <div v-for="(alert, index) in item.alerts" :key="index" class="fs-9" :class="alert.level === 'danger' ? 'text-danger' : 'text-warning-dark'">• {{ alert.message }}</div>
        </div>
        <div class="d-flex gap-2">
          <a class="btn btn-sm btn-phoenix-secondary" :href="item.url">Review</a>
          <form method="POST" :action="item.toggleUrl" data-confirm="Suspend this portal?">
            <input type="hidden" name="_token" :value="csrfToken">
            <button class="btn btn-sm btn-danger" type="submit">Suspend</button>
          </form>
        </div>
      </div>
      <div v-if="flagged.length === 0" class="text-body-tertiary">Nothing suspicious right now.</div>
    </div></div>

    <div class="row g-4">
      <div class="col-xl-8">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Latest activity</h5><a class="fs-9" :href="$page.props.adminLayout.urls.activity">View all</a>
        </div>
        <div class="card"><div class="table-responsive">
          <table class="table table-hover table-sm mb-0 align-middle">
            <thead><tr><th>Time</th><th>Portal</th><th>Method</th><th>Path</th><th>Status</th><th>IP</th><th title="Tenant DB queries / writes">DB q / w</th><th>ms</th></tr></thead>
            <tbody>
              <tr v-for="(activity, index) in recent" :key="`${activity.time}-${index}`" :title="activity.userAgent">
                <td class="text-nowrap fs-9">{{ activity.time }}</td>
                <td><a :href="activity.portalUrl">{{ activity.portalName }}</a></td>
                <td><code>{{ activity.method }}</code></td>
                <td class="path-cell">
                  <span v-if="activity.event === 'login'" class="badge badge-phoenix badge-phoenix-primary">login</span>
                  <span v-else-if="activity.event === 'login_blocked'" class="badge badge-phoenix badge-phoenix-danger">login blocked</span>
                  {{ activity.path }}
                </td>
                <td><span class="badge badge-phoenix" :class="`badge-phoenix-${statusTone(activity.status)}`">{{ activity.status }}</span></td>
                <td class="fs-9">{{ activity.ip }}</td>
                <td class="fs-9">{{ activity.queries }} / <span :class="{ 'fw-bold': activity.writes > 0 }">{{ activity.writes }}</span></td>
                <td class="fs-9">{{ activity.duration }}</td>
              </tr>
              <tr v-if="recent.length === 0"><td colspan="8" class="text-center text-body-tertiary py-5">No activity found for this filter.</td></tr>
            </tbody>
          </table>
        </div></div>
      </div>

      <div class="col-xl-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">New portals</h5><a class="fs-9" :href="$page.props.adminLayout.urls.portals">View all</a>
        </div>
        <div class="card"><ul class="list-group list-group-flush">
          <li v-for="(portal, index) in recentPortals" :key="`${portal.url}-${index}`" class="list-group-item d-flex justify-content-between">
            <a :href="portal.url">{{ portal.name }}</a>
            <span class="fs-9 text-body-tertiary">{{ portal.createdAt }}</span>
          </li>
          <li v-if="recentPortals.length === 0" class="list-group-item text-body-tertiary">No portals yet.</li>
        </ul></div>
      </div>
    </div>
  </div>
</template>
