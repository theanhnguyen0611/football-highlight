<template>
    <AppLayout
        :leagues="leagues"
        :popular-teams="popular_teams"
        :current-league="filters.league"
        :locale="locale"
        v-slot="{ showScore }"
    >
        <div style="max-width:1280px;margin:0 auto;padding:20px 24px">
            <div style="display:grid;grid-template-columns:1fr 270px;gap:24px">

                <!-- Main -->
                <div style="display:flex;flex-direction:column;gap:18px">

                    <div style="font-size:11px;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:0.5px;border-left:2px solid #ef4444;padding-left:8px">
                        {{ filters.q ? `Results: "${filters.q}"` : t('match.latest') }}
                    </div>

                    <!-- Featured -->
                    <Link v-if="featured" :href="localePath(`/match/${featured.slug}`)" class="featured-card">
                        <div class="featured-thumb" :style="featuredBg">
                            <div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);z-index:0"></div>
                            <svg class="pitch-lines" viewBox="0 0 560 200" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <rect width="560" height="200" fill="none" stroke="#fff" stroke-width="1.5"/>
                                <circle cx="280" cy="100" r="38" fill="none" stroke="#fff" stroke-width="1.5"/>
                                <line x1="280" y1="0" x2="280" y2="200" stroke="#fff" stroke-width="1"/>
                                <rect x="0" y="58" width="64" height="84" fill="none" stroke="#fff" stroke-width="1.5"/>
                                <rect x="496" y="58" width="64" height="84" fill="none" stroke="#fff" stroke-width="1.5"/>
                            </svg>

                            <div class="feat-glow-l" style="z-index:1" :style="{ background: `linear-gradient(90deg, ${featured.home_team?.primary_color || '#1C3C6B'}55 0%, transparent 100%)` }"></div>
                            <div class="feat-glow-r" style="z-index:1" :style="{ background: `linear-gradient(270deg, ${featured.away_team?.primary_color || '#8b0015'}55 0%, transparent 100%)` }"></div>

                            <div class="feat-teams" style="position:relative;z-index:2">
                                <div class="feat-team">
                                    <div class="feat-logo">
                                        <img v-if="featured.home_team?.logo_url" :src="featured.home_team.logo_url" style="width:52px;height:52px;object-fit:contain" />
                                        <span v-else style="font-size:16px;font-weight:700;color:#fff">{{ featured.home_team?.initials }}</span>
                                    </div>
                                    <span class="feat-name">{{ teamName(featured.home_team) }}</span>
                                </div>

                                <div class="feat-center">
                                    <div v-if="showScore" class="feat-score">{{ featured.home_score }} – {{ featured.away_score }}</div>
                                    <div v-else class="feat-score-hidden">Reveal score</div>
                                    <span style="font-size:11px;color:#555;margin-top:4px">{{ featured.round || leagueName(featured.league?.name) }}</span>
                                </div>

                                <div class="feat-team">
                                    <div class="feat-logo">
                                        <img v-if="featured.away_team?.logo_url" :src="featured.away_team.logo_url" style="width:52px;height:52px;object-fit:contain" />
                                        <span v-else style="font-size:16px;font-weight:700;color:#fff">{{ featured.away_team?.initials }}</span>
                                    </div>
                                    <span class="feat-name">{{ teamName(featured.away_team) }}</span>
                                </div>
                            </div>

                            <div class="feat-play" style="z-index:2"><i class="ti ti-player-play" aria-hidden="true"></i></div>
                        </div>

                        <div class="feat-info">
                            <span class="league-badge">{{ leagueName(featured.league?.name) }}</span>
                            <div style="display:flex;align-items:center;gap:14px">
                                <span v-if="featured.venue" style="font-size:12px;color:#888;display:flex;align-items:center;gap:4px">
                                    <i class="ti ti-map-pin" style="font-size:13px" aria-hidden="true"></i>{{ featured.venue }}
                                </span>
                                <span style="font-size:12px;color:#888">{{ formatDate(featured.match_date) }}</span>
                            </div>
                        </div>
                    </Link>

                    <!-- Grid -->
                    <div>
                        <div style="font-size:11px;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:0.5px;border-left:2px solid #ef4444;padding-left:8px;margin-bottom:14px">
                            {{ t('match.more') }}
                        </div>
                        <div class="match-grid">
                            <MatchCard
                                v-for="match in rest"
                                :key="match.id"
                                :match="match"
                                :show-score="showScore"
                                :locale="locale"
                            />
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div style="display:flex;justify-content:center;gap:6px;margin-top:8px">
                        <button
                            v-for="link in matches.links"
                            :key="link.label"
                            @click="goToPage(link.url)"
                            :disabled="!link.url"
                            class="page-btn"
                            :class="{ 'page-btn-active': link.active }"
                            v-html="link.label"
                        />
                    </div>
                </div>

                <!-- Sidebar -->
                <div style="display:flex;flex-direction:column;gap:16px">

                    <!-- Popular Teams -->
                    <div class="sidebar-box">
                        <div class="side-label">Popular Teams</div>
                        <Link
                            v-for="team in popular_teams"
                            :key="team.id"
                            :href="localePath(`/team/${team.slug}`)"
                            class="side-team"
                        >
                            <div style="display:flex;align-items:center;gap:10px;flex:1;overflow:hidden">
                                <div class="team-logo">
                                    <img v-if="team.logo_url" :src="team.logo_url" style="width:20px;height:20px;object-fit:contain" />
                                    <span v-else style="font-size:9px;font-weight:700;color:#fff">{{ team.initials }}</span>
                                </div>
                                <span style="font-size:13px;font-weight:600;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ teamName(team) }}</span>
                            </div>
                            <span style="font-size:11px;color:#fff;background:#1a1a1a;padding:2px 8px;border-radius:99px;flex-shrink:0">{{ team.match_count }}</span>
                        </Link>
                    </div>

                    <!-- Leagues -->
                    <div class="sidebar-box">
                        <div class="side-label">Leagues</div>
                        <Link
                            v-for="league in leagues"
                            :key="league.league_slug"
                            :href="localePath(`/league/${league.slug}`)"
                            class="side-league"
                        >
                            <span style="font-size:13px;font-weight:600;color:#fff">{{ leagueName(league.name) }}</span>
                            <span style="font-size:11px;color:#fff;background:#1a1a1a;padding:2px 8px;border-radius:99px;flex-shrink:0">{{ league.match_count }}</span>
                        </Link>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import MatchCard from '@/Components/MatchCard.vue'
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

