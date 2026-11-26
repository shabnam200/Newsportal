<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

const props = defineProps({
  dataUrl: { type: String, required: true },
  storeUrl: { type: String, required: true },
  updateUrl: { type: String, required: true },
})

const rows = ref([])
const loading = ref(true)
const error = ref('')
const search = ref('')
const statusFilter = ref('all')
const collapsed = reactive(new Set())
const modalOpen = ref(false)
const deleteOpen = ref(false)
const saving = ref(false)
const deleting = ref(false)
const imagePreview = ref('')
const imageFile = ref(null)
const formErrors = ref({})
const toast = ref('')
const editingId = ref(null)
const deletingRow = ref(null)
const form = reactive({ parent_id: '', name: '', en_name: '', key_name: '', title: '', keywords: '', description: '', status: true })

const labels = { 1: 'Category', 2: 'Sub-category', 3: 'Sub-sub-category' }
const rowById = computed(() => new Map(rows.value.map((row) => [Number(row.id), row])))
const children = computed(() => {
  const map = new Map()
  rows.value.forEach((row) => {
    const parent = row.parent_id == null ? 0 : Number(row.parent_id)
    if (!map.has(parent)) map.set(parent, [])
    map.get(parent).push(row)
  })
  map.forEach((items) => items.sort((a, b) => a.name.localeCompare(b.name)))
  return map
})
const matchingIds = computed(() => {
  const query = search.value.trim().toLowerCase()
  const matches = new Set()
  rows.value.forEach((row) => {
    const textMatch = !query || [row.name, row.en_name, row.key_name, row.title].some((value) => String(value || '').toLowerCase().includes(query))
    const statusMatch = statusFilter.value === 'all' || String(row.status) === statusFilter.value
    if (!textMatch || !statusMatch) return
    let current = row
    while (current) {
      matches.add(Number(current.id))
      current = current.parent_id == null ? null : rowById.value.get(Number(current.parent_id))
    }
  })
  return matches
})
const parentOptions = computed(() => {
  const blocked = new Set()
  if (editingId.value) {
    const collect = (id) => {
      blocked.add(Number(id))
      ;(children.value.get(Number(id)) || []).forEach((child) => collect(child.id))
    }
    collect(editingId.value)
  }
  const result = [{ id: '', label: 'Top level (main category)' }]
  const walk = (parentId, prefix = '') => {
    ;(children.value.get(parentId) || []).forEach((row) => {
      if (!blocked.has(Number(row.id)) && Number(row.level) < 3) result.push({ id: row.id, label: `${prefix}${row.name}` })
      walk(Number(row.id), `${prefix}　└ `)
    })
  }
  walk(0)
  return result
})
const visibleRows = computed(() => {
  const result = []
  const walk = (parentId) => {
    ;(children.value.get(parentId) || []).forEach((row) => {
      if ((search.value.trim() || statusFilter.value !== 'all') && !matchingIds.value.has(Number(row.id))) return
      result.push({ ...row, depth: Number(row.level) - 1, childCount: (children.value.get(Number(row.id)) || []).length })
      if (!collapsed.has(Number(row.id))) walk(Number(row.id))
    })
  }
  walk(0)
  return result
})

function notify(message) {
  toast.value = message
  window.setTimeout(() => { if (toast.value === message) toast.value = '' }, 3500)
}

async function request(url, options = {}) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || ''
  const response = await fetch(url, {
    credentials: 'same-origin',
    ...options,
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, ...(options.headers || {}) },
  })
  const result = await response.json().catch(() => ({}))
  if (!response.ok) {
    const failure = new Error(result.message || 'The request could not be completed.')
    failure.errors = result.errors || {}
    throw failure
  }
  return result
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const result = await request(props.dataUrl)
    rows.value = Array.isArray(result.data) ? result.data : []
  } catch (failure) {
    error.value = failure.message || 'Could not load categories.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  Object.assign(form, { parent_id: '', name: '', en_name: '', key_name: '', title: '', keywords: '', description: '', status: true })
  formErrors.value = {}
  imageFile.value = null
  imagePreview.value = ''
}

function openForm(row = null, parentId = '') {
  resetForm()
  editingId.value = row ? Number(row.id) : null
  if (row) Object.assign(form, {
    parent_id: row.parent_id == null ? '' : String(row.parent_id), name: row.name || '', en_name: row.en_name || '',
    key_name: row.key_name || '', title: row.title || '', keywords: row.keywords || '', description: row.description || '', status: Boolean(Number(row.status)),
  })
  else form.parent_id = parentId == null ? '' : String(parentId)
  imagePreview.value = row?.image_url || ''
  modalOpen.value = true
}

