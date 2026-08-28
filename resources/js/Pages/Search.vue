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
        <meta name="twitter:title" :content="seo.fullTitle" />
        <meta name="twitter:description" :content="seo.description" />
        <meta v-if="seo.noindex" name="robots" content="noindex,nofollow" />
        <link v-for="alt in seo.alternates" :key="alt.hreflang" rel="alternate" :hreflang="alt.hreflang" :href="alt.href" />
    </Head>
    <AppLayout
        :leagues="allLeagues"
        :popular-teams="popular_teams"
        :locale="locale"
        v-slot="{ showScore }"
    >
        <div class="search-wrap">

            <!-- Header -->
            <div class="search-header">
                <div class="search-header-inner">
                    <div class="sq-label" v-if="q">
                        {{ t('ui.search_results_for') }}
                        <span class="sq-term">"{{ q }}"</span>
                    </div>
                    <div class="sq-label" v-else>{{ t('ui.search_hint') }}</div>
                    <div class="sq-counts" v-if="q">
                        <span v-if="teams.length">{{ teams.length }} {{ t('ui.teams') }}</span>
                        <span v-if="leagues.length">{{ leagues.length }} {{ t('ui.leagues') }}</span>
                        <span v-if="matchList.length">{{ matches.total }} {{ t('ui.matches') }}</span>
                    </div>
                </div>
            </div>

            <!-- Empty query state -->
            <div v-if="!q" class="empty-query">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#444" stroke-width="1.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <p>{{ t('ui.search_hint') }}</p>
            </div>

            <!-- No results -->
            <div v-else-if="!teams.length && !leagues.length && !matchList.length" class="empty-query">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#444" stroke-width="1.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <p>{{ t('ui.no_results_for') }} <strong>"{{ q }}"</strong></p>
            </div>

            <template v-else>

                <!-- Teams section -->
                <section v-if="teams.length" class="result-section">
                    <div class="section-head">
                        <span class="section-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                        </span>
                        <h2 class="section-title">{{ t('ui.teams') }}</h2>
                        <span class="section-count">{{ teams.length }}</span>
                    </div>
                    <div class="teams-grid">
                        <Link
                            v-for="team in teams"
                            :key="team.id"
                            :href="localePath(`/team/${team.slug}`)"
                            class="team-card"
                        >
                            <div class="team-logo">
                                <img v-if="team.logo_url" :src="team.logo_url" :alt="team.name" />
                                <span v-else>{{ team.initials }}</span>
                            </div>
                            <div class="team-info">
                                <span class="team-name">{{ teamName(team) }}</span>
                                <span class="team-meta">{{ team.type === 'national' ? t('ui.national_team') : t('ui.club') }} · {{ team.match_count }} {{ t('ui.matches') }}</span>
                            </div>
                            <svg class="team-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </Link>
                    </div>
                </section>

                <!-- Leagues section -->
                <section v-if="leagues.length" class="result-section">
                    <div class="section-head">
                        <span class="section-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a15 15 0 0 1 0 20M2 12h20"/></svg>
                        </span>
                        <h2 class="section-title">{{ t('ui.leagues') }}</h2>
                        <span class="section-count">{{ leagues.length }}</span>
                    </div>
                    <div class="leagues-grid">
                        <Link
                            v-for="league in leagues"
                            :key="league.id"
                            :href="localePath(`/league/${league.slug}`)"
                            class="league-card"
                        >
                            <div class="league-logo">
                                <img v-if="league.logo_url" :src="league.logo_url" :alt="league.name" />
                                <span v-else>{{ league.name[0] }}</span>
                            </div>
                            <div class="league-info">
                                <span class="league-name">{{ leagueName(league) }}</span>
                                <span class="league-meta">{{ league.match_count }} {{ t('ui.matches') }}</span>
                            </div>
                            <svg class="team-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </Link>
                    </div>
                </section>

                <!-- Matches section -->
                <section v-if="matchList.length" class="result-section">
                    <div class="section-head">
                        <span class="section-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                        </span>
                        <h2 class="section-title">{{ t('ui.matches') }}</h2>
                        <span class="section-count">{{ matches.total }}</span>
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
                                    <span class="ctr-vs">vs</span>
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
                </section>

            </template>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, toRef } from 'vue'