const featured = computed(() => props.matches?.data?.[0] ?? null)
const rest      = computed(() => props.matches?.data?.slice(1) ?? [])

const featuredBg = computed(() => {
    if (!featured.value) return {}
    const h = featured.value.home_team?.primary_color || '#1C3C6B'
    const a = featured.value.away_team?.primary_color || '#8b0015'
    return { background: `linear-gradient(135deg, ${h}55 0%, #151515 50%, ${a}55 100%)` }
})

function formatDate(date) {
    if (!date) return ''
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function goToPage(url) {
    if (url) router.visit(url)
}
</script>

<style scoped>
.featured-card { display:block;text-decoration:none;background:#181818;border:0.5px solid #252525;border-radius:12px;overflow:hidden;transition:border-color 0.15s; }
.featured-card:hover { border-color:#333; }
.featured-card:hover .feat-play { opacity:1;transform:scale(1.08); }

.featured-thumb { aspect-ratio:16/6;position:relative;display:flex;align-items:center;justify-content:center;overflow:hidden; }
.pitch-lines { position:absolute;inset:0;width:100%;height:100%;opacity:0.08;pointer-events:none; }
.feat-glow-l { position:absolute;left:0;top:0;bottom:0;width:45%;pointer-events:none; }
.feat-glow-r { position:absolute;right:0;top:0;bottom:0;width:45%;pointer-events:none; }

.feat-teams { display:flex;align-items:center;justify-content:space-between;padding:0 8%;width:100%;position:relative;z-index:1; }
.feat-team { flex:1;display:flex;flex-direction:column;align-items:center;gap:10px; }
.feat-logo { width:80px;height:80px;border-radius:50%;border:1.5px solid rgba(255,255,255,0.2);background:rgba(20,20,20,0.8);display:flex;align-items:center;justify-content:center; }
.feat-name { font-size:15px;font-weight:700;color:#fff;text-align:center; }
.feat-center { display:flex;flex-direction:column;align-items:center;padding:0 20px; }
.feat-score { font-size:38px;font-weight:700;color:#fff;letter-spacing:-2px;line-height:1; }
.feat-score-hidden { font-size:13px;font-weight:600;color:#fff;background:#1a1a1a;border:0.5px solid #333;padding:7px 16px;border-radius:7px;cursor:pointer; }
.feat-play { position:absolute;bottom:14px;right:16px;width:40px;height:40px;border-radius:50%;border:1.5px solid rgba(255,255,255,0.25);background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;opacity:0.4;transition:opacity 0.15s,transform 0.15s; }

.feat-info { padding:12px 18px;display:flex;align-items:center;justify-content:space-between;border-top:0.5px solid #252525; }
.league-badge { font-size:10px;color:#ef4444;background:#1a0000;border:0.5px solid #2a0000;padding:3px 9px;border-radius:3px;text-transform:uppercase;font-weight:700; }

.match-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:12px; }

.page-btn { padding:5px 12px;border-radius:6px;font-size:13px;background:#1a1a1a;color:#fff;border:0.5px solid #2a2a2a;cursor:pointer;transition:all 0.15s; }
.page-btn-active { background:#ef4444;border-color:#ef4444; }
.page-btn:disabled { opacity:0.3;cursor:not-allowed; }

.sidebar-box { background:#181818;border:0.5px solid #252525;border-radius:10px;padding:14px 16px; }
.side-label { font-size:10px;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:0.6px;border-left:2px solid #ef4444;padding-left:8px;margin-bottom:12px;padding-bottom:10px;border-bottom:0.5px solid #222; }
.side-team { display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:0.5px solid #1a1a1a;text-decoration:none;gap:8px;transition:opacity 0.15s; }
.side-team:last-child { border-bottom:none; }
.side-team:hover { opacity:0.8; }
.team-logo { width:32px;height:32px;border-radius:50%;border:1px solid #333;background:#1a1a1a;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.side-league { display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:0.5px solid #1a1a1a;text-decoration:none;transition:opacity 0.15s; }
.side-league:last-child { border-bottom:none; }
.side-league:hover { opacity:0.8; }
</style>
