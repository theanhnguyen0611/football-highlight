<template>
    <Head>
        <title>{{ seo.title }}</title>
        <meta name="description" :content="seo.description" />
        <link rel="canonical" :href="seo.canonical" />
        <meta property="og:site_name" content="BolaReel" />
        <meta property="og:locale" :content="seo.ogLocale" />
        <meta v-for="l in seo.ogLocaleAlternates" :key="l" property="og:locale:alternate" :content="l" />
        <meta property="og:title" :content="seo.fullTitle" />
        <meta property="og:description" :content="seo.description" />
        <meta property="og:url" :content="seo.canonical" />
        <meta property="og:type" :content="seo.ogType || 'video.other'" />
        <meta v-if="seo.image" property="og:image" :content="seo.image" />
        <meta v-if="seo.image" property="og:image:alt" :content="seo.title" />
        <meta name="twitter:card" content="player" />
        <meta name="twitter:title" :content="seo.fullTitle" />
        <meta name="twitter:description" :content="seo.description" />
        <meta v-if="seo.image" name="twitter:image" :content="seo.image" />
        <link v-for="alt in seo.alternates" :key="alt.hreflang" rel="alternate" :hreflang="alt.hreflang" :href="alt.href" />
    </Head>
    <AppLayout
        :leagues="leagues"
        :popular-teams="popular_teams"
        :current-league="match.league?.slug"
        :locale="locale"
    >
        <div class="match-wrap">

            <!-- H1 ẩn cho SEO: tên đội hiển thị bằng span trong layout tỉ số,
                 không đổi cấu trúc/CSS đó. seo.title đã localize đầy đủ theo
                 từng trận (đội, tỉ số, giải, vòng đấu, ngày giờ). -->
            <h1 class="sr-only">{{ seo.title }}</h1>

            <div class="match-layout">

                <!-- MAIN COLUMN -->
                <div class="main-col">

                    <!-- Video Player -->
                    <div class="player-wrap">
                        <div v-if="!hasVideo" class="player-placeholder">
                            <div class="ph-teams">
                                <div class="ph-logo">
                                    <img v-if="match.home_team?.logo_url" :src="match.home_team.logo_url" :alt="teamName(match.home_team)" />
                                    <span v-else>{{ match.home_team?.initials }}</span>
                                </div>
                                <span class="ph-vs">VS</span>
                                <div class="ph-logo">
                                    <img v-if="match.away_team?.logo_url" :src="match.away_team.logo_url" :alt="teamName(match.away_team)" />
                                    <span v-else>{{ match.away_team?.initials }}</span>
                                </div>
                            </div>
                            <p class="ph-label">{{ t('match.no_video') }}</p>
                        </div>

                        <div v-show="hasVideo" class="plyr-container">
                            <video ref="videoEl" playsinline></video>
                        </div>

                        <!-- Poster mặc định (không dùng thumbnail thật vì hay lộ tỉ số) -->
                        <div v-if="hasVideo && !hasStarted" class="player-placeholder">
                            <div class="ph-teams">
                                <div class="ph-logo">
                                    <img v-if="match.home_team?.logo_url" :src="match.home_team.logo_url" :alt="teamName(match.home_team)" />
                                    <span v-else>{{ match.home_team?.initials }}</span>
                                </div>
                                <span class="ph-vs">VS</span>
                                <div class="ph-logo">
                                    <img v-if="match.away_team?.logo_url" :src="match.away_team.logo_url" :alt="teamName(match.away_team)" />
                                    <span v-else>{{ match.away_team?.initials }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Click overlay: toggle play/pause -->
                        <div v-if="hasVideo" class="player-click-area"
                            @click="onVideoClick"
                            @dblclick="onVideoDblClick"
                            @mousemove="onOverlayMouseMove"
                            @mouseleave="onOverlayMouseLeave"
                        >
                            <!-- Nút play mặc định — luôn hiện khi paused -->
                            <transition name="idle-play">
                                <div v-if="!isPlaying && !flashVisible" class="center-play-btn">
                                    <svg viewBox="0 0 24 24">
                                        <polygon points="6,3 20,12 6,21"/>
                                    </svg>
                                </div>
                            </transition>

                            <!-- Flash icon khi vừa click -->
                            <transition name="icon-flash">
                                <div v-if="flashVisible" class="flash-icon">
                                    <svg v-if="flashIcon === 'pause'" viewBox="0 0 24 24" fill="white">
                                        <rect x="5" y="3" width="4" height="18" rx="1"/>
                                        <rect x="15" y="3" width="4" height="18" rx="1"/>
                                    </svg>
                                    <svg v-else viewBox="0 0 24 24" fill="white">
                                        <polygon points="6,3 20,12 6,21"/>
                                    </svg>
                                </div>
                            </transition>
                        </div>
                    </div>

                    <!-- Source selector -->
                    <div v-if="playableVideos.length > 0" class="src-bar">
                        <button
                            v-for="v in highlightVideos" :key="v.id"
                            class="src-btn"
                            :class="{ active: activeVideoIdx === videoIndexMap.get(v.id) }"
                            @click="switchVideo(videoIndexMap.get(v.id))"
                        >
                            <span class="src-dot hl-dot"></span>
                            {{ highlightLabel(v) }}
                            <span v-if="v.duration_seconds" class="src-dur">{{ fmtDuration(v.duration_seconds) }}</span>
                        </button>
                        <button
                            v-for="v in fullMatchVideos" :key="v.id"
                            class="src-btn fm-btn"
                            :class="{ active: activeVideoIdx === videoIndexMap.get(v.id) }"
                            @click="switchVideo(videoIndexMap.get(v.id))"
                        >
                            <span class="src-dot fm-dot"></span>
                            {{ t('match.video_full_match') }}
                            <span v-if="v.duration_seconds" class="src-dur">{{ fmtDuration(v.duration_seconds) }}</span>
                        </button>
                    </div>

                    <!-- Match Info Card -->
                    <div class="info-card">
                        <div class="info-top">
                            <Link v-if="match.league?.slug" class="info-league" :href="localePath(`/league/${match.league.slug}`)">
                                <img v-if="match.league?.logo_url" :src="match.league.logo_url" class="lg-logo" :alt="leagueName(match.league)" />
                                <span class="lg-name">{{ leagueName(match.league) }}</span>
                                <span v-if="match.round" class="badge-round">{{ formatRound(match.round) }}</span>
                            </Link>
                            <div v-else class="info-league">
                                <img v-if="match.league?.logo_url" :src="match.league.logo_url" class="lg-logo" :alt="leagueName(match.league)" />
                                <span class="lg-name">{{ leagueName(match.league) }}</span>
                                <span v-if="match.round" class="badge-round">{{ formatRound(match.round) }}</span>
                            </div>
                            <div class="info-date">
                                <span>{{ formatDate(match.match_date, match.kick_off_time) }}</span>
                            </div>
                        </div>

                        <div class="teams-row">
                            <Link class="team-col" :href="localePath(`/team/${match.home_team?.slug}`)">
                                <div class="team-logo">
                                    <img v-if="match.home_team?.logo_url" :src="match.home_team.logo_url" :alt="teamName(match.home_team)" />
                                    <span v-else>{{ match.home_team?.initials }}</span>
                                </div>
                                <span class="team-name">{{ teamName(match.home_team) }}</span>
                            </Link>

                            <div class="score-col">
                                <div v-if="scoreVisible" class="score-nums">
                                    <span>{{ match.home_score ?? '?' }}</span>
                                    <span class="score-dash">–</span>
                                    <span>{{ match.away_score ?? '?' }}</span>
                                </div>
                                <button v-else class="show-score-btn" @click="scoreVisible = true">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    {{ t('match.show_score') }}
                                </button>
                                <span class="ft-label">{{ t('match.full_time') }}</span>
                                <span v-if="scoreVisible && match.score_penalties" class="pen-label">
                                    (Pen: {{ match.score_penalties }})
                                </span>
                            </div>

                            <Link class="team-col" :href="localePath(`/team/${match.away_team?.slug}`)">
                                <div class="team-logo">
                                    <img v-if="match.away_team?.logo_url" :src="match.away_team.logo_url" :alt="teamName(match.away_team)" />
                                    <span v-else>{{ match.away_team?.initials }}</span>
                                </div>
                                <span class="team-name">{{ teamName(match.away_team) }}</span>
                            </Link>
                        </div>

                        <div class="meta-row">
                            <div v-if="match.venue" class="meta-item">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                {{ match.venue }}
                            </div>
                            <div v-if="match.referee" class="meta-item">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                                {{ match.referee }}
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="tabs-bar">
                        <button v-for="tab in tabs" :key="tab.key"
                            class="tab-btn" :class="{ active: activeTab === tab.key }"
                            @click="activeTab = tab.key"
                        >{{ tab.label }}</button>
                    </div>

                    <!-- Events Tab -->
                    <div v-if="activeTab === 'events'" class="tab-panel">
                        <!-- Spoiler lock -->
                        <div v-if="!scoreVisible" class="spoiler-lock">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <p>{{ t('match.reveal_hint') }}</p>
                            <button class="show-score-btn" @click="scoreVisible = true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                {{ t('match.show_score_events') }}
                            </button>
                        </div>

                        <template v-else>
                            <div v-if="!match.events?.length" class="empty-tab">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#444" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                {{ t('match.no_events') }}
                            </div>
                            <div v-for="event in match.events" :key="event.id" class="event-row">
                                <!-- Home side -->
                                <div class="event-side event-home">
                                    <template v-if="event.team_id === match.home_team?.id">
                                        <div class="event-info">
                                            <span class="event-player">
                                                <span v-if="event.type === 'subst'" class="subst-out">↓</span> {{ event.player_name }}
                                            </span>
                                            <span v-if="event.assist_name && event.type === 'subst'" class="event-assist"><span class="subst-in">↑</span> {{ event.assist_name }}</span>
                                            <span v-else-if="event.assist_name" class="event-assist">↳ {{ event.assist_name }}</span>
                                        </div>
                                        <span class="event-icon">{{ eventIcon(event) }}</span>
                                    </template>
                                </div>

                                <!-- Minute (center) -->
                                <span class="event-min">{{ event.minute }}<span v-if="event.extra_minute">+{{ event.extra_minute }}</span>'</span>

                                <!-- Away side -->
                                <div class="event-side event-away">
                                    <template v-if="event.team_id !== match.home_team?.id">
                                        <span class="event-icon">{{ eventIcon(event) }}</span>
                                        <div class="event-info">
                                            <span class="event-player">
                                                <span v-if="event.type === 'subst'" class="subst-out">↓</span> {{ event.player_name }}
                                            </span>
                                            <span v-if="event.assist_name && event.type === 'subst'" class="event-assist"><span class="subst-in">↑</span> {{ event.assist_name }}</span>
                                            <span v-else-if="event.assist_name" class="event-assist">↳ {{ event.assist_name }}</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Stats Tab -->
                    <div v-if="activeTab === 'stats'" class="tab-panel stats-panel">
                        <div v-if="!statsGroups.length" class="empty-tab">{{ t('ui.no_stats') }}</div>
                        <template v-else>
                            <!-- Team legend -->
                            <div class="stats-legend">
                                <span class="legend-team">
                                    <span class="legend-dot home-dot"></span>
                                    {{ teamName(match.home_team) }}
                                </span>
                                <span class="legend-team away">
                                    {{ teamName(match.away_team) }}
                                    <span class="legend-dot away-dot"></span>
                                </span>
                            </div>

                            <!-- Top 3 donuts -->
                            <div class="donuts-row">
                                <div v-for="d in topDonuts" :key="d.key" class="donut-item">
                                    <div class="donut-vals">
                                        <span class="donut-val home" :class="{ winner: d.homePct > 50 }">{{ d.home }}</span>
                                        <div class="donut-ring" :style="{
                                            background: `conic-gradient(from 180deg, #e01552 0% ${Math.max(2, Math.min(98, d.homePct))}%, #3b82f6 ${Math.max(2, Math.min(98, d.homePct))}% 100%)`
                                        }"><div class="donut-hole"></div></div>
                                        <span class="donut-val away" :class="{ winner: d.homePct < 50 }">{{ d.away }}</span>
                                    </div>
                                    <div class="donut-label">{{ d.label }}</div>
                                </div>
                            </div>

                            <!-- Grouped stats -->
                            <div v-for="group in statsGroups" :key="group.label" class="stat-group">
                                <div class="stat-group-label">{{ group.label }}</div>