function selectImage(event) {
  imageFile.value = event.target.files?.[0] || null
  if (imagePreview.value.startsWith('blob:')) URL.revokeObjectURL(imagePreview.value)
  imagePreview.value = imageFile.value ? URL.createObjectURL(imageFile.value) : ''
}

async function save() {
  saving.value = true
  formErrors.value = {}
  const body = new FormData()
  Object.entries(form).forEach(([key, value]) => body.append(key, key === 'status' ? (value ? '1' : '0') : value))
  if (imageFile.value) body.append('image', imageFile.value)
  const url = editingId.value ? `${props.updateUrl}/${editingId.value}` : props.storeUrl
  if (editingId.value) body.append('_method', 'PUT')
  try {
    const result = await request(url, { method: 'POST', body })
    modalOpen.value = false
    notify(result.message || 'Category saved.')
    await load()
  } catch (failure) {
    formErrors.value = failure.errors || {}
    if (!Object.keys(formErrors.value).length) notify(failure.message)
  } finally {
    saving.value = false
  }
}

async function toggleStatus(row) {
  try {
    const result = await request(`${props.updateUrl}/${row.id}/toggle`, { method: 'POST' })
    notify(result.message || 'Category status updated.')
    await load()
  } catch (failure) { notify(failure.message) }
}

async function removeCategory() {
  if (!deletingRow.value) return
  deleting.value = true
  try {
    const result = await request(`${props.updateUrl}/${deletingRow.value.id}`, { method: 'DELETE' })
    deleteOpen.value = false
    notify(result.message || 'Category deleted.')
    await load()
  } catch (failure) { notify(failure.message) }
  finally { deleting.value = false }
}

function setAllCollapsed(value) {
  collapsed.clear()
  if (value) rows.value.forEach((row) => { if ((children.value.get(Number(row.id)) || []).length) collapsed.add(Number(row.id)) })
}

onMounted(() => {
  const pageRoot = document.getElementById('portal-inertia')
  const content = document.querySelector('#app .content')
  if (pageRoot && content && pageRoot.parentElement !== content) content.appendChild(pageRoot)
  load()
})
</script>

