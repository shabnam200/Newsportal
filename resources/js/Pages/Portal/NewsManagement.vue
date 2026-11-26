<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { onMounted } from 'vue'
import Icon from '../../Components/Icon.vue'

const props = defineProps({
  news: { type: Object, required: true },
  filters: { type: Object, required: true },
  statusOptions: { type: Object, required: true },
  createUrl: { type: String, required: true },
  managementUrl: { type: String, required: true },
})

const page = usePage()

// The Phoenix shell (sidebar / navbar) is drawn by the dashboard view; move this page into its .content area.
onMounted(() => {
  const root = document.getElementById('portal-inertia')
  let attempts = 0
  const timer = window.setInterval(() => {
    const content = document.querySelector('#app .content')
    attempts += 1
    if (root && content) {
      content.appendChild(root)
      window.clearInterval(timer)
    } else if (attempts >= 300) window.clearInterval(timer)
  }, 100)
})

function removeNews(item) {
  if (!window.confirm(`Delete “${item.news_headline}”? This also removes its attached images and related records.`)) return
  router.delete(item.delete_url, { preserveScroll: true })
}

function statusTone(status) {
  return Number(status) === 1 ? 'success' : 'secondary'
}

// On phones only show the first / last / current page numbers (plus Previous / Next).
function showPageLink(link, index, links) {
  return link.active || index === 0 || index === links.length - 1
}

const hasFilters = () => Boolean(props.filters.q) || (props.filters.status !== '' && props.filters.status != null)
</script>

<template>
  <div class="news-management-page">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h2 class="mb-2 text-body-emphasis">News Management</h2>
        <h5 class="text-body-tertiary fw-semibold mb-0">Browse, edit, or remove news stories from this portal</h5>
      </div>
      <a class="btn btn-primary" :href="createUrl"><Icon name="plus" class="me-2" />Create news</a>
    </div>

    <div v-if="page.props.flash?.success" class="alert alert-subtle-success" role="status">{{ page.props.flash.success }}</div>
    <div v-if="Object.keys(page.props.errors || {}).length" class="alert alert-subtle-danger" role="alert"><div v-for="(message, field) in page.props.errors" :key="field">{{ message }}</div></div>

    <form class="row g-2 g-md-3 align-items-center mb-4" method="GET" :action="managementUrl" role="search">
      <div class="col-12 col-md">
        <label class="visually-hidden" for="news-search">Search news</label>
        <input id="news-search" class="form-control" name="q" type="search" :value="filters.q" placeholder="Search headline, brief, or reporter">
      </div>
      <div class="col-12 col-sm-6 col-md-auto">
        <label class="visually-hidden" for="news-status">Filter by status</label>
        <select id="news-status" class="form-select news-status-filter" name="status" :value="filters.status" @change="$event.target.form.requestSubmit()">
          <option value="">All statuses</option>
          <option v-for="(label, value) in statusOptions" :key="value" :value="value">{{ label }}</option>
        </select>
      </div>
      <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
        <button class="btn btn-primary px-4 flex-grow-1 flex-md-grow-0" type="submit"><Icon name="search" class="me-2" />Search</button>
        <a v-if="hasFilters()" class="btn btn-phoenix-secondary" :href="managementUrl">Clear</a>
      </div>
    </form>

    <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis border-top border-bottom border-translucent position-relative top-1">
      <div class="table-responsive scrollbar mx-n1 px-1">
        <table class="table fs-9 mb-0 news-table">
          <thead>
            <tr>
              <th scope="col" class="ps-3">News</th>
              <th scope="col" class="d-none d-md-table-cell">Category</th>
              <th scope="col" class="d-none d-xl-table-cell">Reporter</th>
              <th scope="col" class="d-none d-lg-table-cell">Published</th>
              <th scope="col" class="d-none d-sm-table-cell">Status</th>
              <th scope="col" class="text-end pe-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in news.data" :key="item.news_id">
              <td class="news-cell ps-3">
                <div class="d-flex align-items-center gap-3">
                  <img v-if="item.cover_url" class="news-cover-thumb" :src="item.cover_url" alt="" loading="lazy">
                  <div v-else class="news-cover-thumb news-cover-empty"><Icon name="file" :size="18" /></div>
                  <div class="news-title-wrap">
                    <Link class="fw-bold fs-8 news-title-link" :href="item.edit_url">{{ item.news_headline }}</Link>
                    <div v-if="item.news_sub_headline || item.news_details_brief" class="text-body-tertiary text-truncate">{{ item.news_sub_headline || item.news_details_brief }}</div>
                    <div class="news-meta text-body-tertiary fs-10">
                      <span>#{{ item.news_id }}</span>
                      <span v-if="item.type_name">{{ item.type_name }}</span>
                      <span class="d-sm-none"><span class="badge badge-phoenix fs-10" :class="`badge-phoenix-${statusTone(item.news_status)}`">{{ item.status_label }}</span></span>
                      <span class="d-md-none">{{ item.category_name || 'Uncategorized' }}</span>
                      <span class="d-xl-none" v-if="item.news_reporter">{{ item.news_reporter }}</span>
                      <span class="d-lg-none" v-if="item.published_at">{{ item.published_at }}</span>
                    </div>
                  </div>
                </div>
              </td>
              <td class="d-none d-md-table-cell"><span class="news-clamp">{{ item.category_name || 'Uncategorized' }}</span></td>
              <td class="d-none d-xl-table-cell"><span class="news-clamp">{{ item.news_reporter || '—' }}</span></td>
              <td class="d-none d-lg-table-cell text-nowrap">{{ item.published_at || '—' }}</td>
              <td class="d-none d-sm-table-cell"><span class="badge badge-phoenix fs-10" :class="`badge-phoenix-${statusTone(item.news_status)}`">{{ item.status_label }}</span></td>
              <td class="text-end pe-3">
                <div class="news-actions">
                  <Link class="btn btn-phoenix-primary" :href="item.edit_url" title="Edit" aria-label="Edit news"><Icon name="edit" :size="14" /></Link>
                  <button class="btn btn-phoenix-danger" type="button" title="Delete" aria-label="Delete news" @click="removeNews(item)"><Icon name="trash" :size="14" /></button>
                </div>
              </td>
            </tr>
            <tr v-if="news.data.length === 0"><td colspan="6" class="text-center text-body-tertiary py-5">{{ hasFilters() ? 'No stories match these filters.' : 'No news stories yet.' }}</td></tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 py-3">
        <div class="text-body-tertiary fs-9">
          <template v-if="news.total">{{ news.from }} to {{ news.to }} of {{ news.total }} stories</template>
          <template v-else>0 stories</template>
          <span class="d-none d-md-inline"> · newest publication date first</span>
        </div>
        <nav v-if="news.last_page > 1" aria-label="News pages">
          <ul class="pagination mb-0 justify-content-center">
            <li v-for="(link, index) in news.links" :key="index" class="page-item" :class="[{ active: link.active, disabled: !link.url }, showPageLink(link, index, news.links) ? '' : 'd-none d-sm-block']">
              <Link class="page-link" :href="link.url || '#'" preserve-scroll><span v-html="link.label"></span></Link>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</template>

