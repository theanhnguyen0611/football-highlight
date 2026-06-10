// resources/js/composables/useLocale.js

const translations = {
    en: {
        'nav.home': 'Home', 'nav.leagues': 'Leagues', 'nav.teams': 'Teams', 'nav.search': 'Search matches...',
        'match.latest': 'Latest Highlights', 'match.more': 'More Highlights', 'match.recent': 'Recent Results',
        'match.show_score': 'Show Score', 'match.hide_score': 'Hide Score', 'match.full_time': 'Full Time',
        'match.results': 'Results for', 'match.events': 'Events', 'match.lineup': 'Lineup',
        'match.statistics': 'Statistics', 'match.players': 'Players', 'match.venue': 'Venue', 'match.referee': 'Referee',
    },
    es: {
        'nav.home': 'Inicio', 'nav.leagues': 'Ligas', 'nav.teams': 'Equipos', 'nav.search': 'Buscar partidos...',
        'match.latest': 'Últimos Highlights', 'match.more': 'Más Highlights', 'match.recent': 'Resultados Recientes',
        'match.show_score': 'Mostrar Marcador', 'match.hide_score': 'Ocultar Marcador', 'match.full_time': 'Tiempo Completo',
        'match.results': 'Resultados de', 'match.events': 'Eventos', 'match.lineup': 'Alineación',
        'match.statistics': 'Estadísticas', 'match.players': 'Jugadores', 'match.venue': 'Estadio', 'match.referee': 'Árbitro',
    },
    pt: {
        'nav.home': 'Início', 'nav.leagues': 'Ligas', 'nav.teams': 'Times', 'nav.search': 'Buscar partidas...',
        'match.latest': 'Últimos Highlights', 'match.more': 'Mais Highlights', 'match.recent': 'Resultados Recentes',
        'match.show_score': 'Mostrar Placar', 'match.hide_score': 'Ocultar Placar', 'match.full_time': 'Tempo Final',
        'match.results': 'Resultados de', 'match.events': 'Eventos', 'match.lineup': 'Escalação',
        'match.statistics': 'Estatísticas', 'match.players': 'Jogadores', 'match.venue': 'Estádio', 'match.referee': 'Árbitro',
    },
    ar: {
        'nav.home': 'الرئيسية', 'nav.leagues': 'الدوريات', 'nav.teams': 'المنتخبات', 'nav.search': 'ابحث عن مباراة...',
        'match.latest': 'أحدث الملخصات', 'match.more': 'ملخصات أخرى', 'match.recent': 'النتائج الأخيرة',
        'match.show_score': 'إظهار النتيجة', 'match.hide_score': 'إخفاء النتيجة', 'match.full_time': 'نهاية المباراة',
        'match.results': 'نتائج', 'match.events': 'الأحداث', 'match.lineup': 'التشكيلة',
        'match.statistics': 'الإحصاءات', 'match.players': 'اللاعبون', 'match.venue': 'الملعب', 'match.referee': 'الحكم',
    },
    id: {
        'nav.home': 'Beranda', 'nav.leagues': 'Liga', 'nav.teams': 'Tim', 'nav.search': 'Cari pertandingan...',
        'match.latest': 'Highlight Terbaru', 'match.more': 'Highlight Lainnya', 'match.recent': 'Hasil Terkini',
        'match.show_score': 'Tampilkan Skor', 'match.hide_score': 'Sembunyikan Skor', 'match.full_time': 'Waktu Penuh',
        'match.results': 'Hasil untuk', 'match.events': 'Peristiwa', 'match.lineup': 'Susunan Pemain',
        'match.statistics': 'Statistik', 'match.players': 'Pemain', 'match.venue': 'Stadion', 'match.referee': 'Wasit',
    },
    bn: {
        'nav.home': 'হোম', 'nav.leagues': 'লিগ', 'nav.teams': 'দল', 'nav.search': 'ম্যাচ খুঁজুন...',
        'match.latest': 'সর্বশেষ হাইলাইটস', 'match.more': 'আরও হাইলাইটস', 'match.recent': 'সাম্প্রতিক ফলাফল',
        'match.show_score': 'স্কোর দেখান', 'match.hide_score': 'স্কোর লুকান', 'match.full_time': 'ম্যাচ শেষ',
        'match.results': 'ফলাফল', 'match.events': 'ঘটনা', 'match.lineup': 'লাইনআপ',
        'match.statistics': 'পরিসংখ্যান', 'match.players': 'খেলোয়াড়', 'match.venue': 'মাঠ', 'match.referee': 'রেফারি',
    },
    ja: {
        'nav.home': 'ホーム', 'nav.leagues': 'リーグ', 'nav.teams': 'チーム', 'nav.search': '試合を検索...',
        'match.latest': '最新ハイライト', 'match.more': 'その他のハイライト', 'match.recent': '最近の結果',
        'match.show_score': 'スコアを表示', 'match.hide_score': 'スコアを隠す', 'match.full_time': '試合終了',
        'match.results': '検索結果', 'match.events': 'イベント', 'match.lineup': 'スターティングメンバー',
        'match.statistics': '統計', 'match.players': '選手', 'match.venue': 'スタジアム', 'match.referee': '審判',
    },
    fr: {
        'nav.home': 'Accueil', 'nav.leagues': 'Ligues', 'nav.teams': 'Équipes', 'nav.search': 'Rechercher des matchs...',
        'match.latest': 'Derniers Highlights', 'match.more': 'Plus de Highlights', 'match.recent': 'Résultats Récents',
        'match.show_score': 'Afficher le Score', 'match.hide_score': 'Masquer le Score', 'match.full_time': 'Temps Plein',
        'match.results': 'Résultats pour', 'match.events': 'Événements', 'match.lineup': 'Composition',
        'match.statistics': 'Statistiques', 'match.players': 'Joueurs', 'match.venue': 'Stade', 'match.referee': 'Arbitre',
    },
    de: {
        'nav.home': 'Startseite', 'nav.leagues': 'Ligen', 'nav.teams': 'Mannschaften', 'nav.search': 'Spiele suchen...',
        'match.latest': 'Neueste Highlights', 'match.more': 'Weitere Highlights', 'match.recent': 'Aktuelle Ergebnisse',
        'match.show_score': 'Ergebnis anzeigen', 'match.hide_score': 'Ergebnis ausblenden', 'match.full_time': 'Spielende',
        'match.results': 'Ergebnisse für', 'match.events': 'Ereignisse', 'match.lineup': 'Aufstellung',
        'match.statistics': 'Statistiken', 'match.players': 'Spieler', 'match.venue': 'Stadion', 'match.referee': 'Schiedsrichter',
    },
    tr: {
        'nav.home': 'Ana Sayfa', 'nav.leagues': 'Ligler', 'nav.teams': 'Takımlar', 'nav.search': 'Maç ara...',
        'match.latest': 'Son Özetler', 'match.more': 'Daha Fazla Özet', 'match.recent': 'Son Sonuçlar',
        'match.show_score': 'Skoru Göster', 'match.hide_score': 'Skoru Gizle', 'match.full_time': 'Maç Sonu',
        'match.results': 'Sonuçlar', 'match.events': 'Olaylar', 'match.lineup': 'İlk 11',
        'match.statistics': 'İstatistikler', 'match.players': 'Oyuncular', 'match.venue': 'Stadyum', 'match.referee': 'Hakem',
    },
    sw: {
        'nav.home': 'Nyumbani', 'nav.leagues': 'Ligi', 'nav.teams': 'Timu', 'nav.search': 'Tafuta mechi...',
        'match.latest': 'Muhtasari wa Hivi Karibuni', 'match.more': 'Muhtasari Zaidi', 'match.recent': 'Matokeo ya Hivi Karibuni',
        'match.show_score': 'Onyesha Matokeo', 'match.hide_score': 'Ficha Matokeo', 'match.full_time': 'Mwisho wa Mechi',
        'match.results': 'Matokeo ya', 'match.events': 'Matukio', 'match.lineup': 'Orodha ya Wachezaji',
        'match.statistics': 'Takwimu', 'match.players': 'Wachezaji', 'match.venue': 'Uwanja', 'match.referee': 'Refa',
    },
    hi: {
        'nav.home': 'होम', 'nav.leagues': 'लीग', 'nav.teams': 'टीमें', 'nav.search': 'मैच खोजें...',
        'match.latest': 'नवीनतम हाइलाइट्स', 'match.more': 'और हाइलाइट्स', 'match.recent': 'हालिया परिणाम',
        'match.show_score': 'स्कोर दिखाएं', 'match.hide_score': 'स्कोर छुपाएं', 'match.full_time': 'पूर्ण समय',
        'match.results': 'परिणाम', 'match.events': 'घटनाएं', 'match.lineup': 'लाइनअप',
        'match.statistics': 'आंकड़े', 'match.players': 'खिलाड़ी', 'match.venue': 'स्टेडियम', 'match.referee': 'रेफरी',
    },
}

