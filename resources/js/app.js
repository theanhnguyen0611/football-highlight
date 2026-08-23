import './bootstrap'
import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { HTML_LANG } from '@/composables/useSeo'

createInertiaApp({
    title: (title) => title ? `${title} | BolaReel` : 'BolaReel',
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue')
    ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
        // Blade-rendered SEO tags served initial HTML for crawlers.
        // After Vue mounts, <Head> manages them — remove blade copies to avoid stale duplicates on SPA navigation.
        setTimeout(() => {
            document.querySelectorAll('[data-blade-seo]').forEach(el => el.remove())
        }, 0)
    },
    progress: {
        color: '#ef4444',
    },
})

// Keep html[lang] and html[dir] in sync on SPA navigation
router.on('navigate', (event) => {
    const locale = event.detail.page.props.locale || 'en'
    document.documentElement.lang = HTML_LANG[locale] || locale
    document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr'
})
