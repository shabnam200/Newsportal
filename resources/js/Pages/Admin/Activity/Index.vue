<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '../../AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  logs: { type: Object, required: true },
  ranges: { type: Object, required: true },
  portals: { type: Array, required: true },
  flagged: { type: Array, required: true },
  filters: { type: Object, required: true },
  portalUrls: { type: Object, required: true },
})

const statusTone = (status) => status >= 500 ? 'danger' : status >= 400 ? 'warning' : status >= 300 ? 'info' : 'success'
</script>

<template>
  <p class="text-body-tertiary">Every request made by a portal user (path, status, IP) and how many queries it ran on the portal's own database. Request bodies and passwords are never stored.</p>

  <div v-for="item in flagged" :key="item.url" class="alert alert-subtle-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div><b>{{ item.portal_name }}</b> — {{ item.alerts.map((alert) => alert.message).join(' ') }}</div>
    <a class="btn btn-sm btn-phoenix-warning" :href="item.url">Review</a>
  </div>

  <form method="GET" :action="$page.props.adminLayout.urls.activity" class="row g-2 mb-3 align-items-end">
    <div class="col-md-3"><select class="form-select" name="portal_id" :value="filters.portal_id || ''" @change="$event.target.form.requestSubmit()"><option value="">All portals</option><option v-for="portal in portals" :key="portal.id" :value="portal.id">{{ portal.portal_name }}</option></select></div>
    <div class="col-md-2"><select class="form-select" name="range" :value="filters.range || '24h'" @change="$event.target.form.requestSubmit()"><option v-for="(label, key) in ranges" :key="key" :value="key">{{ label }}</option></select></div>
    <div class="col-md-2"><select class="form-select" name="event" :value="filters.event || ''" @change="$event.target.form.requestSubmit()"><option value="">All events</option><option value="request">Requests</option><option value="login">Logins</option><option value="login_blocked">Blocked logins</option></select></div>
    <div class="col-md-2"><select class="form-select" name="status" :value="filters.status || ''" @change="$event.target.form.requestSubmit()"><option value="">Any status</option><option value="error">Errors (4xx + 5xx)</option><option value="2xx">2xx</option><option value="3xx">3xx</option><option value="4xx">4xx</option><option value="5xx">5xx</option></select></div>
    <div class="col-md-2"><input class="form-control" type="search" name="q" :value="filters.q || ''" placeholder="Path or IP" enterkeyhint="search" autocomplete="off"></div>
    <div class="col-auto"><button class="btn btn-primary" type="submit">Filter</button></div>
  </form>

  <div class="card"><div class="table-responsive"><table class="table table-hover table-sm mb-0 align-middle">
    <thead><tr><th>Time</th><th>Portal</th><th>Method</th><th>Path</th><th>Status</th><th>IP</th><th title="Tenant DB queries / writes">DB q / w</th><th>ms</th></tr></thead>
    <tbody>
      <tr v-for="log in logs.data" :key="log.id" :title="log.user_agent">
        <td class="text-nowrap fs-9">{{ log.created_at ? new Date(log.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '' }}</td>
        <td><a :href="portalUrls[log.portal_id]">{{ log.portal?.portal_name || `#${log.portal_id}` }}</a></td>
        <td><code>{{ log.method }}</code></td>
        <td class="path-cell"><span v-if="log.event === 'login'" class="badge badge-phoenix badge-phoenix-primary">login</span><span v-else-if="log.event === 'login_blocked'" class="badge badge-phoenix badge-phoenix-danger">login blocked</span> {{ log.path }}</td>
        <td><span class="badge badge-phoenix" :class="`badge-phoenix-${statusTone(log.status)}`">{{ log.status }}</span></td>
        <td class="fs-9">{{ log.ip }}</td><td class="fs-9">{{ log.db_queries }} / <span :class="{ 'fw-bold': log.db_writes > 0 }">{{ log.db_writes }}</span></td><td class="fs-9">{{ log.duration_ms }}</td>
      </tr>
      <tr v-if="logs.data.length === 0"><td colspan="8" class="text-center text-body-tertiary py-5">No activity found for this filter.</td></tr>
    </tbody>
  </table></div></div>
  <nav v-if="logs.last_page > 1" class="mt-3" aria-label="Activity pages"><ul class="pagination mb-0"><li v-for="(link, index) in logs.links" :key="index" class="page-item" :class="{ active: link.active, disabled: !link.url }"><Link class="page-link" :href="link.url || '#'" preserve-scroll><span v-html="link.label"></span></Link></li></ul></nav>
</template>
