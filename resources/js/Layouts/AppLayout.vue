<template>
    <div class="app">

        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-inner">
                <div class="topbar-left">
                    <button class="action-btn" @click.stop="showMegaMenu = !showMegaMenu">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <button class="action-btn" :class="{ active: showScore }" @click="toggleScore">
                        <svg v-if="showScore" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
                <Link :href="localePath('/')" class="logo">
                    <span class="logo-dot"></span>
                    <span class="logo-text">FootHighlight</span>
                </Link>
                <div class="topbar-right">
                    <div class="search-wrap">
                        <div class="search-box" :class="{ focused: searchFocused }">
                            <input v-model="searchQuery" type="text" :placeholder="t('nav.search')" class="search-input"
                                @focus="searchFocused = true; showSuggestions = true"
                                @blur="searchFocused = false; setTimeout(() => showSuggestions = false, 200)"
                                @keyup.enter="search" />
                            <button class="search-btn" @click="search">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </button>
                        </div>
                        <div v-if="showSuggestions && !searchQuery" class="suggestions">
                            <div class="suggestions-label">Popular Teams</div>
                            <Link v-for="team in popularTeams.slice(0,5)" :key="team.id" :href="localePath(`/team/${team.slug}`)" class="suggestion-row">
                                <div class="sug-avatar">{{ team.initials }}</div>
                                <span class="sug-name">{{ teamName(team) }}</span>
                                <span class="sug-count">{{ team.match_count }}</span>
                            </Link>
                        </div>
                    </div>
                    <button class="action-btn mobile-search-trigger" @click="showMobileSearch = true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </button>
                    <div class="lang-wrap" @click.stop="showLangMenu = !showLangMenu">
                        <button class="lang-btn">
                            <span>{{ currentLang.flag }}</span>
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div v-if="showLangMenu" class="lang-dropdown" @click.stop>
                            <div v-for="lang in languages" :key="lang.code" class="lang-item" :class="{ active: lang.code === currentLocale }" @click="switchLocale(lang.code)">
                                <span>{{ lang.flag }}</span><span>{{ lang.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- MOBILE SEARCH -->
        <Transition name="fade">
            <div v-if="showMobileSearch" class="mobile-overlay" @click.self="showMobileSearch = false">
                <div class="mobile-search-bar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2.5" stroke-linecap="round" style="flex-shrink:0"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input v-model="searchQuery" type="text" placeholder="Search matches..." class="mobile-search-input" @keyup.enter="search(); showMobileSearch = false" />
                    <button @click="showMobileSearch = false" class="mobile-close">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                    <button class="mobile-search-go" @click="search(); showMobileSearch = false">Search</button>
                </div>
            </div>
        </Transition>

        <!-- NAV: ẩn hoàn toàn khi scroll -->
        <nav class="nav">
            <!-- Desktop: logo tròn to -->
            <div class="nav-desktop hide-scroll">
                <div class="nav-inner">
                    <Link v-for="item in navLeagues" :key="item.slug"
                        :href="localePath(`/league/${item.slug}`)"
                        class="nav-big-item" :class="{ active: currentLeague === item.slug }">
                        <div class="nav-big-logo">
                            <img :src="item.logo" :alt="item.name" />
                        </div>
                        <span class="nav-big-name">{{ item.name }}</span>
                    </Link>
                </div>
            </div>
            <!-- Mobile: pills -->
            <div class="nav-mobile hide-scroll">
                <Link :href="localePath('/')" class="pill" :class="{ 'pill-active': !currentLeague }">All</Link>
                <Link v-for="item in navLeagues" :key="item.slug"
                    :href="localePath(`/league/${item.slug}`)"
                    class="pill" :class="{ 'pill-active': currentLeague === item.slug }">
                    <img :src="item.logo" class="pill-logo" />
                    <span>{{ item.abbr }}</span>
                </Link>
            </div>
        </nav>

        <!-- DRAWER -->
        <Transition name="fade">
            <div v-if="showMegaMenu" class="drawer-overlay" @click="showMegaMenu = false">
                <div class="drawer" @click.stop>
                    <div class="drawer-header">
                        <span class="drawer-title">Menu</span>
                        <button class="drawer-close" @click="showMegaMenu = false">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <div class="drawer-section">
                        <div class="drawer-label">Leagues</div>
                        <Link v-for="item in [...navLeagues, ...moreLeagues]" :key="item.slug" :href="localePath(`/league/${item.slug}`)" class="drawer-item" @click="showMegaMenu = false">
                            <div class="drawer-avatar"><img :src="item.logo" /></div>
                            <span>{{ item.name }}</span>
                        </Link>
                    </div>
                    <div class="drawer-section">
                        <div class="drawer-label">Popular Teams</div>
                        <Link v-for="team in popularTeams.slice(0,12)" :key="team.id" :href="localePath(`/team/${team.slug}`)" class="drawer-item" @click="showMegaMenu = false">
                            <div class="drawer-avatar">
                                <img v-if="team.logo_url" :src="team.logo_url" />
                                <span v-else>{{ team.initials }}</span>
                            </div>
                            <span>{{ teamName(team) }}</span>
                            <span class="drawer-count">{{ team.match_count }}</span>
                        </Link>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- MAIN -->
        <main class="main">
            <slot :show-score="showScore" />
        </main>

        <!-- FOOTER -->
        <footer class="footer">
            <div class="nav-inner">
                © {{ new Date().getFullYear() }} FootHighlight · All rights reserved
            </div>
        </footer>

    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useLocale } from '@/composables/useLocale'

const props = defineProps({
    leagues:       { type: Array,  default: () => [] },
    popularTeams:  { type: Array,  default: () => [] },
    currentLeague: { type: String, default: null },
    locale:        { type: String, default: 'en' },
})

const { t, teamName, leagueName, localePath } = useLocale(props.locale)

const searchQuery      = ref('')
const searchFocused    = ref(false)
const showSuggestions  = ref(false)
const showLangMenu     = ref(false)
const showMegaMenu     = ref(false)
const showMobileSearch = ref(false)
const currentLocale    = ref(props.locale || 'en')
const showScore        = ref(localStorage.getItem('showScore') === 'true')
const scrolled         = ref(false)

function toggleScore() {
    showScore.value = !showScore.value
    localStorage.setItem('showScore', showScore.value)
}

const navLeagues = [
    { slug: 'premier-league',        name: 'Premier League',   abbr: 'EPL', logo: '/storage/logos/leagues/premier-league.png',        dark: true },
    { slug: 'uefa-champions-league', name: 'Champions League', abbr: 'UCL', logo: '/storage/logos/leagues/uefa-champions-league.png', dark: true },
    { slug: 'la-liga',               name: 'La Liga',          abbr: 'LAL', logo: '/storage/logos/leagues/la-liga.png' },
    { slug: 'bundesliga',            name: 'Bundesliga',       abbr: 'BUN', logo: '/storage/logos/leagues/bundesliga.png' },
    { slug: 'serie-a',               name: 'Serie A',          abbr: 'SA',  logo: '/storage/logos/leagues/serie-a.png',               dark: true },
    { slug: 'ligue-1',               name: 'Ligue 1',          abbr: 'L1',  logo: '/storage/logos/leagues/ligue-1.png' },
    { slug: 'uefa-europa-league',    name: 'Europa League',    abbr: 'UEL', logo: '/storage/logos/leagues/uefa-europa-league.png',    dark: true },
    { slug: 'copa-america',          name: 'Copa América',     abbr: 'CA',  logo: '/storage/logos/leagues/copa-america.png' },
    { slug: 'euro-championship',     name: 'EURO',             abbr: 'EUR', logo: '/storage/logos/leagues/euro-championship.png' },
    { slug: 'world-cup',             name: 'World Cup',        abbr: 'WC',  logo: '/storage/logos/leagues/world-cup.png' },
    { slug: 'friendlies',            name: 'Friendlies',       abbr: 'FRI', logo: '/storage/logos/leagues/friendlies.png',            dark: true },
]

const moreLeagues = [
    { slug: 'concacaf-champions-league', name: 'CONCACAF CL',        abbr: 'CCL', logo: '/storage/logos/leagues/concacaf-champions-league.png' },
    { slug: 'major-league-soccer',       name: 'MLS',                abbr: 'MLS', logo: '/storage/logos/leagues/major-league-soccer.png' },
    { slug: 'super-lig',                 name: 'Süper Lig',          abbr: 'SL',  logo: '/storage/logos/leagues/super-lig.png' },
    { slug: 'liga-profesional-argentina',name: 'Liga Argentina',     abbr: 'LPA', logo: '/storage/logos/leagues/liga-profesional-argentina.png' },
    { slug: 'eredivisie',                name: 'Eredivisie',         abbr: 'ERE', logo: '/storage/logos/leagues/eredivisie.png' },
    { slug: 'liga-mx',                   name: 'Liga MX',            abbr: 'MX',  logo: '/storage/logos/leagues/liga-mx.png' },
    { slug: 'primeira-liga',             name: 'Primeira Liga',      abbr: 'PL',  logo: '/storage/logos/leagues/primeira-liga.png' },
    { slug: 'fa-cup',                    name: 'FA Cup',             abbr: 'FA',  logo: '/storage/logos/leagues/fa-cup.png' },
    { slug: 'dfb-pokal',                 name: 'DFB Pokal',          abbr: 'DFB', logo: '/storage/logos/leagues/dfb-pokal.png' },
    { slug: 'coupe-de-france',           name: 'Coupe de France',    abbr: 'CDF', logo: '/storage/logos/leagues/coupe-de-france.png' },
    { slug: 'copa-del-rey',              name: 'Copa del Rey',       abbr: 'CDR', logo: '/storage/logos/leagues/copa-del-rey.png' },
    { slug: 'coppa-italia',              name: 'Coppa Italia',       abbr: 'CI',  logo: '/storage/logos/leagues/coppa-italia.png' },
    { slug: 'uefa-nations-league',       name: 'UEFA Nations League',abbr: 'UNL', logo: '/storage/logos/leagues/uefa-nations-league.png' },
    { slug: 'jupiler-pro-league',        name: 'Jupiler Pro League', abbr: 'JPL', logo: '/storage/logos/leagues/jupiler-pro-league.png' },
    { slug: 'concacaf-gold-cup',         name: 'CONCACAF Gold Cup',  abbr: 'CGC', logo: '/storage/logos/leagues/concacaf-gold-cup.png' },
]

const languages = [
    { code: 'en', name: 'English',   flag: '🌐' },
    { code: 'es', name: 'Español',   flag: '🇪🇸' },
    { code: 'pt', name: 'Português', flag: '🇧🇷' },
    { code: 'ar', name: 'العربية',   flag: '🇸🇦' },
    { code: 'id', name: 'Indonesia', flag: '🇮🇩' },
    { code: 'bn', name: 'বাংলা',     flag: '🇧🇩' },
    { code: 'ja', name: '日本語',     flag: '🇯🇵' },
    { code: 'fr', name: 'Français',  flag: '🇫🇷' },
    { code: 'de', name: 'Deutsch',   flag: '🇩🇪' },
    { code: 'tr', name: 'Türkçe',    flag: '🇹🇷' },
    { code: 'sw', name: 'Kiswahili', flag: '🇰🇪' },
    { code: 'hi', name: 'हिन्दी',    flag: '🇮🇳' },
]

const currentLang = computed(() => languages.find(l => l.code === currentLocale.value) || languages[0])

function search() {
    if (searchQuery.value.trim()) {
        router.get(localePath('/'), { q: searchQuery.value })
        showSuggestions.value = false
        showMobileSearch.value = false
    }
}

function switchLocale(code) {
    currentLocale.value = code
    showLangMenu.value = false
    const path = window.location.pathname
    const cleanPath = path.replace(/^\/(es|pt|ar|id|bn|ja|fr|de|tr|sw|hi)/, '') || '/'
    router.visit(code === 'en' ? cleanPath : `/${code}${cleanPath}`)
}

function handleClickOutside() {
    showLangMenu.value = false
    showMegaMenu.value = false
    showSuggestions.value = false
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('scroll', () => { scrolled.value = window.scrollY > 60 }, { passive: true })
})
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<style>
*, *::before, *::after { box-sizing: border-box; }
a { text-decoration: none; color: inherit; }