<template>
  <div class="categories-page">
    <div class="mb-4">
      <h2 class="mb-2 text-body-emphasis">Categories</h2>
      <h5 class="text-body-tertiary fw-semibold">Category, sub-category and sub-sub-category in one place</h5>
    </div>

    <div class="row g-3 justify-content-between align-items-end mb-4">
      <div class="col-12 col-sm-auto d-flex flex-wrap gap-2">
        <div class="search-box">
          <div class="position-relative"><input v-model="search" class="form-control search-input" type="search" placeholder="Search categories" aria-label="Search categories"><span class="fas fa-search search-box-icon"></span></div>
        </div>
        <select v-model="statusFilter" class="form-select w-auto" aria-label="Status filter"><option value="all">All status</option><option value="1">Active</option><option value="0">Disabled</option></select>
      </div>
      <div class="col-12 col-sm-auto d-flex flex-wrap justify-content-end gap-2">
        <button class="btn btn-link text-body" type="button" @click="setAllCollapsed(false)">Expand all</button>
        <button class="btn btn-link text-body" type="button" @click="setAllCollapsed(true)">Collapse all</button>
        <button class="btn btn-primary" type="button" @click="openForm()"><span class="fas fa-plus me-2"></span>Add category</button>
      </div>
    </div>

    <div class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis border-top border-bottom border-translucent">
      <div class="table-responsive scrollbar mx-n1 px-1">
        <table class="table fs-9 mb-0 cat-table">
          <colgroup><col><col style="width:11rem"><col style="width:12rem"><col style="width:7rem"><col style="width:8rem"><col style="width:9.5rem"></colgroup>
          <thead><tr><th class="ps-3">Name</th><th>Level</th><th>Key</th><th class="text-center">Sub items</th><th>Status</th><th class="text-end pe-3">Actions</th></tr></thead>
          <tbody>
            <tr v-if="loading"><td colspan="6" class="text-center text-body-tertiary py-5"><span class="spinner-border spinner-border-sm me-2"></span>Loading categories…</td></tr>
            <tr v-else-if="error"><td colspan="6" class="text-center text-danger py-5">{{ error }} <button class="btn btn-sm btn-outline-primary ms-2" @click="load">Retry</button></td></tr>
            <tr v-else-if="!rows.length"><td colspan="6" class="text-center text-body-tertiary py-5">No categories yet. Click <strong>Add category</strong> to create the first one.</td></tr>
            <tr v-else-if="!visibleRows.length"><td colspan="6" class="text-center text-body-tertiary py-5">No category matches your search.</td></tr>
            <tr v-for="row in visibleRows" v-else :key="row.id" :class="`cat-row-l${row.level}`">
              <td class="ps-3"><div class="cat-name-cell" :style="{ paddingLeft: `${row.depth * 1.6}rem` }">
                <button v-if="row.childCount" type="button" class="cat-caret" :class="{ open: !collapsed.has(Number(row.id)) }" aria-label="Expand or collapse" @click="collapsed.has(Number(row.id)) ? collapsed.delete(Number(row.id)) : collapsed.add(Number(row.id))"><span class="fas fa-caret-right"></span></button><span v-else class="cat-caret-space"></span>
                <img v-if="row.image_url" class="cat-image-thumb" :src="row.image_url" :alt="row.name" loading="lazy"><span v-else class="cat-image-placeholder"><span class="fas fa-image"></span></span>
                <div class="cat-title text-body-emphasis">{{ row.name }} <span v-if="row.en_name && row.en_name !== row.name" class="fs-10 fw-normal text-body-tertiary ms-1">{{ row.en_name }}</span></div>
              </div></td>
              <td class="text-body-tertiary text-nowrap">{{ labels[row.level] }}</td><td class="text-body-tertiary text-break">{{ row.key_name }}</td><td class="text-center">{{ row.childCount || '−' }}</td>
              <td><button type="button" class="btn btn-link p-0 text-decoration-none" @click="toggleStatus(row)"><span class="badge badge-phoenix fs-10" :class="Number(row.status) ? 'badge-phoenix-success' : 'badge-phoenix-secondary'"><span class="badge-label">{{ Number(row.status) ? 'Active' : 'Disabled' }}</span></span></button></td>
              <td class="text-end pe-3"><div class="cat-actions"><button v-if="Number(row.level) < 3" type="button" class="btn btn-phoenix-secondary" :title="`Add ${labels[Number(row.level) + 1]}`" @click="openForm(null, row.id)"><span class="fas fa-plus"></span></button><span v-else class="cat-ph"></span><button type="button" class="btn btn-phoenix-secondary" title="Edit" @click="openForm(row)"><span class="fas fa-pen"></span></button><button type="button" class="btn btn-phoenix-danger" title="Delete" @click="deletingRow = row; deleteOpen = true"><span class="fas fa-trash"></span></button></div></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="modalOpen" class="cat-modal-layer" role="presentation" @click.self="modalOpen = false">
      <div class="modal d-block" role="dialog" aria-modal="true" aria-labelledby="catModalTitle"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
        <form novalidate @submit.prevent="save">
          <div class="modal-header"><h5 id="catModalTitle" class="modal-title">{{ editingId ? 'Edit category' : 'Add category' }}</h5><button type="button" class="btn-close" aria-label="Close" @click="modalOpen = false"></button></div>
          <div class="modal-body"><div class="row g-3">
            <div class="col-12"><label class="form-label">Parent</label><select v-model="form.parent_id" class="form-select" :class="{ 'is-invalid': formErrors.parent_id }"><option v-for="option in parentOptions" :key="option.id" :value="String(option.id)">{{ option.label }}</option></select><div v-if="formErrors.parent_id" class="invalid-feedback">{{ formErrors.parent_id[0] }}</div><div class="form-text">Leave on “Top level” to create a main category. You can go up to 3 levels.</div></div>
            <div v-for="field in [{key:'name',label:'Name',required:true},{key:'en_name',label:'English name'},{key:'key_name',label:'Key'},{key:'title',label:'Title'},{key:'keywords',label:'Keywords'}]" :key="field.key" class="col-md-6"><label class="form-label">{{ field.label }}<span v-if="field.required" class="text-danger"> *</span></label><input v-model="form[field.key]" class="form-control" :class="{ 'is-invalid': formErrors[field.key] }" :required="field.required" :maxlength="field.key === 'keywords' ? 255 : 100"><div v-if="formErrors[field.key]" class="invalid-feedback">{{ formErrors[field.key][0] }}</div><div v-if="field.key === 'key_name'" class="form-text">Leave blank to generate from the English name.</div></div>
            <div class="col-12"><label class="form-label">Description</label><textarea v-model="form.description" class="form-control" :class="{ 'is-invalid': formErrors.description }" rows="2" maxlength="255"></textarea><div v-if="formErrors.description" class="invalid-feedback">{{ formErrors.description[0] }}</div></div>
            <div class="col-12"><label class="form-label">Category image</label><input class="form-control" :class="{ 'is-invalid': formErrors.image }" type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp" @change="selectImage"><div v-if="formErrors.image" class="invalid-feedback">{{ formErrors.image[0] }}</div><div class="form-text">JPG, PNG, WebP, GIF or BMP; maximum 5 MB.</div><img v-if="imagePreview" class="cat-image-preview mt-2" :src="imagePreview" alt="Category preview"></div>
            <div class="col-12"><div class="form-check form-switch"><input id="cat-status" v-model="form.status" class="form-check-input" type="checkbox"><label for="cat-status" class="form-check-label">Active</label></div></div>
          </div></div>
          <div class="modal-footer"><button type="button" class="btn btn-phoenix-secondary" @click="modalOpen = false">Cancel</button><button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button></div>
        </form>
      </div></div></div>
    </div>

    <div v-if="deleteOpen" class="cat-modal-layer" role="presentation" @click.self="deleteOpen = false"><div class="modal d-block" role="dialog" aria-modal="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Delete category</h5><button type="button" class="btn-close" aria-label="Close" @click="deleteOpen = false"></button></div><div class="modal-body">Delete <strong>{{ deletingRow?.name }}</strong>? This cannot be undone.</div><div class="modal-footer"><button type="button" class="btn btn-phoenix-secondary" @click="deleteOpen = false">Cancel</button><button type="button" class="btn btn-danger" :disabled="deleting" @click="removeCategory">{{ deleting ? 'Deleting…' : 'Delete' }}</button></div></div></div></div></div>
    <div v-if="toast" class="cat-toast alert alert-subtle-info" role="status">{{ toast }}</div>
  </div>
