<template>
    <AppLayout :leagues="[]" :locale="locale">
        <div class="max-w-7xl mx-auto" style="padding:20px">
            <div style="display:grid;grid-template-columns:1fr 300px;gap:20px">

                <!-- Main -->
                <div>
                    <!-- Video player -->
                    <div style="aspect-ratio:16/9;background:#000;border-radius:10px;overflow:hidden;margin-bottom:16px">
                        <video ref="videoEl" class="w-full h-full" controls />
                    </div>

                    <!-- Match header -->
                    <div style="background:#111;border:0.5px solid #1f1f1f;border-radius:10px;padding:20px;margin-bottom:16px">
                        <!-- Competition + date -->
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="font-size:10px;color:#ef4444;text-transform:uppercase;letter-spacing:1px;font-weight:500">
                                    {{ match.league }}
                                </span>
                                <span v-if="match.round" style="font-size:10px;color:#4b5563">· {{ match.round }}</span>
                            </div>
                            <div style="text-align:right">
                                <div style="font-size:14px;font-weight:500;color:#fff">
                                    {{ formatTime(match.kick_off_time) }}
                                </div>
                                <div style="font-size:11px;color:#6b7280">{{ formatDate(match.match_date) }}</div>
                            </div>
                        </div>

                        <!-- Teams + Score -->
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                            <!-- Home team -->
                            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:10px">
                                <div class="team-logo-lg">
                                    <img v-if="match.home_team?.logo_url" :src="match.home_team.logo_url" :alt="match.home_team.name" class="w-full h-full object-contain" />
                                    <span v-else style="font-size:20px;font-weight:700;color:#e5e7eb">{{ match.home_team?.initials }}</span>
                                </div>
                                <span style="font-size:16px;font-weight:500;color:#fff;text-align:center">{{ match.home_team?.name }}</span>
                            </div>

                            <!-- Score center -->
                            <div style="text-align:center;padding:0 20px">
                                <div v-if="showScore" style="font-size:42px;font-weight:500;color:#fff;letter-spacing:-2px;line-height:1">
                                    {{ match.home_score }} – {{ match.away_score }}
                                </div>
                                <div v-else style="font-size:18px;color:#374151;margin-bottom:4px">
                                    <button @click="showScore = true" style="font-size:11px;color:#6b7280;background:#1a1a1a;border:0.5px solid #2a2a2a;padding:6px 14px;border-radius:5px;cursor:pointer">
                                        Show Score
                                    </button>
                                </div>
                                <div style="font-size:10px;color:#4b5563;margin-top:4px;text-transform:uppercase;letter-spacing:0.5px">Full Time</div>
                            </div>

                            <!-- Away team -->
                            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:10px">
                                <div class="team-logo-lg">
                                    <img v-if="match.away_team?.logo_url" :src="match.away_team.logo_url" :alt="match.away_team.name" class="w-full h-full object-contain" />
                                    <span v-else style="font-size:20px;font-weight:700;color:#e5e7eb">{{ match.away_team?.initials }}</span>
                                </div>
                                <span style="font-size:16px;font-weight:500;color:#fff;text-align:center">{{ match.away_team?.name }}</span>
                            </div>
                        </div>

                        <!-- Venue + Referee -->
                        <div v-if="match.venue || match.referee" style="display:flex;gap:16px;padding-top:14px;border-top:0.5px solid #1a1a1a">
                            <span v-if="match.venue" style="font-size:11px;color:#4b5563;display:flex;align-items:center;gap:5px">
                                📍 {{ match.venue }}
                            </span>
                            <span v-if="match.referee" style="font-size:11px;color:#4b5563;display:flex;align-items:center;gap:5px">
                                👤 {{ match.referee }}
                            </span>
                        </div>
                    </div>

                    <!-- Tabs: Events / Lineup / Stats / Players -->
                    <div style="display:flex;gap:2px;margin-bottom:16px;background:#111;border-radius:8px;padding:4px">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            :class="['tab-btn', activeTab === tab.key ? 'active' : '']"
                        >{{ tab.label }}</button>
                    </div>

                    <!-- Events tab -->
                    <div v-if="activeTab === 'events'" style="background:#111;border:0.5px solid #1f1f1f;border-radius:10px;padding:16px">
                        <div v-if="!match.events?.length" style="color:#4b5563;font-size:13px;text-align:center;padding:20px">
                            No events data
                        </div>
                        <div v-for="event in match.events" :key="event.id" class="event-row">
                            <span class="event-min">{{ event.minute }}'</span>
                            <span class="event-icon">
                                {{ event.type === 'goal' ? '⚽' : event.type === 'card' ? (event.detail?.includes('Yellow') ? '🟨' : '🟥') : '🔄' }}
                            </span>
                            <span class="event-player">{{ event.player }}</span>
                            <span v-if="event.assist" class="event-assist">({{ event.assist }})</span>
                            <span class="event-team-badge" :class="event.team_id === match.home_team?.id ? 'home' : 'away'">
                                {{ event.team_id === match.home_team?.id ? match.home_team?.initials : match.away_team?.initials }}
                            </span>
                        </div>
                    </div>

                    <!-- Lineup tab -->
                    <div v-if="activeTab === 'lineup'" style="background:#111;border:0.5px solid #1f1f1f;border-radius:10px;padding:16px">
                        <div v-if="!match.lineups?.length" style="color:#4b5563;font-size:13px;text-align:center;padding:20px">
                            No lineup data
                        </div>
                        <div v-else style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                            <div v-for="lineup in match.lineups" :key="lineup.id">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                                    <span style="font-size:13px;font-weight:500;color:#fff">
                                        {{ lineup.team_id === match.home_team?.id ? match.home_team?.name : match.away_team?.name }}
                                    </span>
                                    <span style="font-size:11px;color:#6b7280;background:#1a1a1a;padding:3px 8px;border-radius:4px">
                                        {{ lineup.formation }}
                                    </span>
                                </div>
                                <div v-for="player in lineup.starters" :key="player.number" class="player-row">
                                    <span class="player-num">{{ player.number }}</span>
                                    <span class="player-pos">{{ player.position }}</span>
                                    <span class="player-name">{{ player.name }} {{ player.is_captain ? '(C)' : '' }}</span>
                                    <span v-if="getPlayerRating(lineup.team_id, player.name)" class="player-rating" :class="ratingClass(getPlayerRating(lineup.team_id, player.name))">
                                        {{ getPlayerRating(lineup.team_id, player.name) }}
                                    </span>
                                </div>
                                <div v-if="lineup.substitutes?.length" style="margin-top:10px">
                                    <div style="font-size:10px;color:#4b5563;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;padding-top:8px;border-top:0.5px solid #1a1a1a">
                                        Substitutes
                                    </div>
                                    <div v-for="player in lineup.substitutes" :key="player.number" class="player-row sub">
                                        <span class="player-num">{{ player.number }}</span>
                                        <span class="player-pos">{{ player.position }}</span>
                                        <span class="player-name">{{ player.name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats tab -->
                    <div v-if="activeTab === 'stats'" style="background:#111;border:0.5px solid #1f1f1f;border-radius:10px;padding:16px">
                        <div v-if="!match.statistics?.length" style="color:#4b5563;font-size:13px;text-align:center;padding:20px">
                            No statistics data
                        </div>
                        <template v-else>
                            <div v-for="(stat, key) in mergedStats" :key="key" class="stat-row">
                                <span class="stat-val home">{{ stat.home ?? 0 }}</span>
                                <div class="stat-bars">
                                    <div class="stat-bar-wrap home">
                                        <div class="stat-bar-fill" style="background:#ef4444" :style="{ width: statPercent(stat.home, stat.away) + '%' }"></div>
                                    </div>
                                    <span class="stat-label">{{ formatStatKey(key) }}</span>
                                    <div class="stat-bar-wrap away">
                                        <div class="stat-bar-fill" style="background:#3b82f6" :style="{ width: statPercent(stat.away, stat.home) + '%' }"></div>
                                    </div>
                                </div>
                                <span class="stat-val away">{{ stat.away ?? 0 }}</span>
                            </div>
                        </template>
                    </div>

                    <!-- Players tab -->
                    <div v-if="activeTab === 'players'" style="background:#111;border:0.5px solid #1f1f1f;border-radius:10px;padding:16px">
                        <div v-if="!match.player_stats?.length" style="color:#4b5563;font-size:13px;text-align:center;padding:20px">
                            No player stats data
                        </div>
                        <div v-else style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                            <div v-for="teamGroup in groupedPlayers" :key="teamGroup.team_id">
                                <div style="font-size:12px;font-weight:500;color:#fff;margin-bottom:8px;padding-bottom:6px;border-bottom:0.5px solid #1a1a1a">
                                    {{ teamGroup.team_name }}
                                </div>
                                <div v-for="p in teamGroup.players" :key="p.id" class="player-row">
                                    <span class="player-name flex-1">{{ p.name }}</span>
                                    <span style="font-size:10px;color:#4b5563;margin-right:4px">
                                        {{ p.goals > 0 ? `⚽${p.goals}` : '' }}{{ p.assists > 0 ? ` 🅰️${p.assists}` : '' }}
                                    </span>
                                    <span v-if="p.rating" class="player-rating" :class="ratingClass(p.rating)">
                                        {{ p.rating }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar: related matches -->
                <div>
                    <div class="sec-label" style="margin-bottom:12px">More Highlights</div>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        <Link
                            v-for="m in related"
                            :key="m.id"
                            :href="`/match/${m.slug}`"
                            class="related-card"
                        >
                            <div class="related-thumb" :style="relatedThumbBg(m)">
                                <div style="display:flex;align-items:center;justify-content:center;gap:6px;padding:8px">
                                    <div class="rel-logo">
                                        <img v-if="m.home_team?.logo_url" :src="m.home_team.logo_url" style="width:20px;height:20px;object-fit:contain" />
                                        <span v-else style="font-size:9px;color:#e5e7eb">{{ m.home_team?.initials }}</span>
                                    </div>
                                    <span style="font-size:10px;color:#4b5563">vs</span>
                                    <div class="rel-logo">
                                        <img v-if="m.away_team?.logo_url" :src="m.away_team.logo_url" style="width:20px;height:20px;object-fit:contain" />
                                        <span v-else style="font-size:9px;color:#e5e7eb">{{ m.away_team?.initials }}</span>
                                    </div>
                                </div>
                            </div>
                            <div style="padding:6px 8px">
                                <div style="font-size:11px;font-weight:500;color:#e5e7eb;line-height:1.3">
                                    {{ m.home_team?.name }} vs {{ m.away_team?.name }}
                                </div>
                                <div style="font-size:10px;color:#4b5563;margin-top:2px">{{ formatDate(m.match_date) }}</div>
                            </div>
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import Hls from 'hls.js'

const props = defineProps({
    match:   Object,
    related: Array,
    locale:  String,
})

const videoEl   = ref(null)
const showScore = ref(false)
const activeTab = ref('events')

const tabs = [
    { key: 'events',  label: 'Events' },
    { key: 'lineup',  label: 'Lineup' },
    { key: 'stats',   label: 'Statistics' },
    { key: 'players', label: 'Players' },
]

// Load HLS video
async function loadVideo(video) {
    const res  = await fetch(`/api/videos/${video.id}/stream`)
    const data = await res.json()
    if (data.stream_url) playHls(data.stream_url)
}

function playHls(url) {
    const el = videoEl.value
    if (!el) return
    if (Hls.isSupported()) {
        const hls = new Hls()
        hls.loadSource(url)
        hls.attachMedia(el)
        hls.on(Hls.Events.MANIFEST_PARSED, () => el.play())
    } else if (el.canPlayType('application/vnd.apple.mpegurl')) {
        el.src = url
        el.play()
    }
}

// Stats helpers
const mergedStats = computed(() => {
    if (!props.match.statistics?.length) return {}
    const home = props.match.statistics.find(s => s.team_id === props.match.home_team?.id)
    const away = props.match.statistics.find(s => s.team_id === props.match.away_team?.id)
    const result = {}
    const allKeys = new Set([...Object.keys(home?.stats || {}), ...Object.keys(away?.stats || {})])
    allKeys.forEach(key => {
        result[key] = { home: home?.stats[key] ?? 0, away: away?.stats[key] ?? 0 }
    })
    return result
})

function statPercent(val, other) {
    const v = parseInt(val) || 0
    const o = parseInt(other) || 0
    if (v + o === 0) return 50
    return Math.round((v / (v + o)) * 100)
}

function formatStatKey(key) {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

// Player helpers
const groupedPlayers = computed(() => {
    if (!props.match.player_stats?.length) return []
    const groups = {}
    props.match.player_stats.forEach(p => {
        if (!groups[p.team_id]) {
            groups[p.team_id] = {
                team_id:   p.team_id,
                team_name: p.team_id === props.match.home_team?.id
                    ? props.match.home_team?.name
                    : props.match.away_team?.name,
                players: [],
            }
        }
        groups[p.team_id].players.push(p)
    })
    Object.values(groups).forEach(g => {
        g.players.sort((a, b) => (parseFloat(b.rating) || 0) - (parseFloat(a.rating) || 0))
    })
    return Object.values(groups)
})

function getPlayerRating(teamId, playerName) {
    return props.match.player_stats?.find(p =>
        p.team_id === teamId && p.name === playerName
    )?.rating ?? null
}

function ratingClass(rating) {
    const r = parseFloat(rating)
    if (r >= 8)   return 'rating-high'
    if (r >= 6.5) return 'rating-mid'
    return 'rating-low'
}

function formatDate(date) {
    if (!date) return ''
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatTime(time) {
    if (!time) return ''
    return time.substring(0, 5)
}

function relatedThumbBg(m) {
    const h = m.home_team?.primary_color || '#1a1a1a'
    const a = m.away_team?.primary_color || '#1a1a1a'
    return { background: `linear-gradient(135deg, ${h}33 0%, #0a0a0a 50%, ${a}33 100%)` }
}

onMounted(() => {
    const readyVideo = props.match.videos?.find(v => v.status === 'ready')
    if (readyVideo) loadVideo(readyVideo)
})
</script>

<style scoped>
.team-logo-lg {
    width: 80px; height: 80px; border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.4);
    display: flex; align-items: center; justify-content: center;
}

.tab-btn {
    flex: 1; padding: 7px; font-size: 12px;
    color: #6b7280; background: transparent;
    border: none; border-radius: 6px; cursor: pointer;
    transition: all 0.15s;
}
.tab-btn.active { background: #1a1a1a; color: #fff; }
.tab-btn:hover:not(.active) { color: #e5e7eb; }

.event-row {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 0; border-bottom: 0.5px solid #161616;
}
.event-min  { font-size: 11px; color: #6b7280; min-width: 28px; }
.event-icon { font-size: 14px; }
.event-player { font-size: 12px; color: #e5e7eb; flex: 1; }
.event-assist { font-size: 11px; color: #6b7280; }
.event-team-badge {
    font-size: 9px; padding: 2px 6px; border-radius: 3px;
    font-weight: 500;
}
.event-team-badge.home { background: #1a0000; color: #ef4444; }
.event-team-badge.away { background: #001a33; color: #3b82f6; }

.player-row {
    display: flex; align-items: center; gap: 6px;
    padding: 5px 0; border-bottom: 0.5px solid #161616;
}
.player-row.sub { opacity: 0.7; }
.player-num  { font-size: 11px; color: #4b5563; min-width: 20px; }
.player-pos  { font-size: 10px; color: #4b5563; min-width: 16px; }
.player-name { font-size: 12px; color: #e5e7eb; flex: 1; }
.player-rating {
    font-size: 11px; font-weight: 500; padding: 2px 6px; border-radius: 3px;
}
.rating-high { background: #0d2b0d; color: #4ade80; }
.rating-mid  { background: #2b2000; color: #fbbf24; }
.rating-low  { background: #2b1000; color: #f97316; }

.stat-row {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 0; border-bottom: 0.5px solid #161616;
}
.stat-val { font-size: 12px; font-weight: 500; color: #fff; min-width: 28px; }
.stat-val.home { text-align: right; }
.stat-val.away { text-align: left; }
.stat-bars { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; }
.stat-label { font-size: 10px; color: #4b5563; text-align: center; }
.stat-bar-wrap {
    width: 100%; height: 3px; background: #1a1a1a; border-radius: 2px;
    display: flex;
}
.stat-bar-wrap.home { justify-content: flex-end; }
.stat-bar-wrap.away { justify-content: flex-start; }
.stat-bar-fill { height: 100%; border-radius: 2px; transition: width 0.3s; }

.sec-label { font-size: 10px; color: #4b5563; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 500; }

.related-card {
    display: flex; gap: 10px; text-decoration: none;
    background: #111; border: 0.5px solid #1f1f1f;
    border-radius: 8px; overflow: hidden;
}
.related-card:hover { border-color: #2d2d2d; }
.related-thumb { width: 80px; flex-shrink: 0; }
.rel-logo {
    width: 26px; height: 26px; border-radius: 50%;
    background: rgba(0,0,0,0.5); border: 0.5px solid rgba(255,255,255,0.1);
    display: flex; align-items: center; justify-content: center;
}
</style>