<div v-for="row in group.rows" :key="row.name" class="stat-row">
                                    <span class="stat-val" :class="{ winner: row.homeWins }">{{ row.home }}</span>
                                    <div class="stat-mid">
                                        <div class="stat-name">{{ row.name }}</div>
                                        <div class="stat-bar">
                                            <div class="stat-fill-home" :style="{ width: row.homePct + '%' }"></div>
                                            <div class="stat-fill-away" :style="{ width: row.awayPct + '%' }"></div>
                                        </div>
                                    </div>
                                    <span class="stat-val away" :class="{ winner: row.awayWins }">{{ row.away }}</span>
                                </div>
                            </div>
                        </template>
                    </div>

                </div>

                <!-- SIDEBAR -->
                <aside class="sidebar">
                    <h3 class="sidebar-title">{{ t('match.more') }}</h3>
                    <div v-if="!related.length" class="empty-tab" style="padding:20px 0">{{ t('match.no_related') }}</div>
                    <div class="related-list">
                        <Link v-for="m in related" :key="m.id"
                            :href="localePath(`/match/${m.slug}`)"
                            class="related-card"
                        >
                            <div class="rel-thumb" :style="relatedBg(m)">
                                <div class="rel-teams">
                                    <div class="rel-logo">
                                        <img v-if="m.home_team?.logo_url" :src="m.home_team.logo_url" :alt="teamName(m.home_team)" />
                                        <span v-else>{{ m.home_team?.initials }}</span>
                                    </div>
                                    <span class="rel-vs">vs</span>
                                    <div class="rel-logo">
                                        <img v-if="m.away_team?.logo_url" :src="m.away_team.logo_url" :alt="teamName(m.away_team)" />
                                        <span v-else>{{ m.away_team?.initials }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="rel-info">
                                <p class="rel-title">{{ teamName(m.home_team) }} vs {{ teamName(m.away_team) }}</p>
                                <span class="rel-date">{{ formatDate(m.match_date, m.kick_off_time) }}</span>
                            </div>
                        </Link>
                    </div>
                </aside>

            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed, toRef, onMounted, onUnmounted } from 'vue'
