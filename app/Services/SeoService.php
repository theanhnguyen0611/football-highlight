<?php

namespace App\Services;

use App\Models\FootballMatch;
use App\Models\League;
use App\Models\Team;
use Carbon\Carbon;

class SeoService
{
    private const LOCALES = ['en', 'es', 'pt', 'ar', 'id', 'ja', 'fr', 'de', 'tr', 'hi'];

    private const HTML_LANG = [
        'en' => 'en', 'es' => 'es', 'pt' => 'pt-BR', 'ar' => 'ar', 'id' => 'id',
        'ja' => 'ja', 'fr' => 'fr', 'de' => 'de', 'tr' => 'tr', 'hi' => 'hi',
    ];

    private const MATCH_HIGHLIGHTS = [
        'en' => 'Highlights', 'es' => 'Resumen',    'pt' => 'Destaques',
        'ar' => 'ملخص',       'id' => 'Highlight',  'ja' => 'ハイライト',
        'fr' => 'Résumé',     'de' => 'Highlights', 'tr' => 'Özeti',
        'hi' => 'हाइलाइट्स',
    ];

    private const MATCH_VS = [
        'en' => 'vs', 'es' => 'vs', 'pt' => 'vs', 'ar' => 'ضد', 'id' => 'vs',
        'ja' => 'vs', 'fr' => 'vs', 'de' => 'vs', 'tr' => 'vs', 'hi' => 'बनाम',
    ];

    private const CTX_SEP = [
        'en' => ', ', 'es' => ', ', 'pt' => ', ', 'ar' => '، ', 'id' => ', ',
        'ja' => '、',  'fr' => ', ', 'de' => ', ', 'tr' => ', ', 'hi' => ', ',
    ];

    private const DATE_LOCALE = [
        'en' => 'en_GB', 'es' => 'es_ES', 'pt' => 'pt_BR', 'ar' => 'ar_SA',
        'id' => 'id_ID', 'ja' => 'ja_JP', 'fr' => 'fr_FR', 'de' => 'de_DE',
        'tr' => 'tr_TR', 'hi' => 'hi_IN',
    ];

    private const LEAGUE_NAMES = [
        'world-cup'              => ['es'=>'Copa del Mundo','pt'=>'Copa do Mundo','ar'=>'كأس العالم','id'=>'Piala Dunia','ja'=>'ワールドカップ','fr'=>'Coupe du Monde','de'=>'Weltmeisterschaft','tr'=>'Dünya Kupası','hi'=>'विश्व कप'],
        'uefa-champions-league'  => ['es'=>'Liga de Campeones','pt'=>'Liga dos Campeões','ar'=>'دوري أبطال أوروبا','id'=>'Liga Champions','ja'=>'チャンピオンズリーグ','fr'=>'Ligue des Champions','de'=>'Champions League','tr'=>'Şampiyonlar Ligi','hi'=>'चैम्पियंस लीग'],
        'premier-league'         => ['ar'=>'الدوري الإنجليزي الممتاز','ja'=>'プレミアリーグ','hi'=>'प्रीमियर लीग','id'=>'Liga Premier','tr'=>'Premier Lig'],
        'la-liga'                => ['ar'=>'الدوري الإسباني','ja'=>'ラ・リーガ','hi'=>'ला लीगा'],
        'bundesliga'             => ['ar'=>'الدوري الألماني','ja'=>'ブンデスリーガ','hi'=>'बुंडेसलीगा'],
        'serie-a'                => ['ar'=>'الدوري الإيطالي','ja'=>'セリエA','hi'=>'सेरी ए'],
        'ligue-1'                => ['ar'=>'الدوري الفرنسي','ja'=>'リーグ・アン','hi'=>'लिग 1'],
        'uefa-europa-league'     => ['es'=>'Liga Europa','pt'=>'Liga Europa','ar'=>'الدوري الأوروبي','id'=>'Liga Europa','ja'=>'ヨーロッパリーグ','fr'=>'Ligue Europa','de'=>'Europa League','tr'=>'Avrupa Ligi','hi'=>'यूरोपा लीग'],
        'copa-america'           => ['ar'=>'كوبا أمريكا','ja'=>'コパ・アメリカ','tr'=>'Copa Amerika','hi'=>'कोपा अमेरिका'],
        'euro-championship'      => ['es'=>'Eurocopa','pt'=>'Eurocopa','ar'=>'بطولة أوروبا','id'=>'Kejuaraan Euro','ja'=>'欧州選手権','fr'=>'Championnat d\'Europe','de'=>'Europameisterschaft','tr'=>'Avrupa Şampiyonası','hi'=>'यूरो चैम्पियनशिप'],
        'friendlies'             => ['es'=>'Amistosos','pt'=>'Amistosos','ar'=>'مباريات ودية','id'=>'Persahabatan','ja'=>'親善試合','fr'=>'Matchs Amicaux','de'=>'Freundschaftsspiele','tr'=>'Hazırlık Maçları','hi'=>'मैत्री मैच'],
        'uefa-nations-league'    => ['es'=>'Liga de Naciones','pt'=>'Liga das Nações','ar'=>'دوري أمم أوروبا','ja'=>'UEFAネーションズリーグ','fr'=>'Ligue des Nations','de'=>'Nations League','tr'=>'Uluslar Ligi','hi'=>'UEFA नेशंस लीग'],
        'fa-cup'                 => ['ar'=>'كأس إنجلترا','ja'=>'FAカップ','hi'=>'FA कप','es'=>'Copa FA','pt'=>'Copa FA','fr'=>'Coupe FA'],
        'copa-del-rey'           => ['ar'=>'كأس الملك','ja'=>'コパ・デル・レイ','fr'=>'Coupe du Roi','pt'=>'Copa del Rei','hi'=>'कोपा देल रे'],
        'super-lig'              => ['ar'=>'الدوري التركي','ja'=>'スーペル・リグ','fr'=>'Süper Lig','hi'=>'सुपर लिग'],
        'major-league-soccer'    => ['es'=>'Liga Mayor de Fútbol','ar'=>'دوري كرة القدم الأمريكي','ja'=>'メジャーリーグサッカー','fr'=>'MLS','hi'=>'मेजर लीग सॉकर'],
    ];

