<template>
    <Link :href="localePath(`/match/${match.slug}`)" class="match-card">
        <!-- Thumbnail -->
        <div class="card-thumb">
            <div class="thumb-bg" :style="thumbBg">
                <div class="thumb-overlay"></div>
                <!-- Faded initials bg -->
                <span class="init-l" :style="{ color: match.home_team?.primary_color || '#fff' }">{{ match.home_team?.initials }}</span>
                <span class="init-r" :style="{ color: match.away_team?.primary_color || '#fff' }">{{ match.away_team?.initials }}</span>

                <!-- Logos -->
                <div class="thumb-teams">
                    <div class="logo-wrap" :style="{ '--glow': match.home_team?.primary_color || '#fff' }">
                        <img v-if="match.home_team?.logo_url" :src="match.home_team.logo_url" class="logo-img" />
                        <span v-else class="logo-text">{{ match.home_team?.initials }}</span>
                    </div>
                    <span class="thumb-vs">VS</span>
                    <div class="logo-wrap" :style="{ '--glow': match.away_team?.primary_color || '#fff' }">
                        <img v-if="match.away_team?.logo_url" :src="match.away_team.logo_url" class="logo-img" />
                        <span v-else class="logo-text">{{ match.away_team?.initials }}</span>
                    </div>
                </div>

                <!-- Bottom bar -->
                <div class="thumb-foot">
                    <span class="thumb-league">{{ leagueName(match.league?.name) }}</span>
                    <span class="thumb-date">{{ formatDate(match.match_date) }}</span>
                </div>
            </div>

            <!-- Play btn -->
            <div class="play-wrap">
                <div class="play-btn">
                    <i class="ti ti-player-play" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <!-- Card body -->
        <div class="card-body">
            <div class="score-row">
                <span class="team-name">{{ teamName(match.home_team) }}</span>
                <span v-if="showScore" class="score">{{ match.home_score ?? '?' }}–{{ match.away_score ?? '?' }}</span>
                <span v-else class="score muted">?–?</span>
                <span class="team-name right">{{ teamName(match.away_team) }}</span>
            </div>
        </div>
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useLocale } from '@/composables/useLocale'

const props = defineProps({
    match:     { type: Object,  required: true },
    showScore: { type: Boolean, default: true },
    locale:    { type: String,  default: 'en' },
})

const { teamName, leagueName, localePath } = useLocale(props.locale)

const thumbBg = computed(() => {
    if (props.match.thumbnail_url) {
        return {
            backgroundImage: `url(${props.match.thumbnail_url})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center',
        }
    }
    return { background: 'linear-gradient(135deg, #1C3C6B44 0%, #0d0d0d 50%, #8b001544 100%)' }
})

function formatDate(date) {
    if (!date) return ''
    return new Date(date).toLocaleDateString('en-GB', { day:'2-digit', month:'short' })
}
</script>

<style scoped>
.match-card { background:#111;border:0.5px solid #1f1f1f;border-radius:10px;overflow:hidden;cursor:pointer;text-decoration:none;display:block;transition:border-color 0.15s; }
.match-card:hover { border-color:#2d2d2d; }
.match-card:hover .play-btn { opacity:1;transform:scale(1.1); }

.card-thumb { position:relative;aspect-ratio:16/9; }
.thumb-bg { position:absolute;inset:0;overflow:hidden; }
.thumb-overlay { position:absolute;inset:0;background:rgba(0,0,0,0.45);z-index:0; }

.init-l { position:absolute;left:-4%;top:50%;transform:translateY(-50%);font-size:88px;font-weight:700;opacity:0.07;line-height:1;pointer-events:none;user-select:none; }
.init-r { position:absolute;right:-4%;top:50%;transform:translateY(-50%);font-size:88px;font-weight:700;opacity:0.07;line-height:1;pointer-events:none;user-select:none; }

.thumb-teams { position:absolute;inset:0;display:flex;align-items:center;justify-content:space-around;padding:0 12%;z-index:1; }
.logo-wrap {
    width:56px;height:56px;border-radius:50%;
    border:1px solid rgba(255,255,255,0.12);
    background:rgba(0,0,0,0.45);
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 0 16px var(--glow, #fff)22;
}
.logo-img { width:38px;height:38px;object-fit:contain; }
.logo-text { font-size:13px;font-weight:600;color:#e5e7eb; }
.thumb-vs { font-size:11px;color:#4b5563;font-weight:500; }

.thumb-foot { position:absolute;bottom:0;left:0;right:0;display:flex;align-items:center;justify-content:space-between;padding:5px 10px 7px;z-index:1; }
.thumb-league { font-size:10px;color:#ef4444;background:#1a0000;border:0.5px solid #2a0000;padding:2px 7px;border-radius:3px;letter-spacing:0.3px; }
.thumb-date { font-size:10px;color:#4b5563; }

.play-wrap { position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none; }
.play-btn { width:38px;height:38px;border-radius:50%;border:1.5px solid rgba(255,255,255,0.2);background:rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;font-size:15px;color:#fff;opacity:0.4;transition:opacity 0.15s,transform 0.15s; }

.card-body { padding:10px 12px; }
.score-row { display:flex;align-items:center;justify-content:space-between;gap:6px; }
.team-name { font-size:13px;font-weight:500;color:#e5e7eb;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.team-name.right { text-align:right; }
.score { font-size:14px;font-weight:500;color:#fff;flex-shrink:0;padding:0 4px; }
.score.muted { color:#374151; }
</style>
