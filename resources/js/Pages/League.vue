<template>
    <Head>
        <title>{{ seo.title }}</title>
        <meta name="description" :content="seo.description" />
        <link rel="canonical" :href="seo.canonical" />
        <meta property="og:site_name" content="BolaReel" />
        <meta property="og:locale" :content="seo.ogLocale" />
        <meta property="og:title" :content="seo.fullTitle" />
        <meta property="og:description" :content="seo.description" />
        <meta property="og:url" :content="seo.canonical" />
        <meta v-if="seo.image" property="og:image" :content="seo.image" />
        <meta name="twitter:title" :content="seo.fullTitle" />
        <meta name="twitter:description" :content="seo.description" />
        <meta v-if="seo.image" name="twitter:image" :content="seo.image" />
        <link v-for="alt in seo.alternates" :key="alt.hreflang" rel="alternate" :hreflang="alt.hreflang" :href="alt.href" />
    </Head>
    <AppLayout
        :leagues="leagues"
        :popular-teams="popular_teams"
        :current-league="league.slug"
        :locale="locale"
        v-slot="{ showScore }"
    >
        <div class="league-wrap">

            <!-- League hero -->
            <div class="league-hero" :style="heroStyle">
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <div class="hero-logo">
                        <img v-if="league.logo_url" :src="league.logo_url" :alt="league.name" />
                        <span v-else>{{ league.name[0] }}</span>
                    </div>
                    <div class="hero-info">
                        <p class="hero-label">{{ t('ui.league') }}</p>
                        <h1 class="hero-title">{{ leagueName(league) }}</h1>
                        <p class="hero-sub">{{ matchList.length > 0 ? matches.total + ' ' + t('ui.matches_available') : t('ui.no_matches_league') }}</p>
                    </div>
                </div>
            </div>

            <!-- Section header -->
            <div class="section-head">
                <div class="section-head-left">
                    <span class="section-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 2a15 15 0 0 1 0 20M2 12h20M4.2 6.5A15.4 15.4 0 0 0 12 8.5a15.4 15.4 0 0 0 7.8-2M4.2 17.5A15.4 15.4 0 0 1 12 15.5a15.4 15.4 0 0 1 7.8 2"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="section-title">{{ t('ui.latest_matches') }}</h2>
                        <p class="section-sub">{{ matches.total }} {{ t('ui.matches_available') }}</p>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="matchList.length === 0" class="empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#444" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a15 15 0 0 1 0 20M2 12h20"/></svg>
                <p>{{ t('ui.no_matches_league') }}</p>
            </div>

            <!-- Grid -->
            <div v-else class="video-grid">
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
                            <span class="ctr-vs">vs</span>
                            <span class="ctr-name">{{ teamName(match.away_team) }}</span>
                        </div>
                        <div v-if="match.venue" class="card-venue">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>{{ match.venue }}</span>
                        </div>
                        <div class="card-meta">
                            <div class="card-badges">
                                <span v-if="match.round" class="badge-round">{{ formatRound(match.round) }}</span>
                            </div>
                            <span class="meta-date">{{ formatDate(match.match_date) }}</span>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Pagination -->
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
import { computed, toRef } from 'vue'
import { useLocale } from '@/composables/useLocale'
import { useSeo, injectJsonLd } from '@/composables/useSeo'

const props = defineProps({
    league:        Object,
    matches:       Object,
    leagues:       Array,
    popular_teams: { type: Array, default: () => [] },
    locale:        { type: String, default: 'en' },
})

const { teamName, leagueName, localePath, t, formatDate, formatRound, formatPageLabel } = useLocale(toRef(props, 'locale'))
const matchList = computed(() => props.matches?.data ?? [])
const { leagueSeo } = useSeo(toRef(props, 'locale'))
const seo = computed(() => leagueSeo(props.league))
injectJsonLd(computed(() => seo.value.jsonLd))

const heroStyle = computed(() => {
    if (props.league?.background_url_full) {
        return {
            backgroundImage: `url(${props.league.background_url_full})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center',
        }
    }
    const c = props.league?.primary_color || '#ef4444'
    return {
        background: `linear-gradient(135deg, ${c}55 0%, #0b0b10 60%, ${c}22 100%)`,
    }
})

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

function goToPage(url) {
    if (url) router.visit(url)
}
</script>

<style scoped>
* { box-sizing: border-box; }
a { text-decoration: none; color: inherit; }

.league-wrap {
    padding: 0 0 40px;
    max-width: 1400px;
    margin: 0 auto;
    min-height: 100%;
}

/* ── Hero ── */
.league-hero {
    position: relative;
    height: 180px;
    display: flex;
    align-items: flex-end;
    overflow: hidden;
    background: #0b0b10;
    margin-bottom: 24px;
}
@media (max-width: 768px) { .league-hero { height: 140px; } }

.hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(11,11,16,0.92) 100%);
    z-index: 0;
}

.hero-content {
    position: relative; z-index: 1;
    display: flex; align-items: center; gap: 20px;
    padding: 0 28px 24px;
    width: 100%;
}
@media (max-width: 768px) { .hero-content { padding: 0 16px 20px; gap: 14px; } }