<style scoped>
.news-status-filter { min-width: 11rem; }

.news-table th { padding: .6rem .75rem; white-space: nowrap; font-size: .75rem; letter-spacing: .04em; text-transform: uppercase; color: var(--phoenix-tertiary-color); border-bottom-width: 1px; }
.news-table td { padding: .75rem; vertical-align: middle; line-height: 1.35; }
.news-table tbody tr:hover > td { background: var(--phoenix-body-highlight-bg, rgba(0, 0, 0, .03)); }

/* The news column takes the remaining width and may shrink: long titles wrap / truncate inside it
   instead of spilling over the neighbouring columns. */
.news-cell { width: 100%; max-width: 0; min-width: 14rem; }
.news-title-wrap { min-width: 0; flex: 1 1 auto; }
.news-title-link { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; line-clamp: 2; overflow: hidden; overflow-wrap: anywhere; text-decoration: none; }
.news-title-link:hover { text-decoration: underline; }
.news-meta { display: flex; flex-wrap: wrap; column-gap: .75rem; row-gap: .1rem; }
.news-clamp { display: block; max-width: 11rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.news-cover-thumb { width: 3.5rem; height: 2.75rem; flex: 0 0 3.5rem; border-radius: .4rem; object-fit: cover; background: var(--phoenix-secondary-bg); }
.news-cover-empty { display: grid; place-items: center; color: var(--phoenix-tertiary-color); }

.news-actions { display: inline-flex; justify-content: flex-end; gap: .35rem; }
.news-actions .btn { width: 2rem; height: 2rem; padding: 0; display: inline-flex; align-items: center; justify-content: center; }

@media (max-width: 575.98px) {
  .news-cell { min-width: 0; }
  .news-cover-thumb { width: 2.75rem; height: 2.25rem; flex-basis: 2.75rem; }
  .news-status-filter { width: 100%; }
}
</style>
