import { onMounted, onUnmounted, computed, isRef, watch } from 'vue'
import { translations as localeTranslations, leagueTranslations, nationalTeamTranslations, ROUND_LOOKUP } from './useLocale.js'

const LOCALES = ['en', 'es', 'pt', 'ar', 'id', 'ja', 'fr', 'de', 'tr', 'hi']

export const HTML_LANG = {
    en: 'en', es: 'es', pt: 'pt-BR', ar: 'ar', id: 'id',
    ja: 'ja', fr: 'fr', de: 'de', tr: 'tr', hi: 'hi',
}

const SITE_NAME = 'BolaReel'

const OG_LOCALE = {
    en: 'en_US', es: 'es_ES', pt: 'pt_BR', ar: 'ar_AR', id: 'id_ID',
    ja: 'ja_JP', fr: 'fr_FR', de: 'de_DE', tr: 'tr_TR', hi: 'hi_IN',
}

function ogLocaleAlternates(current) {
    return Object.values(OG_LOCALE).filter(l => l !== current)
}

function breadcrumbLd(items) {
    return {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: items.map((it, i) => ({
            '@type': 'ListItem',
            position: i + 1,
            name: it.name,
            item: it.url,
        })),
    }
}

const DATE_LOCALE = {
    en: 'en-GB', es: 'es-ES', pt: 'pt-BR', ar: 'ar-SA', id: 'id-ID',
    ja: 'ja-JP', fr: 'fr-FR', de: 'de-DE', tr: 'tr-TR', hi: 'hi-IN',
}

const MATCH_HIGHLIGHTS = {
    en: 'Highlights', es: 'Resumen',    pt: 'Destaques', ar: 'ملخص',       id: 'Highlight',
    ja: 'ハイライト', fr: 'Résumé',     de: 'Highlights', tr: 'Özeti',      hi: 'हाइलाइट्स',
}

const MATCH_VS = {
    en: 'vs', es: 'vs', pt: 'vs', ar: 'ضد', id: 'vs',
    ja: 'vs', fr: 'vs', de: 'vs', tr: 'vs', hi: 'बनाम',
}

const LEAGUE_TITLE_TPL = {
    en: '%s Highlights & Goals %s',   es: '%s Resumen y Goles %s',
    pt: '%s Destaques e Gols %s',     ar: 'ملخصات وأهداف %s %s',
    id: 'Sorotan & Gol %s %s',        ja: '%s %s ハイライト・ゴール',
    fr: '%s Temps Forts et Buts %s',  de: '%s Highlights & Tore %s',
    tr: '%s Özetler ve Goller %s',    hi: '%s हाइलाइट्स और गोल %s',
}

const TEAM_TITLE_TPL = {
    en: '%s Highlights & Goals %s',   es: '%s Resumen y Goles %s',
    pt: '%s Destaques e Gols %s',     ar: 'ملخصات وأهداف %s %s',
    id: 'Sorotan & Gol %s %s',        ja: '%s %s ハイライト・ゴール',
    fr: '%s Temps Forts et Buts %s',  de: '%s Highlights & Tore %s',
    tr: '%s Özetler ve Goller %s',    hi: '%s हाइलाइट्स और गोल %s',
}

const SEARCH_TITLE_Q = {
    en: (q) => `"${q}" Highlights – Watch Goals & Match Replays`,
    es: (q) => `"${q}" Highlights – Ver Goles y Repeticiones`,
    pt: (q) => `"${q}" Highlights – Assista Gols e Replays`,
    ar: (q) => `"${q}" ملخصات – شاهد الأهداف وإعادات المباريات`,
    id: (q) => `"${q}" Highlight – Tonton Gol dan Replay`,
    ja: (q) => `"${q}" ハイライト – ゴール・試合リプレイ視聴`,
    fr: (q) => `"${q}" Highlights – Regarder Buts et Replays`,
    de: (q) => `"${q}" Highlights – Tore & Spielwiederholungen`,
    tr: (q) => `"${q}" Özetleri – Goller ve Maç Tekrarları`,
    hi: (q) => `"${q}" हाइलाइट्स – गोल और मैच रिप्ले देखें`,
}