import Plyr from 'plyr'
import 'plyr/dist/plyr.css'
import Hls from 'hls.js'
import { useLocale } from '@/composables/useLocale'
import { useSeo, injectJsonLd } from '@/composables/useSeo'

const props = defineProps({
    match:         Object,
    related:       { type: Array, default: () => [] },
    leagues:       { type: Array, default: () => [] },
    popular_teams: { type: Array, default: () => [] },
    locale:        { type: String, default: 'en' },
})

const { teamName, leagueName, localePath, t, formatDate, formatRound } = useLocale(toRef(props, 'locale'))
const { matchSeo } = useSeo(toRef(props, 'locale'))
const seo = computed(() => matchSeo(props.match))
injectJsonLd(computed(() => seo.value.jsonLd))

const videoEl        = ref(null)
const scoreVisible   = ref(false)
const hasStarted     = ref(false) // true khi đã play lần đầu — ẩn poster team logo, không hiện lại khi pause
const activeTab      = ref('events')
const activeVideoIdx = ref(0)
const isPlaying      = ref(false)
const flashVisible   = ref(false)
const flashIcon      = ref('play') // 'play' | 'pause'
let flashTimer = null

let plyrInstance = null
let hlsInstance  = null

const STAT_GROUPS = [
    { labelKey: 'stats.key',        keys: ['Expected Goals', 'Big Chances Created'] },
    { labelKey: 'stats.attack',     keys: ['Shots on target', 'Shots off target', 'Blocked shots', 'Corners', 'Shots within penalty area'] },
    { labelKey: 'stats.passes',     keys: ['Total passes', 'Successful passes', 'Key Passes'] },
    { labelKey: 'stats.defence',    keys: ['Successful Tackles', 'Interceptions', 'Clearances', 'Goalkeeper saves'] },
    { labelKey: 'stats.discipline', keys: ['Fouls', 'Yellow cards', 'Red cards'] },
]