</template>

<style>
.cat-name-cell { display:flex; align-items:center; column-gap:.5rem; min-width:14rem; }
.cat-caret,.cat-caret-space { width:1.5rem; flex:none; }
.cat-caret { padding:0; border:0; background:transparent; color:var(--phoenix-tertiary-color); }
.cat-caret .fa-caret-right { transition:transform .15s; }
.cat-caret.open .fa-caret-right { transform:rotate(90deg); }
.cat-row-l1 .cat-title { font-weight:700; }
.cat-image-thumb,.cat-image-placeholder { width:2rem; height:2rem; flex:none; border:1px solid var(--phoenix-border-color); border-radius:.35rem; object-fit:cover; }
.cat-image-placeholder { display:inline-flex; align-items:center; justify-content:center; color:var(--phoenix-tertiary-color); background:var(--phoenix-secondary-bg-subtle); }
.cat-table th { padding:.6rem .75rem; white-space:nowrap; font-size:.75rem; letter-spacing:.04em; text-transform:uppercase; color:var(--phoenix-tertiary-color); border-bottom-width:1px; }
.cat-table td { padding:.35rem .75rem; vertical-align:middle; line-height:1.3; }
.cat-table tbody tr:hover > td { background:var(--phoenix-body-highlight-bg,rgba(0,0,0,.03)); }
.cat-actions { display:inline-flex; justify-content:flex-end; gap:.35rem; }
.cat-actions .btn,.cat-actions .cat-ph { width:1.75rem; height:1.75rem; padding:0; display:inline-flex; align-items:center; justify-content:center; font-size:.7rem; }
.cat-image-preview { display:block; width:10rem; height:7rem; max-width:100%; object-fit:contain; border:1px solid var(--phoenix-border-color); border-radius:.4rem; }
.cat-modal-layer { position:fixed; inset:0; z-index:1060; overflow-y:auto; background:rgba(0,0,0,.5); }
.cat-modal-layer .modal { overflow-y:auto; }
.cat-toast { position:fixed; top:5rem; left:50%; transform:translateX(-50%); z-index:2100; width:min(34rem,92vw); }
</style>