const SEARCH_TITLE_EMPTY = {
    en: 'Search Football Highlights & Match Replays',
    es: 'Buscar Highlights y Repeticiones de Fútbol',
    pt: 'Pesquisar Highlights e Replays de Futebol',
    ar: 'البحث عن ملخصات وإعادات كرة القدم',
    id: 'Cari Highlight dan Replay Sepak Bola',
    ja: 'サッカーハイライト・試合リプレイ検索',
    fr: 'Rechercher Highlights et Replays Football',
    de: 'Fußball-Highlights & Spielwiederholungen suchen',
    tr: 'Futbol Özetleri ve Maç Tekrarları Ara',
    hi: 'फुटबॉल हाइलाइट्स और मैच रिप्ले खोजें',
}

const SEARCH_DESC_Q = {
    en: (q) => `Watch football highlights for "${q}". Browse goals, full match replays and results – free on BolaReel.`,
    es: (q) => `Mira highlights de "${q}". Goles, repeticiones y resultados – gratis en BolaReel.`,
    pt: (q) => `Assista highlights de "${q}". Gols, replays e resultados – grátis no BolaReel.`,
    ar: (q) => `شاهد ملخصات "${q}". أهداف وإعادات ونتائج – مجاناً في BolaReel.`,
    id: (q) => `Tonton highlight "${q}". Gol, replay, dan hasil – gratis di BolaReel.`,
    ja: (q) => `"${q}"のハイライト。ゴール・試合リプレイ・結果をBolaReelで無料視聴。`,
    fr: (q) => `Regardez les highlights "${q}". Buts, replays et résultats – gratuits sur BolaReel.`,
    de: (q) => `Highlights zu "${q}". Tore, Spielwiederholungen und Ergebnisse – kostenlos auf BolaReel.`,
    tr: (q) => `"${q}" için özetler. Goller, tekrarlar ve sonuçlar – BolaReel'ta ücretsiz.`,
    hi: (q) => `"${q}" के हाइलाइट्स। गोल, रिप्ले और परिणाम – BolaReel पर निःशुल्क।`,
}

const HOME_TITLES = {
    en: 'Football Highlights, Goals & Match Replays',
    es: 'Highlights, Goles y Repeticiones de Fútbol',
    pt: 'Destaques, Gols e Repetições de Futebol',
    ar: 'ملخصات الأهداف وإعادات مباريات كرة القدم',
    id: 'Sorotan, Gol & Tayangan Ulang Sepak Bola',
    ja: 'サッカーハイライト・ゴール・試合リプレイ',
    fr: 'Temps Forts, Buts et Rediffusions Football',
    de: 'Fußball-Highlights, Tore & Spielwiederholungen',
    tr: 'Futbol Özetleri, Goller ve Maç Tekrarları',
    hi: 'फुटबॉल हाइलाइट्स, गोल और मैच रिप्ले',
}

const PAGE_LABEL = {
    en: 'Page', es: 'Página', pt: 'Página', ar: 'صفحة', id: 'Halaman',
    ja: 'ページ', fr: 'Page', de: 'Seite', tr: 'Sayfa', hi: 'पेज',
}

const HOME_LABEL = {
    en: 'Home', es: 'Inicio', pt: 'Início', ar: 'الرئيسية', id: 'Beranda',
    ja: 'ホーム', fr: 'Accueil', de: 'Startseite', tr: 'Ana Sayfa', hi: 'होम',
}

