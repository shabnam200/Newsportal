<script setup>
import { computed, onMounted } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Icon from '../../Components/Icon.vue'

const props = defineProps({
  newsId: { type: Number, required: true }, article: { type: Object, required: true }, sections: { type: Array, required: true },
  categories: { type: Array, required: true }, newsTypes: { type: Array, required: true }, statusOptions: { type: Object, required: true },
  updateUrl: { type: String, required: true }, managementUrl: { type: String, required: true },
})

const page = usePage()
const form = useForm({
  ...props.article,
  sections: props.sections.map((section) => ({ ...section })),
})

const topCategories = computed(() => props.categories.filter((category) => Number(category.level) === 1))
const subCategories = computed(() => props.categories.filter((category) => Number(category.level) === 2 && Number(category.parent_id) === Number(form.cat_id)))
const subSubCategories = computed(() => props.categories.filter((category) => Number(category.level) === 3 && Number(category.parent_id) === Number(form.cat_child_1)))

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

function changeCategory() {
  form.cat_child_1 = ''
  form.cat_child_2 = ''
}

function changeSubCategory() {
  form.cat_child_2 = ''
}

function saveSectionText(section, event) {
  section.text = event.currentTarget.innerHTML
}

function formatText(command) {
  document.execCommand(command, false)
}

function submit() {
  form.put(props.updateUrl, { preserveScroll: true })
}
</script>