    private const ROUND_LOOKUP = [
        'Group Stage'=>'group_stage','Groups Stage'=>'group_stage','Group stage'=>'group_stage',
        'Round of 16'=>'round_of_16','Round of 32'=>'round_of_32','Round of 64'=>'round_of_64',
        'Quarter-Final'=>'quarter_final','Quarter-Finals'=>'quarter_final','Quarterfinal'=>'quarter_final',
        'Semi-Final'=>'semi_final','Semi-Finals'=>'semi_final','Semifinal'=>'semi_final',
        'Final'=>'final',
        '3rd Place Final'=>'third_place','3rd Place'=>'third_place','Third Place'=>'third_place','Third-Place Final'=>'third_place',
        'Playoff Round'=>'playoff','Play-offs'=>'playoff','Play-off'=>'playoff','Playoffs'=>'playoff','Playoff'=>'playoff',
        'Preliminary Round'=>'preliminary','Preliminary'=>'preliminary',
        'Qualification'=>'qualification','Qualifying Round'=>'qualification','Qualifying Rounds'=>'qualification',
    ];

    private const ROUND_TRANSLATIONS = [
        'group_stage'   => ['es'=>'Fase de Grupos','pt'=>'Fase de Grupos','ar'=>'دور المجموعات','id'=>'Fase Grup','ja'=>'グループステージ','fr'=>'Phase de Groupes','de'=>'Gruppenphase','tr'=>'Grup Aşaması','hi'=>'ग्रुप स्टेज'],
        'round_of_16'   => ['es'=>'Octavos de Final','pt'=>'Oitavas de Final','ar'=>'ثمن النهائي','id'=>'Babak 16 Besar','ja'=>'ラウンド16','fr'=>'Huitièmes de Finale','de'=>'Achtelfinale','tr'=>'Son 16','hi'=>'राउंड ऑफ 16'],
        'round_of_32'   => ['es'=>'Dieciseisavos','pt'=>'Dezesseis Avos','ar'=>'دور الـ32','id'=>'Babak 32 Besar','ja'=>'ラウンド32','fr'=>'Seizièmes de Finale','de'=>'Sechzehntelfinale','tr'=>'Son 32','hi'=>'राउंड ऑफ 32'],
        'round_of_64'   => ['es'=>'Treintaidosavos','pt'=>'Trinta e Dois Avos','ar'=>'دور الـ64','id'=>'Babak 64 Besar','ja'=>'ラウンド64','fr'=>'Trente-deuxièmes de Finale','de'=>'Zweiunddreißigstelfinale','tr'=>'Son 64','hi'=>'राउंड ऑफ 64'],
        'quarter_final' => ['es'=>'Cuartos de Final','pt'=>'Quartas de Final','ar'=>'ربع النهائي','id'=>'Perempat Final','ja'=>'準々決勝','fr'=>'Quart de Finale','de'=>'Viertelfinale','tr'=>'Çeyrek Final','hi'=>'क्वार्टर फाइनल'],
        'semi_final'    => ['es'=>'Semifinal','pt'=>'Semifinal','ar'=>'نصف النهائي','id'=>'Semifinal','ja'=>'準決勝','fr'=>'Demi-Finale','de'=>'Halbfinale','tr'=>'Yarı Final','hi'=>'सेमीफाइनल'],
        'final'         => ['es'=>'Final','pt'=>'Final','ar'=>'النهائي','id'=>'Final','ja'=>'決勝','fr'=>'Finale','de'=>'Finale','tr'=>'Final','hi'=>'फाइनल'],
        'third_place'   => ['es'=>'3er Lugar','pt'=>'3º Lugar','ar'=>'المركز الثالث','id'=>'Perebutan Juara 3','ja'=>'3位決定戦','fr'=>'Troisième Place','de'=>'Spiel um Platz 3','tr'=>'3.lük Maçı','hi'=>'तीसरे स्थान'],
        'playoff'       => ['es'=>'Play-off','pt'=>'Play-off','ar'=>'الملحق','id'=>'Playoff','ja'=>'プレーオフ','fr'=>'Barrage','de'=>'Playoff','tr'=>'Play-off','hi'=>'प्लेऑफ'],
        'preliminary'   => ['es'=>'Ronda Preliminar','pt'=>'Rodada Preliminar','ar'=>'الدور التمهيدي','id'=>'Babak Pendahuluan','ja'=>'予備ラウンド','fr'=>'Tour Préliminaire','de'=>'Vorrunde','tr'=>'Ön Eleme','hi'=>'प्रारंभिक राउंड'],
        'qualification' => ['es'=>'Clasificación','pt'=>'Qualificação','ar'=>'التصفيات','id'=>'Kualifikasi','ja'=>'予選','fr'=>'Qualification','de'=>'Qualifikation','tr'=>'Eleme','hi'=>'क्वालीफिकेशन'],
    ];

