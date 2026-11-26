import { createInertiaApp } from '@inertiajs/vue3'
import { createApp, h } from 'vue'
import '../css/portal-responsive.css'

// Vue pages rendered by Inertia.
const pages = {
  ...import.meta.glob('./Pages/Portal/Login.vue'),
  ...import.meta.glob('./Pages/Portal/Signup.vue'),
  ...import.meta.glob('./Pages/Portal/ModuleHost.vue'),
  ...import.meta.glob('./Pages/Portal/DashboardHost.vue'),
  ...import.meta.glob('./Pages/Portal/NewsManagement.vue'),
  ...import.meta.glob('./Pages/Portal/NewsEdit.vue'),
  ...import.meta.glob('./Pages/Admin/Login.vue'),
  ...import.meta.glob('./Pages/Admin/Dashboard.vue'),
  ...import.meta.glob('./Pages/Admin/Portals/Index.vue'),
  ...import.meta.glob('./Pages/Admin/Portals/Show.vue'),
  ...import.meta.glob('./Pages/Admin/Requests/Index.vue'),
  ...import.meta.glob('./Pages/Admin/Activity/Index.vue'),
  ...import.meta.glob('./Pages/Admin/Packages/Index.vue'),
}

const inertiaPage = document.querySelector('script[data-page][type="application/json"]')
const inertiaRootId = inertiaPage?.nextElementSibling?.id || 'app'

if (inertiaPage) createInertiaApp({
  id: inertiaRootId,
  resolve: (name) => {
    const page = pages[`./Pages/${name}.vue`]

    if (!page) {
      throw new Error(`Inertia page not found: ${name}`)
    }

    return page()
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})