const leagueTranslations = {
    'friendly-match': { es:'Amistoso', pt:'Amistoso', ar:'ودية', id:'Persahabatan', bn:'প্রীতি ম্যাচ', ja:'親善試合', fr:'Amical', de:'Freundschaftsspiel', tr:'Hazırlık Maçı', sw:'Mechi ya Kirafiki', hi:'मैत्री मैच' },
    'premier-league': { ar:'الدوري الإنجليزي الممتاز', ja:'プレミアリーグ', hi:'प्रीमियर लीग', bn:'প্রিমিয়ার লিগ', id:'Liga Premier' },
    'la-liga':        { ar:'الدوري الإسباني', ja:'ラ・リーガ', hi:'ला लीगा', bn:'লা লিগা', id:'La Liga' },
    'serie-a':        { ar:'الدوري الإيطالي', ja:'セリエA', hi:'सेरी ए', fr:'Série A' },
    'bundesliga':     { ar:'الدوري الألماني', ja:'ブンデスリーガ', hi:'बुंडेसलीगा', fr:'Bundesliga' },
    'ligue-1':        { ar:'الدوري الفرنسي', ja:'リーグ・アン', hi:'लिग 1' },
    'champions-league': { es:'Liga de Campeones', pt:'Liga dos Campeões', ar:'دوري أبطال أوروبا', id:'Liga Champions', bn:'চ্যাম্পিয়নস লিগ', ja:'チャンピオンズリーグ', fr:'Ligue des Champions', de:'Champions League', tr:'Şampiyonlar Ligi', sw:'Ligi ya Mabingwa', hi:'चैम्पियंस लीग' },
}

const LOCALE_PREFIXES = ['es','pt','ar','id','bn','ja','fr','de','tr','sw','hi']

export function useLocale(locale = 'en') {
    function t(key) {
        return translations[locale]?.[key] ?? translations.en[key] ?? key
    }

    function teamName(team) {
        if (!team) return ''
        if (team.type === 'club') return team.name
        if (locale === 'en') return team.name
        return team.translations?.[locale] ?? team.name
    }

    function leagueName(league) {
        if (!league) return ''
        if (locale === 'en') return league
        const slug = league.toLowerCase().replace(/ /g, '-')
        return leagueTranslations[slug]?.[locale] ?? league
    }

    function localePath(path) {
        if (locale === 'en') return path
        return `/${locale}${path}`
    }

    return { t, teamName, leagueName, localePath }
}