    private const TEAM_NAMES = [
        'france'      =>['es'=>'Francia','pt'=>'França','ar'=>'فرنسا','id'=>'Prancis','ja'=>'フランス','fr'=>'France','de'=>'Frankreich','tr'=>'Fransa','hi'=>'फ्रांस'],
        'england'     =>['es'=>'Inglaterra','pt'=>'Inglaterra','ar'=>'إنجلترا','id'=>'Inggris','ja'=>'イングランド','fr'=>'Angleterre','de'=>'England','tr'=>'İngiltere','hi'=>'इंग्लैंड'],
        'spain'       =>['es'=>'España','pt'=>'Espanha','ar'=>'إسبانيا','id'=>'Spanyol','ja'=>'スペイン','fr'=>'Espagne','de'=>'Spanien','tr'=>'İspanya','hi'=>'स्पेन'],
        'argentina'   =>['es'=>'Argentina','pt'=>'Argentina','ar'=>'الأرجنتين','id'=>'Argentina','ja'=>'アルゼンチン','fr'=>'Argentine','de'=>'Argentinien','tr'=>'Arjantin','hi'=>'अर्जेंटीना'],
        'brazil'      =>['es'=>'Brasil','pt'=>'Brasil','ar'=>'البرازيل','id'=>'Brasil','ja'=>'ブラジル','fr'=>'Brésil','de'=>'Brasilien','tr'=>'Brezilya','hi'=>'ब्राज़ील'],
        'germany'     =>['es'=>'Alemania','pt'=>'Alemanha','ar'=>'ألمانيا','id'=>'Jerman','ja'=>'ドイツ','fr'=>'Allemagne','de'=>'Deutschland','tr'=>'Almanya','hi'=>'जर्मनी'],
        'italy'       =>['es'=>'Italia','pt'=>'Itália','ar'=>'إيطاليا','id'=>'Italia','ja'=>'イタリア','fr'=>'Italie','de'=>'Italien','tr'=>'İtalya','hi'=>'इटली'],
        'portugal'    =>['es'=>'Portugal','pt'=>'Portugal','ar'=>'البرتغال','id'=>'Portugal','ja'=>'ポルトガル','fr'=>'Portugal','de'=>'Portugal','tr'=>'Portekiz','hi'=>'पुर्तगाल'],
        'netherlands' =>['es'=>'Países Bajos','pt'=>'Holanda','ar'=>'هولندا','id'=>'Belanda','ja'=>'オランダ','fr'=>'Pays-Bas','de'=>'Niederlande','tr'=>'Hollanda','hi'=>'नीदरलैंड'],
        'belgium'     =>['es'=>'Bélgica','pt'=>'Bélgica','ar'=>'بلجيكا','id'=>'Belgia','ja'=>'ベルギー','fr'=>'Belgique','de'=>'Belgien','tr'=>'Belçika','hi'=>'बेल्जियम'],
        'croatia'     =>['es'=>'Croacia','pt'=>'Croácia','ar'=>'كرواتيا','id'=>'Kroasia','ja'=>'クロアチア','fr'=>'Croatie','de'=>'Kroatien','tr'=>'Hırvatistan','hi'=>'क्रोएशिया'],
        'morocco'     =>['es'=>'Marruecos','pt'=>'Marrocos','ar'=>'المغرب','id'=>'Maroko','ja'=>'モロッコ','fr'=>'Maroc','de'=>'Marokko','tr'=>'Fas','hi'=>'मोरक्को'],
        'japan'       =>['es'=>'Japón','pt'=>'Japão','ar'=>'اليابان','id'=>'Jepang','fr'=>'Japon','de'=>'Japan','tr'=>'Japonya','hi'=>'जापान'],
        'mexico'      =>['es'=>'México','pt'=>'México','ar'=>'المكسيك','id'=>'Meksiko','ja'=>'メキシコ','fr'=>'Mexique','de'=>'Mexiko','tr'=>'Meksika','hi'=>'मेक्सिको'],
        'usa'         =>['es'=>'Estados Unidos','pt'=>'EUA','ar'=>'الولايات المتحدة','id'=>'Amerika Serikat','ja'=>'アメリカ','fr'=>'États-Unis','de'=>'USA','tr'=>'ABD','hi'=>'संयुक्त राज्य'],
        'south-korea' =>['es'=>'Corea del Sur','pt'=>'Coreia do Sul','ar'=>'كوريا الجنوبية','id'=>'Korea Selatan','ja'=>'韓国','fr'=>'Corée du Sud','de'=>'Südkorea','tr'=>'Güney Kore','hi'=>'दक्षिण कोरिया'],
        'australia'   =>['es'=>'Australia','pt'=>'Austrália','ar'=>'أستراليا','id'=>'Australia','ja'=>'オーストラリア','fr'=>'Australie','de'=>'Australien','tr'=>'Avustralya','hi'=>'ऑस्ट्रेलिया'],
        'switzerland' =>['es'=>'Suiza','pt'=>'Suíça','ar'=>'سويسرا','id'=>'Swiss','ja'=>'スイス','fr'=>'Suisse','de'=>'Schweiz','tr'=>'İsviçre','hi'=>'स्विट्जरलैंड'],
        'denmark'     =>['es'=>'Dinamarca','pt'=>'Dinamarca','ar'=>'الدنمارك','id'=>'Denmark','ja'=>'デンマーク','fr'=>'Danemark','de'=>'Dänemark','tr'=>'Danimarka','hi'=>'डेनमार्क'],
        'sweden'      =>['es'=>'Suecia','pt'=>'Suécia','ar'=>'السويد','id'=>'Swedia','ja'=>'スウェーデン','fr'=>'Suède','de'=>'Schweden','tr'=>'İsveç','hi'=>'स्वीडन'],
        'poland'      =>['es'=>'Polonia','pt'=>'Polônia','ar'=>'بولندا','id'=>'Polandia','ja'=>'ポーランド','fr'=>'Pologne','de'=>'Polen','tr'=>'Polonya','hi'=>'पोलैंड'],
        'ukraine'     =>['es'=>'Ucrania','pt'=>'Ucrânia','ar'=>'أوكرانيا','id'=>'Ukraina','ja'=>'ウクライナ','fr'=>'Ukraine','de'=>'Ukraine','tr'=>'Ukrayna','hi'=>'यूक्रेन'],
        'turkey'      =>['es'=>'Turquía','pt'=>'Turquia','ar'=>'تركيا','id'=>'Turki','ja'=>'トルコ','fr'=>'Turquie','de'=>'Türkei','hi'=>'तुर्की'],
        'senegal'     =>['es'=>'Senegal','pt'=>'Senegal','ar'=>'السنغال','id'=>'Senegal','ja'=>'セネガル','fr'=>'Sénégal','de'=>'Senegal','tr'=>'Senegal','hi'=>'सेनेगल'],
        'nigeria'     =>['es'=>'Nigeria','pt'=>'Nigéria','ar'=>'نيجيريا','id'=>'Nigeria','ja'=>'ナイジェリア','fr'=>'Nigéria','de'=>'Nigeria','tr'=>'Nijerya','hi'=>'नाइजीरिया'],
        'egypt'       =>['es'=>'Egipto','pt'=>'Egito','ar'=>'مصر','id'=>'Mesir','ja'=>'エジプト','fr'=>'Égypte','de'=>'Ägypten','tr'=>'Mısır','hi'=>'मिस्र'],
    ];

