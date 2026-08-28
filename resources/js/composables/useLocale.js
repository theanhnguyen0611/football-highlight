import { isRef, computed } from 'vue'

export const translations = {
    en: {
        'nav.home': 'Home', 'nav.leagues': 'Leagues', 'nav.teams': 'Teams',
        'nav.search': 'Search matches...', 'nav.menu': 'Menu', 'nav.all': 'All',
        'nav.popular_teams': 'Popular Teams', 'nav.search_btn': 'Search',

        'match.latest': 'Latest Highlights', 'match.more': 'More Highlights',
        'match.recent': 'Recent Results', 'match.show_score': 'Show Score',
        'match.hide_score': 'Hide Score', 'match.full_time': 'Full Time',
        'match.results': 'Results for', 'match.events': 'Timeline',
        'match.statistics': 'Stats', 'match.lineup': 'Lineup',
        'match.players': 'Players', 'match.venue': 'Venue', 'match.referee': 'Referee',
        'match.show_score_events': 'Show Score & Events',
        'match.reveal_hint': 'Reveal the score to see match events',
        'match.no_events': 'No events data available',
        'match.no_video': 'Video not available yet',
        'match.no_related': 'No related matches',
        'match.source': 'Source',
        'match.embed': 'Embed',
        'match.copy_link': 'Copy Link',
        'match.copied': 'Copied!',
        'match.embed_desc': 'Paste this code to embed the highlight on your site:',
        'match.video_extended': 'Extended', 'match.video_alt': 'Alt Highlight',
        'match.video_highlight': 'Highlights', 'match.video_full_match': 'Full Match',

        'footer.tagline': 'Watch football highlights, goals & replays – free',
        'footer.rights': 'All rights reserved',
        'footer.more_leagues': 'More Leagues',

        'ui.matches_available': 'matches available',
        'ui.no_matches_league': 'No matches found for this league yet.',
        'ui.no_matches_team': 'No matches found for this team yet.',
        'ui.no_stats': 'No statistics available',
        'ui.ball_possession': 'BALL POSSESSION',
        'ui.league': 'League', 'ui.club': 'Club', 'ui.national_team': 'National Team',
        'ui.match_highlights': 'Match Highlights', 'ui.latest_matches': 'Latest Matches',
        'ui.search_results_for': 'Results for',
        'ui.search_hint': 'Type something in the search bar above',
        'ui.no_results_for': 'No results found for',
        'ui.teams': 'Teams', 'ui.leagues': 'Leagues', 'ui.matches': 'Matches',
        'ui.vs': 'vs',
        'ui.featured_highlights': 'Featured Highlights', 'ui.featured_sub': 'Top clubs · last 7 days',

        'date.today': 'Today', 'date.yesterday': 'Yesterday', 'date.days_ago': '{n} days ago',
        'date.hours_ago': '{n}h ago', 'date.just_now': 'Just now',

        'stats.key': 'Key Stats', 'stats.attack': 'Attack', 'stats.passes': 'Passes',
        'stats.defence': 'Defence', 'stats.discipline': 'Discipline',

        'stat.expected_goals': 'Expected Goals', 'stat.big_chances_created': 'Big Chances Created',
        'stat.shots_on_target': 'Shots on Target', 'stat.shots_off_target': 'Shots off Target',
        'stat.blocked_shots': 'Blocked Shots', 'stat.corners': 'Corners',
        'stat.shots_in_box': 'Shots in Box', 'stat.total_passes': 'Total Passes',
        'stat.successful_passes': 'Successful Passes', 'stat.key_passes': 'Key Passes',
        'stat.successful_tackles': 'Successful Tackles', 'stat.interceptions': 'Interceptions',
        'stat.clearances': 'Clearances', 'stat.goalkeeper_saves': 'Goalkeeper Saves',
        'stat.fouls': 'Fouls', 'stat.yellow_cards': 'Yellow Cards', 'stat.red_cards': 'Red Cards',

        'page.prev': 'Previous', 'page.next': 'Next',

        'round.regular': 'Round {n}',
        'round.group_stage': 'Group Stage',
        'round.round_of_16': 'Round of 16',
        'round.round_of_32': 'Round of 32',
        'round.round_of_64': 'Round of 64',
        'round.quarter_final': 'Quarter-Final',
        'round.semi_final': 'Semi-Final',
        'round.final': 'Final',
        'round.third_place': '3rd Place',
        'round.playoff': 'Playoff',
        'round.preliminary': 'Preliminary Round',
        'round.qualification': 'Qualification',
    },
    es: {
        'nav.home': 'Inicio', 'nav.leagues': 'Ligas', 'nav.teams': 'Equipos',
        'nav.search': 'Buscar partidos...', 'nav.menu': 'Menú', 'nav.all': 'Todo',
        'nav.popular_teams': 'Equipos Populares', 'nav.search_btn': 'Buscar',

        'match.latest': 'Últimos Highlights', 'match.more': 'Más Highlights',
        'match.recent': 'Resultados Recientes', 'match.show_score': 'Ver Marcador',
        'match.hide_score': 'Ocultar Marcador', 'match.full_time': 'Tiempo Completo',
        'match.results': 'Resultados de', 'match.events': 'Cronología',
        'match.statistics': 'Estadísticas', 'match.lineup': 'Alineación',
        'match.players': 'Jugadores', 'match.venue': 'Estadio', 'match.referee': 'Árbitro',
        'match.show_score_events': 'Ver Marcador y Eventos',
        'match.reveal_hint': 'Revela el marcador para ver los eventos del partido',
        'match.no_events': 'Sin datos de eventos disponibles',
        'match.no_video': 'Video no disponible aún',
        'match.no_related': 'Sin partidos relacionados',
        'match.source': 'Fuente',
        'match.embed': 'Insertar',
        'match.copy_link': 'Copiar Enlace',
        'match.copied': '¡Copiado!',
        'match.embed_desc': 'Pega este código para insertar el resumen en tu sitio:',
        'match.video_extended': 'Extendido', 'match.video_alt': 'Alternativo',
        'match.video_highlight': 'Resumen', 'match.video_full_match': 'Partido Completo',

        'footer.tagline': 'Mira highlights, goles y repeticiones de fútbol – gratis',
        'footer.rights': 'Todos los derechos reservados',
        'footer.more_leagues': 'Más Ligas',

        'ui.matches_available': 'partidos disponibles',
        'ui.no_matches_league': 'Aún no hay partidos para esta liga.',
        'ui.no_matches_team': 'Aún no hay partidos para este equipo.',
        'ui.no_stats': 'Sin estadísticas disponibles',
        'ui.ball_possession': 'POSESIÓN DEL BALÓN',
        'ui.league': 'Liga', 'ui.club': 'Club', 'ui.national_team': 'Selección Nacional',
        'ui.match_highlights': 'Momentos Destacados', 'ui.latest_matches': 'Últimos Partidos',
        'ui.search_results_for': 'Resultados de',
        'ui.search_hint': 'Escribe algo en la barra de búsqueda',
        'ui.no_results_for': 'No se encontraron resultados para',
        'ui.teams': 'Equipos', 'ui.leagues': 'Ligas', 'ui.matches': 'Partidos',
        'ui.vs': 'vs',
        'ui.featured_highlights': 'Lo Más Destacado', 'ui.featured_sub': 'Top clubes · últimos 7 días',

        'date.today': 'Hoy', 'date.yesterday': 'Ayer', 'date.days_ago': 'hace {n} días',
        'date.hours_ago': 'hace {n}h', 'date.just_now': 'Ahora mismo',

        'stats.key': 'Estadísticas Clave', 'stats.attack': 'Ataque', 'stats.passes': 'Pases',
        'stats.defence': 'Defensa', 'stats.discipline': 'Disciplina',

        'stat.expected_goals': 'Goles Esperados', 'stat.big_chances_created': 'Grandes Oportunidades',
        'stat.shots_on_target': 'Tiros a Puerta', 'stat.shots_off_target': 'Tiros Fuera',
        'stat.blocked_shots': 'Tiros Bloqueados', 'stat.corners': 'Córneres',
        'stat.shots_in_box': 'Tiros desde el Área', 'stat.total_passes': 'Pases Totales',
        'stat.successful_passes': 'Pases Exitosos', 'stat.key_passes': 'Pases Clave',
        'stat.successful_tackles': 'Entradas Exitosas', 'stat.interceptions': 'Intercepciones',
        'stat.clearances': 'Despejes', 'stat.goalkeeper_saves': 'Paradas del Portero',
        'stat.fouls': 'Faltas', 'stat.yellow_cards': 'Tarjetas Amarillas', 'stat.red_cards': 'Tarjetas Rojas',

        'page.prev': 'Anterior', 'page.next': 'Siguiente',

        'round.regular': 'Jornada {n}',
        'round.group_stage': 'Fase de Grupos',
        'round.round_of_16': 'Octavos de Final',
        'round.round_of_32': 'Dieciseisavos de Final',
        'round.round_of_64': '1/64 Final',
        'round.quarter_final': 'Cuartos de Final',
        'round.semi_final': 'Semifinal',
        'round.final': 'Final',
        'round.third_place': '3er Lugar',
        'round.playoff': 'Play-off',
        'round.preliminary': 'Ronda Preliminar',
        'round.qualification': 'Clasificación',
    },
    pt: {
        'nav.home': 'Início', 'nav.leagues': 'Ligas', 'nav.teams': 'Times',
        'nav.search': 'Buscar partidas...', 'nav.menu': 'Menu', 'nav.all': 'Todos',
        'nav.popular_teams': 'Times Populares', 'nav.search_btn': 'Buscar',

        'match.latest': 'Últimos Destaques', 'match.more': 'Mais Destaques',
        'match.recent': 'Resultados Recentes', 'match.show_score': 'Mostrar Placar',
        'match.hide_score': 'Ocultar Placar', 'match.full_time': 'Tempo Final',
        'match.results': 'Resultados de', 'match.events': 'Linha do Tempo',
        'match.statistics': 'Estatísticas', 'match.lineup': 'Escalação',
        'match.players': 'Jogadores', 'match.venue': 'Estádio', 'match.referee': 'Árbitro',
        'match.show_score_events': 'Ver Placar e Eventos',
        'match.reveal_hint': 'Revele o placar para ver os eventos da partida',
        'match.no_events': 'Sem dados de eventos disponíveis',
        'match.no_video': 'Vídeo indisponível',
        'match.no_related': 'Sem partidas relacionadas',
        'match.source': 'Fonte',
        'match.embed': 'Incorporar',
        'match.copy_link': 'Copiar Link',
        'match.copied': 'Copiado!',
        'match.embed_desc': 'Cole este código para incorporar o destaque no seu site:',
        'match.video_extended': 'Completo', 'match.video_alt': 'Alternativo',
        'match.video_highlight': 'Destaques', 'match.video_full_match': 'Jogo Completo',

        'footer.tagline': 'Assista highlights, gols e replays de futebol – grátis',
        'footer.rights': 'Todos os direitos reservados',
        'footer.more_leagues': 'Mais Ligas',

        'ui.matches_available': 'partidas disponíveis',
        'ui.no_matches_league': 'Ainda não há partidas para esta liga.',
        'ui.no_matches_team': 'Ainda não há partidas para este time.',
        'ui.no_stats': 'Sem estatísticas disponíveis',
        'ui.ball_possession': 'POSSE DE BOLA',
        'ui.league': 'Liga', 'ui.club': 'Clube', 'ui.national_team': 'Seleção Nacional',
        'ui.match_highlights': 'Melhores Momentos', 'ui.latest_matches': 'Últimas Partidas',
        'ui.search_results_for': 'Resultados de',
        'ui.search_hint': 'Digite algo na barra de pesquisa acima',
        'ui.no_results_for': 'Nenhum resultado encontrado para',
        'ui.teams': 'Times', 'ui.leagues': 'Ligas', 'ui.matches': 'Partidas',
        'ui.vs': 'vs',
        'ui.featured_highlights': 'Principais Destaques', 'ui.featured_sub': 'Top clubes · últimos 7 dias',

        'date.today': 'Hoje', 'date.yesterday': 'Ontem', 'date.days_ago': 'há {n} dias',
        'date.hours_ago': 'há {n}h', 'date.just_now': 'Agora mesmo',

        'stats.key': 'Estatísticas Chave', 'stats.attack': 'Ataque', 'stats.passes': 'Passes',
        'stats.defence': 'Defesa', 'stats.discipline': 'Disciplina',

        'stat.expected_goals': 'Gols Esperados', 'stat.big_chances_created': 'Grandes Chances',
        'stat.shots_on_target': 'Chutes no Alvo', 'stat.shots_off_target': 'Chutes para Fora',
        'stat.blocked_shots': 'Chutes Bloqueados', 'stat.corners': 'Escanteios',
        'stat.shots_in_box': 'Chutes na Área', 'stat.total_passes': 'Passes Totais',
        'stat.successful_passes': 'Passes Certos', 'stat.key_passes': 'Passes-Chave',
        'stat.successful_tackles': 'Desarmes Certos', 'stat.interceptions': 'Interceptações',
        'stat.clearances': 'Cortes', 'stat.goalkeeper_saves': 'Defesas do Goleiro',
        'stat.fouls': 'Faltas', 'stat.yellow_cards': 'Cartões Amarelos', 'stat.red_cards': 'Cartões Vermelhos',

        'page.prev': 'Anterior', 'page.next': 'Próximo',

        'round.regular': 'Rodada {n}',
        'round.group_stage': 'Fase de Grupos',
        'round.round_of_16': 'Oitavas de Final',
        'round.round_of_32': 'Dezesseis Avos',
        'round.round_of_64': 'Trinta e Dois Avos',
        'round.quarter_final': 'Quartas de Final',
        'round.semi_final': 'Semifinal',
        'round.final': 'Final',
        'round.third_place': '3º Lugar',
        'round.playoff': 'Play-off',
        'round.preliminary': 'Rodada Preliminar',
        'round.qualification': 'Qualificação',
    },
    ar: {
        'nav.home': 'الرئيسية', 'nav.leagues': 'الدوريات', 'nav.teams': 'الفرق',
        'nav.search': 'ابحث عن مباراة...', 'nav.menu': 'القائمة', 'nav.all': 'الكل',
        'nav.popular_teams': 'أشهر الفرق', 'nav.search_btn': 'بحث',

        'match.latest': 'أحدث الملخصات', 'match.more': 'ملخصات أخرى',
        'match.recent': 'النتائج الأخيرة', 'match.show_score': 'إظهار النتيجة',
        'match.hide_score': 'إخفاء النتيجة', 'match.full_time': 'نهاية المباراة',
        'match.results': 'نتائج', 'match.events': 'الجدول الزمني',
        'match.statistics': 'الإحصاءات', 'match.lineup': 'التشكيلة',
        'match.players': 'اللاعبون', 'match.venue': 'الملعب', 'match.referee': 'الحكم',
        'match.show_score_events': 'إظهار النتيجة والأحداث',
        'match.reveal_hint': 'اكشف النتيجة لرؤية أحداث المباراة',
        'match.no_events': 'لا توجد بيانات أحداث',
        'match.no_video': 'الفيديو غير متاح حتى الآن',
        'match.no_related': 'لا توجد مباريات ذات صلة',
        'match.source': 'مصدر',
        'match.embed': 'تضمين',
        'match.copy_link': 'نسخ الرابط',
        'match.copied': 'تم النسخ!',
        'match.embed_desc': 'الصق هذا الكود لتضمين الهايلايت في موقعك:',
        'match.video_extended': 'ممتد', 'match.video_alt': 'ملخص بديل',
        'match.video_highlight': 'ملخص', 'match.video_full_match': 'المباراة الكاملة',

        'footer.tagline': 'شاهد ملخصات الأهداف وإعادات المباريات مجاناً',
        'footer.rights': 'جميع الحقوق محفوظة',
        'footer.more_leagues': 'مزيد من الدوريات',

        'ui.matches_available': 'مباريات متاحة',
        'ui.no_matches_league': 'لا توجد مباريات لهذه البطولة حتى الآن.',
        'ui.no_matches_team': 'لا توجد مباريات لهذا الفريق حتى الآن.',
        'ui.no_stats': 'لا توجد إحصاءات متاحة',
        'ui.ball_possession': 'حيازة الكرة',
        'ui.league': 'دوري', 'ui.club': 'نادي', 'ui.national_team': 'المنتخب الوطني',
        'ui.match_highlights': 'أبرز لحظات المباراة', 'ui.latest_matches': 'آخر المباريات',
        'ui.search_results_for': 'نتائج البحث عن',
        'ui.search_hint': 'اكتب شيئًا في شريط البحث أعلاه',
        'ui.no_results_for': 'لا توجد نتائج لـ',
        'ui.teams': 'الفرق', 'ui.leagues': 'الدوريات', 'ui.matches': 'مباريات',
        'ui.vs': 'ضد',
        'ui.featured_highlights': 'أبرز الملخصات', 'ui.featured_sub': 'أفضل الأندية · آخر 7 أيام',

        'date.today': 'اليوم', 'date.yesterday': 'أمس', 'date.days_ago': 'منذ {n} أيام',
        'date.hours_ago': 'منذ {n}س', 'date.just_now': 'الآن',

        'stats.key': 'إحصاءات رئيسية', 'stats.attack': 'الهجوم', 'stats.passes': 'التمريرات',
        'stats.defence': 'الدفاع', 'stats.discipline': 'الانضباط',

        'stat.expected_goals': 'الأهداف المتوقعة', 'stat.big_chances_created': 'فرص كبيرة مصنوعة',
        'stat.shots_on_target': 'تسديدات في المرمى', 'stat.shots_off_target': 'تسديدات خارج المرمى',
        'stat.blocked_shots': 'تسديدات محجوبة', 'stat.corners': 'ركلات الركنية',
        'stat.shots_in_box': 'تسديدات داخل المنطقة', 'stat.total_passes': 'إجمالي التمريرات',
        'stat.successful_passes': 'التمريرات الناجحة', 'stat.key_passes': 'التمريرات الحاسمة',
        'stat.successful_tackles': 'المعالجات الناجحة', 'stat.interceptions': 'الاعتراضات',
        'stat.clearances': 'الإبعادات', 'stat.goalkeeper_saves': 'تصديات الحارس',
        'stat.fouls': 'المخالفات', 'stat.yellow_cards': 'البطاقات الصفراء', 'stat.red_cards': 'البطاقات الحمراء',

        'page.prev': 'السابق', 'page.next': 'التالي',

        'round.regular': 'الجولة {n}',
        'round.group_stage': 'دور المجموعات',
        'round.round_of_16': 'ثمن النهائي',
        'round.round_of_32': 'دور الـ32',
        'round.round_of_64': 'دور الـ64',
        'round.quarter_final': 'ربع النهائي',
        'round.semi_final': 'نصف النهائي',
        'round.final': 'النهائي',
        'round.third_place': 'المركز الثالث',
        'round.playoff': 'الملحق',
        'round.preliminary': 'الدور التمهيدي',
        'round.qualification': 'التصفيات',
    },
    id: {
        'nav.home': 'Beranda', 'nav.leagues': 'Liga', 'nav.teams': 'Tim',
        'nav.search': 'Cari pertandingan...', 'nav.menu': 'Menu', 'nav.all': 'Semua',
        'nav.popular_teams': 'Tim Populer', 'nav.search_btn': 'Cari',

        'match.latest': 'Sorotan Terbaru', 'match.more': 'Sorotan Lainnya',
        'match.recent': 'Hasil Terkini', 'match.show_score': 'Tampilkan Skor',
        'match.hide_score': 'Sembunyikan Skor', 'match.full_time': 'Waktu Penuh',
        'match.results': 'Hasil untuk', 'match.events': 'Linimasa',
        'match.statistics': 'Statistik', 'match.lineup': 'Susunan Pemain',
        'match.players': 'Pemain', 'match.venue': 'Stadion', 'match.referee': 'Wasit',
        'match.show_score_events': 'Tampilkan Skor & Peristiwa',
        'match.reveal_hint': 'Tampilkan skor untuk melihat peristiwa pertandingan',
        'match.no_events': 'Tidak ada data peristiwa',
        'match.no_video': 'Video belum tersedia',
        'match.no_related': 'Tidak ada pertandingan terkait',
        'match.source': 'Sumber',
        'match.embed': 'Sematkan',
        'match.copy_link': 'Salin Tautan',
        'match.copied': 'Tersalin!',
        'match.embed_desc': 'Tempel kode ini untuk menyematkan highlight di situsmu:',
        'match.video_extended': 'Diperpanjang', 'match.video_alt': 'Sorotan Lain',
        'match.video_highlight': 'Sorotan', 'match.video_full_match': 'Pertandingan Penuh',

        'footer.tagline': 'Tonton highlight, gol & replay sepak bola – gratis',
        'footer.rights': 'Semua hak dilindungi',
        'footer.more_leagues': 'Liga Lainnya',

        'ui.matches_available': 'pertandingan tersedia',
        'ui.no_matches_league': 'Belum ada pertandingan untuk liga ini.',
        'ui.no_matches_team': 'Belum ada pertandingan untuk tim ini.',
        'ui.no_stats': 'Tidak ada statistik tersedia',
        'ui.ball_possession': 'PENGUASAAN BOLA',
        'ui.league': 'Liga', 'ui.club': 'Klub', 'ui.national_team': 'Tim Nasional',
        'ui.match_highlights': 'Highlight Pertandingan', 'ui.latest_matches': 'Pertandingan Terbaru',
        'ui.search_results_for': 'Hasil untuk',
        'ui.search_hint': 'Ketik sesuatu di bilah pencarian di atas',
        'ui.no_results_for': 'Tidak ada hasil untuk',
        'ui.teams': 'Tim', 'ui.leagues': 'Liga', 'ui.matches': 'Pertandingan',
        'ui.vs': 'vs',
        'ui.featured_highlights': 'Sorotan Unggulan', 'ui.featured_sub': 'Klub terbaik · 7 hari terakhir',

        'date.today': 'Hari ini', 'date.yesterday': 'Kemarin', 'date.days_ago': '{n} hari lalu',
        'date.hours_ago': '{n} jam lalu', 'date.just_now': 'Baru saja',

        'stats.key': 'Statistik Utama', 'stats.attack': 'Serangan', 'stats.passes': 'Umpan',
        'stats.defence': 'Pertahanan', 'stats.discipline': 'Disiplin',

        'stat.expected_goals': 'Gol yang Diharapkan', 'stat.big_chances_created': 'Peluang Besar',
        'stat.shots_on_target': 'Tembakan ke Gawang', 'stat.shots_off_target': 'Tembakan Meleset',
        'stat.blocked_shots': 'Tembakan Diblokir', 'stat.corners': 'Tendangan Sudut',
        'stat.shots_in_box': 'Tembakan di Kotak', 'stat.total_passes': 'Total Umpan',
        'stat.successful_passes': 'Umpan Sukses', 'stat.key_passes': 'Umpan Kunci',
        'stat.successful_tackles': 'Tekel Sukses', 'stat.interceptions': 'Intersepsi',
        'stat.clearances': 'Sapuan', 'stat.goalkeeper_saves': 'Penyelamatan Kiper',
        'stat.fouls': 'Pelanggaran', 'stat.yellow_cards': 'Kartu Kuning', 'stat.red_cards': 'Kartu Merah',

        'page.prev': 'Sebelumnya', 'page.next': 'Berikutnya',

        'round.regular': 'Pekan {n}',
        'round.group_stage': 'Fase Grup',
        'round.round_of_16': 'Babak 16 Besar',
        'round.round_of_32': 'Babak 32 Besar',
        'round.round_of_64': 'Babak 64 Besar',
        'round.quarter_final': 'Perempat Final',
        'round.semi_final': 'Semifinal',
        'round.final': 'Final',
        'round.third_place': 'Perebutan Juara 3',
        'round.playoff': 'Playoff',
        'round.preliminary': 'Babak Pendahuluan',
        'round.qualification': 'Kualifikasi',
    },
    ja: {
        'nav.home': 'ホーム', 'nav.leagues': 'リーグ', 'nav.teams': 'チーム',
        'nav.search': '試合を検索...', 'nav.menu': 'メニュー', 'nav.all': 'すべて',
        'nav.popular_teams': '人気チーム', 'nav.search_btn': '検索',

        'match.latest': '最新ハイライト', 'match.more': 'その他のハイライト',
        'match.recent': '最近の結果', 'match.show_score': 'スコアを表示',
        'match.hide_score': 'スコアを隠す', 'match.full_time': '試合終了',
        'match.results': '検索結果', 'match.events': 'タイムライン',
        'match.statistics': '統計', 'match.lineup': 'スターティングメンバー',
        'match.players': '選手', 'match.venue': 'スタジアム', 'match.referee': '審判',
        'match.show_score_events': 'スコアとイベントを表示',
        'match.reveal_hint': 'スコアを公開して試合のイベントを確認',
        'match.no_events': 'イベントデータなし',
        'match.no_video': '動画はまだありません',
        'match.no_related': '関連試合なし',
        'match.source': 'ソース',
        'match.embed': '埋め込む',
        'match.copy_link': 'リンクをコピー',
        'match.copied': 'コピー完了！',
        'match.embed_desc': 'このコードをサイトに貼り付けてハイライトを埋め込む:',
        'match.video_extended': 'エクステンデッド', 'match.video_alt': '別ハイライト',
        'match.video_highlight': 'ハイライト', 'match.video_full_match': 'フル動画',

        'footer.tagline': 'サッカーハイライト・ゴール・リプレイを無料で視聴',
        'footer.rights': '全権利保有',
        'footer.more_leagues': 'その他のリーグ',

        'ui.matches_available': '試合',
        'ui.no_matches_league': 'このリーグの試合はまだありません。',
        'ui.no_matches_team': 'このチームの試合はまだありません。',
        'ui.no_stats': '統計データなし',
        'ui.ball_possession': 'ボール保持率',
        'ui.league': 'リーグ', 'ui.club': 'クラブ', 'ui.national_team': 'ナショナルチーム',
        'ui.match_highlights': '試合ハイライト', 'ui.latest_matches': '最新試合',
        'ui.search_results_for': '検索結果',
        'ui.search_hint': '上の検索バーに入力してください',
        'ui.no_results_for': 'の検索結果なし',
        'ui.teams': 'チーム', 'ui.leagues': 'リーグ', 'ui.matches': '試合',
        'ui.vs': 'vs',
        'ui.featured_highlights': '注目のハイライト', 'ui.featured_sub': 'トップクラブ · 過去7日間',

        'date.today': '今日', 'date.yesterday': '昨日', 'date.days_ago': '{n}日前',
        'date.hours_ago': '{n}時間前', 'date.just_now': 'たった今',

        'stats.key': '主要統計', 'stats.attack': '攻撃', 'stats.passes': 'パス',
        'stats.defence': '守備', 'stats.discipline': '規律',

        'stat.expected_goals': '期待ゴール', 'stat.big_chances_created': 'ビッグチャンス作成',
        'stat.shots_on_target': '枠内シュート', 'stat.shots_off_target': '枠外シュート',
        'stat.blocked_shots': 'ブロックシュート', 'stat.corners': 'コーナーキック',
        'stat.shots_in_box': 'エリア内シュート', 'stat.total_passes': '総パス数',
        'stat.successful_passes': '成功パス数', 'stat.key_passes': 'キーパス',
        'stat.successful_tackles': '成功タックル', 'stat.interceptions': 'インターセプト',
        'stat.clearances': 'クリアランス', 'stat.goalkeeper_saves': 'GKセーブ',
        'stat.fouls': 'ファウル', 'stat.yellow_cards': 'イエローカード', 'stat.red_cards': 'レッドカード',

        'page.prev': '前へ', 'page.next': '次へ',

        'round.regular': '第{n}節',
        'round.group_stage': 'グループステージ',
        'round.round_of_16': 'ラウンド16',
        'round.round_of_32': 'ラウンド32',
        'round.round_of_64': 'ラウンド64',
        'round.quarter_final': '準々決勝',
        'round.semi_final': '準決勝',
        'round.final': '決勝',
        'round.third_place': '3位決定戦',
        'round.playoff': 'プレーオフ',
        'round.preliminary': '予備ラウンド',
        'round.qualification': '予選',
    },
    fr: {
        'nav.home': 'Accueil', 'nav.leagues': 'Ligues', 'nav.teams': 'Équipes',
        'nav.search': 'Rechercher des matchs...', 'nav.menu': 'Menu', 'nav.all': 'Tous',
        'nav.popular_teams': 'Équipes Populaires', 'nav.search_btn': 'Rechercher',

        'match.latest': 'Derniers Temps Forts', 'match.more': 'Plus de Temps Forts',
        'match.recent': 'Résultats Récents', 'match.show_score': 'Afficher le Score',
        'match.hide_score': 'Masquer le Score', 'match.full_time': 'Temps Plein',
        'match.results': 'Résultats pour', 'match.events': 'Chronologie',
        'match.statistics': 'Statistiques', 'match.lineup': 'Composition',
        'match.players': 'Joueurs', 'match.venue': 'Stade', 'match.referee': 'Arbitre',
        'match.show_score_events': 'Voir Score et Événements',
        'match.reveal_hint': 'Révélez le score pour voir les événements du match',
        'match.no_events': 'Pas de données d\'événements',
        'match.no_video': 'Vidéo non disponible',
        'match.no_related': 'Aucun match associé',
        'match.source': 'Source',
        'match.embed': 'Intégrer',
        'match.copy_link': 'Copier le lien',
        'match.copied': 'Copié !',
        'match.embed_desc': 'Collez ce code pour intégrer le résumé sur votre site :',
        'match.video_extended': 'Version longue', 'match.video_alt': 'Autre résumé',
        'match.video_highlight': 'Résumé', 'match.video_full_match': 'Match complet',

        'footer.tagline': 'Regardez les highlights, buts et replays de football – gratuits',
        'footer.rights': 'Tous droits réservés',
        'footer.more_leagues': 'Plus de ligues',

        'ui.matches_available': 'matchs disponibles',
        'ui.no_matches_league': 'Aucun match pour cette ligue.',
        'ui.no_matches_team': 'Aucun match pour cette équipe.',
        'ui.no_stats': 'Aucune statistique disponible',
        'ui.ball_possession': 'POSSESSION DU BALLON',
        'ui.league': 'Ligue', 'ui.club': 'Club', 'ui.national_team': 'Équipe Nationale',
        'ui.match_highlights': 'Moments Forts', 'ui.latest_matches': 'Derniers Matchs',
        'ui.search_results_for': 'Résultats pour',
        'ui.search_hint': 'Tapez quelque chose dans la barre de recherche',
        'ui.no_results_for': 'Aucun résultat pour',
        'ui.teams': 'Équipes', 'ui.leagues': 'Ligues', 'ui.matches': 'Matchs',
        'ui.vs': 'vs',
        'ui.featured_highlights': 'Temps Forts', 'ui.featured_sub': 'Meilleurs clubs · 7 derniers jours',

        'date.today': 'Aujourd\'hui', 'date.yesterday': 'Hier', 'date.days_ago': 'il y a {n} jours',
        'date.hours_ago': 'il y a {n}h', 'date.just_now': 'À l\'instant',

        'stats.key': 'Stats Clés', 'stats.attack': 'Attaque', 'stats.passes': 'Passes',
        'stats.defence': 'Défense', 'stats.discipline': 'Discipline',

        'stat.expected_goals': 'Buts Attendus', 'stat.big_chances_created': 'Grandes Occasions',
        'stat.shots_on_target': 'Tirs Cadrés', 'stat.shots_off_target': 'Tirs Non Cadrés',
        'stat.blocked_shots': 'Tirs Bloqués', 'stat.corners': 'Corners',
        'stat.shots_in_box': 'Tirs dans la Surface', 'stat.total_passes': 'Passes Totales',
        'stat.successful_passes': 'Passes Réussies', 'stat.key_passes': 'Passes Décisives',
        'stat.successful_tackles': 'Tacles Réussis', 'stat.interceptions': 'Interceptions',
        'stat.clearances': 'Dégagements', 'stat.goalkeeper_saves': 'Arrêts du Gardien',
        'stat.fouls': 'Fautes', 'stat.yellow_cards': 'Cartons Jaunes', 'stat.red_cards': 'Cartons Rouges',

        'page.prev': 'Précédent', 'page.next': 'Suivant',

        'round.regular': 'Journée {n}',
        'round.group_stage': 'Phase de Groupes',
        'round.round_of_16': 'Huitièmes de Finale',
        'round.round_of_32': 'Seizièmes de Finale',
        'round.round_of_64': '32es de Finale',
        'round.quarter_final': 'Quart de Finale',
        'round.semi_final': 'Demi-Finale',
        'round.final': 'Finale',
        'round.third_place': 'Troisième Place',
        'round.playoff': 'Barrage',
        'round.preliminary': 'Tour Préliminaire',
        'round.qualification': 'Qualification',
    },
    de: {
        'nav.home': 'Startseite', 'nav.leagues': 'Ligen', 'nav.teams': 'Mannschaften',
        'nav.search': 'Spiele suchen...', 'nav.menu': 'Menü', 'nav.all': 'Alle',
        'nav.popular_teams': 'Beliebte Teams', 'nav.search_btn': 'Suchen',

        'match.latest': 'Neueste Highlights', 'match.more': 'Weitere Highlights',
        'match.recent': 'Aktuelle Ergebnisse', 'match.show_score': 'Ergebnis anzeigen',
        'match.hide_score': 'Ergebnis ausblenden', 'match.full_time': 'Spielende',
        'match.results': 'Ergebnisse für', 'match.events': 'Zeitleiste',
        'match.statistics': 'Statistiken', 'match.lineup': 'Aufstellung',
        'match.players': 'Spieler', 'match.venue': 'Stadion', 'match.referee': 'Schiedsrichter',
        'match.show_score_events': 'Ergebnis & Ereignisse anzeigen',
        'match.reveal_hint': 'Ergebnis aufdecken um Spielereignisse zu sehen',
        'match.no_events': 'Keine Ereignisdaten verfügbar',
        'match.no_video': 'Video noch nicht verfügbar',
        'match.no_related': 'Keine verwandten Spiele',
        'match.source': 'Quelle',
        'match.embed': 'Einbetten',
        'match.copy_link': 'Link kopieren',
        'match.copied': 'Kopiert!',
        'match.embed_desc': 'Füge diesen Code ein, um das Highlight in deine Seite einzubetten:',
        'match.video_extended': 'Erweitert', 'match.video_alt': 'Alt. Highlight',
        'match.video_highlight': 'Highlights', 'match.video_full_match': 'Vollständiges Spiel',

        'footer.tagline': 'Fußball-Highlights, Tore & Spielwiederholungen – kostenlos',
        'footer.rights': 'Alle Rechte vorbehalten',
        'footer.more_leagues': 'Weitere Ligen',

        'ui.matches_available': 'Spiele verfügbar',
        'ui.no_matches_league': 'Noch keine Spiele für diese Liga.',
        'ui.no_matches_team': 'Noch keine Spiele für dieses Team.',
        'ui.no_stats': 'Keine Statistiken verfügbar',
        'ui.ball_possession': 'BALLBESITZ',
        'ui.league': 'Liga', 'ui.club': 'Verein', 'ui.national_team': 'Nationalmannschaft',
        'ui.match_highlights': 'Spielhöhepunkte', 'ui.latest_matches': 'Neueste Spiele',
        'ui.search_results_for': 'Ergebnisse für',
        'ui.search_hint': 'Geben Sie etwas in die Suchleiste ein',
        'ui.no_results_for': 'Keine Ergebnisse für',
        'ui.teams': 'Teams', 'ui.leagues': 'Ligen', 'ui.matches': 'Spiele',
        'ui.vs': 'vs',
        'ui.featured_highlights': 'Top Highlights', 'ui.featured_sub': 'Top Clubs · letzte 7 Tage',

        'date.today': 'Heute', 'date.yesterday': 'Gestern', 'date.days_ago': 'vor {n} Tagen',
        'date.hours_ago': 'vor {n}h', 'date.just_now': 'Gerade eben',

        'stats.key': 'Schlüsselstatistiken', 'stats.attack': 'Angriff', 'stats.passes': 'Pässe',
        'stats.defence': 'Abwehr', 'stats.discipline': 'Disziplin',

        'stat.expected_goals': 'Erwartete Tore', 'stat.big_chances_created': 'Große Chancen',
        'stat.shots_on_target': 'Schüsse aufs Tor', 'stat.shots_off_target': 'Schüsse Daneben',
        'stat.blocked_shots': 'Geblockte Schüsse', 'stat.corners': 'Ecken',
        'stat.shots_in_box': 'Schüsse im Strafraum', 'stat.total_passes': 'Pässe Gesamt',
        'stat.successful_passes': 'Erfolgreiche Pässe', 'stat.key_passes': 'Schlüsselpässe',
        'stat.successful_tackles': 'Erfolgreiche Zweikämpfe', 'stat.interceptions': 'Abfangaktionen',
        'stat.clearances': 'Klärungsaktionen', 'stat.goalkeeper_saves': 'Torhüter-Paraden',
        'stat.fouls': 'Fouls', 'stat.yellow_cards': 'Gelbe Karten', 'stat.red_cards': 'Rote Karten',

        'page.prev': 'Zurück', 'page.next': 'Weiter',

        'round.regular': '{n}. Spieltag',
        'round.group_stage': 'Gruppenphase',
        'round.round_of_16': 'Achtelfinale',
        'round.round_of_32': 'Sechzehntelfinale',
        'round.round_of_64': 'Zweiunddreißigstelfinale',
        'round.quarter_final': 'Viertelfinale',
        'round.semi_final': 'Halbfinale',
        'round.final': 'Finale',
        'round.third_place': 'Spiel um Platz 3',
        'round.playoff': 'Playoff',
        'round.preliminary': 'Vorrunde',
        'round.qualification': 'Qualifikation',
    },
    tr: {
        'nav.home': 'Ana Sayfa', 'nav.leagues': 'Ligler', 'nav.teams': 'Takımlar',
        'nav.search': 'Maç ara...', 'nav.menu': 'Menü', 'nav.all': 'Tümü',
        'nav.popular_teams': 'Popüler Takımlar', 'nav.search_btn': 'Ara',

        'match.latest': 'Son Özetler', 'match.more': 'Daha Fazla Özet',
        'match.recent': 'Son Sonuçlar', 'match.show_score': 'Skoru Göster',
        'match.hide_score': 'Skoru Gizle', 'match.full_time': 'Maç Sonu',
        'match.results': 'Sonuçlar', 'match.events': 'Zaman Çizelgesi',
        'match.statistics': 'İstatistikler', 'match.lineup': 'İlk 11',
        'match.players': 'Oyuncular', 'match.venue': 'Stadyum', 'match.referee': 'Hakem',
        'match.show_score_events': 'Skor ve Olayları Göster',
        'match.reveal_hint': 'Maç olaylarını görmek için skoru açıkla',
        'match.no_events': 'Olay verisi yok',
        'match.no_video': 'Video henüz mevcut değil',
        'match.no_related': 'İlgili maç yok',
        'match.source': 'Kaynak',
        'match.embed': 'Göm',
        'match.copy_link': 'Bağlantıyı Kopyala',
        'match.copied': 'Kopyalandı!',
        'match.embed_desc': 'Bu kodu sitenize yapıştırarak özeti yerleştirin:',
        'match.video_extended': 'Uzatılmış', 'match.video_alt': 'Alternatif Özet',
        'match.video_highlight': 'Özet', 'match.video_full_match': 'Maçın Tamamı',

        'footer.tagline': 'Futbol özetlerini, golleri ve tekrarları ücretsiz izleyin',
        'footer.rights': 'Tüm hakları saklıdır',
        'footer.more_leagues': 'Daha Fazla Lig',

        'ui.matches_available': 'maç mevcut',
        'ui.no_matches_league': 'Bu lig için henüz maç yok.',
        'ui.no_matches_team': 'Bu takım için henüz maç yok.',
        'ui.no_stats': 'İstatistik yok',
        'ui.ball_possession': 'TOP KONTROLÜ',
        'ui.league': 'Lig', 'ui.club': 'Kulüp', 'ui.national_team': 'Milli Takım',
        'ui.match_highlights': 'Maç Özeti', 'ui.latest_matches': 'Son Maçlar',
        'ui.search_results_for': 'Arama Sonuçları',
        'ui.search_hint': 'Yukarıdaki arama çubuğuna bir şeyler yazın',
        'ui.no_results_for': 'İçin sonuç bulunamadı',
        'ui.teams': 'Takımlar', 'ui.leagues': 'Ligler', 'ui.matches': 'Maçlar',
        'ui.vs': 'vs',
        'ui.featured_highlights': 'Öne Çıkan Özetler', 'ui.featured_sub': 'En iyi kulüpler · son 7 gün',

        'date.today': 'Bugün', 'date.yesterday': 'Dün', 'date.days_ago': '{n} gün önce',
        'date.hours_ago': '{n} saat önce', 'date.just_now': 'Az önce',

        'stats.key': 'Temel İstatistikler', 'stats.attack': 'Hücum', 'stats.passes': 'Paslar',
        'stats.defence': 'Savunma', 'stats.discipline': 'Disiplin',

        'stat.expected_goals': 'Beklenen Goller', 'stat.big_chances_created': 'Büyük Fırsatlar',
        'stat.shots_on_target': 'İsabetli Şutlar', 'stat.shots_off_target': 'İsabetsiz Şutlar',
        'stat.blocked_shots': 'Bloklanan Şutlar', 'stat.corners': 'Köşe Vuruşu',
        'stat.shots_in_box': 'Ceza Sahası Şutları', 'stat.total_passes': 'Toplam Paslar',
        'stat.successful_passes': 'Başarılı Paslar', 'stat.key_passes': 'Anahtar Paslar',
        'stat.successful_tackles': 'Başarılı Müdahaleler', 'stat.interceptions': 'Top Kapma',
        'stat.clearances': 'Uzaklaştırmalar', 'stat.goalkeeper_saves': 'Kaleci Kurtarışları',
        'stat.fouls': 'Faul', 'stat.yellow_cards': 'Sarı Kartlar', 'stat.red_cards': 'Kırmızı Kartlar',

        'page.prev': 'Önceki', 'page.next': 'Sonraki',

        'round.regular': '{n}. Hafta',
        'round.group_stage': 'Grup Aşaması',
        'round.round_of_16': 'Son 16',
        'round.round_of_32': 'Son 32',
        'round.round_of_64': 'Son 64',
        'round.quarter_final': 'Çeyrek Final',
        'round.semi_final': 'Yarı Final',
        'round.final': 'Final',
        'round.third_place': '3. lük Maçı',
        'round.playoff': 'Play-off',
        'round.preliminary': 'Ön Eleme',
        'round.qualification': 'Eleme',
    },
    hi: {
        'nav.home': 'होम', 'nav.leagues': 'लीग', 'nav.teams': 'टीमें',
        'nav.search': 'मैच खोजें...', 'nav.menu': 'मेनू', 'nav.all': 'सभी',
        'nav.popular_teams': 'लोकप्रिय टीमें', 'nav.search_btn': 'खोजें',

        'match.latest': 'नवीनतम हाइलाइट्स', 'match.more': 'और हाइलाइट्स',
        'match.recent': 'हालिया परिणाम', 'match.show_score': 'स्कोर दिखाएं',
        'match.hide_score': 'स्कोर छुपाएं', 'match.full_time': 'पूर्ण समय',
        'match.results': 'परिणाम', 'match.events': 'टाइमलाइन',
        'match.statistics': 'आंकड़े', 'match.lineup': 'लाइनअप',
        'match.players': 'खिलाड़ी', 'match.venue': 'स्टेडियम', 'match.referee': 'रेफरी',
        'match.show_score_events': 'स्कोर और घटनाएं दिखाएं',
        'match.reveal_hint': 'मैच इवेंट देखने के लिए स्कोर दिखाएं',
        'match.no_events': 'कोई इवेंट डेटा नहीं',
        'match.no_video': 'वीडियो अभी उपलब्ध नहीं है',
        'match.no_related': 'कोई संबंधित मैच नहीं',
        'match.source': 'स्रोत',
        'match.embed': 'एम्बेड करें',
        'match.copy_link': 'लिंक कॉपी करें',
        'match.copied': 'कॉपी हो गया!',
        'match.embed_desc': 'इस कोड को अपनी साइट पर हाइलाइट एम्बेड करने के लिए पेस्ट करें:',
        'match.video_extended': 'विस्तारित', 'match.video_alt': 'वैकल्पिक हाइलाइट',
        'match.video_highlight': 'हाइलाइट्स', 'match.video_full_match': 'पूरा मैच',

        'footer.tagline': 'फुटबॉल हाइलाइट्स, गोल और रिप्ले निःशुल्क देखें',
        'footer.rights': 'सर्वाधिकार सुरक्षित',
        'footer.more_leagues': 'और लीग',

        'ui.matches_available': 'मैच उपलब्ध',
        'ui.no_matches_league': 'इस लीग के लिए अभी कोई मैच नहीं।',
        'ui.no_matches_team': 'इस टीम के लिए अभी कोई मैच नहीं।',
        'ui.no_stats': 'कोई आंकड़े नहीं',
        'ui.ball_possession': 'गेंद का कब्जा',
        'ui.league': 'लीग', 'ui.club': 'क्लब', 'ui.national_team': 'राष्ट्रीय टीम',
        'ui.match_highlights': 'मैच हाइलाइट्स', 'ui.latest_matches': 'नवीनतम मैच',
        'ui.search_results_for': 'परिणाम',
        'ui.search_hint': 'ऊपर खोज बार में कुछ टाइप करें',
        'ui.no_results_for': 'के लिए कोई परिणाम नहीं',
        'ui.teams': 'टीमें', 'ui.leagues': 'लीग', 'ui.matches': 'मैच',
        'ui.vs': 'बनाम',
        'ui.featured_highlights': 'फीचर्ड हाइलाइट्स', 'ui.featured_sub': 'शीर्ष क्लब · पिछले 7 दिन',

        'date.today': 'आज', 'date.yesterday': 'कल', 'date.days_ago': '{n} दिन पहले',
        'date.hours_ago': '{n} घंटे पहले', 'date.just_now': 'अभी',

        'stats.key': 'मुख्य आंकड़े', 'stats.attack': 'हमला', 'stats.passes': 'पास',
        'stats.defence': 'रक्षा', 'stats.discipline': 'अनुशासन',

        'stat.expected_goals': 'अपेक्षित गोल', 'stat.big_chances_created': 'बड़े मौके',
        'stat.shots_on_target': 'लक्ष्य पर शॉट', 'stat.shots_off_target': 'लक्ष्य से बाहर शॉट',
        'stat.blocked_shots': 'ब्लॉक्ड शॉट', 'stat.corners': 'कॉर्नर किक',
        'stat.shots_in_box': 'बॉक्स में शॉट', 'stat.total_passes': 'कुल पास',
        'stat.successful_passes': 'सफल पास', 'stat.key_passes': 'की पास',
        'stat.successful_tackles': 'सफल टैकल', 'stat.interceptions': 'इंटरसेप्शन',
        'stat.clearances': 'क्लियरेंस', 'stat.goalkeeper_saves': 'गोलकीपर सेव',
        'stat.fouls': 'फाउल', 'stat.yellow_cards': 'पीले कार्ड', 'stat.red_cards': 'लाल कार्ड',

        'page.prev': 'पिछला', 'page.next': 'अगला',

        'round.regular': 'राउंड {n}',
        'round.group_stage': 'ग्रुप स्टेज',
        'round.round_of_16': 'राउंड ऑफ 16',
        'round.round_of_32': 'राउंड ऑफ 32',
        'round.round_of_64': 'राउंड ऑफ 64',
        'round.quarter_final': 'क्वार्टर फाइनल',
        'round.semi_final': 'सेमीफाइनल',
        'round.final': 'फाइनल',
        'round.third_place': 'तीसरे स्थान',
        'round.playoff': 'प्लेऑफ',
        'round.preliminary': 'प्रारंभिक राउंड',
        'round.qualification': 'क्वालीफिकेशन',
    },
}