<template>
  <Head :title="`Edit news #${newsId}`" />
  <div class="news-edit-page">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div><h2 class="mb-2 text-body-emphasis">Edit news</h2><h5 class="text-body-tertiary fw-semibold mb-0">Story #{{ newsId }} · media files are preserved while you edit the text and story details.</h5></div>
      <a class="btn btn-phoenix-secondary" :href="managementUrl"><Icon name="arrow-left" class="me-2" />Back to news</a>
    </div>

    <div v-if="page.props.flash?.success" class="alert alert-subtle-success">{{ page.props.flash.success }}</div>
    <div v-if="Object.keys(form.errors).length" class="alert alert-subtle-danger" role="alert"><div v-for="(message, field) in form.errors" :key="field">{{ message }}</div></div>

    <form @submit.prevent="submit">
      <div class="card mb-3"><div class="card-header"><h4 class="card-title mb-0">Category</h4></div><div class="card-body"><div class="row g-3">
        <div class="col-md-4"><label class="form-label">Category <span class="text-danger">*</span></label><select v-model="form.cat_id" class="form-select" name="cat_id" required @change="changeCategory"><option value="">Choose category</option><option v-for="category in topCategories" :key="category.id" :value="category.id">{{ category.name }}{{ !category.status ? ' (inactive)' : '' }}</option></select><div v-if="form.errors.cat_id" class="invalid-feedback d-block">{{ form.errors.cat_id }}</div></div>
        <div class="col-md-4"><label class="form-label">Sub category</label><select v-model="form.cat_child_1" class="form-select" name="cat_child_1" :disabled="!subCategories.length" @change="changeSubCategory"><option value="">Choose sub category</option><option v-for="category in subCategories" :key="category.id" :value="category.id">{{ category.name }}{{ !category.status ? ' (inactive)' : '' }}</option></select><div v-if="form.errors.cat_child_1" class="invalid-feedback d-block">{{ form.errors.cat_child_1 }}</div></div>
        <div class="col-md-4"><label class="form-label">Sub-sub category</label><select v-model="form.cat_child_2" class="form-select" name="cat_child_2" :disabled="!subSubCategories.length"><option value="">Choose sub-sub category</option><option v-for="category in subSubCategories" :key="category.id" :value="category.id">{{ category.name }}{{ !category.status ? ' (inactive)' : '' }}</option></select><div v-if="form.errors.cat_child_2" class="invalid-feedback d-block">{{ form.errors.cat_child_2 }}</div></div>
      </div></div></div>

      <div class="card mb-3"><div class="card-header"><h4 class="card-title mb-0">News info</h4></div><div class="card-body"><div class="row g-3">
        <div class="col-md-4"><label class="form-label">News status</label><select v-model="form.news_status" class="form-select" name="news_status"><option v-for="(label, value) in statusOptions" :key="value" :value="Number(value)">{{ label }}</option></select><div v-if="form.errors.news_status" class="invalid-feedback d-block">{{ form.errors.news_status }}</div></div>
        <div class="col-md-4"><label class="form-label">News type</label><select v-model="form.news_type" class="form-select" name="news_type"><option v-for="type in newsTypes" :key="type.news_type_id" :value="Number(type.news_type_id)">{{ type.type_name }}{{ !type.type_status ? ' (inactive)' : '' }}</option></select><div v-if="form.errors.news_type" class="invalid-feedback d-block">{{ form.errors.news_type }}</div></div>
        <div class="col-md-4"><label class="form-label">News caption</label><input v-model="form.headline_tag" class="form-control" name="headline_tag" maxlength="100"></div>
        <div class="col-12"><label class="form-label">Heading <span class="text-danger">*</span></label><input v-model="form.news_headline" class="form-control" name="news_headline" maxlength="250" required><div v-if="form.errors.news_headline" class="invalid-feedback d-block">{{ form.errors.news_headline }}</div></div>
        <div class="col-12"><label class="form-label">Sub heading</label><input v-model="form.news_sub_headline" class="form-control" name="news_sub_headline" maxlength="400"></div>
        <div class="col-12"><label class="form-label">Brief</label><textarea v-model="form.news_details_brief" class="form-control" name="news_details_brief" maxlength="400" rows="3"></textarea></div>
      </div></div></div>

      <div class="card mb-3"><div class="card-header"><h4 class="card-title mb-0">News details</h4></div><div class="card-body"><div v-for="(section, index) in form.sections" :key="section.section_no" class="news-section-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h5 class="mb-0">Para {{ index + 1 }}</h5><span v-if="section.media_type === 1" class="badge badge-phoenix badge-phoenix-info">Image section</span><span v-else-if="section.media_type === 2" class="badge badge-phoenix badge-phoenix-primary">Video section</span><span v-else class="badge badge-phoenix badge-phoenix-secondary">Text section</span></div>
        <div v-if="section.media_url" class="mb-3"><img class="news-edit-media-preview" :src="section.media_url" alt="News media"><div class="form-text">Existing image is retained. Contact image sections are not replaced from this page.</div></div>
        <div v-else-if="section.video_link" class="mb-3"><a :href="section.video_link" target="_blank" rel="noopener">{{ section.video_link }}</a><div class="form-text">Existing video link is retained.</div></div>
        <div v-if="section.media_type !== 0" class="mb-3"><label class="form-label">Media caption</label><input v-model="section.caption" class="form-control" maxlength="300"></div>
        <label class="form-label">Section text</label>
        <div class="btn-toolbar mb-2" role="toolbar" aria-label="Text formatting"><div class="btn-group btn-group-sm"><button class="btn btn-phoenix-secondary" type="button" title="Bold" @mousedown.prevent @click="formatText('bold')"><b>B</b></button><button class="btn btn-phoenix-secondary" type="button" title="Italic" @mousedown.prevent @click="formatText('italic')"><i>I</i></button><button class="btn btn-phoenix-secondary" type="button" title="Underline" @mousedown.prevent @click="formatText('underline')"><u>U</u></button><button class="btn btn-phoenix-secondary" type="button" title="Bulleted list" @mousedown.prevent @click="formatText('insertUnorderedList')">• List</button></div></div>
        <div class="form-control news-rich-editor" contenteditable="true" :aria-label="`News section ${index + 1} text`" :data-section="section.section_no" v-html="section.text" @blur="saveSectionText(section, $event)"></div>
        <div v-if="form.errors[`sections.${index}.text`]" class="invalid-feedback d-block">{{ form.errors[`sections.${index}.text`] }}</div>
      </div><div v-if="form.errors.sections" class="invalid-feedback d-block">{{ form.errors.sections }}</div></div></div>

      <div class="card mb-3"><div class="card-header"><h4 class="card-title mb-0">Tags</h4></div><div class="card-body"><label class="form-label">Tags</label><input v-model="form.tags" class="form-control" name="tags" placeholder="Separate tags with commas"><div class="form-text">Up to 20 tags, 250 characters each.</div></div></div>
      <div class="card mb-3"><div class="card-header"><h4 class="card-title mb-0">Reporter</h4></div><div class="card-body"><label class="form-label">Reporter names</label><input v-model="form.reporter_names" class="form-control" name="reporter_names" maxlength="1000" placeholder="Separate names with commas"><div class="form-text">The names are kept on this portal's news record.</div><div v-if="form.errors.reporter_names" class="invalid-feedback d-block">{{ form.errors.reporter_names }}</div></div></div>
      <div class="card mb-3"><div class="card-header"><h4 class="card-title mb-0">News source</h4></div><div class="card-body row g-3"><div class="col-md-6"><label class="form-label">Source</label><input v-model="form.news_source" class="form-control" name="news_source" maxlength="150"></div><div class="col-md-6"><label class="form-label">Source link</label><input v-model="form.news_source_link" class="form-control" name="news_source_link" type="url" maxlength="500"><div v-if="form.errors.news_source_link" class="invalid-feedback d-block">{{ form.errors.news_source_link }}</div></div></div></div>
      <div class="card mb-4"><div class="card-header"><h4 class="card-title mb-0">SEO</h4></div><div class="card-body row g-3"><div class="col-12"><label class="form-label">SEO title</label><input v-model="form.seo_title" class="form-control" name="seo_title" maxlength="250"></div><div class="col-12"><label class="form-label">SEO keywords</label><input v-model="form.seo_keyword" class="form-control" name="seo_keyword" maxlength="350"></div><div class="col-12"><label class="form-label">SEO description</label><textarea v-model="form.seo_description" class="form-control" name="seo_description" maxlength="400" rows="3"></textarea></div></div></div>

      <div class="d-grid d-sm-flex justify-content-sm-end gap-2 mb-4"><a class="btn btn-phoenix-secondary" :href="managementUrl">Cancel</a><button class="btn btn-primary" type="submit" :disabled="form.processing"><span v-if="form.processing" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Save changes</button></div>
    </form>
  </div>
</template>

<style scoped>
.news-section-card { border: 1px solid var(--phoenix-border-color); border-radius: .5rem; padding: 1rem; margin-bottom: 1rem; }
.news-section-card:last-child { margin-bottom: 0; }
.news-rich-editor { overflow-wrap: anywhere; }
.btn-toolbar { overflow-x: auto; flex-wrap: nowrap; }
@media (max-width: 575.98px) { .news-section-card { padding: .75rem; } }
.news-edit-media-preview { max-width: min(100%, 24rem); max-height: 16rem; object-fit: contain; border-radius: .4rem; background: var(--phoenix-secondary-bg); }
.news-rich-editor { min-height: 9rem; overflow-y: auto; }
.news-rich-editor:focus { border-color: var(--phoenix-primary); box-shadow: 0 0 0 .15rem rgba(var(--phoenix-primary-rgb), .15); outline: 0; }
</style>