const STAT_NAME_KEYS = {
    'Expected Goals':              'stat.expected_goals',
    'Big Chances Created':         'stat.big_chances_created',
    'Shots on target':             'stat.shots_on_target',
    'Shots off target':            'stat.shots_off_target',
    'Blocked shots':               'stat.blocked_shots',
    'Corners':                     'stat.corners',
    'Shots within penalty area':   'stat.shots_in_box',
    'Total passes':                'stat.total_passes',
    'Successful passes':           'stat.successful_passes',
    'Key Passes':                  'stat.key_passes',
    'Successful Tackles':          'stat.successful_tackles',
    'Interceptions':               'stat.interceptions',
    'Clearances':                  'stat.clearances',
    'Goalkeeper saves':            'stat.goalkeeper_saves',
    'Fouls':                       'stat.fouls',
    'Yellow cards':                'stat.yellow_cards',
    'Red cards':                   'stat.red_cards',
}

const tabs = computed(() => {
    const tabList = [{ key: 'events', label: t('match.events') }]
    if (statsGroups.value.length) tabList.push({ key: 'stats', label: t('match.statistics') })
    return tabList
})

const statsData = computed(() => {
    const raw = props.match.statistics
    if (!raw?.length) return null
    const homeId = props.match.home_team?.highlightly_id
    const homeData = raw.find(s => s.team.id === homeId) ?? raw[0]
    const awayData = raw.find(s => s.team.id !== homeId) ?? raw[1]
    const toMap = arr => Object.fromEntries((arr ?? []).map(s => [s.displayName, s.value]))
    return { home: toMap(homeData?.statistics), away: toMap(awayData?.statistics) }
})

const homePossession = computed(() =>
    Math.round((statsData.value?.home['Possession'] ?? 0.5) * 100)
)
const awayPossession = computed(() => 100 - homePossession.value)

const topDonuts = computed(() => {
    if (!statsData.value) return []
    const { home, away } = statsData.value
    const result = []

    const xgH = home['Expected Goals'], xgA = away['Expected Goals']
    if (xgH != null || xgA != null) {
        const h = xgH ?? 0, a = xgA ?? 0
        const pct = Math.round((h / (h + a || 1)) * 100)
        result.push({ key: 'xg', label: t('stat.expected_goals'), home: parseFloat(h.toFixed(2)), away: parseFloat(a.toFixed(2)), homePct: pct })
    }

    const hPct = homePossession.value
    result.push({ key: 'poss', label: t('ui.ball_possession'), home: `${hPct}%`, away: `${awayPossession.value}%`, homePct: hPct })

    const sotH = home['Shots on target'], sotA = away['Shots on target']
    if (sotH != null || sotA != null) {
        const h = sotH ?? 0, a = sotA ?? 0
        const pct = Math.round((h / (h + a || 1)) * 100)
        result.push({ key: 'sot', label: t('stat.shots_on_target'), home: h, away: a, homePct: pct })
    }

    return result
})

const statsGroups = computed(() => {
    if (!statsData.value) return []
    const { home, away } = statsData.value
    const fmt = v => Number.isInteger(v) ? v : parseFloat(v.toFixed(2))

    return STAT_GROUPS.map(group => ({
        label: t(group.labelKey),
        rows: group.keys.map(key => {
            const h = home[key] ?? 0
            const a = away[key] ?? 0
            const total = h + a || 1
            return {
                name: STAT_NAME_KEYS[key] ? t(STAT_NAME_KEYS[key]) : key,
                home: fmt(h),
                away: fmt(a),
                homePct: (h / total) * 100,
                awayPct: (a / total) * 100,
                homeWins: h > a,
                awayWins: a > h,
            }
        }).filter(r => r.home > 0 || r.away > 0)
    })).filter(g => g.rows.length)
})

const playableVideos = computed(() =>
    props.match.videos?.filter(v => v.status === 'ready') ?? []
)

const highlightVideos = computed(() =>
    playableVideos.value.filter(v => v.video_type !== 'full_match')
)

const fullMatchVideos = computed(() =>
    playableVideos.value.filter(v => v.video_type === 'full_match')
)

const hasVideo = computed(() => playableVideos.value.length > 0)

const videoIndexMap = computed(() => {
    const map = new Map()
    playableVideos.value.forEach((v, i) => map.set(v.id, i))
    return map
})

// ── Video ──────────────────────────────────────────
async function loadVideo(video) {
    try {
        const res = await fetch(`/api/videos/${video.id}/stream`)
        if (!res.ok) { tryNextVideo(); return }
        const { stream_url, cdn_token, cdn_expires, cdn_token_path } = await res.json()
        if (stream_url) playHls(stream_url, { cdn_token, cdn_expires, cdn_token_path })
        else tryNextVideo()
    } catch {
        tryNextVideo()
    }
}