    private const LEAGUE_TITLE_TPL = [
        'en' => '%s Highlights & Goals %s',
        'es' => '%s Resumen y Goles %s',
        'pt' => '%s Destaques e Gols %s',
        'ar' => 'ملخصات وأهداف %s %s',
        'id' => 'Sorotan & Gol %s %s',
        'ja' => '%s %s ハイライト・ゴール',
        'fr' => '%s Temps Forts et Buts %s',
        'de' => '%s Highlights & Tore %s',
        'tr' => '%s Özetler ve Goller %s',
        'hi' => '%s हाइलाइट्स और गोल %s',
    ];

    private const TEAM_TITLE_TPL = [
        'en' => '%s Highlights & Goals %s',
        'es' => '%s Resumen y Goles %s',
        'pt' => '%s Destaques e Gols %s',
        'ar' => 'ملخصات وأهداف %s %s',
        'id' => 'Sorotan & Gol %s %s',
        'ja' => '%s %s ハイライト・ゴール',
        'fr' => '%s Temps Forts et Buts %s',
        'de' => '%s Highlights & Tore %s',
        'tr' => '%s Özetler ve Goller %s',
        'hi' => '%s हाइलाइट्स और गोल %s',
    ];

    private const HOME_TITLES = [
        'en' => 'Football Highlights, Goals & Match Replays',
        'es' => 'Highlights, Goles y Repeticiones de Fútbol',
        'pt' => 'Destaques, Gols e Repetições de Futebol',
        'ar' => 'ملخصات الأهداف وإعادات مباريات كرة القدم',
        'id' => 'Sorotan, Gol & Tayangan Ulang Sepak Bola',
        'ja' => 'サッカーハイライト・ゴール・試合リプレイ',
        'fr' => 'Temps Forts, Buts et Rediffusions Football',
        'de' => 'Fußball-Highlights, Tore & Spielwiederholungen',
        'tr' => 'Futbol Özetleri, Goller ve Maç Tekrarları',
        'hi' => 'फुटबॉल हाइलाइट्स, गोल और मैच रिप्ले',
    ];

    private const PAGE_LABEL = [
        'en' => 'Page', 'es' => 'Página', 'pt' => 'Página', 'ar' => 'صفحة', 'id' => 'Halaman',
        'ja' => 'ページ', 'fr' => 'Page', 'de' => 'Seite', 'tr' => 'Sayfa', 'hi' => 'पेज',
    ];

    private const HOME_LABEL = [
        'en' => 'Home', 'es' => 'Inicio', 'pt' => 'Início', 'ar' => 'الرئيسية', 'id' => 'Beranda',
        'ja' => 'ホーム', 'fr' => 'Accueil', 'de' => 'Startseite', 'tr' => 'Ana Sayfa', 'hi' => 'होम',
    ];

    private const SITE_DESCS = [
        'en' => 'Watch the latest football highlights, goals and full match replays. Premier League, Champions League, La Liga, Serie A, Bundesliga – all free on BolaReel.',
        'es' => 'Mira los últimos highlights, goles y repeticiones de fútbol. Premier League, Champions League, La Liga, Serie A, Bundesliga – todo gratis en BolaReel.',
        'pt' => 'Assista aos últimos destaques, gols e repetições completas de futebol. Premier League, Champions League, La Liga, Serie A, Bundesliga – grátis no BolaReel.',
        'ar' => 'شاهد أحدث ملخصات كرة القدم والأهداف وإعادات المباريات الكاملة. الدوري الإنجليزي، دوري الأبطال، الليغا، السيريا، البوندسليغا – مجاناً في BolaReel.',
        'id' => 'Tonton sorotan sepak bola terbaru, gol, dan tayangan ulang lengkap. Premier League, Liga Champions, La Liga, Serie A, Bundesliga – gratis di BolaReel.',
        'ja' => '最新のサッカーハイライト・ゴール・フル試合リプレイを無料視聴。プレミアリーグ、チャンピオンズリーグ、ラ・リーガ、セリエA、ブンデスリーガ – すべて無料。',
        'fr' => 'Regardez les derniers temps forts, buts et rediffusions de football. Premier League, Ligue des Champions, La Liga, Serie A, Bundesliga – gratuits sur BolaReel.',
        'de' => 'Die neuesten Fußball-Highlights, Tore und vollständigen Spielwiederholungen. Premier League, Champions League, La Liga, Serie A, Bundesliga – kostenlos auf BolaReel.',
        'tr' => 'En son futbol özetlerini, golleri ve tam maç tekrarlarını izleyin. Premier League, Şampiyonlar Ligi, La Liga, Serie A, Bundesliga – BolaReel\'ta ücretsiz.',
        'hi' => 'नवीनतम फुटबॉल हाइलाइट्स, गोल और पूरे मैच रिप्ले देखें। प्रीमियर लीग, चैम्पियंस लीग, ला लीगा, सीरी ए, बुंडेसलीगा – BolaReel पर निःशुल्क।',
    ];

