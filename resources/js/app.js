import { createInertiaApp, router } from '@inertiajs/vue3'
import { createApp, h } from 'vue'
import '../css/portal-responsive.css'

// Vue pages rendered by Inertia.
const pages = {
  ...import.meta.glob('./Pages/Portal/Login.vue'),
  ...import.meta.glob('./Pages/Portal/Signup.vue'),
  ...import.meta.glob('./Pages/Portal/Categories.vue'),
  ...import.meta.glob('./Pages/Portal/NewsEntry.vue'),
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

// Keep the Phoenix shell mounted when a plain internal link is used inside either
// panel. Inertia <Link> components already prevent the native click themselves.
document.addEventListener('click', (event) => {
  if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return

  const link = event.target.closest('a[href]')
  if (!link || link.target && link.target !== '_self' || link.hasAttribute('download')) return

  const url = new URL(link.href, window.location.href)
  if (url.origin !== window.location.origin || url.hash && url.pathname === window.location.pathname) return

  const isAdminPage = /^\/admin(?:\/|$)/.test(url.pathname)
  const isPortalPage = url.pathname === '/' || /^\/(?:dashboard|categories|news)(?:\/|$)/.test(url.pathname)
  if (!isAdminPage && !isPortalPage) return

  event.preventDefault()
  router.visit(url.href)
})

// Portal navigation is rendered by the Blade shell, so update its active state
// after an Inertia visit without rebuilding the shell or its icons.
function updatePortalModuleShell(path) {
  if (!document.getElementById('portal-inertia')) return
  const isModule = /^\/(?:categories|news)(?:\/|$)/.test(path)
  document.documentElement.classList.toggle('portal-module-page', isModule)
}

updatePortalModuleShell(window.location.pathname)

router.on('navigate', (event) => {
  const path = new URL(event.detail.page.url, window.location.origin).pathname
  updatePortalModuleShell(path)

  const nav = document.getElementById('navbarVerticalNav')
  if (!nav || !document.getElementById('portal-inertia')) return

  const categoryActive = path.startsWith('/categories')

  nav.querySelectorAll('a.nav-link').forEach((link) => {
    const url = new URL(link.href, window.location.origin)
    if (url.origin !== window.location.origin || url.hash) return

    const target = url.pathname
    const active = target === '/' || target === '/dashboard'
      ? path === '/' || path === '/dashboard' || path === '/dashboard/project-management'
      : target === '/categories'
        ? categoryActive
        : target === '/news/create'
          ? path === '/news/create'
          : target === '/news'
            ? path === '/news' || /^\/news\/\d+(?:\/edit)?$/.test(path)
            : false
    link.classList.toggle('active', active)
  })

  const basicLink = nav.querySelector('a[href="#portal-basic-setup"]')
  const categoryMenu = nav.querySelector('#portal-basic-setup')
  if (basicLink) {
    basicLink.classList.toggle('active', categoryActive)
    basicLink.setAttribute('aria-expanded', String(categoryActive))
  }
  categoryMenu?.classList.toggle('show', categoryActive)
})