function tryNextVideo() {
    const next = activeVideoIdx.value + 1
    if (next < playableVideos.value.length) {
        activeVideoIdx.value = next
        loadVideo(playableVideos.value[next])
    }
}

function playHls(url, cdnAuth = {}) {
    if (hlsInstance) { hlsInstance.destroy(); hlsInstance = null }
    const media = plyrInstance?.media ?? videoEl.value
    if (!media) return

    const { cdn_token, cdn_expires, cdn_token_path } = cdnAuth
    const hlsConfig = {
        enableWorker: true,
        maxBufferLength: 30,
        maxMaxBufferLength: 30,
        maxBufferSize: 20 * 1000 * 1000,
        backBufferLength: 0,
    }

    if (cdn_token) {
        hlsConfig.xhrSetup = (xhr, reqUrl) => {
            const sep = reqUrl.includes('?') ? '&' : '?'
            xhr.open('GET', `${reqUrl}${sep}token=${cdn_token}&expires=${cdn_expires}&token_path=${cdn_token_path}`, true)
        }
    }

    if (Hls.isSupported()) {
        hlsInstance = new Hls(hlsConfig)

        hlsInstance.on(Hls.Events.ERROR, (event, data) => {
            console.error('[hls] error', data.type, data.details, data)
            if (data.fatal) {
                if (data.type === Hls.ErrorTypes.NETWORK_ERROR) hlsInstance.startLoad()
                else if (data.type === Hls.ErrorTypes.MEDIA_ERROR) hlsInstance.recoverMediaError()
                else { hlsInstance.destroy(); tryNextVideo() }
            }
        })

        hlsInstance.loadSource(url)
        hlsInstance.attachMedia(media)
    } else if (media.canPlayType('application/vnd.apple.mpegurl')) {
        media.src = url
    }
}

function switchVideo(idx) {
    activeVideoIdx.value = idx
    loadVideo(playableVideos.value[idx])
}

function onOverlayMouseMove() {
    plyrInstance?.elements?.container?.dispatchEvent(new MouseEvent('mousemove', { bubbles: false }))
}

function onOverlayMouseLeave() {
    plyrInstance?.elements?.container?.dispatchEvent(new MouseEvent('mouseleave', { bubbles: false }))
}

function onVideoClick() {
    flashIcon.value = isPlaying.value ? 'pause' : 'play'
    plyrInstance?.togglePlay()
    clearTimeout(flashTimer)
    flashVisible.value = true
    flashTimer = setTimeout(() => { flashVisible.value = false }, 600)
}

// Đúp chuột: vào/thoát fullscreen. click đơn (toggle play) vẫn bắn trước đó —
// bấm đúp 2 lần liền nên play/pause tự triệt tiêu nhau, không lệch trạng thái.
function onVideoDblClick() {
    plyrInstance?.fullscreen?.toggle()
}

// ── Events ─────────────────────────────────────────
function eventIcon(event) {
    switch (event.type) {
        case 'goal':             return '⚽'
        case 'own_goal':         return '⚽'
        case 'yellow_card':      return '🟨'
        case 'red_card':         return '🟥'
        case 'yellow_red_card':  return '🟨🟥'
        case 'subst':            return '🔄'
        case 'penalty':          return '🥅'
        default:                 return '•'
    }
}

// ── Utils ──────────────────────────────────────────
function highlightLabel(video) {
    if (video.source === 'hoofoot') return t('match.video_extended')
    if (video.source === 'dasfootball') return t('match.video_alt')
    return t('match.video_highlight')
}

function fmtDuration(secs) {
    if (!secs) return ''
    const h = Math.floor(secs / 3600)
    const m = Math.floor((secs % 3600) / 60)
    const s = secs % 60
    if (h) return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`
    return `${m}:${String(s).padStart(2,'0')}`
}

function formatTime(time) { return time ? time.substring(0, 5) : '' }

function relatedBg(m) {
    const c = m.league?.primary_color || '#1a1a24'
    return { background: `linear-gradient(135deg, ${c}55 0%, #0b0b10 60%, ${c}22 100%)` }
}

// ── Lifecycle ──────────────────────────────────────
onMounted(() => {
    plyrInstance = new Plyr(videoEl.value, {
        controls: ['play', 'rewind', 'fast-forward', 'progress', 'current-time', 'mute', 'volume', 'settings', 'fullscreen'],
        settings: ['speed'],
        speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 2] },
        seekTime: 10,
        resetOnEnd: false,
        invertTime: false,
        tooltips: { controls: true, seek: true },
        youtube: { noCookie: true, rel: 0, showinfo: 0, iv_load_policy: 3, modestbranding: 1 },
    })

    plyrInstance.on('play',  () => { isPlaying.value = true; hasStarted.value = true })
    plyrInstance.on('pause', () => { isPlaying.value = false })
    plyrInstance.on('ended', () => { isPlaying.value = false })

    if (playableVideos.value.length > 0) loadVideo(playableVideos.value[0])
})

onUnmounted(() => {
    hlsInstance?.destroy()
    plyrInstance?.destroy()
})
</script>

<style>
/* Plyr theme override — global (not scoped) */
:root {
    --plyr-color-main: #e01552;
    --plyr-video-background: #000;
    --plyr-font-family: system-ui, -apple-system, sans-serif;
    --plyr-range-fill-background: #e01552;
}
/* Plyr fill container */
.plyr-container .plyr,
.plyr-container .plyr__video-wrapper,
.plyr-container .plyr video {
    height: 100% !important;
    width: 100% !important;
}

