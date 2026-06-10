<template>
    <div style="min-height:100vh;display:flex;flex-direction:column;background:#0f0f0f;color:#fff;font-family:system-ui,-apple-system,sans-serif">

        <!-- Row 1: Logo + Search + Icons -->
        <div style="background:#0f0f0f;border-bottom:0.5px solid #222;padding:0 24px;display:flex;align-items:center;gap:16px;height:56px;position:sticky;top:0;z-index:50">

            <Link href="/" style="text-decoration:none;display:flex;align-items:center;gap:8px;flex-shrink:0">
                <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block"></span>
                <span style="color:#fff;font-size:18px;font-weight:700">FootHighlight</span>
            </Link>

            <!-- YouTube-style search -->
            <div style="flex:1;display:flex;justify-content:center">
                <div style="display:flex;align-items:center;width:100%;max-width:560px;height:40px;position:relative">
                    <div
                        style="flex:1;display:flex;align-items:center;background:#121212;border:1px solid #303030;border-right:none;border-radius:40px 0 0 40px;outline:none;height:100%;padding:0 20px;transition:border-color 0.15s"
                        :style="{ borderColor: searchFocused ? '#888' : '#303030' }"
                    >
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="t('nav.search')"
                            style="flex:1;background:none;border:none;outline:none;color:#fff;font-size:15px;width:100%"
                            @focus="searchFocused = true; showSuggestions = true"
                            @blur="searchFocused = false; setTimeout(() => showSuggestions = false, 200)"
                            @keyup.enter="search"
                        />
                    </div>
                    <button
                        @click="search"
                        style="width:64px;height:100%;background:#222;border:1px solid #303030;border-radius:0 40px 40px 0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;cursor:pointer;flex-shrink:0;transition:background 0.15s"
                        @mouseover="e => e.currentTarget.style.background='#3d3d3d'"
                        @mouseleave="e => e.currentTarget.style.background='#222'"
                    >
                        <i class="ti ti-search" aria-hidden="true"></i>
                    </button>

                    <!-- Suggestions -->
                    <div v-if="showSuggestions && !searchQuery" style="position:absolute;top:calc(100% + 6px);left:0;right:0;background:#212121;border:0.5px solid #303030;border-radius:12px;overflow:hidden;z-index:100;box-shadow:0 8px 24px rgba(0,0,0,0.6)">
                        <div style="font-size:10px;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:0.6px;padding:12px 16px 6px;border-left:2px solid #ef4444;margin-left:14px">Popular Teams</div>
                        <Link
                            v-for="team in popularTeams.slice(0,5)"
                            :key="team.id"
                            :href="localePath(`/team/${team.slug}`)"
                            style="display:flex;align-items:center;gap:10px;padding:9px 16px;text-decoration:none;transition:background 0.1s"
                            @mouseover="e => e.currentTarget.style.background='#2d2d2d'"
                            @mouseleave="e => e.currentTarget.style.background='transparent'"
                        >
                            <div style="width:28px;height:28px;border-radius:50%;background:#1a1a1a;border:0.5px solid #333;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;flex-shrink:0">{{ team.initials }}</div>
                            <span style="font-size:14px;font-weight:500;color:#fff">{{ teamName(team) }}</span>
                            <span style="font-size:11px;color:#717171;margin-left:auto">{{ team.match_count }}</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Icons -->
            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
                <button
                    @click="toggleScore"
                    :title="showScore ? 'Hide scores' : 'Show scores'"
                    :style="{ background: showScore ? '#ef4444' : '#1a1a1a', borderColor: showScore ? '#ef4444' : '#2a2a2a' }"
                    style="width:38px;height:38px;border-radius:50%;border:1px solid;display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;cursor:pointer;transition:all 0.15s;outline:none"
                >
                    <i :class="showScore ? 'ti ti-eye' : 'ti ti-eye-off'" aria-hidden="true"></i>
                </button>

                <!-- Language picker -->
                <div style="position:relative" @click.stop="showLangMenu = !showLangMenu">
                    <button style="display:flex;align-items:center;gap:6px;background:#1a1a1a;border:1px solid #2a2a2a;border-radius:8px;padding:0 12px;height:38px;color:#fff;font-size:13px;font-weight:600;cursor:pointer">
                        <span style="font-size:15px">{{ currentLang.flag }}</span>
                        {{ currentLang.code.toUpperCase() }}
                        <i class="ti ti-chevron-down" style="font-size:11px;color:#666" aria-hidden="true"></i>
                    </button>
                    <div v-if="showLangMenu" style="position:absolute;top:calc(100% + 6px);right:0;background:#212121;border:0.5px solid #303030;border-radius:10px;width:170px;max-height:320px;overflow-y:auto;z-index:100;box-shadow:0 8px 24px rgba(0,0,0,0.5)" @click.stop>
                        <div
                            v-for="lang in languages"
                            :key="lang.code"
                            @click="switchLocale(lang.code)"
                            style="display:flex;align-items:center;gap:10px;padding:9px 14px;cursor:pointer;transition:background 0.1s"
                            :style="{ background: lang.code === currentLocale ? '#2a2a2a' : 'transparent' }"
                            @mouseover="e => e.currentTarget.style.background='#2a2a2a'"
                            @mouseleave="e => e.currentTarget.style.background = lang.code === currentLocale ? '#2a2a2a' : 'transparent'"
                        >
                            <span style="font-size:16px">{{ lang.flag }}</span>
                            <span style="font-size:13px;font-weight:500" :style="{ color: lang.code === currentLocale ? '#ef4444' : '#fff' }">{{ lang.name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: League tabs -->
        <nav style="background:#0f0f0f;border-bottom:0.5px solid #222;position:sticky;top:56px;z-index:49">
            <div style="display:flex;align-items:stretch;padding:0 24px;overflow-x:auto;scrollbar-width:none" class="hide-scroll">
                <Link :href="localePath('/')" :class="['ltab', !currentLeague ? 'ltab-active' : '']">
                    <i class="ti ti-layout-grid" style="font-size:15px" aria-hidden="true"></i> All
                </Link>
                <div class="vdiv"></div>
                <Link :href="localePath('/league/premier-league')" :class="['ltab', currentLeague === 'premier-league' ? 'ltab-active' : '']">🏴󠁧󠁢󠁥󠁮󠁧󠁿 Premier League</Link>
                <Link :href="localePath('/league/la-liga')" :class="['ltab', currentLeague === 'la-liga' ? 'ltab-active' : '']">🇪🇸 La Liga</Link>
                <Link :href="localePath('/league/bundesliga')" :class="['ltab', currentLeague === 'bundesliga' ? 'ltab-active' : '']">🇩🇪 Bundesliga</Link>
                <Link :href="localePath('/league/serie-a')" :class="['ltab', currentLeague === 'serie-a' ? 'ltab-active' : '']">🇮🇹 Serie A</Link>
                <Link :href="localePath('/league/ligue-1')" :class="['ltab', currentLeague === 'ligue-1' ? 'ltab-active' : '']">🇫🇷 Ligue 1</Link>
                <div class="vdiv"></div>
                <Link :href="localePath('/league/champions-league')" :class="['ltab', currentLeague === 'champions-league' ? 'ltab-active' : '']">⭐ Champions League</Link>
                <Link :href="localePath('/league/europa-league')" :class="['ltab', currentLeague === 'europa-league' ? 'ltab-active' : '']">🟠 Europa League</Link>
                <div class="vdiv"></div>
                <Link :href="localePath('/league/world-cup')" :class="['ltab', currentLeague === 'world-cup' ? 'ltab-active' : '']">🏆 World Cup</Link>
                <Link :href="localePath('/league/euro')" :class="['ltab', currentLeague === 'euro' ? 'ltab-active' : '']">🌍 EURO</Link>
                <Link :href="localePath('/league/copa-america')" :class="['ltab', currentLeague === 'copa-america' ? 'ltab-active' : '']">🌎 Copa América</Link>
                <template v-if="dynamicLeagues.length">
                    <div class="vdiv"></div>
                    <Link v-for="league in dynamicLeagues" :key="league.league_slug" :href="localePath(`/league/${league.league_slug}`)" :class="['ltab', currentLeague === league.league_slug ? 'ltab-active' : '']">
                        ⚽ {{ leagueName(league.league) }}
                    </Link>
                </template>
                <button class="more-tab" @click.stop="showMegaMenu = !showMegaMenu">
                    More <i class="ti ti-chevron-down" :style="{ transform: showMegaMenu ? 'rotate(180deg)' : '' }" style="font-size:13px;transition:transform 0.2s" aria-hidden="true"></i>
                </button>
            </div>

            <!-- Mega menu -->
            <Transition name="slide">
                <div v-if="showMegaMenu" style="background:#161616;border-top:0.5px solid #222;padding:20px 24px;display:grid;grid-template-columns:1fr 1fr;gap:32px" @click.stop>
                    <div>
                        <div style="font-size:10px;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:0.6px;border-left:2px solid #ef4444;padding-left:8px;margin-bottom:12px">More Leagues</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:2px">
                            <Link v-for="item in moreLeagues" :key="item.slug" :href="localePath(`/league/${item.slug}`)" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:6px;text-decoration:none;color:#fff;font-size:13px;font-weight:500;transition:background 0.1s" @mouseover="e=>e.currentTarget.style.background='#222'" @mouseleave="e=>e.currentTarget.style.background='transparent'">
                                <span style="font-size:15px">{{ item.flag }}</span> {{ item.name }}
                            </Link>
                        </div>
                    </div>
                    <div>
                        <div style="font-size:10px;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:0.6px;border-left:2px solid #ef4444;padding-left:8px;margin-bottom:12px">Popular Teams</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:2px">
                            <Link v-for="team in popularTeams.slice(0,9)" :key="team.id" :href="localePath(`/team/${team.slug}`)" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:6px;text-decoration:none;color:#fff;font-size:13px;font-weight:500;transition:background 0.1s" @mouseover="e=>e.currentTarget.style.background='#222'" @mouseleave="e=>e.currentTarget.style.background='transparent'">
                                <div style="width:24px;height:24px;border-radius:50%;background:#2a2a2a;display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;flex-shrink:0;color:#fff">{{ team.initials }}</div>
                                {{ teamName(team) }}
                            </Link>
                        </div>
                    </div>
                </div>
            </Transition>
        </nav>

        <!-- Provide showScore to child pages -->
        <main style="flex:1">
            <slot :show-score="showScore" />
        </main>

        <footer style="background:#0f0f0f;border-top:0.5px solid #222;padding:20px 24px;text-align:center">
            <p style="font-size:12px;color:#555">© {{ new Date().getFullYear() }} FootHighlight · All rights reserved</p>
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

const searchQuery    = ref('')
const searchFocused  = ref(false)
const showSuggestions = ref(false)
const showLangMenu   = ref(false)
const showMegaMenu   = ref(false)
const currentLocale  = ref(props.locale || 'en')

// Score toggle — persist in localStorage
const showScore = ref(localStorage.getItem('showScore') === 'true')
function toggleScore() {
    showScore.value = !showScore.value
    localStorage.setItem('showScore', showScore.value)
}

const fixedSlugs = new Set([
    'premier-league','la-liga','bundesliga','serie-a','ligue-1',
    'champions-league','europa-league','world-cup','euro','copa-america',
])

const dynamicLeagues = computed(() =>
    props.leagues.filter(l => !fixedSlugs.has(l.league_slug))
)

const moreLeagues = [
    { slug: 'conference-league', name: 'Conference League', flag: '🟢' },
    { slug: 'mls',               name: 'MLS',               flag: '🇺🇸' },
    { slug: 'saudi-pro-league',  name: 'Saudi Pro League',  flag: '🇸🇦' },
    { slug: 'brasileirao',       name: 'Brasileirão',       flag: '🇧🇷' },
    { slug: 'eredivisie',        name: 'Eredivisie',        flag: '🇳🇱' },
    { slug: 'liga-mx',           name: 'Liga MX',           flag: '🇲🇽' },
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

const currentLang = computed(() =>
    languages.find(l => l.code === currentLocale.value) || languages[0]
)

function search() {
    if (searchQuery.value.trim()) {
        router.get(localePath('/'), { q: searchQuery.value })
        showSuggestions.value = false
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

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<style scoped>
.hide-scroll::-webkit-scrollbar { display: none; }

.ltab {
    display: flex; align-items: center; gap: 6px;
    padding: 0 14px; height: 44px;
    font-size: 13px; font-weight: 600; color: #fff;
    white-space: nowrap; text-decoration: none;
    border-bottom: 2px solid transparent;
    flex-shrink: 0; opacity: 0.55;
    transition: opacity 0.15s; cursor: pointer;
}
.ltab:hover { opacity: 0.85; }
.ltab-active { opacity: 1 !important; border-bottom-color: #ef4444; }

.vdiv { width: 0.5px; background: #2a2a2a; margin: 10px 6px; flex-shrink: 0; }

.more-tab {
    display: flex; align-items: center; gap: 5px;
    padding: 0 14px; height: 44px;
    font-size: 13px; font-weight: 600; color: #fff;
    white-space: nowrap; background: none; border: none;
    border-left: 0.5px solid #2a2a2a; margin-left: 4px;
    cursor: pointer; flex-shrink: 0; opacity: 0.55;
    transition: opacity 0.15s;
}
.more-tab:hover { opacity: 1; }

.slide-enter-active, .slide-leave-active { transition: opacity 0.15s, transform 0.15s; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