const SITE_DESCS = {
    en: 'Watch the latest football highlights, goals and full match replays. Premier League, Champions League, La Liga, Serie A, Bundesliga – all free on BolaReel.',
    es: 'Mira los últimos highlights, goles y repeticiones de fútbol. Premier League, Champions League, La Liga, Serie A, Bundesliga – todo gratis en BolaReel.',
    pt: 'Assista aos últimos destaques, gols e repetições completas de futebol. Premier League, Champions League, La Liga, Serie A, Bundesliga – grátis no BolaReel.',
    ar: 'شاهد أحدث ملخصات كرة القدم والأهداف وإعادات المباريات الكاملة. الدوري الإنجليزي، دوري الأبطال، الليغا، السيريا، البوندسليغا – مجاناً في BolaReel.',
    id: 'Tonton sorotan sepak bola terbaru, gol, dan tayangan ulang lengkap. Premier League, Liga Champions, La Liga, Serie A, Bundesliga – gratis di BolaReel.',
    ja: '最新のサッカーハイライト・ゴール・フル試合リプレイを無料視聴。プレミアリーグ、チャンピオンズリーグ、ラ・リーガ、セリエA、ブンデスリーガ – すべて無料。',
    fr: 'Regardez les derniers temps forts, buts et rediffusions de football. Premier League, Ligue des Champions, La Liga, Serie A, Bundesliga – gratuits sur BolaReel.',
    de: 'Die neuesten Fußball-Highlights, Tore und vollständigen Spielwiederholungen. Premier League, Champions League, La Liga, Serie A, Bundesliga – kostenlos auf BolaReel.',
    tr: 'En son futbol özetlerini, golleri ve tam maç tekrarlarını izleyin. Premier League, Şampiyonlar Ligi, La Liga, Serie A, Bundesliga – BolaReel\'ta ücretsiz.',
    hi: 'नवीनतम फुटबॉल हाइलाइट्स, गोल और पूरे मैच रिप्ले देखें। प्रीमियर लीग, चैम्पियंस लीग, ला लीगा, सीरी ए, बुंडेसलीगा – BolaReel पर निःशुल्क।',
}

function getOrigin() {
    return typeof window !== 'undefined' ? window.location.origin : ''
}

function localUrl(locale, path) {
    const base = getOrigin()
    return locale === 'en' ? `${base}${path}` : `${base}/${locale}${path}`
}

function buildAlternates(path) {
    return [
        ...LOCALES.map(l => ({ hreflang: HTML_LANG[l], href: localUrl(l, path) })),
        { hreflang: 'x-default', href: getOrigin() + path },
    ]
}

function currentSeason() {
    const now = new Date()
    const year = now.getFullYear()
    const month = now.getMonth() + 1
    return month >= 7 ? `${year}/${year + 1}` : `${year - 1}/${year}`
}

function matchAgeInDays(matchDate) {
    if (!matchDate) return 0
    return Math.floor((Date.now() - new Date(matchDate).getTime()) / 86400000)
}

// Accepts a plain schema or a computed/ref — watches for reactive updates
export function injectJsonLd(schema) {
    const schemaRef = isRef(schema) ? schema : computed(() => schema)
    let el = null

    const inject = (data) => {
        if (el) { el.remove(); el = null }
        if (!data) return
        el = document.createElement('script')
        el.type = 'application/ld+json'
        el.textContent = JSON.stringify(data)
        document.head.appendChild(el)
    }

    onMounted(() => inject(schemaRef.value))
    watch(schemaRef, (val) => inject(val))
    onUnmounted(() => { if (el) el.remove() })
}