/* Two-row controls: progress bar on top, buttons below */
.plyr__controls {
    flex-wrap: wrap !important;
    padding: 0 8px 8px !important;
    gap: 0 !important;
    align-items: center !important;
}
.plyr__controls .plyr__progress__container {
    order: -1 !important;
    flex: 0 0 100% !important;
    width: 100% !important;
    padding: 8px 0 4px !important;
    margin: 0 !important;
}
.plyr__controls .plyr__progress {
    width: 100% !important;
    margin: 0 !important;
}

/* Gom nút trái sát nhau, đẩy phần phải sang mép */
.plyr__controls [data-plyr="play"],
.plyr__controls [data-plyr="rewind"],
.plyr__controls [data-plyr="fast-forward"] {
    margin-right: 0 !important;
    flex-shrink: 0;
}
/* current-time đẩy các nút còn lại sang phải */
.plyr__controls .plyr__time--current {
    margin-right: auto !important;
    padding-left: 4px;
}

</style>

<style scoped>
* { box-sizing: border-box; }
a { text-decoration: none; color: inherit; }

.match-wrap {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px 28px 40px;
    min-height: 100%;
}
@media (max-width: 768px) { .match-wrap { padding: 16px 16px 32px; } }

/* ── Layout ── */
.match-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 24px;
    align-items: start;
}
@media (max-width: 1000px) { .match-layout { grid-template-columns: 1fr; } }

/* Grid item mặc định không co dưới kích thước nội dung con (min-width: auto) —
   không có dòng này, donuts-row rộng cố định sẽ đẩy cả cột content tràn viewport
   trên mobile thay vì bị co lại/wrap đúng cách. */
.main-col { min-width: 0; }

/* ── Player ── */
.player-wrap {
    position: relative;
    aspect-ratio: 16 / 9;
    background: #000;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 12px;
}
.plyr-container { width: 100%; height: 100%; }

