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
        <meta v-if="seo.image" property="og:image" :content="seo.image" />
        <meta v-if="seo.image" property="og:image:width" content="512" />
        <meta v-if="seo.image" property="og:image:height" content="512" />
        <meta v-if="seo.image" property="og:image:type" content="image/webp" />
        <meta name="twitter:card" :content="seo.image ? 'summary_large_image' : 'summary'" />
        <meta name="twitter:title" :content="seo.fullTitle" />
        <meta name="twitter:description" :content="seo.description" />
        <meta v-if="seo.image" name="twitter:image" :content="seo.image" />
        <link v-for="alt in seo.alternates" :key="alt.hreflang" rel="alternate" :hreflang="alt.hreflang" :href="alt.href" />
    </Head>
    <AppLayout
        :leagues="leagues"
        :popular-teams="popular_teams"
        :locale="locale"
        v-slot="{ showScore }"
    >
        <div class="home-wrap">

            <!-- Featured Highlights -->
            <div v-if="featured_highlights.length" class="featured-section">
                <div class="section-head">
                    <div class="section-head-left">
                        <span class="section-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </span>
                        <div>
                            <h2 class="section-title">{{ t('ui.featured_highlights') }}</h2>
                            <p class="section-sub">{{ t('ui.featured_sub') }}</p>
                        </div>
                    </div>
                </div>

                <div class="featured-grid">
                    <Link
                        v-for="match in featured_highlights"
                        :key="match.id"
                        :href="localePath(`/match/${match.slug}`)"
                        class="video-card"
                    >
                        <div class="thumb" :style="thumbBg(match)">
                            <div class="thumb-overlay"></div>
                            <div class="thumb-fade"></div>

                            <div class="thumb-teams">
                                <div class="fb-team">
                                    <div class="fb-logo">
                                        <img v-if="match.home_team?.logo_url" :src="match.home_team.logo_url" />
                                        <span v-else>{{ match.home_team?.initials }}</span>
                                    </div>
                                    <span class="fb-name">{{ teamName(match.home_team) }}</span>
                                </div>

                                <div class="fb-middle">
                                    <span class="fb-vs">VS</span>
                                    <div class="fb-score" :style="{ visibility: showScore ? 'visible' : 'hidden' }">
                                        {{ match.home_score ?? '?' }} – {{ match.away_score ?? '?' }}
                                    </div>
                                </div>

                                <div class="fb-team">
                                    <div class="fb-logo">
                                        <img v-if="match.away_team?.logo_url" :src="match.away_team.logo_url" />
                                        <span v-else>{{ match.away_team?.initials }}</span>
                                    </div>
                                    <span class="fb-name">{{ teamName(match.away_team) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="card-info">
                            <div class="card-teams-row">
                                <span class="ctr-name">{{ teamName(match.home_team) }}</span>
                                <span class="ctr-vs">{{ t('ui.vs') }}</span>
                                <span class="ctr-name">{{ teamName(match.away_team) }}</span>
                            </div>
                            <div v-if="match.venue" class="card-venue">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span>{{ match.venue }}</span>
                            </div>
                            <div class="card-meta">
                                <div class="card-badges">
                                    <span v-if="isHotMatch(match)" class="badge-hot">🔥 HOT</span>
                                    <span class="badge-league">{{ leagueName(match.league) }}</span>
                                    <span v-if="match.round" class="badge-round">{{ formatRound(match.round) }}</span>
                                </div>
                                <span class="meta-date">{{ formatDate(match.match_date, match.kick_off_time) }}</span>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <div class="section-head" style="margin-top:32px;">
                <div class="section-head-left">
                    <span class="section-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 2a15 15 0 0 1 0 20M2 12h20M4.2 6.5A15.4 15.4 0 0 0 12 8.5a15.4 15.4 0 0 0 7.8-2M4.2 17.5A15.4 15.4 0 0 1 12 15.5a15.4 15.4 0 0 1 7.8 2"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="section-title">{{ t('match.latest') }}</h2>
                        <p class="section-sub">{{ matches.total ?? matchList.length }} {{ t('ui.matches_available') }}</p>
                    </div>
                </div>
            </div>

            <div class="video-grid">
                <Link
                    v-for="match in matchList"
                    :key="match.id"
                    :href="localePath(`/match/${match.slug}`)"
                    class="video-card"
                >
                    <div class="thumb" :style="thumbBg(match)">
                        <div class="thumb-overlay"></div>
                        <div class="thumb-fade"></div>

                        <div class="thumb-teams">
                            <div class="fb-team">
                                <div class="fb-logo">
                                    <img v-if="match.home_team?.logo_url" :src="match.home_team.logo_url" />
                                    <span v-else>{{ match.home_team?.initials }}</span>
                                </div>
                                <span class="fb-name">{{ teamName(match.home_team) }}</span>
                            </div>

                            <div class="fb-middle">
                                <span class="fb-vs">VS</span>
                                <div class="fb-score" :style="{ visibility: showScore ? 'visible' : 'hidden' }">
                                    {{ match.home_score ?? '?' }} – {{ match.away_score ?? '?' }}
                                </div>
                            </div>

                            <div class="fb-team">
                                <div class="fb-logo">
                                    <img v-if="match.away_team?.logo_url" :src="match.away_team.logo_url" />
                                    <span v-else>{{ match.away_team?.initials }}</span>
                                </div>
                                <span class="fb-name">{{ teamName(match.away_team) }}</span>
                            </div>
                        </div>

                    </div>

                    <div class="card-info">
                        <div class="card-teams-row">
                            <span class="ctr-name">{{ teamName(match.home_team) }}</span>
                            <span class="ctr-vs">{{ t('ui.vs') }}</span>
                            <span class="ctr-name">{{ teamName(match.away_team) }}</span>
                        </div>
                        <div v-if="match.venue" class="card-venue">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>{{ match.venue }}</span>
                        </div>
                        <div class="card-meta">
                            <div class="card-badges">
                                <span class="badge-league">{{ leagueName(match.league) }}</span>
                                <span v-if="match.round" class="badge-round">{{ formatRound(match.round) }}</span>
                            </div>
                            <span class="meta-date">{{ formatDate(match.match_date, match.kick_off_time) }}</span>
                        </div>
                    </div>
                </Link>
            </div>

            <div class="pagination">
                <button
                    v-for="link in matches.links"
                    :key="link.label"
                    @click="goToPage(link.url)"
                    :disabled="!link.url"
                    class="page-btn"
                    :class="{ 'page-active': link.active }"
                    v-html="formatPageLabel(link.label)"
                />
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, toRef, ref } from 'vue'
import { useLocale } from '@/composables/useLocale'
import { useSeo, injectJsonLd } from '@/composables/useSeo'
import { isHotMatch } from '@/constants/famousTeams'

const props = defineProps({
    matches:              Object,
    leagues:              Array,
    popular_teams:        { type: Array, default: () => [] },
    featured_highlights:  { type: Array, default: () => [] },
    locale:               { type: String, default: 'en' },
})

const featuredRowRef = ref(null)
function scrollFeatured(dir) {
    if (featuredRowRef.value) featuredRowRef.value.scrollBy({ left: dir * 800, behavior: 'smooth' })
}

const { teamName, leagueName, t, formatDate, formatRound, formatPageLabel, localePath } = useLocale(toRef(props, 'locale'))
const matchList = computed(() => props.matches?.data ?? [])
const { homeSeo } = useSeo(toRef(props, 'locale'))
const seo = computed(() => homeSeo())
injectJsonLd(computed(() => seo.value.jsonLd))

function thumbBg(match) {
    const league = match?.league
    if (league?.background_url_full) return {
        backgroundImage: `url(${league.background_url_full})`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
    }
    const c = league?.primary_color || '#1a1a24'
    return { background: `linear-gradient(135deg, ${c}66 0%, #0b0b10 60%, ${c}33 100%)` }
}

// Trang 1 "sống" ở đây, từ trang 2 trở đi chuyển sang /matches để có URL riêng, index được cả trận cũ
function goToPage(url) {
    if (!url) return
    const page = new URL(url).searchParams.get('page')
    if (!page || page === '1') {
        router.visit(url)
    } else {
        router.visit(`${localePath('/matches')}?page=${page}`)
    }
}
</script>

<style scoped>
* { box-sizing: border-box; }
a { text-decoration: none; color: inherit; }

.home-wrap {
    padding: 22px 28px 40px;
    max-width: 1400px;
    margin: 0 auto;
    background: #0b0b10;
    min-height: 100%;
}
@media (max-width: 768px) { .home-wrap { padding: 18px 16px 32px; } }

/* ── Section header ── */
.section-head { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; }
.section-head-left { display: flex; align-items: flex-start; gap: 10px; }
.section-icon { color: #ff4d6d; flex-shrink: 0; margin-top: 2px; display: flex; }
.section-title { margin: 0; font-size: 15px; font-weight: 700; color: #f5f5f7; letter-spacing: -0.02em; line-height: 1.3; }
.section-sub { margin: 3px 0 0; font-size: 11.5px; color: #cfcfd8; line-height: 1; }

/* ── Featured section ── */
.featured-section { margin-bottom: 32px; }

.featured-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
@media (max-width: 1200px) { .featured-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 860px)  { .featured-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }

/* ── Grid ── */
.video-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
@media (max-width: 1200px) { .video-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 860px)  { .video-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }

/* ── Card ── */
.video-card {
    display: block;
    background: #16161e;
    border: 1px solid #26262f;
    border-radius: 10px;
    overflow: hidden;
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
    box-shadow: 0 4px 16px rgba(0,0,0,0.4);
}
.video-card:hover {
    border-color: #3a3a44;
    box-shadow: 0 8px 28px rgba(0,0,0,0.55);
    transform: translateY(-1px);
}

/* ── Thumb ── */
.thumb {
    position: relative;
    aspect-ratio: 16 / 9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background-color: #0b0b10;
}
.thumb-overlay {
    position: absolute; inset: 0;
    background: rgba(0,0,0,0.18);
    z-index: 0;
}
.thumb-fade {
    position: absolute; left: 0; right: 0; bottom: 0;
    height: 56px;
    background: linear-gradient(to bottom, rgba(22,22,30,0) 0%, rgba(22,22,30,0.85) 100%);
    z-index: 1;
    pointer-events: none;
}

/* ── Teams ── */
.thumb-teams {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 0 6%;
    position: relative;
    z-index: 2;
}

.fb-team {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    flex: 1;
    min-width: 0;
}

.fb-logo {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: #fff;
    border: 1.5px solid rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; flex-shrink: 0;
}
.fb-logo img { width: 56px; height: 56px; object-fit: contain; display: block; }
.fb-logo span { font-size: 14px; font-weight: 700; color: #1a1a1a; }

.fb-name {
    font-size: 11px; font-weight: 700; color: #f5f5f7;
    text-shadow: 0 1px 6px rgba(0,0,0,1), 0 0 12px rgba(0,0,0,0.9);
    width: 100%;
    text-align: center;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    white-space: normal;
    word-break: break-word;
    line-height: 1.35;
}

/* Mobile: ẩn tên trong thumbnail, logo to */
@media (max-width: 860px) {
    .fb-logo { width: 56px; height: 56px; }
    .fb-logo img { width: 38px; height: 38px; }
    .fb-name { display: none; }
    .fb-vs { font-size: 14px !important; }
}

.fb-middle {
    display: flex; flex-direction: column; align-items: center; gap: 4px;
    flex-shrink: 0;
}
.fb-vs {
    font-size: 16px; font-weight: 800; color: #fff; opacity: 0.9;
    text-shadow: 0 1px 4px rgba(0,0,0,0.7);
    letter-spacing: 0.04em;
}
.fb-score {
    font-size: 11px; font-weight: 700; color: #fff;
    background: rgba(0,0,0,0.55);
    border: 0.5px solid rgba(255,255,255,0.15);
    padding: 2px 7px; border-radius: 5px;
}

/* ── Card info ── */
.card-info { padding: 8px 10px 10px; display: flex; flex-direction: column; gap: 6px; }

.card-teams-row { display: none; align-items: center; justify-content: center; gap: 5px; flex-wrap: wrap; }
.ctr-name { font-size: 11.5px; font-weight: 700; color: #f5f5f7; text-align: center; }
.ctr-vs { font-size: 10px; color: #a8a8b4; flex-shrink: 0; }
@media (max-width: 860px) {
    .card-teams-row { display: flex; }
}

.card-venue {
    display: flex; align-items: center; gap: 5px;
    color: #b0b0bc; font-size: 10.5px;
}
.card-venue span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.card-meta { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.card-badges { display: flex; align-items: center; gap: 5px; overflow: hidden; flex: 1; min-width: 0; }

.badge-league {
    font-size: 10.5px; font-weight: 700;
    color: #ff4d6d;
    background: rgba(224,21,82,0.1);
    border: 0.5px solid rgba(255,77,109,0.25);
    padding: 3px 7px; border-radius: 4px;
    text-transform: uppercase; letter-spacing: 0.04em;
    white-space: nowrap; flex-shrink: 0;
}
.badge-hot {
    font-size: 10.5px; font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #ff8a00, #e01552);
    padding: 3px 7px; border-radius: 4px;
    letter-spacing: 0.02em;
    white-space: nowrap; flex-shrink: 0;
}
.badge-round {
    font-size: 10px; font-weight: 600; color: #b8b8c8;
    background: #1c1c26; border: 0.5px solid #26262f;
    padding: 3px 7px; border-radius: 99px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.meta-date { font-size: 10.5px; color: #b8b8c4; flex-shrink: 0; white-space: nowrap; }

/* ── Pagination ── */
.pagination { display: flex; justify-content: center; gap: 6px; margin-top: 32px; flex-wrap: wrap; }
.page-btn {
    padding: 9px 16px; border-radius: 8px;
    font-size: 14px; font-weight: 600; min-width: 42px;
    background: #16161e; color: #f5f5f7;
    border: 1px solid #26262f;
    cursor: pointer; transition: border-color 0.15s, background 0.15s;
    line-height: 1;
}
.page-btn:hover:not(:disabled) { border-color: #3a3a44; background: #1c1c26; }
.page-active { background: #e01552 !important; border-color: #e01552 !important; color: #fff !important; }
.page-btn:disabled { opacity: 0.3; cursor: not-allowed; }
</style>