import { useLocale } from '@/composables/useLocale'
import { useSeo } from '@/composables/useSeo'
import { isHotMatch } from '@/constants/famousTeams'

const props = defineProps({
    q:             { type: String, default: '' },
    matches:       { type: [Object, Array], default: () => ({ data: [], links: [], total: 0 }) },
    teams:         { type: Array, default: () => [] },
    leagues:       { type: Array, default: () => [] },
    allLeagues:    { type: Array, default: () => [] },
    popular_teams: { type: Array, default: () => [] },
    locale:        { type: String, default: 'en' },
})

const { teamName, leagueName, localePath, t, formatDate, formatRound, formatPageLabel } = useLocale(toRef(props, 'locale'))
const matchList = computed(() => props.matches?.data ?? [])
const { searchSeo } = useSeo(toRef(props, 'locale'))
const seo = computed(() => searchSeo(props.q))

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

.search-wrap {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px 28px 48px;
    min-height: 100%;
}
@media (max-width: 768px) { .search-wrap { padding: 16px 16px 32px; } }

/* ── Header ── */
.search-header {
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 0.5px solid #222;
}
.search-header-inner { display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.sq-label { font-size: 18px; font-weight: 700; color: #f5f5f7; }
.sq-term { color: #ef4444; }
.sq-counts { display: flex; gap: 12px; }
.sq-counts span { font-size: 12px; color: #b8b8c4; background: #1c1c26; border: 0.5px solid #26262f; padding: 3px 10px; border-radius: 99px; }
@media (max-width: 480px) {
    .sq-label { font-size: 16px; }
    .sq-counts { gap: 8px; flex-wrap: wrap; }
    .sq-counts span { font-size: 10.5px; padding: 3px 8px; }
}

/* ── Empty ── */
.empty-query {
    display: flex; flex-direction: column; align-items: center; gap: 16px;
    padding: 80px 20px; color: #b8b8c4; font-size: 14px; text-align: center;
}
.empty-query strong { color: #f5f5f7; }

/* ── Section ── */
.result-section { margin-bottom: 40px; }
.section-head {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 14px;
}
.section-icon { color: #ef4444; display: flex; flex-shrink: 0; }
.section-title { margin: 0; font-size: 14px; font-weight: 700; color: #f5f5f7; letter-spacing: -0.01em; }
.section-count {
    font-size: 11px; font-weight: 600; color: #b8b8c4;
    background: #1c1c26; border: 0.5px solid #26262f;
    padding: 2px 8px; border-radius: 99px;
}

/* ── Teams grid ── */
.teams-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}
@media (max-width: 1000px) { .teams-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 640px)  { .teams-grid { grid-template-columns: repeat(2, 1fr); } }

.team-card {
    display: flex; align-items: center; gap: 12px;
    background: #16161e; border: 1px solid #26262f;
    border-radius: 10px; padding: 12px 14px;
    transition: border-color 0.15s, background 0.15s;
}
.team-card:hover { border-color: #3a3a44; background: #1c1c26; }
.team-logo {
    width: 40px; height: 40px; border-radius: 50%;
    background: #fff; border: 1px solid rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; flex-shrink: 0;
}
.team-logo img { width: 28px; height: 28px; object-fit: contain; }
.team-logo span { font-size: 10px; font-weight: 700; color: #1a1a1a; }
.team-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.team-name { font-size: 13px; font-weight: 600; color: #f5f5f7; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.team-meta { font-size: 10.5px; color: #b8b8c4; }
.team-arrow { color: #555; flex-shrink: 0; }
.team-card:hover .team-arrow { color: #ef4444; }

/* ── Leagues grid ── */
.leagues-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}
@media (max-width: 860px) { .leagues-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .leagues-grid { grid-template-columns: 1fr; } }

.league-card {
    display: flex; align-items: center; gap: 12px;
    background: #16161e; border: 1px solid #26262f;
    border-radius: 10px; padding: 12px 14px;
    transition: border-color 0.15s, background 0.15s;
}
.league-card:hover { border-color: #3a3a44; background: #1c1c26; }
.league-logo {
    width: 40px; height: 40px; border-radius: 50%;
    background: #fff; border: 1px solid rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; flex-shrink: 0;
}
.league-logo img { width: 28px; height: 28px; object-fit: contain; }
.league-logo span { font-size: 14px; font-weight: 700; color: #1a1a1a; }
.league-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.league-name { font-size: 13px; font-weight: 600; color: #f5f5f7; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.league-meta { font-size: 10.5px; color: #b8b8c4; }

/* ── Match grid ── */
.video-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
@media (max-width: 1200px) { .video-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 860px)  { .video-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }

.video-card {
    display: block;
    background: #16161e; border: 1px solid #26262f;
    border-radius: 10px; overflow: hidden;
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
    box-shadow: 0 4px 16px rgba(0,0,0,0.4);
}
.video-card:hover { border-color: #3a3a44; box-shadow: 0 8px 28px rgba(0,0,0,0.55); transform: translateY(-1px); }

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
.thumb-teams {
    display: flex; align-items: center; justify-content: center;
    gap: 10px; width: 100%; padding: 0 6%;
    position: relative; z-index: 2;
}
.fb-team { display: flex; flex-direction: column; align-items: center; gap: 6px; flex: 1; min-width: 0; }
.fb-logo {
    width: 72px; height: 72px; border-radius: 50%; background: #fff;
    border: 1.5px solid rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; flex-shrink: 0;
}
.fb-logo img { width: 50px; height: 50px; object-fit: contain; display: block; }
.fb-logo span { font-size: 13px; font-weight: 700; color: #1a1a1a; }
.fb-name {
    font-size: 11px; font-weight: 700; color: #f5f5f7;
    text-shadow: 0 1px 6px rgba(0,0,0,1);
    width: 100%; text-align: center;
    overflow: hidden; display: -webkit-box;
    -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    white-space: normal; word-break: break-word; line-height: 1.35;
}
@media (max-width: 860px) {
    .fb-logo { width: 50px; height: 50px; }
    .fb-logo img { width: 34px; height: 34px; }
    .fb-name { display: none; }
}
.fb-middle { display: flex; flex-direction: column; align-items: center; gap: 4px; flex-shrink: 0; }
.fb-vs { font-size: 15px; font-weight: 800; color: #fff; opacity: 0.9; text-shadow: 0 1px 4px rgba(0,0,0,0.7); }
.fb-score {
    font-size: 11px; font-weight: 700; color: #fff;
    background: rgba(0,0,0,0.55); border: 0.5px solid rgba(255,255,255,0.15);
    padding: 2px 7px; border-radius: 5px;
}

.card-info { padding: 8px 10px 10px; display: flex; flex-direction: column; gap: 6px; }
.card-teams-row { display: none; align-items: center; justify-content: center; gap: 5px; flex-wrap: wrap; }
.ctr-name { font-size: 11.5px; font-weight: 700; color: #f5f5f7; text-align: center; }
.ctr-vs { font-size: 10px; color: #a8a8b4; flex-shrink: 0; }
@media (max-width: 860px) { .card-teams-row { display: flex; } }
.card-venue { display: flex; align-items: center; gap: 5px; color: #b0b0bc; font-size: 10.5px; }
.card-venue span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.card-meta { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.card-badges { display: flex; align-items: center; gap: 5px; overflow: hidden; flex: 1; min-width: 0; }
.badge-hot {
    font-size: 10.5px; font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #ff8a00, #e01552);
    padding: 3px 7px; border-radius: 4px;
    letter-spacing: 0.02em;
    white-space: nowrap; flex-shrink: 0;
}
.badge-league {
    font-size: 10.5px; font-weight: 700;
    color: #ff4d6d;
    background: rgba(224,21,82,0.1);
    border: 0.5px solid rgba(255,77,109,0.25);
    padding: 3px 7px; border-radius: 4px;
    text-transform: uppercase; letter-spacing: 0.04em;
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
.pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; flex-wrap: wrap; }
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