/* ── Click area + flash ── */
.player-click-area {
    position: absolute;
    inset: 0;
    bottom: 80px; /* không che Plyr controls (progress row + buttons row) */
    z-index: 10;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
/* Nút play mặc định khi paused */
.center-play-btn {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(6px);
    border: 2px solid rgba(224,21,82,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}
.center-play-btn svg {
    width: 30px;
    height: 30px;
    fill: #e01552;
    filter: drop-shadow(0 0 6px rgba(224,21,82,0.6));
    margin-left: 3px; /* optical center for triangle */
}

/* Transition idle play button */
.idle-play-enter-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.idle-play-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.idle-play-enter-from   { opacity: 0; transform: scale(0.85); }
.idle-play-leave-to     { opacity: 0; transform: scale(1.1); }

/* Flash icon khi click */
.flash-icon {
    position: absolute;
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}
.flash-icon svg {
    width: 28px;
    height: 28px;
}
.flash-icon svg polygon { margin-left: 3px; }

/* Transition flash */
.icon-flash-enter-active { transition: opacity 0.08s ease, transform 0.08s ease; }
.icon-flash-leave-active { transition: opacity 0.5s ease, transform 0.5s ease; }
.icon-flash-enter-from   { opacity: 0; transform: scale(0.7); }
.icon-flash-leave-to     { opacity: 0; transform: scale(1.5); }

.player-placeholder {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 16px;
    background:
        radial-gradient(ellipse at center, rgba(224,21,82,0.14) 0%, transparent 65%),
        #0d0d12;
}
.ph-teams { display: flex; align-items: center; gap: 24px; }
.ph-logo {
    width: 88px; height: 88px; border-radius: 50%;
    background: #fff; border: 1.5px solid rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center; overflow: hidden;
    box-shadow: 0 0 0 6px rgba(255,255,255,0.04);
}
.ph-logo img { width: 62px; height: 62px; object-fit: contain; }
.ph-logo span { font-size: 18px; font-weight: 700; color: #1a1a1a; }
.ph-vs { font-size: 26px; font-weight: 800; color: rgba(255,255,255,0.6); }
.ph-label { font-size: 13px; color: #c0c0cc; }
@media (min-width: 768px) {
    .ph-teams { gap: 40px; }
    .ph-logo { width: 120px; height: 120px; }
    .ph-logo img { width: 84px; height: 84px; }
    .ph-logo span { font-size: 24px; }
    .ph-vs { font-size: 34px; }
}

/* ── Source bar ── */
.src-bar {
    display: flex; align-items: center; gap: 6px;
    margin-bottom: 12px; flex-wrap: wrap;
}
.src-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 13px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
    background: #16161e; border: 1px solid #2a2a38;
    color: #9090aa; cursor: pointer; transition: all 0.15s;
    white-space: nowrap;
}
.src-btn:hover:not(.active) { border-color: #3a3a50; color: #e0e0f0; background: #1c1c28; }
.src-btn.active { background: #e01552; border-color: #e01552; color: #fff; }
.src-btn.fm-btn.active { background: #7c6fcd; border-color: #7c6fcd; color: #fff; }
.src-btn.fm-btn:hover:not(.active) { border-color: #6050b0; color: #c0b8f0; }
.src-dot {
    width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0;
}
.hl-dot { background: #e01552; }
.fm-dot { background: #7c6fcd; }
.src-btn.active .src-dot { background: rgba(255,255,255,0.7); }
.src-dur {
    font-size: 10px; font-weight: 600; opacity: 1;
    background: rgba(255,255,255,.15); border-radius: 4px;
    padding: 1px 5px; margin-left: 2px; color: #e0e0f0;
}
.src-loading {
    font-size: 11px; color: #9090aa;
    margin-left: auto; padding: 0 4px;
    animation: pulse 1s ease-in-out infinite;
}
@keyframes pulse { 0%, 100% { opacity: 1 } 50% { opacity: 0.4 } }
@media (max-width: 480px) {
    .src-btn { padding: 5px 11px; font-size: 11px; }
    .src-dur { display: none; }
}

/* ── Match info card ── */
.info-card {
    background: #16161e;
    border: 1px solid #26262f;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
}

.info-top {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; flex-wrap: wrap; gap: 8px;
}
.info-league { display: flex; align-items: center; gap: 8px; }
.lg-logo { width: 22px; height: 22px; object-fit: contain; }
.lg-name { font-size: 12px; font-weight: 700; color: #e01552; text-transform: uppercase; letter-spacing: 0.05em; }
.badge-round {
    font-size: 10px; font-weight: 600; color: #c0c0cc;
    background: #1c1c26; border: 0.5px solid #26262f;
    padding: 3px 8px; border-radius: 99px;
}
.info-date { font-size: 12px; color: #b8b8c4; display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
.kick-time { font-size: 11px; color: #b8b8c8; }

.teams-row {
    display: flex; align-items: center; justify-content: space-between;
    gap: 16px; margin-bottom: 16px;
}
.team-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 12px; border-radius: 10px; padding: 8px 4px; transition: background 0.15s; cursor: pointer; }
.team-col:hover { background: rgba(255,255,255,0.04); }
.team-col:hover .team-logo { box-shadow: 0 0 0 2px #ef4444; }
.team-logo {
    width: 80px; height: 80px; border-radius: 50%;
    background: #fff; border: 1.5px solid rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.team-logo img { width: 56px; height: 56px; object-fit: contain; }
.team-logo span { font-size: 18px; font-weight: 700; color: #1a1a1a; }
.team-name { font-size: 15px; font-weight: 600; color: #f5f5f7; text-align: center; line-height: 1.3; }

.score-col { display: flex; flex-direction: column; align-items: center; gap: 8px; flex-shrink: 0; }
.score-nums { display: flex; align-items: center; gap: 8px; font-size: 46px; font-weight: 800; color: #fff; letter-spacing: -2px; line-height: 1; }
.score-dash { font-size: 32px; color: #888; }
.show-score-btn {
    display: flex; align-items: center; gap: 6px;
    font-size: 12px; color: #ffffff;
    background: #1c1c26; border: 1px solid #26262f;
    padding: 8px 16px; border-radius: 8px; cursor: pointer;
    transition: all 0.15s; white-space: nowrap;
}
.show-score-btn:hover { border-color: #e01552; color: #e01552; }
.ft-label { font-size: 10px; color: #ffffff; text-transform: uppercase; letter-spacing: 0.08em; }

.meta-row {
    display: flex; flex-wrap: wrap; gap: 6px 20px;
    padding-top: 14px; border-top: 0.5px solid #26262f;
}
.meta-item {
    display: flex; align-items: center; gap: 6px;
    font-size: 11.5px; color: #c0c0cc;
}

.pen-label { font-size: 11px; color: #b8b8c4; }


/* ── Tabs ── */
.tabs-bar {
    display: flex; gap: 2px; margin-bottom: 12px;
    background: #16161e; border: 1px solid #26262f;
    border-radius: 10px; padding: 4px;
}
.tab-btn {
    flex: 1; padding: 8px 12px; font-size: 12px; font-weight: 600;
    color: #c0c0cc; background: transparent;
    border: none; border-radius: 7px; cursor: pointer; transition: all 0.15s;
}
.tab-btn.active { background: #e01552; color: #fff; }
.tab-btn:hover:not(.active) { color: #f5f5f7; background: #1c1c26; }

/* ── Tab panel ── */
.tab-panel {
    background: #16161e; border: 1px solid #26262f;
    border-radius: 12px; padding: 16px;
    min-height: 120px;
}
.empty-tab {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 10px; padding: 32px 16px;
    font-size: 13px; color: #909098; text-align: center;
}

/* Stats panel */
.stats-panel { padding: 0; }

.stats-legend {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 20px 14px;
    border-bottom: 1px solid #1c1c28;
}
.legend-team {
    display: flex; align-items: center; gap: 8px;
    font-size: 11px; font-weight: 700; color: #e8e8f0;
    text-transform: uppercase; letter-spacing: 0.08em;
}
.legend-team.away { flex-direction: row-reverse; }
.legend-dot {
    width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0;
}
.home-dot { background: #e01552; }
.away-dot { background: #3b82f6; }

/* Top donuts row */
.donuts-row {
    display: flex; flex-wrap: wrap; justify-content: space-around; align-items: flex-start;
    padding: 24px 12px 12px; gap: 8px 4px;
}
.donut-item {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    flex: 1 1 96px;
}
.donut-vals {
    display: flex; align-items: center; gap: 10px;
}
.donut-val {
    font-size: 16px; font-weight: 800; min-width: 38px; text-align: center;
}
.donut-val.home { color: #b03550; }
.donut-val.home.winner { color: #e01552; }
.donut-val.away { color: #4070b8; }
.donut-val.away.winner { color: #3b82f6; }
.donut-ring {
    width: 72px; height: 72px; border-radius: 50%;
    position: relative; flex-shrink: 0;
}
.donut-hole {
    position: absolute; inset: 13px;
    border-radius: 50%; background: #16161e;
}
.donut-label {
    font-size: 9px; font-weight: 700;
    color: #a0a0bc; letter-spacing: 0.12em;
    text-transform: uppercase; text-align: center;
}
@media (max-width: 480px) {
    .donut-ring { width: 58px; height: 58px; }
    .donut-hole { inset: 10px; }
    .donut-val { font-size: 14px; min-width: 28px; }
    .donut-label { font-size: 8px; }
}

/* Stat groups */
.stat-group { border-top: 1px solid #1c1c28; }
.stat-group-label {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 20px 8px;
    font-size: 10px; font-weight: 700; letter-spacing: 0.14em;
    color: #a0a0bc; text-transform: uppercase;
}
.stat-group-label::before,
.stat-group-label::after {
    content: ''; flex: 1; height: 1px; background: #2c2c40;
}
.stat-row {
    display: grid;
    grid-template-columns: 54px 1fr 54px;
    align-items: center;
    gap: 12px;
    padding: 11px 20px;
}
.stat-val {
    font-size: 15px; font-weight: 700; color: #d06080;
    text-align: right;
}
.stat-val.away { text-align: left; color: #6090d0; }
.stat-val.winner { color: #e01552; font-weight: 800; }
.stat-val.away.winner { color: #3b82f6; }

.stat-mid { display: flex; flex-direction: column; gap: 7px; }
.stat-name {
    font-size: 10px; color: #b8b8d8; text-align: center;
    text-transform: uppercase; letter-spacing: 0.07em;
}
.stat-bar {
    display: flex; height: 7px; border-radius: 4px; overflow: hidden;
    background: #1c1c28;
}
.stat-fill-home {
    background: #e01552; height: 100%;
    border-radius: 4px 0 0 4px;
}
.stat-fill-away {
    background: #3b82f6; height: 100%;
    border-radius: 0 4px 4px 0;
    margin-left: auto;
}


/* Spoiler lock */
.spoiler-lock {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 12px; padding: 36px 16px;
    text-align: center;
}
.spoiler-lock p { font-size: 13px; color: #c0c0cc; margin: 0; }

/* Events */
.event-row {
    display: grid;
    grid-template-columns: 1fr 36px 1fr;
    align-items: center;
    gap: 4px;
    padding: 8px 0;
    border-bottom: 0.5px solid #1e1e28;
}
.event-row:last-child { border-bottom: none; }

.event-min {
    font-size: 11px; color: #c0c0cc; font-weight: 600;
    text-align: center; flex-shrink: 0;
}
.event-icon { font-size: 14px; flex-shrink: 0; }

.event-side {
    display: flex; align-items: center; gap: 7px;
}
.event-home {
    justify-content: flex-end;
    text-align: right;
}
.event-away {
    justify-content: flex-start;
    text-align: left;
}

.event-info { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.event-player { font-size: 12.5px; color: #f0f0f0; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.event-assist { font-size: 10.5px; color: #b8b8c8; }
.subst-out { color: #ef4444; font-weight: 700; }
.subst-in  { color: #22c55e; font-weight: 700; }

/* ── Sidebar ── */
.sidebar { display: flex; flex-direction: column; gap: 12px; }
.sidebar-title {
    margin: 0; font-size: 12px; font-weight: 700; color: #e0e0ec;
    text-transform: uppercase; letter-spacing: 0.08em;
}

.related-list { display: flex; flex-direction: column; gap: 10px; }

.related-card {
    display: flex; gap: 0;
    background: #16161e; border: 1px solid #26262f;
    border-radius: 10px; overflow: hidden;
    transition: border-color 0.15s, transform 0.15s;
}
.related-card:hover { border-color: #3a3a44; transform: translateY(-1px); }

.rel-thumb {
    width: 96px; flex-shrink: 0; aspect-ratio: 16/9;
    display: flex; align-items: center; justify-content: center;
}
.rel-teams { display: flex; align-items: center; gap: 6px; padding: 8px; }
.rel-logo {
    width: 28px; height: 28px; border-radius: 50%;
    background: #fff; border: 1px solid rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;
}
.rel-logo img { width: 20px; height: 20px; object-fit: contain; }
.rel-logo span { font-size: 7px; font-weight: 700; color: #1a1a1a; }
.rel-vs { font-size: 9px; color: #b0b0bc; flex-shrink: 0; }

.rel-info { flex: 1; padding: 8px 10px; display: flex; flex-direction: column; justify-content: center; gap: 4px; min-width: 0; }
.rel-title { margin: 0; font-size: 12px; font-weight: 600; color: #e5e7eb; line-height: 1.35; }
.rel-date { font-size: 10.5px; color: #c0c0cc; }

@media (max-width: 1000px) {
    .sidebar { margin-top: 8px; }
    .related-list { display: grid; grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .related-list { grid-template-columns: 1fr; }
    .team-logo { width: 64px; height: 64px; }
    .team-logo img { width: 44px; height: 44px; }
    .score-nums { font-size: 36px; }
    .team-name { font-size: 13px; }
}


</style>