export const leagueTranslations = {
    'premier-league': {
        ar: 'الدوري الإنجليزي الممتاز', ja: 'プレミアリーグ',
        hi: 'प्रीमियर लीग', bn: 'প্রিমিয়ার লিগ', id: 'Liga Premier',
        de: 'Premier League', fr: 'Premier League', es: 'Premier League',
        pt: 'Premier League', tr: 'Premier Lig', sw: 'Premier League',
    },
    'uefa-champions-league': {
        es: 'Liga de Campeones', pt: 'Liga dos Campeões', ar: 'دوري أبطال أوروبا',
        id: 'Liga Champions', bn: 'চ্যাম্পিয়নস লিগ', ja: 'チャンピオンズリーグ',
        fr: 'Ligue des Champions', de: 'Champions League', tr: 'Şampiyonlar Ligi',
        sw: 'Ligi ya Mabingwa', hi: 'चैम्पियंस लीग',
    },
    'la-liga': {
        ar: 'الدوري الإسباني', ja: 'ラ・リーガ', hi: 'ला लीगा', bn: 'লা লিগা',
        de: 'La Liga', fr: 'La Liga', pt: 'La Liga', tr: 'La Liga', id: 'La Liga',
    },
    'bundesliga': {
        ar: 'الدوري الألماني', ja: 'ブンデスリーガ', hi: 'बुंडेसलीगा', bn: 'বুন্দেসলিগা',
        es: 'Bundesliga', fr: 'Bundesliga', pt: 'Bundesliga', tr: 'Bundesliga', id: 'Bundesliga',
    },
    'serie-a': {
        ar: 'الدوري الإيطالي', ja: 'セリエA', hi: 'सेरी ए', bn: 'সেরি আ',
        fr: 'Serie A', de: 'Serie A', es: 'Serie A', pt: 'Série A', tr: 'Serie A', id: 'Serie A',
    },
    'ligue-1': {
        ar: 'الدوري الفرنسي', ja: 'リーグ・アン', hi: 'लिग 1', bn: 'লিগ ১',
        de: 'Ligue 1', es: 'Ligue 1', pt: 'Ligue 1', tr: 'Ligue 1', id: 'Ligue 1',
    },
    'uefa-europa-league': {
        es: 'Liga Europa', pt: 'Liga Europa', ar: 'الدوري الأوروبي',
        id: 'Liga Europa', bn: 'ইউরোপা লিগ', ja: 'ヨーロッパリーグ',
        fr: 'Ligue Europa', de: 'Europa League', tr: 'Avrupa Ligi',
        sw: 'Ligi ya Ulaya', hi: 'यूरोपा लीग',
    },
    'copa-america': {
        ar: 'كوبا أمريكا', bn: 'কোপা আমেরিকা', ja: 'コパ・アメリカ',
        tr: 'Copa Amerika', sw: 'Copa America', hi: 'कोपा अमेरिका',
    },
    'euro-championship': {
        es: 'Eurocopa', pt: 'Eurocopa', ar: 'بطولة أوروبا',
        id: 'Kejuaraan Euro', bn: 'ইউরো চ্যাম্পিয়নশিপ', ja: '欧州選手権',
        fr: 'Championnat d\'Europe', de: 'Europameisterschaft', tr: 'Avrupa Şampiyonası',
        sw: 'Kombe la Ulaya', hi: 'यूरो चैम्पियनशिप',
    },
    'world-cup': {
        es: 'Copa del Mundo', pt: 'Copa do Mundo', ar: 'كأس العالم',
        id: 'Piala Dunia', bn: 'বিশ্বকাপ', ja: 'ワールドカップ',
        fr: 'Coupe du Monde', de: 'Weltmeisterschaft', tr: 'Dünya Kupası',
        sw: 'Kombe la Dunia', hi: 'विश्व कप',
    },
    'friendlies': {
        es: 'Amistosos', pt: 'Amistosos', ar: 'مباريات ودية',
        id: 'Persahabatan', bn: 'প্রীতি ম্যাচ', ja: '親善試合',
        fr: 'Matchs Amicaux', de: 'Freundschaftsspiele', tr: 'Hazırlık Maçları',
        sw: 'Mechi za Kirafiki', hi: 'मैत्री मैच',
    },
    'uefa-nations-league': {
        es: 'Liga de Naciones', pt: 'Liga das Nações', ar: 'دوري أمم أوروبا',
        bn: 'UEFA নেশনস লিগ', ja: 'UEFAネーションズリーグ',
        fr: 'Ligue des Nations', de: 'Nations League', tr: 'Uluslar Ligi',
        hi: 'UEFA नेशंस लीग',
    },
    'fa-cup': {
        ar: 'كأس إنجلترا', ja: 'FAカップ', hi: 'FA कप',
        es: 'Copa FA', pt: 'Copa FA', fr: 'Coupe FA',
    },
    'copa-del-rey': {
        ar: 'كأس الملك', ja: 'コパ・デル・レイ',
        fr: 'Coupe du Roi', pt: 'Copa del Rei', hi: 'कोपा देल रे',
    },
    'coupe-de-france': {
        es: 'Copa de Francia', pt: 'Copa da França', ar: 'كأس فرنسا',
        ja: 'フランスカップ', hi: 'फ्रांस कप', de: 'Französischer Pokal',
    },
    'dfb-pokal': {
        ar: 'كأس ألمانيا', ja: 'DFBポカール',
        fr: 'Coupe d\'Allemagne', es: 'Copa de Alemania', pt: 'Copa da Alemanha', hi: 'जर्मनी कप',
    },
    'coppa-italia': {
        es: 'Copa Italia', pt: 'Copa da Itália', ar: 'كأس إيطاليا',
        ja: 'コッパ・イタリア', fr: 'Coupe d\'Italie', hi: 'इटली कप',
    },
    'major-league-soccer': {
        es: 'Liga Mayor de Fútbol', ar: 'دوري كرة القدم الأمريكي',
        ja: 'メジャーリーグサッカー', fr: 'MLS', hi: 'मेजर लीग सॉकर',
        pt: 'Liga Principal de Futebol',
    },
    'concacaf-champions-league': {
        es: 'Liga de Campeones CONCACAF', pt: 'Liga dos Campeões CONCACAF',
        ar: 'دوري أبطال الكونكاكاف', fr: 'Ligue des Champions CONCACAF', hi: 'CONCACAF चैम्पियंस लीग',
    },
    'concacaf-gold-cup': {
        es: 'Copa de Oro CONCACAF', pt: 'Copa Ouro CONCACAF',
        ar: 'كأس الذهب للكونكاكاف', fr: 'Gold Cup CONCACAF', hi: 'CONCACAF गोल्ड कप',
    },
    'super-lig': {
        ar: 'الدوري التركي', ja: 'スーペル・リグ', fr: 'Süper Lig',
        es: 'Superliga turca', hi: 'सुपर लिग',
    },
    'liga-profesional-argentina': {
        ar: 'الدوري الأرجنتيني', ja: 'アルゼンチンリーグ',
        fr: 'Liga Argentina', pt: 'Liga Argentina', hi: 'अर्जेंटीना लिगा',
    },
    'eredivisie': {
        ar: 'الدوري الهولندي', ja: 'エールディヴィジ',
        fr: 'Eredivisie', es: 'Eredivisie', hi: 'एरेडिविसी',
    },
    'liga-mx': {
        ar: 'دوري المكسيك', ja: 'リーガMX',
        fr: 'Liga MX', pt: 'Liga MX', hi: 'लिगा MX',
    },
    'primeira-liga': {
        ar: 'الدوري البرتغالي', ja: 'プリメイラ・リーガ',
        fr: 'Primeira Liga', es: 'Primera Liga', hi: 'प्रिमेरा लिगा',
    },
    'jupiler-pro-league': {
        ar: 'الدوري البلجيكي', ja: 'ジュピラー・プロ・リーグ',
        fr: 'Pro League belge', hi: 'जुपिलर प्रो लीग',
    },
    'international-friendlies': {
        es: 'Amistosos Internacionales', pt: 'Amistosos Internacionais',
        fr: 'Matchs Amicaux Internationaux', de: 'Internationale Freundschaftsspiele',
        tr: 'Uluslararası Hazırlık Maçları', id: 'Persahabatan Internasional',
        ar: 'مباريات دولية ودية', ja: '国際親善試合', hi: 'अंतर्राष्ट्रीय मैत्री मैच',
    },
    'club-friendlies': {
        es: 'Amistosos de Clubes', pt: 'Amistosos de Clubes',
        fr: 'Matchs Amicaux de Clubs', de: 'Vereinsfreundschaftsspiele',
        tr: 'Kulüp Hazırlık Maçları', id: 'Persahabatan Klub',
        ar: 'مباريات ودية للأندية', ja: 'クラブ親善試合', hi: 'क्लब मैत्री मैच',
    },
    'concacaf-champions-league': {
        es: 'Liga de Campeones CONCACAF', pt: 'Liga dos Campeões CONCACAF',
        fr: 'Ligue des Champions CONCACAF', de: 'CONCACAF Champions League',
        tr: 'CONCACAF Şampiyonlar Ligi', id: 'Liga Champions CONCACAF',
        ar: 'دوري أبطال الكونكاكاف', ja: 'CONCACAFチャンピオンズリーグ', hi: 'CONCACAF चैम्पियंस लीग',
    },
    'concacaf-gold-cup': {
        es: 'Copa de Oro CONCACAF', pt: 'Copa Ouro CONCACAF',
        fr: 'Gold Cup CONCACAF', de: 'CONCACAF Gold Cup',
        tr: 'CONCACAF Altın Kupası', id: 'Piala Emas CONCACAF',
        ar: 'كأس الذهب للكونكاكاف', ja: 'CONCACAFゴールドカップ', hi: 'CONCACAF गोल्ड कप',
    },
    'friendlies-women': {
        es: 'Amistosos Femeninos', pt: 'Amistosos Femininos',
        fr: 'Matchs Amicaux Féminins', de: 'Frauen-Freundschaftsspiele',
        tr: 'Kadınlar Hazırlık Maçları', id: 'Persahabatan Wanita',
        ar: 'مباريات ودية نسائية', ja: '女子親善試合', hi: 'महिला मैत्री मैच',
    },
}

