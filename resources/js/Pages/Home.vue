<template>
    <AppLayout
        :leagues="leagues"
        :popular-teams="popular_teams"
        :current-league="filters.league"
        :locale="locale"
        v-slot="{ showScore }"
    >
        <div class="home-wrap">

            <div class="section-label">
                {{ filters.q ? `Results: "${filters.q}"` : t('match.latest') }}
            </div>

            <div class="video-grid">
                <Link
                    v-for="match in matchList"
                    :key="match.id"
                    :href="localePath(`/match/${match.slug}`)"
                    class="video-card"
                >
                    <div class="thumb-wrap">
                        <div class="thumb" :style="thumbBg(match)"></div>
                        <div v-if="!match.thumbnail_url" class="thumb-fallback">
                            <div class="fb-logo">
                                <img v-if="match.home_team?.logo_url" :src="match.home_team.logo_url" />
                                <span v-else>{{ match.home_team?.initials }}</span>
                            </div>
                            <span class="fb-vs">VS</span>
                            <div class="fb-logo">
                                <img v-if="match.away_team?.logo_url" :src="match.away_team.logo_url" />
                                <span v-else>{{ match.away_team?.initials }}</span>
                            </div>
                        </div>
                        <div class="play-btn">
                            <svg width="10" height="12" viewBox="0 0 10 12" fill="white"><polygon points="0,0 10,6 0,12"/></svg>
                        </div>
                    </div>
                    <div class="card-info">
                        <p class="card-title">
                            {{ teamName(match.home_team) }}
                            <span v-if="showScore" class="inline-score"> {{ match.home_score ?? '?' }} - {{ match.away_score ?? '?' }} </span>
                            <span v-else class="inline-vs"> vs </span>
                            {{ teamName(match.away_team) }}
                        </p>
                        <div class="card-meta">
                            <span class="meta-league">{{ leagueName(match.league?.name) }}</span>
                            <span class="meta-sep">|</span>
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
                    v-html="link.label"
                />
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useLocale } from '@/composables/useLocale'

const props = defineProps({
    matches:       Object,
    leagues:       Array,
    popular_teams: { type: Array, default: () => [] },
    filters:       Object,
    locale:        { type: String, default: 'en' },
})

const { teamName, leagueName, t, localePath } = useLocale(props.locale)

const matchList = computed(() => props.matches?.data ?? [])

function thumbBg(match) {
    if (match?.thumbnail_url) return {
        backgroundImage: `url(${match.thumbnail_url})`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
    }
    return { background: '#1a1a1a' }
}

function formatDate(date) {
    if (!date) return ''
    const d = new Date(date)
    const diff = Math.floor((Date.now() - d) / 86400000)
    if (diff === 0) return 'Today'
    if (diff === 1) return 'Yesterday'
    if (diff < 7) return `${diff} days ago`
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })
}

function goToPage(url) {
    if (url) router.visit(url)
}
</script>

<style scoped>
* { box-sizing: border-box; }
a { text-decoration: none; color: inherit; }

.home-wrap { padding: 24px; max-width: 1400px; margin: 0 auto; }
@media (max-width: 768px) { .home-wrap { padding: 16px; } }

.section-label {
    font-size: 18px; font-weight: 700; color: #fff;
    border-left: 3px solid #ef4444; padding-left: 12px;
    margin-bottom: 20px;
}

/* Grid */
.video-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}
@media (max-width: 1100px) { .video-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 700px)  { .video-grid { grid-template-columns: repeat(2, 1fr); } }

/* Card */
.video-card { display: block; }
.video-card:hover .thumb { transform: scale(1.03); }

.thumb-wrap {
    position: relative; border-radius: 8px; overflow: hidden;
    aspect-ratio: 4/3; margin-bottom: 10px; background: #1a1a1a;
}

.thumb { width: 100%; height: 100%; transition: transform 0.2s ease; position: relative; }

.thumb-fallback {
    position: absolute; inset: 0; z-index: 1;
    display: flex; align-items: center; justify-content: center; gap: 16px;
    background: linear-gradient(135deg, #2a2a3e 0%, #222 50%, #2a1a1a 100%);
}
.fb-logo {
    width: 72px; height: 72px; border-radius: 50%;
    background: #fff; border: 1px solid #e5e5e5;
    display: flex; align-items: center; justify-content: center;
}
.fb-logo img { width: 36px; height: 36px; object-fit: contain; display: block; }
.fb-logo span { font-size: 11px; font-weight: 700; color: #fff; }
.fb-vs { font-size: 18px; font-weight: 700; color: #ef4444; }

.play-btn {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    width: 52px; height: 52px; border-radius: 50%;
    background: #ef4444;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 8px rgba(239,68,68,0.5);
    z-index: 2; transition: transform 0.15s;
}
.video-card:hover .play-btn { transform: translate(-50%, -50%) scale(1.1); }

.league-chip {
    position: absolute; top: 8px; left: 8px;
    font-size: 9px; font-weight: 700; color: #fff;
    background: rgba(0,0,0,0.6); padding: 2px 7px; border-radius: 3px;
    text-transform: uppercase; letter-spacing: 0.4px;
    backdrop-filter: blur(4px); z-index: 2;
}

/* Info */
.card-info { padding: 0 2px; }
.card-title {
    font-size: 16px; font-weight: 600; color: #fff;
    line-height: 1.4; margin: 0 0 6px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
}
.inline-score { font-weight: 700; color: #ef4444; margin: 0 2px; }
.inline-vs { font-weight: 600; color: #ef4444; margin: 0 2px; }
.card-meta { display: flex; align-items: center; gap: 6px; font-size: 12px; }
.meta-league { color: #fff; font-weight: 700; background: #ef4444; padding: 2px 8px; border-radius: 4px; font-size: 11px; }
.meta-sep { color: #444; }
.meta-date { color: #9ca3af; }

/* Pagination */
.pagination { display: flex; justify-content: center; gap: 8px; margin-top: 32px; flex-wrap: wrap; }
.page-btn { padding: 10px 18px; border-radius: 8px; font-size: 15px; font-weight: 600; min-width: 44px; background: #1a1a1a; color: #fff; border: 0.5px solid #2a2a2a; cursor: pointer; transition: all 0.15s; }
.page-btn:hover { border-color: #444; }
.page-active { background: #ef4444; border-color: #ef4444; }
.page-btn:disabled { opacity: 0.3; cursor: not-allowed; }
</style>