.hero-logo {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; flex-shrink: 0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.5);
}
.hero-logo img { width: 50px; height: 50px; object-fit: contain; }
.hero-logo span { font-size: 22px; font-weight: 700; color: #1a1a1a; }
@media (max-width: 768px) { .hero-logo { width: 56px; height: 56px; } .hero-logo img { width: 38px; height: 38px; } }

.hero-label { margin: 0 0 2px; font-size: 10px; font-weight: 700; color: #ef4444; text-transform: uppercase; letter-spacing: 0.08em; }
.hero-title { margin: 0 0 4px; font-size: 26px; font-weight: 800; color: #fff; letter-spacing: -0.02em; line-height: 1.1; }
.hero-sub { margin: 0; font-size: 12px; color: #b8b8c4; }
@media (max-width: 768px) { .hero-title { font-size: 20px; } }

/* ── Section header ── */
.section-head { display: flex; align-items: flex-start; margin: 0 28px 16px; }
.section-head-left { display: flex; align-items: flex-start; gap: 10px; }
.section-icon { color: #ff4d6d; flex-shrink: 0; margin-top: 2px; display: flex; }
.section-title { margin: 0; font-size: 15px; font-weight: 700; color: #f5f5f7; letter-spacing: -0.02em; line-height: 1.3; }
.section-sub { margin: 3px 0 0; font-size: 11.5px; color: #b8b8c4; line-height: 1; }
@media (max-width: 768px) { .section-head { margin: 0 16px 14px; } }

/* ── Empty ── */
.empty {
    display: flex; flex-direction: column; align-items: center; gap: 16px;
    padding: 60px 20px; color: #909098; font-size: 14px;
}

/* ── Grid ── */
.video-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    padding: 0 28px;
}
@media (max-width: 1200px) { .video-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 860px)  { .video-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 0 16px; } }

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
    position: relative; aspect-ratio: 16/9;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; background-color: #0b0b10;
}
.thumb-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.18); z-index: 0; }
.thumb-fade {
    position: absolute; left: 0; right: 0; bottom: 0; height: 56px;
    background: linear-gradient(to bottom, rgba(22,22,30,0) 0%, rgba(22,22,30,0.85) 100%);
    z-index: 1; pointer-events: none;
}

/* ── Teams in thumb ── */
.thumb-teams {
    display: flex; align-items: center; justify-content: center;
    gap: 10px; width: 100%; padding: 0 6%;
    position: relative; z-index: 2;
}
.fb-team {
    display: flex; flex-direction: column; align-items: center;
    gap: 6px; flex: 1; min-width: 0;
}
.fb-logo {
    width: 80px; height: 80px;
    border-radius: 50%; background: #fff;
    border: 1.5px solid rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; flex-shrink: 0;
}
.fb-logo img { width: 56px; height: 56px; object-fit: contain; display: block; }
.fb-logo span { font-size: 14px; font-weight: 700; color: #1a1a1a; }

.fb-name {
    font-size: 11px; font-weight: 700; color: #f5f5f7;
    text-shadow: 0 1px 6px rgba(0,0,0,1), 0 0 12px rgba(0,0,0,0.9);
    width: 100%; text-align: center;
    overflow: hidden;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    white-space: normal; word-break: break-word; line-height: 1.35;
}

@media (max-width: 860px) {
    .fb-logo { width: 56px; height: 56px; }
    .fb-logo img { width: 38px; height: 38px; }
    .fb-name { display: none; }
    .fb-vs { font-size: 14px !important; }
}

.fb-middle { display: flex; flex-direction: column; align-items: center; gap: 4px; flex-shrink: 0; }
.fb-vs { font-size: 16px; font-weight: 800; color: #fff; opacity: 0.9; text-shadow: 0 1px 4px rgba(0,0,0,0.7); letter-spacing: 0.04em; }
.fb-score {
    font-size: 11px; font-weight: 700; color: #fff;
    background: rgba(0,0,0,0.55); border: 0.5px solid rgba(255,255,255,0.15);
    padding: 2px 7px; border-radius: 5px;
}

/* ── Card info ── */
.card-info { padding: 8px 10px 10px; display: flex; flex-direction: column; gap: 6px; }

.card-teams-row { display: none; align-items: center; justify-content: center; gap: 5px; flex-wrap: wrap; }
.ctr-name { font-size: 11.5px; font-weight: 700; color: #f5f5f7; text-align: center; }
.ctr-vs { font-size: 10px; color: #a8a8b4; flex-shrink: 0; }
@media (max-width: 860px) { .card-teams-row { display: flex; } }

.card-venue { display: flex; align-items: center; gap: 5px; color: #b0b0bc; font-size: 10.5px; }
.card-venue span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.card-meta { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.card-badges { display: flex; align-items: center; gap: 5px; overflow: hidden; flex: 1; min-width: 0; }
.badge-round {
    font-size: 10px; font-weight: 600; color: #b8b8c8;
    background: #1c1c26; border: 0.5px solid #26262f;
    padding: 3px 7px; border-radius: 99px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.meta-date { font-size: 10.5px; color: #b8b8c4; flex-shrink: 0; white-space: nowrap; }

/* ── Pagination ── */
.pagination { display: flex; justify-content: center; gap: 6px; margin-top: 32px; flex-wrap: wrap; padding: 0 28px; }
.page-btn {
    padding: 9px 16px; border-radius: 8px;
    font-size: 14px; font-weight: 600; min-width: 42px;
    background: #16161e; color: #f5f5f7;
    border: 1px solid #26262f;
    cursor: pointer; transition: border-color 0.15s, background 0.15s; line-height: 1;
}
.page-btn:hover:not(:disabled) { border-color: #3a3a44; background: #1c1c26; }
.page-active { background: #e01552 !important; border-color: #e01552 !important; color: #fff !important; }
.page-btn:disabled { opacity: 0.3; cursor: not-allowed; }
</style>