.app { min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; background: #0f0f0f; color: #fff; font-family: system-ui, -apple-system, sans-serif; }

/* ── SINGLE WRAPPER — dùng cho tất cả sections ── */
.nav-inner {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 24px;
}
@media (max-width: 768px) { .nav-inner { padding: 0 16px; } }

/* ── TOPBAR ── */
.topbar { background: #0f0f0f; border-bottom: 0.5px solid #222; position: sticky; top: 0; z-index: 50; }
.topbar-inner { width: 100%; max-width: 1400px; margin: 0 auto; padding: 0 24px; height: 64px; display: flex; align-items: center; justify-content: space-between; gap: 12px; position: relative; }
@media (max-width: 768px) { .topbar-inner { padding: 0 16px; } }
.topbar-left { display: flex; align-items: center; gap: 8px; }
.topbar-right { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
.logo { display: flex; align-items: center; gap: 8px; position: absolute; left: 50%; transform: translateX(-50%); }
.logo-dot { width: 10px; height: 10px; border-radius: 50%; background: #ef4444; display: block; }
.logo-text { font-size: 20px; font-weight: 700; color: #fff; }

.action-btn { width: 52px; height: 52px; border-radius: 50%; background: none; border: none; color: #ef4444; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
.action-btn:hover { background: #2a2a2a; }
.action-btn.active { color: #ef4444; }
.mobile-search-trigger { display: none; flex-shrink: 0; }
@media (max-width: 640px) { .mobile-search-trigger { display: flex; } }

.search-wrap { position: relative; }
@media (max-width: 640px) { .search-wrap { display: none; } }
.search-box { display: flex; align-items: center; width: 260px; height: 38px; background: #181818; border: 1px solid #ef4444; border-radius: 40px; overflow: hidden; transition: border-color 0.15s; }
.search-box.focused { border-color: #666; }
.search-input { flex: 1; height: 100%; background: none; border: none; outline: none; color: #fff; font-size: 14px; padding: 0 14px; }
.search-input::placeholder { color: #aaa; }
.search-btn { width: 42px; height: 100%; background: #2a2a2a; border: none; border-left: 1px solid #333; color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; }
.search-btn:hover { background: #3a3a3a; }
.suggestions { position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: #212121; border: 0.5px solid #303030; border-radius: 12px; overflow: hidden; z-index: 100; box-shadow: 0 8px 24px rgba(0,0,0,0.6); }
.suggestions-label { font-size: 10px; font-weight: 700; color: #ef4444; text-transform: uppercase; letter-spacing: 0.6px; padding: 12px 16px 6px; border-left: 2px solid #ef4444; margin-left: 14px; }
.suggestion-row { display: flex; align-items: center; gap: 10px; padding: 9px 16px; transition: background 0.1s; }
.suggestion-row:hover { background: #2d2d2d; }
.sug-avatar { width: 28px; height: 28px; border-radius: 50%; background: #1a1a1a; border: 0.5px solid #333; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 700; color: #fff; flex-shrink: 0; }
.sug-name { font-size: 14px; color: #fff; }
.sug-count { font-size: 11px; color: #717171; margin-left: auto; }

.lang-wrap { position: relative; }
.lang-btn { display: flex; align-items: center; gap: 5px; background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 8px; padding: 0 12px; height: 40px; color: #fff; font-size: 28px; cursor: pointer; }
.lang-dropdown { position: absolute; top: calc(100% + 6px); right: 0; background: #212121; border: 0.5px solid #303030; border-radius: 10px; width: 170px; max-height: 320px; overflow-y: auto; z-index: 100; box-shadow: 0 8px 24px rgba(0,0,0,0.5); }
.lang-item { display: flex; align-items: center; gap: 10px; padding: 9px 14px; cursor: pointer; font-size: 13px; color: #fff; transition: background 0.1s; }
.lang-item:hover { background: #2a2a2a; }
.lang-item.active { color: #ef4444; background: #2a2a2a; }

.mobile-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 200; }
.mobile-search-bar { background: #181818; border-bottom: 0.5px solid #333; padding: 12px 16px; display: flex; align-items: center; gap: 12px; }
.mobile-search-input { flex: 1; background: none; border: none; outline: none; color: #fff; font-size: 16px; }
.mobile-search-input::placeholder { color: #666; }
.mobile-close { background: none; border: none; color: #aaa; cursor: pointer; display: flex; align-items: center; padding: 4px; }
.mobile-search-go { background: #ef4444; border: none; border-radius: 6px; color: #fff; font-size: 14px; font-weight: 600; padding: 8px 16px; cursor: pointer; flex-shrink: 0; }

/* ── NAV ── */
.nav { background: #0f0f0f; border-bottom: 0.5px solid #222; width: 100%; overflow: hidden; }

/* Desktop nav */
.nav-desktop { display: block; scrollbar-width: none; }
.nav-desktop::-webkit-scrollbar { display: none; }
.nav-desktop .nav-inner { display: flex; padding-top: 16px; padding-bottom: 16px; padding-left: 24px; padding-right: 24px; gap: 0; overflow-x: auto; scrollbar-width: none; }
.nav-desktop .nav-inner::-webkit-scrollbar { display: none; }
@media (max-width: 768px) { .nav-desktop { display: none; } }

.nav-big-item { display: flex; flex-direction: column; align-items: center; width: 150px; margin-right: 28px; gap: 8px; flex-shrink: 0; cursor: pointer; border-radius: 10px; transition: background 0.15s; padding: 8px 0; }
.nav-big-item:hover .nav-big-logo { box-shadow: 0 0 0 3px #ef4444; }
.nav-big-item.active .nav-big-logo { box-shadow: 0 0 0 3px #ef4444; }
.nav-big-logo { width: 76px; height: 76px; border-radius: 50%; background: #fff; border: 1px solid #e5e5e5; display: flex; align-items: center; justify-content: center; transition: box-shadow 0.2s; overflow: hidden; }
.nav-big-logo img { width: 54px; height: 54px; object-fit: contain; }
.nav-big-name { font-size: 14px; font-weight: 600; color: #fff; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; }
.nav-big-item.active .nav-big-name, .nav-big-item:hover .nav-big-name { color: #fff; font-weight: 700; }

/* Mobile nav */
.nav-mobile { display: none; overflow-x: auto; scrollbar-width: none; padding: 8px 16px; gap: 8px; width: 0; min-width: 100%; box-sizing: border-box; }
.nav-mobile::-webkit-scrollbar { display: none; }
@media (max-width: 768px) { .nav-mobile { display: flex; } }

.pill { display: flex; align-items: center; gap: 6px; padding: 6px 12px; height: 38px; border: 1px solid #2a2a2a; border-radius: 99px; font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.5); white-space: nowrap; flex-shrink: 0; transition: all 0.15s; }
.pill:hover { color: #fff; border-color: #444; }
.pill-active { background: #1a1a1a; border-color: #ef4444 !important; color: #fff !important; }
.pill-logo { width: 18px; height: 18px; object-fit: contain; background: #fff; border-radius: 50%; }

/* ── DRAWER ── */
.drawer-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 200; }
.drawer { position: fixed; top: 0; left: 0; bottom: 0; width: 300px; background: #141414; border-right: 0.5px solid #2a2a2a; overflow-y: auto; z-index: 201; display: flex; flex-direction: column; }
@media (max-width: 640px) { .drawer { width: 85vw; } }
.drawer-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 0.5px solid #222; }
.drawer-title { font-size: 16px; font-weight: 700; color: #fff; }
.drawer-close { background: none; border: none; color: #888; cursor: pointer; display: flex; }
.drawer-close:hover { color: #fff; }
.drawer-section { padding: 16px 0; border-bottom: 0.5px solid #1a1a1a; }
.drawer-section:last-child { border-bottom: none; }
.drawer-label { font-size: 10px; font-weight: 700; color: #ef4444; text-transform: uppercase; letter-spacing: 0.6px; padding: 0 20px 10px; border-left: 2px solid #ef4444; margin-left: 18px; }
.drawer-item { display: flex; align-items: center; gap: 12px; padding: 10px 20px; color: #e5e7eb; font-size: 14px; font-weight: 500; transition: background 0.1s; }
.drawer-item:hover { background: #1f1f1f; }
.drawer-avatar { width: 36px; height: 36px; border-radius: 50%; background: #fff; border: 0.5px solid #e5e5e5; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; }
.drawer-avatar img { width: 26px; height: 26px; object-fit: contain; }
.drawer-avatar span { font-size: 8px; font-weight: 700; color: #fff; }
.logo-fallback { font-size: 8px; font-weight: 700; color: #fff; }
.drawer-count { margin-left: auto; font-size: 11px; color: #6b7280; background: #1a1a1a; padding: 1px 7px; border-radius: 99px; }

/* ── MAIN / FOOTER ── */
.main { flex: 1; }
.footer { border-top: 0.5px solid #222; padding: 20px 0; text-align: center; font-size: 12px; color: #555; }

.hide-scroll::-webkit-scrollbar { display: none; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.fade-enter-active .drawer, .fade-leave-active .drawer { transition: transform 0.2s ease; }
.fade-enter-from .drawer, .fade-leave-to .drawer { transform: translateX(-100%); }
</style>