// Static translations for national teams (slug → locale → name)
export const nationalTeamTranslations = {
    // CONMEBOL
    'brazil':      { ar: 'البرازيل',   ja: 'ブラジル',      hi: 'ब्राज़ील',      bn: 'ব্রাজিল',      id: 'Brasil',        de: 'Brasilien',      fr: 'Brésil',        tr: 'Brezilya' },
    'argentina':   { ar: 'الأرجنتين', ja: 'アルゼンチン',   hi: 'अर्जेंटीना',    bn: 'আর্জেন্টিনা',   id: 'Argentina',     de: 'Argentinien',    fr: 'Argentine',     tr: 'Arjantin' },
    'uruguay':     { ar: 'أوروغواي',  ja: 'ウルグアイ',     hi: 'उरुग्वे',       bn: 'উরুগুয়ে',     id: 'Uruguay',       fr: 'Uruguay',        de: 'Uruguay',       tr: 'Uruguay' },
    'colombia':    { ar: 'كولومبيا',  ja: 'コロンビア',     hi: 'कोलम्बिया',     bn: 'কলম্বিয়া',     id: 'Kolombia',      fr: 'Colombie',       de: 'Kolumbien',     tr: 'Kolombiya' },
    'chile':       { ar: 'تشيلي',     ja: 'チリ',           hi: 'चिली',          bn: 'চিলি',          id: 'Chili',         fr: 'Chili',          de: 'Chile',         tr: 'Şili' },
    'peru':        { ar: 'بيرو',      ja: 'ペルー',         hi: 'पेरू',          bn: 'পেরু',          id: 'Peru',          fr: 'Pérou',          de: 'Peru',          tr: 'Peru' },
    'ecuador':     { ar: 'الإكوادور',ja: 'エクアドル',      hi: 'इक्वाडोर',      bn: 'ইকুয়েডর',     id: 'Ekuador',       fr: 'Équateur',       de: 'Ecuador',       tr: 'Ekvador' },
    'paraguay':    { ar: 'باراغواي', ja: 'パラグアイ',      hi: 'पैराग्वे',      bn: 'প্যারাগুয়ে',   id: 'Paraguay',      fr: 'Paraguay',       de: 'Paraguay',      tr: 'Paraguay' },
    'venezuela':   { ar: 'فنزويلا',  ja: 'ベネズエラ',      hi: 'वेनेज़ुएला',    bn: 'ভেনেজুয়েলা',   id: 'Venezuela',     fr: 'Venezuela',      de: 'Venezuela',     tr: 'Venezuela' },
    'bolivia':     { ar: 'بوليفيا',  ja: 'ボリビア',        hi: 'बोलीविया',      bn: 'বলিভিয়া',     id: 'Bolivia',       fr: 'Bolivie',        de: 'Bolivien',      tr: 'Bolivya' },

    // UEFA
    'france':      { ar: 'فرنسا',     ja: 'フランス',       hi: 'फ्रांस',        bn: 'ফ্রান্স',       id: 'Prancis',       de: 'Frankreich',     tr: 'Fransa',        sw: 'Ufaransa',   es: 'Francia',       pt: 'França' },
    'germany':     { ar: 'ألمانيا',   ja: 'ドイツ',         hi: 'जर्मनी',        bn: 'জার্মানি',      id: 'Jerman',        es: 'Alemania',       pt: 'Alemanha',      fr: 'Allemagne',   tr: 'Almanya',  sw: 'Ujerumani' },
    'spain':       { ar: 'إسبانيا',   ja: 'スペイン',       hi: 'स्पेन',         bn: 'স্পেন',         id: 'Spanyol',       de: 'Spanien',        fr: 'Espagne',       tr: 'İspanya',  sw: 'Hispania',   es: 'España',        pt: 'Espanha' },
    'italy':       { ar: 'إيطاليا',   ja: 'イタリア',       hi: 'इटली',          bn: 'ইতালি',         id: 'Italia',        de: 'Italien',        fr: 'Italie',        tr: 'İtalya',   sw: 'Italia',     es: 'Italia',        pt: 'Itália' },
    'england':     { ar: 'إنجلترا',   ja: 'イングランド',   hi: 'इंग्लैंड',      bn: 'ইংল্যান্ড',     id: 'Inggris',       de: 'England',        fr: 'Angleterre',    tr: 'İngiltere', sw: 'Uingereza', es: 'Inglaterra',   pt: 'Inglaterra' },
    'portugal':    { ar: 'البرتغال',  ja: 'ポルトガル',     hi: 'पुर्तगाल',      bn: 'পর্তুগাল',      id: 'Portugal',      de: 'Portugal',       fr: 'Portugal',      tr: 'Portekiz',   es: 'Portugal',      pt: 'Portugal' },
    'netherlands': { ar: 'هولندا',    ja: 'オランダ',       hi: 'नीदरलैंड',      bn: 'নেদারল্যান্ডস', id: 'Belanda',       de: 'Niederlande',    fr: 'Pays-Bas',      tr: 'Hollanda',  es: 'Países Bajos',  pt: 'Holanda' },
    'belgium':     { ar: 'بلجيكا',    ja: 'ベルギー',       hi: 'बेल्जियम',      bn: 'বেলজিয়াম',     id: 'Belgia',        de: 'Belgien',        fr: 'Belgique',      tr: 'Belçika',    es: 'Bélgica',       pt: 'Bélgica' },
    'croatia':     { ar: 'كرواتيا',   ja: 'クロアチア',     hi: 'क्रोएशिया',     bn: 'ক্রোয়েশিয়া',  id: 'Kroasia',       de: 'Kroatien',       fr: 'Croatie',       tr: 'Hırvatistan', es: 'Croacia',      pt: 'Croácia' },
    'switzerland': { ar: 'سويسرا',    ja: 'スイス',         hi: 'स्विट्जरलैंड',  bn: 'সুইজারল্যান্ড', id: 'Swiss',         de: 'Schweiz',        fr: 'Suisse',        tr: 'İsviçre',    es: 'Suiza',         pt: 'Suíça' },
    'denmark':     { ar: 'الدنمارك',  ja: 'デンマーク',     hi: 'डेनमार्क',      bn: 'ডেনমার্ক',      id: 'Denmark',       de: 'Dänemark',       fr: 'Danemark',      tr: 'Danimarka',  es: 'Dinamarca',     pt: 'Dinamarca' },
    'sweden':      { ar: 'السويد',    ja: 'スウェーデン',   hi: 'स्वीडन',        bn: 'সুইডেন',        id: 'Swedia',        de: 'Schweden',       fr: 'Suède',         tr: 'İsveç',      es: 'Suecia',        pt: 'Suécia' },
    'austria':     { ar: 'النمسا',    ja: 'オーストリア',   hi: 'ऑस्ट्रिया',     bn: 'অস্ট্রিয়া',    id: 'Austria',       de: 'Österreich',     fr: 'Autriche',      tr: 'Avusturya',  es: 'Austria',       pt: 'Áustria' },
    'poland':      { ar: 'بولندا',    ja: 'ポーランド',     hi: 'पोलैंड',        bn: 'পোল্যান্ড',     id: 'Polandia',      de: 'Polen',          fr: 'Pologne',       tr: 'Polonya',    es: 'Polonia',       pt: 'Polônia' },
    'serbia':      { ar: 'صربيا',     ja: 'セルビア',       hi: 'सर्बिया',       bn: 'সার্বিয়া',     id: 'Serbia',        de: 'Serbien',        fr: 'Serbie',        tr: 'Sırbistan',  es: 'Serbia',        pt: 'Sérvia' },
    'turkey':      { ar: 'تركيا',     ja: 'トルコ',         hi: 'तुर्की',        bn: 'তুরস্ক',        id: 'Turki',         de: 'Türkei',         fr: 'Turquie',       es: 'Turquía',    pt: 'Turquia' },
    'ukraine':     { ar: 'أوكرانيا',  ja: 'ウクライナ',     hi: 'यूक्रेन',       bn: 'ইউক্রেন',       id: 'Ukraina',       de: 'Ukraine',        fr: 'Ukraine',       tr: 'Ukrayna',    es: 'Ucrania',       pt: 'Ucrânia' },
    'czech-republic': { ar: 'جمهورية التشيك', ja: 'チェコ', hi: 'चेक गणराज्य', bn: 'চেক প্রজাতন্ত্র', id: 'Republik Ceko', de: 'Tschechien', fr: 'Tchéquie', tr: 'Çekya', es: 'República Checa', pt: 'República Tcheca' },
    'czechia':     { ar: 'جمهورية التشيك', ja: 'チェコ',   hi: 'चेक गणराज्य',  bn: 'চেক প্রজাতন্ত্র', id: 'Republik Ceko', de: 'Tschechien', fr: 'Tchéquie', tr: 'Çekya', es: 'Chequia',       pt: 'Tchéquia' },
    'hungary':     { ar: 'المجر',     ja: 'ハンガリー',     hi: 'हंगरी',         bn: 'হাঙ্গেরি',      id: 'Hongaria',      de: 'Ungarn',         fr: 'Hongrie',       tr: 'Macaristan', es: 'Hungría',       pt: 'Hungria' },
    'romania':     { ar: 'رومانيا',   ja: 'ルーマニア',     hi: 'रोमानिया',      bn: 'রোমানিয়া',     id: 'Rumania',       de: 'Rumänien',       fr: 'Roumanie',      tr: 'Romanya',    es: 'Rumanía',       pt: 'Romênia' },
    'greece':      { ar: 'اليونان',   ja: 'ギリシャ',       hi: 'यूनान',         bn: 'গ্রিস',         id: 'Yunani',        de: 'Griechenland',   fr: 'Grèce',         tr: 'Yunanistan', es: 'Grecia',        pt: 'Grécia' },
    'scotland':    { ar: 'اسكتلندا',  ja: 'スコットランド', hi: 'स्कॉटलैंड',     bn: 'স্কটল্যান্ড',   id: 'Skotlandia',    de: 'Schottland',     fr: 'Écosse',        tr: 'İskoçya',    es: 'Escocia',       pt: 'Escócia' },
    'wales':       { ar: 'ويلز',      ja: 'ウェールズ',     hi: 'वेल्स',         bn: 'ওয়েলস',        id: 'Wales',         de: 'Wales',          fr: 'Pays de Galles', tr: 'Galler',    es: 'Gales',         pt: 'País de Gales' },
    'norway':      { ar: 'النرويج',   ja: 'ノルウェー',     hi: 'नॉर्वे',        bn: 'নরওয়ে',        id: 'Norwegia',      de: 'Norwegen',       fr: 'Norvège',       tr: 'Norveç',     es: 'Noruega',       pt: 'Noruega' },
    'slovakia':    { ar: 'سلوفاكيا',  ja: 'スロバキア',     hi: 'स्लोवाकिया',    bn: 'স্লোভাকিয়া',   id: 'Slovakia',      de: 'Slowakei',       fr: 'Slovaquie',     tr: 'Slovakya',   es: 'Eslovaquia',    pt: 'Eslováquia' },
    'slovenia':    { ar: 'سلوفينيا', ja: 'スロベニア',      hi: 'स्लोवेनिया',    bn: 'স্লোভেনিয়া',   id: 'Slovenia',      de: 'Slowenien',      fr: 'Slovénie',      tr: 'Slovenya',   es: 'Eslovenia',     pt: 'Eslovênia' },
    'albania':     { ar: 'ألبانيا',   ja: 'アルバニア',     hi: 'अल्बानिया',     bn: 'আলবেনিয়া',     id: 'Albania',       de: 'Albanien',       fr: 'Albanie',       tr: 'Arnavutluk', es: 'Albania',       pt: 'Albânia' },
    'finland':     { ar: 'فنلندا',    ja: 'フィンランド',   hi: 'फ़िनलैंड',      bn: 'ফিনল্যান্ড',    id: 'Finlandia',     de: 'Finnland',       fr: 'Finlande',      tr: 'Finlandiya', es: 'Finlandia',     pt: 'Finlândia' },
    'iceland':     { ar: 'آيسلندا',   ja: 'アイスランド',   hi: 'आइसलैंड',       bn: 'আইসল্যান্ড',    id: 'Islandia',      de: 'Island',         fr: 'Islande',       tr: 'İzlanda',    es: 'Islandia',      pt: 'Islândia' },
    'ireland':     { ar: 'أيرلندا',   ja: 'アイルランド',   hi: 'आयरलैंड',       bn: 'আয়ারল্যান্ড',   id: 'Irlandia',      de: 'Irland',         fr: 'Irlande',       tr: 'İrlanda',    es: 'Irlanda',       pt: 'Irlanda' },
    'georgia':     { ar: 'جورجيا',    ja: 'ジョージア',     hi: 'जॉर्जिया',      bn: 'জর্জিয়া',      id: 'Georgia',       de: 'Georgien',       fr: 'Géorgie',       tr: 'Gürcistan',  es: 'Georgia',       pt: 'Geórgia' },

    // CONCACAF
    'mexico':      { ar: 'المكسيك',   ja: 'メキシコ',       hi: 'मेक्सिको',      bn: 'মেক্সিকো',      id: 'Meksiko',       de: 'Mexiko',         fr: 'Mexique',       tr: 'Meksika',  sw: 'Meksiko' },
    'usa':         { ar: 'الولايات المتحدة', ja: 'アメリカ', hi: 'संयुक्त राज्य', bn: 'আমেরিকা',       id: 'Amerika Serikat', de: 'USA',           fr: 'États-Unis',    tr: 'ABD' },
    'united-states': { ar: 'الولايات المتحدة', ja: 'アメリカ合衆国', hi: 'संयुक्त राज्य अमेरिका', bn: 'মার্কিন যুক্তরাষ্ট্র', id: 'Amerika Serikat', de: 'Vereinigte Staaten', fr: 'États-Unis', tr: 'Amerika Birleşik Devletleri' },
    'canada':      { ar: 'كندا',      ja: 'カナダ',          hi: 'कनाडा',         bn: 'কানাডা',        id: 'Kanada',        de: 'Kanada',         fr: 'Canada',        tr: 'Kanada' },
    'costa-rica':  { ar: 'كوستاريكا', ja: 'コスタリカ',     hi: 'कोस्टा रिका',   bn: 'কোস্টা রিকা',   id: 'Kosta Rika',    de: 'Costa Rica',     fr: 'Costa Rica',    tr: 'Kosta Rika' },
    'jamaica':     { ar: 'جامايكا',   ja: 'ジャマイカ',     hi: 'जमैका',         bn: 'জামাইকা',       id: 'Jamaika',       de: 'Jamaika',        fr: 'Jamaïque',      tr: 'Jamaika' },
    'panama':      { ar: 'بنما',      ja: 'パナマ',          hi: 'पनामा',         bn: 'পানামা',        id: 'Panama',        de: 'Panama',         fr: 'Panama',        tr: 'Panama' },
    'honduras':    { ar: 'هندوراس',   ja: 'ホンジュラス',   hi: 'होंडुरास',      bn: 'হন্ডুরাস',      id: 'Honduras',      de: 'Honduras',       fr: 'Honduras',      tr: 'Honduras' },

    // AFC
    'japan':       { ar: 'اليابان',   hi: 'जापान',          bn: 'জাপান',          id: 'Jepang',        de: 'Japan',          fr: 'Japon',          tr: 'Japonya',       sw: 'Japani',   es: 'Japón',         pt: 'Japão' },
    'south-korea': { ar: 'كوريا الجنوبية', ja: '韓国',      hi: 'दक्षिण कोरिया', bn: 'দক্ষিণ কোরিয়া', id: 'Korea Selatan', de: 'Südkorea',       fr: 'Corée du Sud',  tr: 'Güney Kore', es: 'Corea del Sur', pt: 'Coreia do Sul' },
    'korea-republic': { ar: 'كوريا الجنوبية', ja: '韓国',   hi: 'दक्षिण कोरिया', bn: 'দক্ষিণ কোরিয়া', id: 'Korea Selatan', de: 'Südkorea',       fr: 'Corée du Sud',  tr: 'Güney Kore', es: 'Corea del Sur', pt: 'Coreia do Sul' },
    'australia':   { ar: 'أستراليا',  ja: 'オーストラリア', hi: 'ऑस्ट्रेलिया',   bn: 'অস্ট্রেলিয়া',   id: 'Australia',     de: 'Australien',     fr: 'Australie',     tr: 'Avustralya', sw: 'Australia',  es: 'Australia',     pt: 'Austrália' },
    'iran':        { ar: 'إيران',     ja: 'イラン',          hi: 'ईरान',          bn: 'ইরান',           id: 'Iran',           de: 'Iran',           fr: 'Iran',          tr: 'İran',       es: 'Irán',          pt: 'Irã' },
    'saudi-arabia':{ ar: 'المملكة العربية السعودية', ja: 'サウジアラビア', hi: 'सऊदी अरब', bn: 'সৌদি আরব', id: 'Arab Saudi', de: 'Saudi-Arabien', fr: 'Arabie Saoudite', tr: 'Suudi Arabistan', es: 'Arabia Saudita', pt: 'Arábia Saudita' },
    'qatar':       { ar: 'قطر',       ja: 'カタール',        hi: 'क़तर',           bn: 'কাতার',          id: 'Qatar',         de: 'Katar',          fr: 'Qatar',         tr: 'Katar',      es: 'Catar',         pt: 'Catar' },
    'china':       { ar: 'الصين',     ja: '中国',            hi: 'चीन',            bn: 'চীন',            id: 'Tiongkok',      de: 'China',          fr: 'Chine',         tr: 'Çin',  es: 'China', sw: 'China',         pt: 'China' },
    'china-pr':    { ar: 'الصين',     ja: '中国',            hi: 'चीन',            bn: 'চীন',            id: 'Tiongkok',      de: 'China',          fr: 'Chine',         tr: 'Çin',        es: 'China',         pt: 'China' },

    // CAF
    'morocco':     { ar: 'المغرب',    ja: 'モロッコ',        hi: 'मोरक्को',        bn: 'মরক্কো',         id: 'Maroko',        de: 'Marokko',        fr: 'Maroc',         tr: 'Fas',  sw: 'Moroko',     es: 'Marruecos',     pt: 'Marrocos' },
    'senegal':     { ar: 'السنغال',   ja: 'セネガル',        hi: 'सेनेगल',         bn: 'সেনেগাল',        id: 'Senegal',       de: 'Senegal',        fr: 'Sénégal',       tr: 'Senegal',    es: 'Senegal',       pt: 'Senegal' },
    'nigeria':     { ar: 'نيجيريا',   ja: 'ナイジェリア',   hi: 'नाइजीरिया',      bn: 'নাইজেরিয়া',     id: 'Nigeria',       de: 'Nigeria',        fr: 'Nigéria',       tr: 'Nijerya',  sw: 'Nigeria',    es: 'Nigeria',       pt: 'Nigéria' },
    'egypt':       { ar: 'مصر',       ja: 'エジプト',        hi: 'मिस्र',           bn: 'মিশর',           id: 'Mesir',         de: 'Ägypten',        fr: 'Égypte',        tr: 'Mısır',  sw: 'Misri',      es: 'Egipto',        pt: 'Egito' },
    'cameroon':    { ar: 'الكاميرون', ja: 'カメルーン',      hi: 'कैमरून',         bn: 'ক্যামেরুন',      id: 'Kamerun',       de: 'Kamerun',        fr: 'Cameroun',      tr: 'Kamerun',    es: 'Camerún',       pt: 'Camarões' },
    'ghana':       { ar: 'غانا',      ja: 'ガーナ',          hi: 'घाना',           bn: 'ঘানা',           id: 'Ghana',         de: 'Ghana',          fr: 'Ghana',         tr: 'Gana',       es: 'Ghana',         pt: 'Gana' },
    'tunisia':     { ar: 'تونس',      ja: 'チュニジア',      hi: 'ट्यूनीशिया',     bn: 'তিউনিসিয়া',     id: 'Tunisia',       de: 'Tunesien',       fr: 'Tunisie',       tr: 'Tunus',      es: 'Túnez',         pt: 'Tunísia' },
    'algeria':     { ar: 'الجزائر',   ja: 'アルジェリア',   hi: 'अल्जीरिया',      bn: 'আলজেরিয়া',      id: 'Aljazair',      de: 'Algerien',       fr: 'Algérie',       tr: 'Cezayir',    es: 'Argelia',       pt: 'Argélia' },
    'ivory-coast': { ar: 'ساحل العاج', ja: 'コートジボワール', hi: 'आइवरी कोस्ट', bn: 'আইভরি কোস্ট',   id: "Pantai Gading", de: 'Elfenbeinküste', fr: "Côte d'Ivoire", tr: 'Fildişi Sahili', es: 'Costa de Marfil', pt: 'Costa do Marfim' },
    'south-africa':{ ar: 'جنوب أفريقيا', ja: '南アフリカ', hi: 'दक्षिण अफ्रीका', bn: 'দক্ষিণ আফ্রিকা', id: 'Afrika Selatan', de: 'Südafrika',    fr: 'Afrique du Sud', tr: 'Güney Afrika', es: 'Sudáfrica',     pt: 'África do Sul' },
}

