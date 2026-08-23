<template>
    <Head>
        <title>{{ matchTitle }}</title>
        <meta name="robots" content="noindex,nofollow" />
    </Head>

    <div class="embed-root">
        <!-- Player -->
        <div class="player-wrap">
            <div v-if="!hasVideo" class="no-video">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/>
                </svg>
                <span>No highlight available</span>
            </div>

            <div v-show="hasVideo" class="plyr-wrap">
                <video ref="videoEl" playsinline></video>
            </div>

            <!-- Branding -->
            <a :href="matchPageUrl" target="_blank" rel="noopener" class="brand-badge">
                <svg viewBox="0 0 20 20" fill="currentColor" width="12" height="12">
                    <polygon points="4,2 16,10 4,18"/>
                </svg>
                BolaReel
            </a>
        </div>

        <!-- Match bar -->
        <div class="match-bar">
            <div class="team home-team">
                <img v-if="match.home_team?.logo_url" :src="match.home_team.logo_url" class="team-logo" alt="" />
                <span v-else class="team-initial">{{ match.home_team?.initials }}</span>
                <span class="team-name">{{ match.home_team?.name }}</span>
            </div>

            <div class="score-block">
                <span class="score">{{ match.home_score ?? 0 }} – {{ match.away_score ?? 0 }}</span>
                <span v-if="match.league?.name" class="league-name">{{ match.league.name }}</span>
            </div>

            <div class="team away-team">
                <span class="team-name">{{ match.away_team?.name }}</span>
                <img v-if="match.away_team?.logo_url" :src="match.away_team.logo_url" class="team-logo" alt="" />
                <span v-else class="team-initial">{{ match.away_team?.initials }}</span>
            </div>
        </div>

        <!-- Source switcher -->
        <div v-if="playableVideos.length > 1" class="src-bar">
            <button
                v-for="(v, i) in playableVideos" :key="v.id"
                class="src-btn"
                :class="{ active: activeIdx === i }"
                @click="switchVideo(i)"
            >
                {{ v.quality || v.language || `Source ${i + 1}` }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import Plyr from 'plyr'
import 'plyr/dist/plyr.css'
import Hls from 'hls.js'

const props = defineProps({
    match: Object,
})

const videoEl  = ref(null)
const activeIdx = ref(0)
let plyrInstance = null
let hlsInstance  = null

const matchTitle = computed(() => {
    const h = props.match.home_team?.name ?? ''
    const a = props.match.away_team?.name ?? ''
    return `${h} vs ${a} – BolaReel`
})

const matchPageUrl = computed(() =>
    `${window.location.origin}/match/${props.match.slug}`
)

const playableVideos = computed(() =>
    props.match.videos?.filter(v => v.source_url || v.local_path) ?? []
)

const hasVideo = computed(() => playableVideos.value.length > 0)

function getYouTubeId(url) {
    if (!url) return null
    const m = url.match(/(?:youtube\.com\/(?:watch\?.*v=|embed\/|v\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/)
    return m ? m[1] : null
}

function loadVideo(video) {
    const ytId = getYouTubeId(video.source_url)
    if (ytId) {
        plyrInstance.source = {
            type: 'video',
            sources: [{ src: ytId, provider: 'youtube' }],
        }
        return
    }
    if (video.status === 'ready') fetchAndPlayHls(video)
}

async function fetchAndPlayHls(video) {
    try {
        const res = await fetch(`/api/videos/${video.id}/stream`)
        if (!res.ok) return
        const data = await res.json()
        if (data.stream_url) playHls(data.stream_url)
    } catch (e) {
        console.error('Stream error', e)
    }
}

function playHls(url) {
    if (hlsInstance) { hlsInstance.destroy(); hlsInstance = null }
    const media = plyrInstance?.media ?? videoEl.value
    if (!media) return

    if (Hls.isSupported()) {
        hlsInstance = new Hls({
            enableWorker: true,
            maxBufferLength: 30,
            maxMaxBufferLength: 30,
            maxBufferSize: 20 * 1000 * 1000,
            backBufferLength: 0,
        })
        hlsInstance.on(Hls.Events.ERROR, (_, data) => {
            if (data.fatal) {
                if (data.type === Hls.ErrorTypes.NETWORK_ERROR) hlsInstance.startLoad()
                else if (data.type === Hls.ErrorTypes.MEDIA_ERROR) hlsInstance.recoverMediaError()
                else hlsInstance.destroy()
            }
        })
        hlsInstance.loadSource(url)
        hlsInstance.attachMedia(media)
    } else if (media.canPlayType('application/vnd.apple.mpegurl')) {
        media.src = url
    }
}

function switchVideo(idx) {
    activeIdx.value = idx
    loadVideo(playableVideos.value[idx])
}

onMounted(() => {
    plyrInstance = new Plyr(videoEl.value, {
        controls: ['play', 'rewind', 'fast-forward', 'progress', 'current-time', 'mute', 'volume', 'fullscreen'],
        seekTime: 10,
        resetOnEnd: false,
        invertTime: false,
        youtube: { noCookie: true, rel: 0, showinfo: 0, iv_load_policy: 3, modestbranding: 1 },
    })

    if (playableVideos.value.length > 0) loadVideo(playableVideos.value[0])
})

onUnmounted(() => {
    hlsInstance?.destroy()
    plyrInstance?.destroy()
})
</script>

<style>
:root {
    --plyr-color-main: #e01552;
    --plyr-video-background: #000;
    --plyr-font-family: system-ui, -apple-system, sans-serif;
    --plyr-range-fill-background: #e01552;
}
</style>

<style scoped>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.embed-root {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: #0b0b10;
    color: #fff;
    font-family: system-ui, -apple-system, sans-serif;
    overflow: hidden;
}

/* ── Player ── */
.player-wrap {
    position: relative;
    flex: 1;
    background: #000;
    min-height: 0;
}

.plyr-wrap {
    position: absolute;
    inset: 0;
}

.plyr-wrap :deep(.plyr),
.plyr-wrap :deep(.plyr video),
.plyr-wrap :deep(.plyr__video-wrapper) {
    width: 100% !important;
    height: 100% !important;
}

.no-video {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: #555;
    font-size: 13px;
}

.no-video svg {
    width: 48px;
    height: 48px;
    opacity: .4;
}

/* Branding badge */
.brand-badge {
    position: absolute;
    bottom: 48px;
    right: 8px;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 4px;
    background: rgba(0,0,0,.55);
    backdrop-filter: blur(4px);
    color: rgba(255,255,255,.7);
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    letter-spacing: .3px;
    transition: color .15s, background .15s;
}

.brand-badge:hover {
    color: #fff;
    background: rgba(224,21,82,.8);
}

/* ── Match bar ── */
.match-bar {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: #13131c;
    border-top: 1px solid rgba(255,255,255,.06);
    min-height: 48px;
}

.team {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
    min-width: 0;
}

.home-team { justify-content: flex-start; }
.away-team { justify-content: flex-end; }

.team-logo {
    width: 22px;
    height: 22px;
    object-fit: contain;
    flex-shrink: 0;
}

.team-initial {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #2a2a3a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
    font-weight: 700;
    flex-shrink: 0;
}

.team-name {
    font-size: 12px;
    font-weight: 600;
    color: #ccc;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.score-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    min-width: 72px;
    gap: 2px;
}

.score {
    font-size: 16px;
    font-weight: 700;
    letter-spacing: .5px;
    color: #fff;
    white-space: nowrap;
}

.league-name {
    font-size: 10px;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100px;
}

/* ── Source bar ── */
.src-bar {
    flex-shrink: 0;
    display: flex;
    gap: 4px;
    padding: 6px 12px;
    background: #0f0f18;
    border-top: 1px solid rgba(255,255,255,.05);
}

.src-btn {
    padding: 4px 10px;
    border-radius: 4px;
    border: 1px solid rgba(255,255,255,.12);
    background: transparent;
    color: #888;
    font-size: 11px;
    cursor: pointer;
    transition: all .15s;
}

.src-btn:hover { border-color: rgba(255,255,255,.3); color: #ccc; }
.src-btn.active { background: #e01552; border-color: #e01552; color: #fff; }
</style>