    private string $locale;
    private string $appUrl;

    public function __construct()
    {
        $this->locale = app()->getLocale();
        $this->appUrl = rtrim(config('app.url'), '/');
    }

    public function home(): array
    {
        $year  = now()->year;
        $path  = '/';
        $base  = self::HOME_TITLES[$this->locale] ?? self::HOME_TITLES['en'];
        $title = "{$base} {$year} | BolaReel";
        $desc  = self::SITE_DESCS[$this->locale] ?? self::SITE_DESCS['en'];

        return [
            'title'       => $title,
            'description' => $desc,
            'canonical'   => $this->localUrl($this->locale, $path),
            'image'       => $this->appUrl . '/images/favicon-512.webp',
            'noindex'     => false,
            'alternates'  => $this->buildAlternates($path),
            'jsonLd'      => [
                '@context'        => 'https://schema.org',
                '@type'           => 'WebSite',
                'name'            => 'BolaReel',
                'url'             => $this->appUrl . '/',
                'description'     => self::SITE_DESCS['en'],
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => [
                        '@type'       => 'EntryPoint',
                        'urlTemplate' => $this->appUrl . '/search?q={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];
    }

    public function matches(int $page = 1): array
    {
        $path  = '/matches';
        $base  = self::HOME_TITLES[$this->locale] ?? self::HOME_TITLES['en'];
        $label = self::PAGE_LABEL[$this->locale] ?? self::PAGE_LABEL['en'];
        $title = $page > 1 ? "{$base} – {$label} {$page} | BolaReel" : "{$base} | BolaReel";
        $desc  = self::SITE_DESCS[$this->locale] ?? self::SITE_DESCS['en'];

        // Canonical tự trỏ theo page (khác pattern home/league) để Google index được cả các trận cũ hơn
        $canonical = $this->localUrl($this->locale, $path) . ($page > 1 ? "?page={$page}" : '');

        return [
            'title'       => $title,
            'description' => $desc,
            'canonical'   => $canonical,
            'image'       => null,
            'noindex'     => false,
            'alternates'  => $this->buildAlternates($path),
            'jsonLd'      => null,
        ];
    }

    public function match(FootballMatch $match): array
    {
        $home   = $this->translateTeam($match->homeTeam);
        $away   = $this->translateTeam($match->awayTeam);
        $league = $this->translateLeague($match->league);
        $round  = $match->round ? $this->formatRound($match->round) : '';
        $date   = $match->match_date
            ? $this->formatDate(Carbon::parse($match->match_date)) : '';
        $time   = $match->kick_off_time
            ? substr((string) $match->kick_off_time, 0, 5) : '';

        $hasScore  = isset($match->home_score) && isset($match->away_score);
        $scoreStr  = $hasScore ? "{$match->home_score}–{$match->away_score}" : null;

        $hl  = self::MATCH_HIGHLIGHTS[$this->locale] ?? self::MATCH_HIGHLIGHTS['en'];
        $vs  = self::MATCH_VS[$this->locale] ?? 'vs';
        $roundSuffix = $round ? " – {$round}" : '';
        $dateSuffix  = $date ? ", {$date}" : '';
        if ($scoreStr) {
            $base  = "{$home} {$scoreStr} {$away} {$hl}{$dateSuffix} – {$league}{$roundSuffix}";
        } else {
            $base  = "{$home} {$vs} {$away} {$hl}{$dateSuffix} – {$league}{$roundSuffix}";
        }
        $title = "{$base} | BolaReel";
        $desc  = $this->matchDesc($home, $away, $league, $round, $date, $time, $scoreStr);
        $path  = "/match/{$match->slug}";

        $thumbnail = $match->thumbnail_url ?? $match->league?->logo_url;

        return [
            'title'       => $title,
            'description' => $desc,
            'canonical'   => $this->localUrl($this->locale, $path),
            'image'       => $thumbnail,
            'ogType'      => 'video.other',
            'noindex'     => false,
            'alternates'  => $this->buildAlternates($path),
            'jsonLd'      => [
                $this->matchJsonLd($match, $base, $desc, $thumbnail, $date),
                $this->breadcrumbLd(array_filter([
                    ['name' => self::HOME_LABEL[$this->locale] ?? self::HOME_LABEL['en'], 'url' => $this->appUrl . '/'],
                    $league ? ['name' => $league, 'url' => $this->appUrl . "/league/{$match->league?->slug}"] : null,
                    ['name' => $base, 'url' => $this->localUrl($this->locale, $path)],
                ])),
            ],
        ];
    }

    public function league(League $league): array
    {
        $path   = "/league/{$league->slug}";
        $season = $this->currentSeason();
        $tpl    = self::LEAGUE_TITLE_TPL[$this->locale] ?? self::LEAGUE_TITLE_TPL['en'];
        $title  = sprintf($tpl, $league->name, $season) . ' | BolaReel';
        $desc   = $this->leagueDesc($league->name, $season);

        return [
            'title'       => $title,
            'description' => $desc,
            'canonical'   => $this->localUrl($this->locale, $path),
            'image'       => $league->logo_url,
            'noindex'     => false,
            'alternates'  => $this->buildAlternates($path),
            'jsonLd'      => [
                [
                    '@context'    => 'https://schema.org',
                    '@type'       => 'SportsOrganization',
                    'name'        => $league->name,
                    'url'         => $this->localUrl($this->locale, $path),
                    'logo'        => $league->logo_url,
                    'description' => $this->leagueDesc($league->name, $season),
                ],
                $this->breadcrumbLd([
                    ['name' => self::HOME_LABEL[$this->locale] ?? self::HOME_LABEL['en'], 'url' => $this->appUrl . '/'],
                    ['name' => $league->name, 'url' => $this->localUrl($this->locale, $path)],
                ]),
            ],
        ];
    }

    public function team(Team $team): array
    {
        $path   = "/team/{$team->slug}";
        $season = $this->currentSeason();
        $tpl    = self::TEAM_TITLE_TPL[$this->locale] ?? self::TEAM_TITLE_TPL['en'];
        $title  = sprintf($tpl, $team->name, $season) . ' | BolaReel';
        $desc   = $this->teamDesc($team->name, $season);

        return [
            'title'       => $title,
            'description' => $desc,
            'canonical'   => $this->localUrl($this->locale, $path),
            'image'       => $team->logo_url,
            'noindex'     => false,
            'alternates'  => $this->buildAlternates($path),
            'jsonLd'      => [
                [
                    '@context'    => 'https://schema.org',
                    '@type'       => 'SportsTeam',
                    'name'        => $team->name,
                    'url'         => $this->localUrl($this->locale, $path),
                    'logo'        => $team->logo_url,
                    'description' => $this->teamDesc($team->name, $season),
                ],
                $this->breadcrumbLd([
                    ['name' => self::HOME_LABEL[$this->locale] ?? self::HOME_LABEL['en'], 'url' => $this->appUrl . '/'],
                    ['name' => $team->name, 'url' => $this->localUrl($this->locale, $path)],
                ]),
            ],
        ];
    }

    public function search(string $q): array
    {
        $path = '/search';

        if ($q) {
            $title = "\"{$q}\" Highlights – Watch Goals & Match Replays | BolaReel";
            $desc  = "Watch football highlights for \"{$q}\". Browse goals, full match replays and results – free on BolaReel.";
        } else {
            $title = 'Search Football Highlights & Match Replays | BolaReel';
            $desc  = self::SITE_DESCS[$this->locale] ?? self::SITE_DESCS['en'];
        }

        return [
            'title'       => $title,
            'description' => $desc,
            'canonical'   => $this->localUrl($this->locale, $path) . ($q ? '?q=' . urlencode($q) : ''),
            'image'       => null,
            'noindex'     => empty($q),
            'alternates'  => $this->buildAlternates($path),
            'jsonLd'      => null,
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function matchJsonLd(FootballMatch $match, string $name, string $desc, ?string $thumbnail, string $date): array
    {
        $ld = [
            '@context'     => 'https://schema.org',
            '@type'        => 'VideoObject',
            'name'         => $name,
            'description'  => $desc,
            'uploadDate'   => $match->match_date ?? now()->toDateString(),
            'publisher'    => [
                '@type' => 'Organization',
                'name'  => 'BolaReel',
                'url'   => $this->appUrl,
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => $this->appUrl . '/favicon.ico',
                ],
            ],
        ];

        if ($thumbnail) {
            $ld['thumbnailUrl'] = $thumbnail;
        }

        // Use our own embed page for Google Video rich results
        $ld['embedUrl'] = $this->appUrl . "/embed/match/{$match->slug}";

        // Không set contentUrl: đây là link CDN trực tiếp (m3u8), lộ ra là bị
        // hotlink/tải thẳng. contentUrl không bắt buộc theo schema.org —
        // embedUrl ở trên (trỏ về trang embed của mình) đã đủ cho rich results.
        $video = $match->videos?->first();
        if ($video?->duration_seconds) {
            $ld['duration'] = 'PT' . (int) $video->duration_seconds . 'S';
        }

        $ld['isFamilyFriendly'] = true;
        $ld['inLanguage']       = self::HTML_LANG[$this->locale] ?? 'en';

        return $ld;
    }

    private function breadcrumbLd(array $items): array
    {
        $items = array_values($items);

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => array_map(fn ($it, $i) => [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $it['name'],
                'item'     => $it['url'],
            ], $items, array_keys($items)),
        ];
    }

    private function buildAlternates(string $path): array
    {
        $alts = array_map(fn ($l) => [
            'hreflang' => self::HTML_LANG[$l],
            'href'     => $this->localUrl($l, $path),
        ], self::LOCALES);

        $alts[] = ['hreflang' => 'x-default', 'href' => $this->appUrl . $path];
        return $alts;
    }

    private function localUrl(string $locale, string $path): string
    {
        return $locale === 'en'
            ? $this->appUrl . $path
            : $this->appUrl . '/' . $locale . $path;
    }

    private function currentSeason(): string
    {
        $year  = (int) now()->format('Y');
        $month = (int) now()->format('n');
        // Football season typically spans two calendar years (Aug–May)
        return $month >= 7 ? "{$year}/" . ($year + 1) : ($year - 1) . "/{$year}";
    }

    private function matchDesc(
        string $home,
        string $away,
        string $league,
        string $round,
        string $date,
        string $time,
        ?string $score
    ): string {
        $sep  = self::CTX_SEP[$this->locale] ?? ', ';
        $ctx  = trim(implode($sep, array_filter([$league, $round])));
        $when = trim(implode(' ', array_filter([$date, $time])));

        return match ($this->locale) {
            'es' => $this->matchDescEs($home, $away, $ctx, $when, $score),
            'pt' => $this->matchDescPt($home, $away, $ctx, $when, $score),
            'ar' => $this->matchDescAr($home, $away, $ctx, $when, $score),
            'id' => $this->matchDescId($home, $away, $ctx, $when, $score),
            'ja' => $this->matchDescJa($home, $away, $ctx, $when, $score),
            'fr' => $this->matchDescFr($home, $away, $ctx, $when, $score),
            'de' => $this->matchDescDe($home, $away, $ctx, $when, $score),
            'tr' => $this->matchDescTr($home, $away, $ctx, $when, $score),
            'hi' => $this->matchDescHi($home, $away, $ctx, $when, $score),
            default => $this->matchDescEn($home, $away, $ctx, $when, $score),
        };
    }

    private function matchDescEn(string $h, string $a, string $ctx, string $when, ?string $score): string
    {
        $scorePart = $score ? " ({$score})" : '';
        $ctxPart   = $ctx ? " – {$ctx}" : '';
        $whenPart  = $when ? ", {$when}" : '';
        return "Watch {$h} vs {$a}{$scorePart} match highlights{$ctxPart}{$whenPart}. Goals, key moments and full match replay – free on BolaReel.";
    }

    private function matchDescEs(string $h, string $a, string $ctx, string $when, ?string $score): string
    {
        $scorePart = $score ? " ({$score})" : '';
        $ctxPart   = $ctx ? " – {$ctx}" : '';
        $whenPart  = $when ? ", {$when}" : '';
        return "Mira el resumen de {$h} vs {$a}{$scorePart}{$ctxPart}{$whenPart}. Goles, momentos clave y repetición completa – gratis en BolaReel.";
    }

    private function matchDescPt(string $h, string $a, string $ctx, string $when, ?string $score): string
    {
        $scorePart = $score ? " ({$score})" : '';
        $ctxPart   = $ctx ? " – {$ctx}" : '';
        $whenPart  = $when ? ", {$when}" : '';
        return "Assista ao resumo de {$h} vs {$a}{$scorePart}{$ctxPart}{$whenPart}. Gols, melhores momentos e replay completo – grátis no BolaReel.";
    }

    private function matchDescAr(string $h, string $a, string $ctx, string $when, ?string $score): string
    {
        $scorePart = $score ? " ({$score})" : '';
        $ctxPart   = $ctx ? " – {$ctx}" : '';
        $whenPart  = $when ? "، {$when}" : '';
        return "شاهد ملخص {$h} ضد {$a}{$scorePart}{$ctxPart}{$whenPart}. الأهداف والأوقات الحاسمة وإعادة المباراة الكاملة – مجاناً في BolaReel.";
    }

    private function matchDescId(string $h, string $a, string $ctx, string $when, ?string $score): string
    {
        $scorePart = $score ? " ({$score})" : '';
        $ctxPart   = $ctx ? " – {$ctx}" : '';
        $whenPart  = $when ? ", {$when}" : '';
        return "Tonton highlight {$h} vs {$a}{$scorePart}{$ctxPart}{$whenPart}. Gol, momen kunci, dan replay lengkap – gratis di BolaReel.";
    }

    private function matchDescJa(string $h, string $a, string $ctx, string $when, ?string $score): string
    {
        $scorePart = $score ? "（{$score}）" : '';
        $ctxPart   = $ctx ? "、{$ctx}" : '';
        $whenPart  = $when ? "（{$when}）" : '';
        return "{$h} vs {$a}{$scorePart}{$whenPart}{$ctxPart}のハイライト。ゴール・名場面・フル試合リプレイをBolaReelで無料視聴。";
    }

    private function matchDescFr(string $h, string $a, string $ctx, string $when, ?string $score): string
    {
        $scorePart = $score ? " ({$score})" : '';
        $ctxPart   = $ctx ? " – {$ctx}" : '';
        $whenPart  = $when ? ", {$when}" : '';
        return "Regardez le résumé de {$h} vs {$a}{$scorePart}{$ctxPart}{$whenPart}. Buts, temps forts et rediffusion complète – gratuits sur BolaReel.";
    }

    private function matchDescDe(string $h, string $a, string $ctx, string $when, ?string $score): string
    {
        $scorePart = $score ? " ({$score})" : '';
        $ctxPart   = $ctx ? " – {$ctx}" : '';
        $whenPart  = $when ? ", {$when}" : '';
        return "Highlights von {$h} vs {$a}{$scorePart}{$ctxPart}{$whenPart}. Tore, Schlüsselszenen und vollständige Spielwiederholung – kostenlos auf BolaReel.";
    }

    private function matchDescTr(string $h, string $a, string $ctx, string $when, ?string $score): string
    {
        $scorePart = $score ? " ({$score})" : '';
        $ctxPart   = $ctx ? " – {$ctx}" : '';
        $whenPart  = $when ? ", {$when}" : '';
        return "{$h} vs {$a}{$scorePart}{$ctxPart}{$whenPart} maç özeti. Goller, kritik anlar ve tam maç tekrarı – BolaReel'ta ücretsiz.";
    }

    private function matchDescHi(string $h, string $a, string $ctx, string $when, ?string $score): string
    {
        $scorePart = $score ? " ({$score})" : '';
        $ctxPart   = $ctx ? " – {$ctx}" : '';
        $whenPart  = $when ? ", {$when}" : '';
        return "{$h} बनाम {$a}{$scorePart}{$ctxPart}{$whenPart} हाइलाइट्स देखें। गोल, मुख्य क्षण और पूरा मैच रिप्ले – BolaReel पर निःशुल्क।";
    }

    private function translateTeam(?object $team): string
    {
        if (!$team) return '';
        if ($this->locale === 'en') return $team->name ?? '';
        // DB translations first
        if ($team->translations) {
            $tr = $team->translations->firstWhere('locale', $this->locale);
            if ($tr?->name) return $tr->name;
        }
        $slug = $team->slug ?? '';
        return self::TEAM_NAMES[$slug][$this->locale] ?? $team->name ?? '';
    }

    private function translateLeague(?object $league): string
    {
        if (!$league) return '';
        if ($this->locale === 'en') return $league->name ?? '';
        $slug = $league->slug ?? '';
        return self::LEAGUE_NAMES[$slug][$this->locale] ?? $league->name ?? '';
    }

    private function formatDate(Carbon $date): string
    {
        if ($this->locale === 'ja') return $date->format('Y年n月j日');
        if ($this->locale === 'ar') {
            $months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
            return $date->day . ' ' . $months[$date->month - 1] . ' ' . $date->year;
        }
        if ($this->locale === 'de') return $date->format('d. M Y');
        if ($this->locale === 'fr') {
            $months = ['jan.','fév.','mars','avr.','mai','juin','juil.','août','sep.','oct.','nov.','déc.'];
            return $date->day . ' ' . $months[$date->month - 1] . ' ' . $date->year;
        }
        return $date->format('d M Y');
    }

    private function formatRound(string $round): string
    {
        if (preg_match('/Regular Season\s*-\s*(\d+)/i', $round, $m)) {
            return $this->locale === 'ja' ? '第' . $m[1] . '節' : 'Matchday ' . $m[1];
        }
        if (preg_match('/(?:Matchday|Match Day|Week|Day|Gameweek)\s*(\d+)/i', $round, $m)) {
            return $this->locale === 'ja' ? '第' . $m[1] . '節' : 'Matchday ' . $m[1];
        }
        if ($this->locale !== 'en' && isset(self::ROUND_LOOKUP[$round])) {
            $key = self::ROUND_LOOKUP[$round];
            return self::ROUND_TRANSLATIONS[$key][$this->locale] ?? $round;
        }
        return $round;
    }

    private function leagueDesc(string $name, string $season): string
    {
        return match ($this->locale) {
            'es' => "Todos los highlights de {$name} {$season}: goles, repeticiones completas y resultados. Actualizados tras cada jornada – gratis en BolaReel.",
            'pt' => "Todos os highlights de {$name} {$season}: gols, replays completos e resultados. Atualizados após cada rodada – grátis no BolaReel.",
            'ar' => "جميع ملخصات {$name} {$season}: أهداف، إعادات كاملة ونتائج. تُحدَّث بعد كل جولة – مجاناً في BolaReel.",
            'id' => "Semua highlight {$name} {$season}: gol, replay lengkap, dan hasil pertandingan. Diperbarui setiap pekan – gratis di BolaReel.",
            'ja' => "{$name} {$season}の全ハイライト：ゴール、フル試合リプレイ、試合結果。毎節更新・無料視聴 – BolaReel。",
            'fr' => "Tous les highlights de {$name} {$season} : buts, replays complets et résultats. Mis à jour après chaque journée – gratuits sur BolaReel.",
            'de' => "Alle Highlights der {$name} {$season}: Tore, vollständige Spielwiederholungen und Ergebnisse. Nach jedem Spieltag aktualisiert – kostenlos auf BolaReel.",
            'tr' => "{$name} {$season} tüm maç özetleri: goller, tam tekrarlar ve sonuçlar. Her haftadan sonra güncellenir – BolaReel'ta ücretsiz.",
            'hi' => "{$name} {$season} के सभी हाइलाइट्स: गोल, पूरे मैच रिप्ले और परिणाम। हर मैचडे के बाद अपडेट – BolaReel पर निःशुल्क।",
            default => "Watch all {$name} {$season} highlights: goals, full match replays and results. Updated after every matchday – free on BolaReel.",
        };
    }

    private function teamDesc(string $name, string $season): string
    {
        return match ($this->locale) {
            'es' => "Highlights de {$name} {$season}: todos los goles y repeticiones de sus partidos. Sigue cada encuentro gratis en BolaReel.",
            'pt' => "Highlights de {$name} {$season}: todos os gols e replays dos seus jogos. Acompanhe cada partida grátis no BolaReel.",
            'ar' => "ملخصات {$name} {$season}: جميع الأهداف وإعادات المباريات. تابع كل مباراة مجاناً في BolaReel.",
            'id' => "Highlight {$name} {$season}: semua gol dan replay pertandingan mereka. Ikuti setiap laga gratis di BolaReel.",
            'ja' => "{$name} {$season}のハイライト：全ゴールと試合リプレイ。BolaReelで毎試合無料視聴。",
            'fr' => "Highlights de {$name} {$season} : tous les buts et replays de leurs matchs. Suivez chaque rencontre gratuitement sur BolaReel.",
            'de' => "Highlights von {$name} {$season}: alle Tore und Spielwiederholungen. Jedes Spiel kostenlos verfolgen auf BolaReel.",
            'tr' => "{$name} {$season} highlights: tüm goller ve maç tekrarları. Her maçı BolaReel'ta ücretsiz takip edin.",
            'hi' => "{$name} {$season} हाइलाइट्स: सभी गोल और मैच रिप्ले। BolaReel पर हर मैच निःशुल्क देखें।",
            default => "Watch {$name} {$season} highlights: all goals and match replays. Follow every game free on BolaReel.",
        };
    }
}