// Round label → translation key
export const ROUND_LOOKUP = {
    'Group Stage': 'round.group_stage',
    'Groups Stage': 'round.group_stage',
    'Group stage': 'round.group_stage',
    'Round of 16': 'round.round_of_16',
    'Round of 32': 'round.round_of_32',
    'Round of 64': 'round.round_of_64',
    'Quarter-Final': 'round.quarter_final',
    'Quarter-Finals': 'round.quarter_final',
    'Quarter-finals': 'round.quarter_final',
    'Quarterfinal': 'round.quarter_final',
    'Quarterfinals': 'round.quarter_final',
    'Semi-Final': 'round.semi_final',
    'Semi-Finals': 'round.semi_final',
    'Semi-finals': 'round.semi_final',
    'Semifinal': 'round.semi_final',
    'Semifinals': 'round.semi_final',
    'Final': 'round.final',
    '3rd Place Final': 'round.third_place',
    '3rd Place': 'round.third_place',
    'Third Place': 'round.third_place',
    'Third-Place Final': 'round.third_place',
    'Playoff Round': 'round.playoff',
    'Play-offs': 'round.playoff',
    'Play-off': 'round.playoff',
    'Playoffs': 'round.playoff',
    'Playoff': 'round.playoff',
    'Preliminary Round': 'round.preliminary',
    'Preliminary': 'round.preliminary',
    'Qualification': 'round.qualification',
    'Qualifying Round': 'round.qualification',
    'Qualifying Rounds': 'round.qualification',
}

