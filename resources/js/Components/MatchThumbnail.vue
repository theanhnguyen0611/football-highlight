<template>
    <div class="match-thumb" :style="bgStyle">
        <div class="thumb-overlay"></div>

        <div v-if="!match.league?.background_url_full" class="pitch-lines-wrap">
            <svg class="pitch-lines" viewBox="0 0 560 200" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect width="560" height="200" fill="none" stroke="#fff" stroke-width="1.5"/>
                <circle cx="280" cy="100" r="38" fill="none" stroke="#fff" stroke-width="1.5"/>
                <line x1="280" y1="0" x2="280" y2="200" stroke="#fff" stroke-width="1"/>
                <rect x="0" y="58" width="64" height="84" fill="none" stroke="#fff" stroke-width="1.5"/>
                <rect x="496" y="58" width="64" height="84" fill="none" stroke="#fff" stroke-width="1.5"/>
            </svg>
        </div>

        <div class="thumb-teams">
            <div class="thumb-team">
                <div class="thumb-logo">
                    <img v-if="match.home_team?.logo_url" :src="match.home_team.logo_url" style="width:100%;height:100%;object-fit:contain;padding:8px" />
                    <span v-else class="thumb-initials">{{ match.home_team?.initials }}</span>
                </div>
                <span class="thumb-name">{{ teamName(match.home_team) }}</span>
            </div>

            <span class="thumb-vs">VS</span>

            <div class="thumb-team">
                <div class="thumb-logo">
                    <img v-if="match.away_team?.logo_url" :src="match.away_team.logo_url" style="width:100%;height:100%;object-fit:contain;padding:8px" />
                    <span v-else class="thumb-initials">{{ match.away_team?.initials }}</span>
                </div>
                <span class="thumb-name">{{ teamName(match.away_team) }}</span>
            </div>
        </div>

        <slot />
    </div>
</template>

<script setup>
import { computed, toRef } from 'vue'
import { useLocale } from '@/composables/useLocale'

const props = defineProps({
    match:  { type: Object, required: true },
    locale: { type: String, default: 'en' },
})

const { teamName } = useLocale(toRef(props, 'locale'))

// Background: league.background_url_full (local image) if present, otherwise
// a gradient fallback derived from the league's primary color.
const bgStyle = computed(() => {
    const league = props.match?.league
    if (league?.background_url_full) {
        return {
            backgroundImage: `url(${league.background_url_full})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center',
        }
    }

    const c = league?.primary_color || '#1f1f1f'
    return {
        background: `linear-gradient(135deg, ${c}66 0%, #151515 60%, ${c}33 100%)`,
    }
})
</script>

<style scoped>
.match-thumb {
    position: relative;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    aspect-ratio: 16/9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background-color: #151515;
}

.thumb-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 0;
}

.pitch-lines-wrap {
    position: absolute;
    inset: 0;
    z-index: 1;
}

.pitch-lines {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0.08;
    pointer-events: none;
}

.thumb-teams {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 28px;
    padding: 0 10%;
    width: 100%;
    position: relative;
    z-index: 2;
}

.thumb-team {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.thumb-logo {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: 1.5px solid rgba(255,255,255,0.2);
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}

.thumb-initials {
    font-size: 13px;
    font-weight: 700;
    color: #1a1a1a;
}

.thumb-name {
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    text-align: center;
    text-shadow: 0 1px 4px rgba(0,0,0,0.6);
    max-width: 130px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.thumb-vs {
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    opacity: 0.9;
    text-shadow: 0 1px 4px rgba(0,0,0,0.6);
}

/* Smaller cards (grid context) scale down logos/text */
.match-thumb.compact .thumb-logo { width: 44px; height: 44px; }
.match-thumb.compact .thumb-name { font-size: 11px; max-width: 90px; }
.match-thumb.compact .thumb-vs { font-size: 16px; }
.match-thumb.compact .thumb-teams { gap: 18px; }
</style>