// locale can be a plain string or a Ref<string> (toRef(props, 'locale'))
export function useSeo(locale = 'en') {
    const loc = computed(() => {
        const l = isRef(locale) ? locale.value : locale
        return LOCALES.includes(l) ? l : 'en'
    })

    function homeSeo() {
        const path = '/'
        const year = new Date().getFullYear()
        const base = HOME_TITLES[loc.value] || HOME_TITLES.en
        const title = `${base} ${year}`
        const description = SITE_DESCS[loc.value] || SITE_DESCS.en
        const canonical = localUrl(loc.value, path)
        return {
            title,
            fullTitle: `${title} | ${SITE_NAME}`,
            description,
            canonical,
            image: getOrigin() + '/images/favicon-512.webp',
            ogLocale: OG_LOCALE[loc.value] || 'en_US',
            ogLocaleAlternates: ogLocaleAlternates(OG_LOCALE[loc.value] || 'en_US'),
            alternates: buildAlternates(path),
            jsonLd: {
                '@context': 'https://schema.org',
                '@type': 'WebSite',
                name: SITE_NAME,
                url: getOrigin() + '/',
                description: SITE_DESCS.en,
                potentialAction: {
                    '@type': 'SearchAction',
                    target: { '@type': 'EntryPoint', urlTemplate: `${getOrigin()}/search?q={search_term_string}` },
                    'query-input': 'required name=search_term_string',
                },
            },
        }
    }

    function matchesSeo(page = 1) {
        const path = '/matches'
        const base = HOME_TITLES[loc.value] || HOME_TITLES.en
        const label = PAGE_LABEL[loc.value] || PAGE_LABEL.en
        const title = page > 1 ? `${base} – ${label} ${page}` : base
        const description = SITE_DESCS[loc.value] || SITE_DESCS.en
        // Canonical tự trỏ theo page (khác home/league) để các trận cũ hơn cũng được index
        const canonical = localUrl(loc.value, path) + (page > 1 ? `?page=${page}` : '')
        return {
            title,
            fullTitle: `${title} | ${SITE_NAME}`,
            description,
            canonical,
            ogLocale: OG_LOCALE[loc.value] || 'en_US',
            ogLocaleAlternates: ogLocaleAlternates(OG_LOCALE[loc.value] || 'en_US'),
            alternates: buildAlternates(path),
        }
    }

    function localizeTeam(team) {
        if (!team) return ''
        if (loc.value === 'en') return team.name || ''
        const dbTr = Array.isArray(team.translations)
            ? team.translations.find(tr => tr.locale === loc.value)
            : null
        if (dbTr?.name) return dbTr.name
        const slug = team.slug
            || (team.name || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
        return nationalTeamTranslations[slug]?.[loc.value] ?? team.name ?? ''
    }

    function localizeLeague(leagueObj) {
        if (!leagueObj) return ''
        if (loc.value === 'en') return leagueObj.name || ''
        const slug = leagueObj.slug || ''
        return leagueTranslations[slug]?.[loc.value] ?? leagueObj.name ?? ''
    }

    function formatRound(round) {
        if (!round) return ''
        const reg = round.match(/Regular Season\s*-\s*(\d+)/i)
        if (reg) {
            if (loc.value === 'en') return `Matchday ${reg[1]}`
            const tpl = localeTranslations[loc.value]?.['round.regular']
            return tpl ? tpl.replace('{n}', reg[1]) : `Matchday ${reg[1]}`
        }
        const day = round.match(/(?:Matchday|Match Day|Week|Day|Gameweek)\s*(\d+)/i)
        if (day) {
            if (loc.value === 'en') return `Matchday ${day[1]}`
            const tpl = localeTranslations[loc.value]?.['round.regular']
            return tpl ? tpl.replace('{n}', day[1]) : `Matchday ${day[1]}`
        }
        if (loc.value !== 'en') {
            const key = ROUND_LOOKUP[round]
            if (key) return localeTranslations[loc.value]?.[key] ?? round
        }
        return round
    }

    function matchSeo(match) {
        const home   = localizeTeam(match.home_team)
        const away   = localizeTeam(match.away_team)
        const league = localizeLeague(match.league)
        const round  = formatRound(match.round || '')
        const date   = match.match_date
            ? new Date(match.match_date).toLocaleDateString(DATE_LOCALE[loc.value] || 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
            : ''
        const time   = match.kick_off_time ? String(match.kick_off_time).slice(0, 5) : ''
        const path   = `/match/${match.slug}`
        const canonical = localUrl(loc.value, path)
        const image  = match.thumbnail_url || match.league?.logo_url || null

        const ctx   = [league, round].filter(Boolean).join(', ')
        const ctxJa = [league, round].filter(Boolean).join('、')
        const when  = [date, time].filter(Boolean).join(' ')

        const hasScore = match.home_score != null && match.away_score != null
        const scoreStr = hasScore ? `${match.home_score}–${match.away_score}` : null

        const hl = MATCH_HIGHLIGHTS[loc.value] || MATCH_HIGHLIGHTS.en
        const vs = MATCH_VS[loc.value] || 'vs'
        const roundSuffix = round ? ` – ${round}` : ''
        const dateSuffix  = date ? `, ${date}` : ''
        const title = scoreStr
            ? `${home} ${scoreStr} ${away} ${hl}${dateSuffix} – ${league}${roundSuffix}`
            : `${home} ${vs} ${away} ${hl}${dateSuffix} – ${league}${roundSuffix}`

        const scorePart   = scoreStr ? ` (${scoreStr})` : ''
        const ctxPart     = ctx ? ` – ${ctx}` : ''
        const whenPart    = when ? `, ${when}` : ''
        const whenPartAr  = when ? `، ${when}` : ''
        const scorePartJa = scoreStr ? `（${scoreStr}）` : ''
        const whenPartJa  = when ? `（${when}）` : ''
        const ctxPartJa   = ctxJa ? `、${ctxJa}` : ''

        const descFns = {
            en: () => `Watch ${home} vs ${away}${scorePart} match highlights${ctxPart}${whenPart}. Goals, key moments and full match replay – free on BolaReel.`,
            es: () => `Mira el resumen de ${home} vs ${away}${scorePart}${ctxPart}${whenPart}. Goles, momentos clave y repetición completa – gratis en BolaReel.`,
            pt: () => `Assista ao resumo de ${home} vs ${away}${scorePart}${ctxPart}${whenPart}. Gols, melhores momentos e replay completo – grátis no BolaReel.`,
            ar: () => `شاهد ملخص ${home} ضد ${away}${scorePart}${ctxPart}${whenPartAr}. الأهداف والأوقات الحاسمة وإعادة المباراة الكاملة – مجاناً في BolaReel.`,
            id: () => `Tonton highlight ${home} vs ${away}${scorePart}${ctxPart}${whenPart}. Gol, momen kunci, dan replay lengkap – gratis di BolaReel.`,
            ja: () => `${home} vs ${away}${scorePartJa}${whenPartJa}${ctxPartJa}のハイライト。ゴール・名場面・フル試合リプレイをBolaReelで無料視聴。`,
            fr: () => `Regardez le résumé de ${home} vs ${away}${scorePart}${ctxPart}${whenPart}. Buts, temps forts et rediffusion complète – gratuits sur BolaReel.`,
            de: () => `Highlights von ${home} vs ${away}${scorePart}${ctxPart}${whenPart}. Tore, Schlüsselszenen und vollständige Spielwiederholung – kostenlos auf BolaReel.`,
            tr: () => `${home} vs ${away}${scorePart}${ctxPart}${whenPart} maç özeti. Goller, kritik anlar ve tam maç tekrarı – BolaReel'ta ücretsiz.`,
            hi: () => `${home} बनाम ${away}${scorePart}${ctxPart}${whenPart} हाइलाइट्स देखें। गोल, मुख्य क्षण और पूरा मैच रिप्ले – BolaReel पर निःशुल्क।`,
        }
        const description = (descFns[loc.value] || descFns.en)()

        const video  = match.videos?.[0]
        const jsonLd = {
            '@context': 'https://schema.org',
            '@type': 'VideoObject',
            name: title,
            description,
            uploadDate: match.match_date || undefined,
            publisher: {
                '@type': 'Organization',
                name: SITE_NAME,
                url: getOrigin(),
                logo: { '@type': 'ImageObject', url: getOrigin() + '/favicon.ico' },
            },
        }
        if (image) jsonLd.thumbnailUrl = image
        if (video?.embed_url) jsonLd.embedUrl = video.embed_url
        else if (video?.source_url) jsonLd.contentUrl = video.source_url
        if (video?.duration_seconds) jsonLd.duration = `PT${Math.round(video.duration_seconds)}S`
        jsonLd.isFamilyFriendly = true
        jsonLd.inLanguage = HTML_LANG[loc.value] || 'en'

        const homeLabel = HOME_LABEL[loc.value] || HOME_LABEL.en
        const breadcrumb = breadcrumbLd([
            { name: homeLabel, url: getOrigin() + '/' },
            ...(league ? [{ name: league, url: getOrigin() + `/league/${match.league?.slug}` }] : []),
            { name: title, url: canonical },
        ])

        return {
            title,
            fullTitle: `${title} | ${SITE_NAME}`,
            description,
            canonical,
            image,
            ogType: 'video.other',
            ogLocale: OG_LOCALE[loc.value] || 'en_US',
            ogLocaleAlternates: ogLocaleAlternates(OG_LOCALE[loc.value] || 'en_US'),
            alternates: buildAlternates(path),
            jsonLd: [jsonLd, breadcrumb],
        }
    }

    function leagueSeo(league) {
        const name   = league.name || ''
        const path   = `/league/${league.slug}`
        const canonical = localUrl(loc.value, path)
        const season = currentSeason()
        const tpl    = LEAGUE_TITLE_TPL[loc.value] || LEAGUE_TITLE_TPL.en
        const title  = tpl.replace('%s', name).replace('%s', season)

        const descFns = {
            en: () => `Watch all ${name} ${season} highlights: goals, full match replays and results. Updated after every matchday – free on BolaReel.`,
            es: () => `Todos los highlights de ${name} ${season}: goles, repeticiones completas y resultados. Actualizados tras cada jornada – gratis en BolaReel.`,
            pt: () => `Todos os highlights de ${name} ${season}: gols, replays completos e resultados. Atualizados após cada rodada – grátis no BolaReel.`,
            ar: () => `جميع ملخصات ${name} ${season}: أهداف، إعادات كاملة ونتائج. تُحدَّث بعد كل جولة – مجاناً في BolaReel.`,
            id: () => `Semua highlight ${name} ${season}: gol, replay lengkap, dan hasil pertandingan. Diperbarui setiap pekan – gratis di BolaReel.`,
            ja: () => `${name} ${season}の全ハイライト：ゴール、フル試合リプレイ、試合結果。毎節更新・無料視聴 – BolaReel。`,
            fr: () => `Tous les highlights de ${name} ${season} : buts, replays complets et résultats. Mis à jour après chaque journée – gratuits sur BolaReel.`,
            de: () => `Alle Highlights der ${name} ${season}: Tore, vollständige Spielwiederholungen und Ergebnisse. Nach jedem Spieltag aktualisiert – kostenlos auf BolaReel.`,
            tr: () => `${name} ${season} tüm maç özetleri: goller, tam tekrarlar ve sonuçlar. Her haftadan sonra güncellenir – BolaReel'ta ücretsiz.`,
            hi: () => `${name} ${season} के सभी हाइलाइट्स: गोल, पूरे मैच रिप्ले और परिणाम। हर मैचडे के बाद अपडेट – BolaReel पर निःशुल्क।`,
        }
        const description = (descFns[loc.value] || descFns.en)()

        return {
            title,
            fullTitle: `${title} | ${SITE_NAME}`,
            description,
            canonical,
            image: league.logo_url || null,
            ogLocale: OG_LOCALE[loc.value] || 'en_US',
            ogLocaleAlternates: ogLocaleAlternates(OG_LOCALE[loc.value] || 'en_US'),
            alternates: buildAlternates(path),
            jsonLd: [
                {
                    '@context': 'https://schema.org',
                    '@type': 'SportsOrganization',
                    name,
                    url: canonical,
                    logo: league.logo_url || undefined,
                    description,
                },
                breadcrumbLd([
                    { name: HOME_LABEL[loc.value] || HOME_LABEL.en, url: getOrigin() + '/' },
                    { name, url: canonical },
                ]),
            ],
        }
    }

    function teamSeo(team) {
        const name   = team.name || ''
        const path   = `/team/${team.slug}`
        const canonical = localUrl(loc.value, path)
        const season = currentSeason()
        const tpl    = TEAM_TITLE_TPL[loc.value] || TEAM_TITLE_TPL.en
        const title  = tpl.replace('%s', name).replace('%s', season)

        const descFns = {
            en: () => `Watch ${name} ${season} highlights: all goals and match replays. Follow every game free on BolaReel.`,
            es: () => `Highlights de ${name} ${season}: todos los goles y repeticiones de sus partidos. Sigue cada encuentro gratis en BolaReel.`,
            pt: () => `Highlights de ${name} ${season}: todos os gols e replays dos seus jogos. Acompanhe cada partida grátis no BolaReel.`,
            ar: () => `ملخصات ${name} ${season}: جميع الأهداف وإعادات المباريات. تابع كل مباراة مجاناً في BolaReel.`,
            id: () => `Highlight ${name} ${season}: semua gol dan replay pertandingan mereka. Ikuti setiap laga gratis di BolaReel.`,
            ja: () => `${name} ${season}のハイライト：全ゴールと試合リプレイ。BolaReelで毎試合無料視聴。`,
            fr: () => `Highlights de ${name} ${season} : tous les buts et replays de leurs matchs. Suivez chaque rencontre gratuitement sur BolaReel.`,
            de: () => `Highlights von ${name} ${season}: alle Tore und Spielwiederholungen. Jedes Spiel kostenlos verfolgen auf BolaReel.`,
            tr: () => `${name} ${season} highlights: tüm goller ve maç tekrarları. Her maçı BolaReel'ta ücretsiz takip edin.`,
            hi: () => `${name} ${season} हाइलाइट्स: सभी गोल और मैच रिप्ले। BolaReel पर हर मैच निःशुल्क देखें।`,
        }
        const description = (descFns[loc.value] || descFns.en)()

        return {
            title,
            fullTitle: `${title} | ${SITE_NAME}`,
            description,
            canonical,
            image: team.logo_url || null,
            ogLocale: OG_LOCALE[loc.value] || 'en_US',
            ogLocaleAlternates: ogLocaleAlternates(OG_LOCALE[loc.value] || 'en_US'),
            alternates: buildAlternates(path),
            jsonLd: [
                {
                    '@context': 'https://schema.org',
                    '@type': 'SportsTeam',
                    name,
                    url: canonical,
                    logo: team.logo_url || undefined,
                    description,
                },
                breadcrumbLd([
                    { name: HOME_LABEL[loc.value] || HOME_LABEL.en, url: getOrigin() + '/' },
                    { name, url: canonical },
                ]),
            ],
        }
    }

    function searchSeo(q) {
        const path = '/search'
        const canonical = localUrl(loc.value, path) + (q ? `?q=${encodeURIComponent(q)}` : '')
        const title = q
            ? (SEARCH_TITLE_Q[loc.value] || SEARCH_TITLE_Q.en)(q)
            : (SEARCH_TITLE_EMPTY[loc.value] || SEARCH_TITLE_EMPTY.en)
        const description = q
            ? (SEARCH_DESC_Q[loc.value] || SEARCH_DESC_Q.en)(q)
            : SITE_DESCS[loc.value] || SITE_DESCS.en
        return {
            title,
            fullTitle: `${title} | ${SITE_NAME}`,
            description,
            canonical,
            noindex: !q,
            ogLocale: OG_LOCALE[loc.value] || 'en_US',
            ogLocaleAlternates: ogLocaleAlternates(OG_LOCALE[loc.value] || 'en_US'),
            alternates: buildAlternates(path),
        }
    }

    return { homeSeo, matchesSeo, matchSeo, leagueSeo, teamSeo, searchSeo }
}