const LOCALE_PREFIXES = ['es','pt','ar','id','ja','fr','de','tr','hi']

export function useLocale(locale = 'en') {
    const loc = computed(() => {
        const l = isRef(locale) ? locale.value : locale
        return (l === 'en' || LOCALE_PREFIXES.includes(l)) ? l : 'en'
    })

    function t(key) {
        return translations[loc.value]?.[key] ?? translations.en[key] ?? key
    }

    function teamName(team) {
        if (!team) return ''
        if (loc.value === 'en') return team.name
        // Check DB translations first
        const dbTr = Array.isArray(team.translations)
            ? team.translations.find(tr => tr.locale === loc.value)
            : null
        if (dbTr?.name) return dbTr.name
        // Fallback: static national team dictionary
        const slug = team.slug
            || team.name?.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
        return nationalTeamTranslations[slug]?.[loc.value] ?? team.name
    }

    function leagueName(leagueOrName) {
        if (!leagueOrName) return ''
        const name = typeof leagueOrName === 'string' ? leagueOrName : leagueOrName.name
        const slug = typeof leagueOrName === 'object' && leagueOrName.slug
            ? leagueOrName.slug
            : name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
        if (loc.value === 'en') return name
        return leagueTranslations[slug]?.[loc.value] ?? name
    }

    function formatDate(date, kickOffTime) {
        if (!date) return ''
        // kickOffTime (kick_off_time, dạng "HH:mm:ss" UTC) là giờ đá thật lấy từ
        // Highlightly — ghép vào để tính "x giờ trước" chính xác, thay vì luôn tính
        // từ nửa đêm của match_date (khiến trận vừa đá xong bị ghi nhầm "yesterday"
        // ngay khi vừa qua nửa đêm). Match cũ/nguồn khác không có giờ thì vẫn fallback
        // về match_date như cũ.
        const d = kickOffTime ? new Date(`${String(date).slice(0, 10)}T${kickOffTime}Z`) : new Date(date)
        const diffMs = Date.now() - d
        const diffMins = Math.floor(diffMs / 60000)
        const diffHours = Math.floor(diffMs / 3600000)
        const diffDays = Math.floor(diffMs / 86400000)
        if (diffMins < 60) return diffMins < 5 ? t('date.just_now') : t('date.hours_ago').replace('{n}', diffHours || 1)
        if (diffHours < 24) return t('date.hours_ago').replace('{n}', diffHours)
        if (diffDays === 1) return t('date.yesterday')
        if (diffDays < 7) return t('date.days_ago').replace('{n}', diffDays)
        const localeMap = { en: 'en-GB', es: 'es-ES', pt: 'pt-BR', ar: 'ar-SA', id: 'id-ID', ja: 'ja-JP', fr: 'fr-FR', de: 'de-DE', tr: 'tr-TR', hi: 'hi-IN' }
        return d.toLocaleDateString(localeMap[loc.value] || 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
    }

    function formatRound(round) {
        if (!round) return ''
        if (loc.value === 'en') return round

        // "Regular Season - N" → localized round number
        const regMatch = round.match(/Regular Season\s*-\s*(\d+)/i)
        if (regMatch) return t('round.regular').replace('{n}', regMatch[1])

        // "Matchday N" / "Week N" / "Day N"
        const dayMatch = round.match(/(?:Matchday|Match Day|Week|Day|Gameweek)\s*(\d+)/i)
        if (dayMatch) return t('round.regular').replace('{n}', dayMatch[1])

        // Direct lookup
        const key = ROUND_LOOKUP[round]
        if (key) return t(key)

        return round
    }

    function formatPageLabel(label) {
        if (!label) return ''
        if (loc.value === 'en') return label
        // Laravel paginates with "Previous" and "Next" (with « »)
        return label
            .replace('Previous', t('page.prev'))
            .replace('Next', t('page.next'))
    }

    function localePath(path) {
        if (loc.value === 'en') return path
        return `/${loc.value}${path}`
    }

    return { t, teamName, leagueName, formatDate, formatRound, formatPageLabel, localePath }
}
