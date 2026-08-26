<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamTranslationSeeder extends Seeder
{
    public function run(): void
    {
        // [team_name_in_db => [locale => translated_name]]
        // National teams: dịch đủ tất cả 10 ngôn ngữ
        // Clubs: chủ yếu ja/ar/hi (tên CLB ít thay đổi), thêm khi cần
        $translations = [

            // ── NATIONAL TEAMS ──────────────────────────────────────────────
            'Afghanistan' => [
                'es'=>'Afganistán','pt'=>'Afeganistão','fr'=>'Afghanistan','de'=>'Afghanistan',
                'tr'=>'Afganistan','id'=>'Afghanistan','ar'=>'أفغانستان','ja'=>'アフガニスタン','hi'=>'अफ़गानिस्तान',
            ],
            'Albania' => [
                'es'=>'Albania','pt'=>'Albânia','fr'=>'Albanie','de'=>'Albanien',
                'tr'=>'Arnavutluk','id'=>'Albania','ar'=>'ألبانيا','ja'=>'アルバニア','hi'=>'अल्बानिया',
            ],
            'Algeria' => [
                'es'=>'Argelia','pt'=>'Argélia','fr'=>'Algérie','de'=>'Algerien',
                'tr'=>'Cezayir','id'=>'Aljazair','ar'=>'الجزائر','ja'=>'アルジェリア','hi'=>'अल्जीरिया',
            ],
            'Angola' => [
                'es'=>'Angola','pt'=>'Angola','fr'=>'Angola','de'=>'Angola',
                'tr'=>'Angola','id'=>'Angola','ar'=>'أنغولا','ja'=>'アンゴラ','hi'=>'अंगोला',
            ],
            'Argentina' => [
                'es'=>'Argentina','pt'=>'Argentina','fr'=>'Argentine','de'=>'Argentinien',
                'tr'=>'Arjantin','id'=>'Argentina','ar'=>'الأرجنتين','ja'=>'アルゼンチン','hi'=>'अर्जेंटीना',
            ],
            'Armenia' => [
                'es'=>'Armenia','pt'=>'Armênia','fr'=>'Arménie','de'=>'Armenien',
                'tr'=>'Ermenistan','id'=>'Armenia','ar'=>'أرمينيا','ja'=>'アルメニア','hi'=>'आर्मेनिया',
            ],
            'Australia' => [
                'es'=>'Australia','pt'=>'Austrália','fr'=>'Australie','de'=>'Australien',
                'tr'=>'Avustralya','id'=>'Australia','ar'=>'أستراليا','ja'=>'オーストラリア','hi'=>'ऑस्ट्रेलिया',
            ],
            'Austria' => [
                'es'=>'Austria','pt'=>'Áustria','fr'=>'Autriche','de'=>'Österreich',
                'tr'=>'Avusturya','id'=>'Austria','ar'=>'النمسا','ja'=>'オーストリア','hi'=>'ऑस्ट्रिया',
            ],
            'Azerbaijan' => [
                'es'=>'Azerbaiyán','pt'=>'Azerbaijão','fr'=>'Azerbaïdjan','de'=>'Aserbaidschan',
                'tr'=>'Azerbaycan','id'=>'Azerbaijan','ar'=>'أذربيجان','ja'=>'アゼルバイジャン','hi'=>'अज़रबैजान',
            ],
            'Bahrain' => [
                'es'=>'Baréin','pt'=>'Bahrein','fr'=>'Bahreïn','de'=>'Bahrain',
                'tr'=>'Bahreyn','id'=>'Bahrain','ar'=>'البحرين','ja'=>'バーレーン','hi'=>'बहरीन',
            ],
            'Bangladesh' => [
                'es'=>'Bangladés','pt'=>'Bangladesh','fr'=>'Bangladesh','de'=>'Bangladesch',
                'tr'=>'Bangladeş','id'=>'Bangladesh','ar'=>'بنغلاديش','ja'=>'バングラデシュ','hi'=>'बांग्लादेश',
            ],
            'Belarus' => [
                'es'=>'Bielorrusia','pt'=>'Bielorrússia','fr'=>'Biélorussie','de'=>'Weißrussland',
                'tr'=>'Beyaz Rusya','id'=>'Belarus','ar'=>'بيلاروسيا','ja'=>'ベラルーシ','hi'=>'बेलारूस',
            ],
            'Belgium' => [
                'es'=>'Bélgica','pt'=>'Bélgica','fr'=>'Belgique','de'=>'Belgien',
                'tr'=>'Belçika','id'=>'Belgia','ar'=>'بلجيكا','ja'=>'ベルギー','hi'=>'बेल्जियम',
            ],
            'Bolivia' => [
                'es'=>'Bolivia','pt'=>'Bolívia','fr'=>'Bolivie','de'=>'Bolivien',
                'tr'=>'Bolivya','id'=>'Bolivia','ar'=>'بوليفيا','ja'=>'ボリビア','hi'=>'बोलिविया',
            ],
            'Bosnia & Herzegovina' => [
                'es'=>'Bosnia','pt'=>'Bósnia','fr'=>'Bosnie','de'=>'Bosnien',
                'tr'=>'Bosna','id'=>'Bosnia','ar'=>'البوسنة','ja'=>'ボスニア','hi'=>'बोस्निया',
            ],
            'Bosnia & Herzegovina' => [
                'es'=>'Bosnia y Herzegovina','pt'=>'Bósnia e Herzegovina','fr'=>'Bosnie-Herzégovine','de'=>'Bosnien und Herzegowina',
                'tr'=>'Bosna-Hersek','id'=>'Bosnia dan Herzegovina','ar'=>'البوسنة والهرسك','ja'=>'ボスニア・ヘルツェゴビナ','hi'=>'बोस्निया और हर्ज़ेगोविना',
            ],
            'Brazil' => [
                'es'=>'Brasil','pt'=>'Brasil','fr'=>'Brésil','de'=>'Brasilien',
                'tr'=>'Brezilya','id'=>'Brasil','ar'=>'البرازيل','ja'=>'ブラジル','hi'=>'ब्राज़ील',
            ],
            'Bulgaria' => [
                'es'=>'Bulgaria','pt'=>'Bulgária','fr'=>'Bulgarie','de'=>'Bulgarien',
                'tr'=>'Bulgaristan','id'=>'Bulgaria','ar'=>'بلغاريا','ja'=>'ブルガリア','hi'=>'बुल्गारिया',
            ],
            'Burkina Faso' => [
                'es'=>'Burkina Faso','pt'=>'Burkina Faso','fr'=>'Burkina Faso','de'=>'Burkina Faso',
                'tr'=>'Burkina Faso','id'=>'Burkina Faso','ar'=>'بوركينا فاسو','ja'=>'ブルキナファソ','hi'=>'बुर्किना फ़ासो',
            ],
            'Cambodia' => [
                'es'=>'Camboya','pt'=>'Camboja','fr'=>'Cambodge','de'=>'Kambodscha',
                'tr'=>'Kamboçya','id'=>'Kamboja','ar'=>'كمبوديا','ja'=>'カンボジア','hi'=>'कंबोडिया',
            ],
            'Canada' => [
                'es'=>'Canadá','pt'=>'Canadá','fr'=>'Canada','de'=>'Kanada',
                'tr'=>'Kanada','id'=>'Kanada','ar'=>'كندا','ja'=>'カナダ','hi'=>'कनाडा',
            ],
            'Cape Verde' => [
                'es'=>'Cabo Verde','pt'=>'Cabo Verde','fr'=>'Cap-Vert','de'=>'Kap Verde',
                'tr'=>'Yeşil Burun','id'=>'Tanjung Verde','ar'=>'الرأس الأخضر','ja'=>'カーボベルデ','hi'=>'केप वर्डे',
            ],
            'Chile' => [
                'es'=>'Chile','pt'=>'Chile','fr'=>'Chili','de'=>'Chile',
                'tr'=>'Şili','id'=>'Chile','ar'=>'تشيلي','ja'=>'チリ','hi'=>'चिली',
            ],
            'Colombia' => [
                'es'=>'Colombia','pt'=>'Colômbia','fr'=>'Colombie','de'=>'Kolumbien',
                'tr'=>'Kolombiya','id'=>'Kolombia','ar'=>'كولومبيا','ja'=>'コロンビア','hi'=>'कोलंबिया',
            ],
            'Costa Rica' => [
                'es'=>'Costa Rica','pt'=>'Costa Rica','fr'=>'Costa Rica','de'=>'Costa Rica',
                'tr'=>'Kosta Rika','id'=>'Kosta Rika','ar'=>'كوستاريكا','ja'=>'コスタリカ','hi'=>'कोस्टा रिका',
            ],
            'Croatia' => [
                'es'=>'Croacia','pt'=>'Croácia','fr'=>'Croatie','de'=>'Kroatien',
                'tr'=>'Hırvatistan','id'=>'Kroasia','ar'=>'كرواتيا','ja'=>'クロアチア','hi'=>'क्रोएशिया',
            ],
            'Czech Republic' => [
                'es'=>'República Checa','pt'=>'República Tcheca','fr'=>'République tchèque','de'=>'Tschechien',
                'tr'=>'Çek Cumhuriyeti','id'=>'Republik Ceko','ar'=>'جمهورية التشيك','ja'=>'チェコ','hi'=>'चेक गणराज्य',
            ],
            'Denmark' => [
                'es'=>'Dinamarca','pt'=>'Dinamarca','fr'=>'Danemark','de'=>'Dänemark',
                'tr'=>'Danimarka','id'=>'Denmark','ar'=>'الدنمارك','ja'=>'デンマーク','hi'=>'डेनमार्क',
            ],
            'Ecuador' => [
                'es'=>'Ecuador','pt'=>'Equador','fr'=>'Équateur','de'=>'Ecuador',
                'tr'=>'Ekvador','id'=>'Ekuador','ar'=>'الإكوادور','ja'=>'エクアドル','hi'=>'इक्वाडोर',
            ],
            'Egypt' => [
                'es'=>'Egipto','pt'=>'Egito','fr'=>'Égypte','de'=>'Ägypten',
                'tr'=>'Mısır','id'=>'Mesir','ar'=>'مصر','ja'=>'エジプト','hi'=>'मिस्र',
            ],
            'El Salvador' => [
                'es'=>'El Salvador','pt'=>'El Salvador','fr'=>'El Salvador','de'=>'El Salvador',
                'tr'=>'El Salvador','id'=>'El Salvador','ar'=>'السلفادور','ja'=>'エルサルバドル','hi'=>'एल साल्वाडोर',
            ],
            'England' => [
                'es'=>'Inglaterra','pt'=>'Inglaterra','fr'=>'Angleterre','de'=>'England',
                'tr'=>'İngiltere','id'=>'Inggris','ar'=>'إنجلترا','ja'=>'イングランド','hi'=>'इंग्लैंड',
            ],
            'Estonia' => [
                'es'=>'Estonia','pt'=>'Estônia','fr'=>'Estonie','de'=>'Estland',
                'tr'=>'Estonya','id'=>'Estonia','ar'=>'إستونيا','ja'=>'エストニア','hi'=>'एस्टोनिया',
            ],
            'Finland' => [
                'es'=>'Finlandia','pt'=>'Finlândia','fr'=>'Finlande','de'=>'Finnland',
                'tr'=>'Finlandiya','id'=>'Finlandia','ar'=>'فنلندا','ja'=>'フィンランド','hi'=>'फ़िनलैंड',
            ],
            'France' => [
                'es'=>'Francia','pt'=>'França','fr'=>'France','de'=>'Frankreich',
                'tr'=>'Fransa','id'=>'Prancis','ar'=>'فرنسا','ja'=>'フランス','hi'=>'फ़्रांस',
            ],
            'Georgia' => [
                'es'=>'Georgia','pt'=>'Geórgia','fr'=>'Géorgie','de'=>'Georgien',
                'tr'=>'Gürcistan','id'=>'Georgia','ar'=>'جورجيا','ja'=>'ジョージア','hi'=>'जॉर्जिया',
            ],
            'Germany' => [
                'es'=>'Alemania','pt'=>'Alemanha','fr'=>'Allemagne','de'=>'Deutschland',
                'tr'=>'Almanya','id'=>'Jerman','ar'=>'ألمانيا','ja'=>'ドイツ','hi'=>'जर्मनी',
            ],
            'Ghana' => [
                'es'=>'Ghana','pt'=>'Gana','fr'=>'Ghana','de'=>'Ghana',
                'tr'=>'Gana','id'=>'Ghana','ar'=>'غانا','ja'=>'ガーナ','hi'=>'घाना',
            ],
            'Greece' => [
                'es'=>'Grecia','pt'=>'Grécia','fr'=>'Grèce','de'=>'Griechenland',
                'tr'=>'Yunanistan','id'=>'Yunani','ar'=>'اليونان','ja'=>'ギリシャ','hi'=>'ग्रीस',
            ],
            'Guatemala' => [
                'es'=>'Guatemala','pt'=>'Guatemala','fr'=>'Guatemala','de'=>'Guatemala',
                'tr'=>'Guatemala','id'=>'Guatemala','ar'=>'غواتيمالا','ja'=>'グアテマラ','hi'=>'ग्वाटेमाला',
            ],
            'Guinea' => [
                'es'=>'Guinea','pt'=>'Guiné','fr'=>'Guinée','de'=>'Guinea',
                'tr'=>'Gine','id'=>'Guinea','ar'=>'غينيا','ja'=>'ギニア','hi'=>'गिनी',
            ],
            'Haiti' => [
                'es'=>'Haití','pt'=>'Haiti','fr'=>'Haïti','de'=>'Haiti',
                'tr'=>'Haiti','id'=>'Haiti','ar'=>'هايتي','ja'=>'ハイチ','hi'=>'हैती',
            ],
            'Honduras' => [
                'es'=>'Honduras','pt'=>'Honduras','fr'=>'Honduras','de'=>'Honduras',
                'tr'=>'Honduras','id'=>'Honduras','ar'=>'هندوراس','ja'=>'ホンジュラス','hi'=>'होंडुरास',
            ],
            'Hungary' => [
                'es'=>'Hungría','pt'=>'Hungria','fr'=>'Hongrie','de'=>'Ungarn',
                'tr'=>'Macaristan','id'=>'Hungaria','ar'=>'المجر','ja'=>'ハンガリー','hi'=>'हंगरी',
            ],
            'Iceland' => [
                'es'=>'Islandia','pt'=>'Islândia','fr'=>'Islande','de'=>'Island',
                'tr'=>'İzlanda','id'=>'Islandia','ar'=>'آيسلندا','ja'=>'アイスランド','hi'=>'आइसलैंड',
            ],
            'India' => [
                'es'=>'India','pt'=>'Índia','fr'=>'Inde','de'=>'Indien',
                'tr'=>'Hindistan','id'=>'India','ar'=>'الهند','ja'=>'インド','hi'=>'भारत',
            ],
            'Iran' => [
                'es'=>'Irán','pt'=>'Irã','fr'=>'Iran','de'=>'Iran',
                'tr'=>'İran','id'=>'Iran','ar'=>'إيران','ja'=>'イラン','hi'=>'ईरान',
            ],
            'Iraq' => [
                'es'=>'Irak','pt'=>'Iraque','fr'=>'Irak','de'=>'Irak',
                'tr'=>'Irak','id'=>'Irak','ar'=>'العراق','ja'=>'イラク','hi'=>'इराक',
            ],
            'Republic of Ireland' => [
                'es'=>'Irlanda','pt'=>'Irlanda','fr'=>'Irlande','de'=>'Irland',
                'tr'=>'İrlanda','id'=>'Irlandia','ar'=>'إيرلندا','ja'=>'アイルランド','hi'=>'आयरलैंड',
            ],
            'Israel' => [
                'es'=>'Israel','pt'=>'Israel','fr'=>'Israël','de'=>'Israel',
                'tr'=>'İsrail','id'=>'Israel','ar'=>'إسرائيل','ja'=>'イスラエル','hi'=>'इज़राइल',
            ],
            'Italy' => [
                'es'=>'Italia','pt'=>'Itália','fr'=>'Italie','de'=>'Italien',
                'tr'=>'İtalya','id'=>'Italia','ar'=>'إيطاليا','ja'=>'イタリア','hi'=>'इटली',
            ],
            'Ivory Coast' => [
                'es'=>'Costa de Marfil','pt'=>'Costa do Marfim','fr'=>"Côte d'Ivoire",'de'=>'Elfenbeinküste',
                'tr'=>'Fildişi Sahili','id'=>'Pantai Gading','ar'=>'كوت ديفوار','ja'=>'コートジボワール','hi'=>'आइवरी कोस्ट',
            ],
            'Jamaica' => [
                'es'=>'Jamaica','pt'=>'Jamaica','fr'=>'Jamaïque','de'=>'Jamaika',
                'tr'=>'Jamaika','id'=>'Jamaika','ar'=>'جامايكا','ja'=>'ジャマイカ','hi'=>'जमैका',
            ],
            'Japan' => [
                'es'=>'Japón','pt'=>'Japão','fr'=>'Japon','de'=>'Japan',
                'tr'=>'Japonya','id'=>'Jepang','ar'=>'اليابان','ja'=>'日本','hi'=>'जापान',
            ],
            'Jordan' => [
                'es'=>'Jordania','pt'=>'Jordânia','fr'=>'Jordanie','de'=>'Jordanien',
                'tr'=>'Ürdün','id'=>'Yordania','ar'=>'الأردن','ja'=>'ヨルダン','hi'=>'जॉर्डन',
            ],
            'Kazakhstan' => [
                'es'=>'Kazajistán','pt'=>'Cazaquistão','fr'=>'Kazakhstan','de'=>'Kasachstan',
                'tr'=>'Kazakistan','id'=>'Kazakhstan','ar'=>'كازاخستان','ja'=>'カザフスタン','hi'=>'कज़ाकिस्तान',
            ],
            'Kenya' => [
                'es'=>'Kenia','pt'=>'Quênia','fr'=>'Kenya','de'=>'Kenia',
                'tr'=>'Kenya','id'=>'Kenya','ar'=>'كينيا','ja'=>'ケニア','hi'=>'केन्या',
            ],
            'Kosovo National Team' => [
                'es'=>'Kosovo','pt'=>'Kosovo','fr'=>'Kosovo','de'=>'Kosovo',
                'tr'=>'Kosova','id'=>'Kosovo','ar'=>'كوسوفو','ja'=>'コソボ','hi'=>'कोसोवो',
            ],
            'Latvia' => [
                'es'=>'Letonia','pt'=>'Letônia','fr'=>'Lettonie','de'=>'Lettland',
                'tr'=>'Letonya','id'=>'Latvia','ar'=>'لاتفيا','ja'=>'ラトビア','hi'=>'लातविया',
            ],
            'Lebanon' => [
                'es'=>'Líbano','pt'=>'Líbano','fr'=>'Liban','de'=>'Libanon',
                'tr'=>'Lübnan','id'=>'Lebanon','ar'=>'لبنان','ja'=>'レバノン','hi'=>'लेबनान',
            ],
            'Lithuania' => [
                'es'=>'Lituania','pt'=>'Lituânia','fr'=>'Lituanie','de'=>'Litauen',
                'tr'=>'Litvanya','id'=>'Lituania','ar'=>'ليتوانيا','ja'=>'リトアニア','hi'=>'लिथुआनिया',
            ],
            'Luxembourg' => [
                'es'=>'Luxemburgo','pt'=>'Luxemburgo','fr'=>'Luxembourg','de'=>'Luxemburg',
                'tr'=>'Lüksemburg','id'=>'Luksemburg','ar'=>'لوكسمبورغ','ja'=>'ルクセンブルク','hi'=>'लक्ज़मबर्ग',
            ],
            'Mexico' => [
                'es'=>'México','pt'=>'México','fr'=>'Mexique','de'=>'Mexiko',
                'tr'=>'Meksika','id'=>'Meksiko','ar'=>'المكسيك','ja'=>'メキシコ','hi'=>'मेक्सिको',
            ],
            'Moldova' => [
                'es'=>'Moldavia','pt'=>'Moldávia','fr'=>'Moldavie','de'=>'Moldau',
                'tr'=>'Moldova','id'=>'Moldova','ar'=>'مولدوفا','ja'=>'モルドバ','hi'=>'मोल्डोवा',
            ],
            'Montenegro' => [
                'es'=>'Montenegro','pt'=>'Montenegro','fr'=>'Monténégro','de'=>'Montenegro',
                'tr'=>'Karadağ','id'=>'Montenegro','ar'=>'الجبل الأسود','ja'=>'モンテネグロ','hi'=>'मोंटेनेग्रो',
            ],
            'Morocco' => [
                'es'=>'Marruecos','pt'=>'Marrocos','fr'=>'Maroc','de'=>'Marokko',
                'tr'=>'Fas','id'=>'Maroko','ar'=>'المغرب','ja'=>'モロッコ','hi'=>'मोरक्को',
            ],
            'Myanmar' => [
                'es'=>'Myanmar','pt'=>'Mianmar','fr'=>'Myanmar','de'=>'Myanmar',
                'tr'=>'Myanmar','id'=>'Myanmar','ar'=>'ميانمار','ja'=>'ミャンマー','hi'=>'म्यांमार',
            ],
            'Netherlands' => [
                'es'=>'Países Bajos','pt'=>'Holanda','fr'=>'Pays-Bas','de'=>'Niederlande',
                'tr'=>'Hollanda','id'=>'Belanda','ar'=>'هولندا','ja'=>'オランダ','hi'=>'नीदरलैंड',
            ],
            'New Zealand' => [
                'es'=>'Nueva Zelanda','pt'=>'Nova Zelândia','fr'=>'Nouvelle-Zélande','de'=>'Neuseeland',
                'tr'=>'Yeni Zelanda','id'=>'Selandia Baru','ar'=>'نيوزيلندا','ja'=>'ニュージーランド','hi'=>'न्यूज़ीलैंड',
            ],
            'Nigeria' => [
                'es'=>'Nigeria','pt'=>'Nigéria','fr'=>'Nigeria','de'=>'Nigeria',
                'tr'=>'Nijerya','id'=>'Nigeria','ar'=>'نيجيريا','ja'=>'ナイジェリア','hi'=>'नाइजीरिया',
            ],
            'North Macedonia' => [
                'es'=>'Macedonia del Norte','pt'=>'Macedônia do Norte','fr'=>'Macédoine du Nord','de'=>'Nordmazedonien',
                'tr'=>'Kuzey Makedonya','id'=>'Makedonia Utara','ar'=>'مقدونيا الشمالية','ja'=>'北マケドニア','hi'=>'उत्तर मैसेडोनिया',
            ],
            'Northern Ireland' => [
                'es'=>'Irlanda del Norte','pt'=>'Irlanda do Norte','fr'=>'Irlande du Nord','de'=>'Nordirland',
                'tr'=>'Kuzey İrlanda','id'=>'Irlandia Utara','ar'=>'أيرلندا الشمالية','ja'=>'北アイルランド','hi'=>'उत्तरी आयरलैंड',
            ],
            'Norway' => [
                'es'=>'Noruega','pt'=>'Noruega','fr'=>'Norvège','de'=>'Norwegen',
                'tr'=>'Norveç','id'=>'Norwegia','ar'=>'النرويج','ja'=>'ノルウェー','hi'=>'नॉर्वे',
            ],
            'Panama' => [
                'es'=>'Panamá','pt'=>'Panamá','fr'=>'Panama','de'=>'Panama',
                'tr'=>'Panama','id'=>'Panama','ar'=>'بنما','ja'=>'パナマ','hi'=>'पनामा',
            ],
            'Paraguay' => [
                'es'=>'Paraguay','pt'=>'Paraguai','fr'=>'Paraguay','de'=>'Paraguay',
                'tr'=>'Paraguay','id'=>'Paraguay','ar'=>'باراغواي','ja'=>'パラグアイ','hi'=>'पैराग्वे',
            ],
            'Poland' => [
                'es'=>'Polonia','pt'=>'Polônia','fr'=>'Pologne','de'=>'Polen',
                'tr'=>'Polonya','id'=>'Polandia','ar'=>'بولندا','ja'=>'ポーランド','hi'=>'पोलैंड',
            ],
            'Portugal' => [
                'es'=>'Portugal','pt'=>'Portugal','fr'=>'Portugal','de'=>'Portugal',
                'tr'=>'Portekiz','id'=>'Portugal','ar'=>'البرتغال','ja'=>'ポルトガル','hi'=>'पुर्तगाल',
            ],
            'Qatar' => [
                'es'=>'Catar','pt'=>'Catar','fr'=>'Qatar','de'=>'Katar',
                'tr'=>'Katar','id'=>'Qatar','ar'=>'قطر','ja'=>'カタール','hi'=>'क़तर',
            ],
            'Romania' => [
                'es'=>'Rumanía','pt'=>'Romênia','fr'=>'Roumanie','de'=>'Rumänien',
                'tr'=>'Romanya','id'=>'Rumania','ar'=>'رومانيا','ja'=>'ルーマニア','hi'=>'रोमानिया',
            ],
            'Russia' => [
                'es'=>'Rusia','pt'=>'Rússia','fr'=>'Russie','de'=>'Russland',
                'tr'=>'Rusya','id'=>'Rusia','ar'=>'روسيا','ja'=>'ロシア','hi'=>'रूस',
            ],
            'Saudi Arabia' => [
                'es'=>'Arabia Saudita','pt'=>'Arábia Saudita','fr'=>'Arabie Saoudite','de'=>'Saudi-Arabien',
                'tr'=>'Suudi Arabistan','id'=>'Arab Saudi','ar'=>'المملكة العربية السعودية','ja'=>'サウジアラビア','hi'=>'सऊदी अरब',
            ],
            'Scotland' => [
                'es'=>'Escocia','pt'=>'Escócia','fr'=>'Écosse','de'=>'Schottland',
                'tr'=>'İskoçya','id'=>'Skotlandia','ar'=>'اسكتلندا','ja'=>'スコットランド','hi'=>'स्कॉटलैंड',
            ],
            'Senegal' => [
                'es'=>'Senegal','pt'=>'Senegal','fr'=>'Sénégal','de'=>'Senegal',
                'tr'=>'Senegal','id'=>'Senegal','ar'=>'السنغال','ja'=>'セネガル','hi'=>'सेनेगल',
            ],
            'Serbia' => [
                'es'=>'Serbia','pt'=>'Sérvia','fr'=>'Serbie','de'=>'Serbien',
                'tr'=>'Sırbistan','id'=>'Serbia','ar'=>'صربيا','ja'=>'セルビア','hi'=>'सर्बिया',
            ],
            'Slovakia' => [
                'es'=>'Eslovaquia','pt'=>'Eslováquia','fr'=>'Slovaquie','de'=>'Slowakei',
                'tr'=>'Slovakya','id'=>'Slovakia','ar'=>'سلوفاكيا','ja'=>'スロバキア','hi'=>'स्लोवाकिया',
            ],
            'Slovenia' => [
                'es'=>'Eslovenia','pt'=>'Eslovênia','fr'=>'Slovénie','de'=>'Slowenien',
                'tr'=>'Slovenya','id'=>'Slovenia','ar'=>'سلوفينيا','ja'=>'スロベニア','hi'=>'स्लोवेनिया',
            ],
            'South Africa' => [
                'es'=>'Sudáfrica','pt'=>'África do Sul','fr'=>'Afrique du Sud','de'=>'Südafrika',
                'tr'=>'Güney Afrika','id'=>'Afrika Selatan','ar'=>'جنوب أفريقيا','ja'=>'南アフリカ','hi'=>'दक्षिण अफ़्रीका',
            ],
            'South Korea' => [
                'es'=>'Corea del Sur','pt'=>'Coreia do Sul','fr'=>'Corée du Sud','de'=>'Südkorea',
                'tr'=>'Güney Kore','id'=>'Korea Selatan','ar'=>'كوريا الجنوبية','ja'=>'韓国','hi'=>'दक्षिण कोरिया',
            ],
            'Spain' => [
                'es'=>'España','pt'=>'Espanha','fr'=>'Espagne','de'=>'Spanien',
                'tr'=>'İspanya','id'=>'Spanyol','ar'=>'إسبانيا','ja'=>'スペイン','hi'=>'स्पेन',
            ],
            'Sweden' => [
                'es'=>'Suecia','pt'=>'Suécia','fr'=>'Suède','de'=>'Schweden',
                'tr'=>'İsveç','id'=>'Swedia','ar'=>'السويد','ja'=>'スウェーデン','hi'=>'स्वीडन',
            ],
            'Switzerland' => [
                'es'=>'Suiza','pt'=>'Suíça','fr'=>'Suisse','de'=>'Schweiz',
                'tr'=>'İsviçre','id'=>'Swiss','ar'=>'سويسرا','ja'=>'スイス','hi'=>'स्विट्ज़रलैंड',
            ],
            'Syria' => [
                'es'=>'Siria','pt'=>'Síria','fr'=>'Syrie','de'=>'Syrien',
                'tr'=>'Suriye','id'=>'Suriah','ar'=>'سوريا','ja'=>'シリア','hi'=>'सीरिया',
            ],
            'Tunisia' => [
                'es'=>'Túnez','pt'=>'Tunísia','fr'=>'Tunisie','de'=>'Tunesien',
                'tr'=>'Tunus','id'=>'Tunisia','ar'=>'تونس','ja'=>'チュニジア','hi'=>'ट्यूनीशिया',
            ],
            'Turkey' => [
                'es'=>'Turquía','pt'=>'Turquia','fr'=>'Turquie','de'=>'Türkei',
                'tr'=>'Türkiye','id'=>'Turki','ar'=>'تركيا','ja'=>'トルコ','hi'=>'तुर्की',
            ],
            'Ukraine' => [
                'es'=>'Ucrania','pt'=>'Ucrânia','fr'=>'Ukraine','de'=>'Ukraine',
                'tr'=>'Ukrayna','id'=>'Ukraina','ar'=>'أوكرانيا','ja'=>'ウクライナ','hi'=>'यूक्रेन',
            ],
            'Uruguay' => [
                'es'=>'Uruguay','pt'=>'Uruguai','fr'=>'Uruguay','de'=>'Uruguay',
                'tr'=>'Uruguay','id'=>'Uruguay','ar'=>'أوروغواي','ja'=>'ウルグアイ','hi'=>'उरुग्वे',
            ],
            'USA' => [
                'es'=>'Estados Unidos','pt'=>'Estados Unidos','fr'=>'États-Unis','de'=>'USA',
                'tr'=>'ABD','id'=>'Amerika Serikat','ar'=>'الولايات المتحدة','ja'=>'アメリカ','hi'=>'अमेरिका',
            ],
            'Uzbekistan' => [
                'es'=>'Uzbekistán','pt'=>'Uzbequistão','fr'=>'Ouzbékistan','de'=>'Usbekistan',
                'tr'=>'Özbekistan','id'=>'Uzbekistan','ar'=>'أوزبكستان','ja'=>'ウズベキスタン','hi'=>'उज़्बेकिस्तान',
            ],
            'Venezuela' => [
                'es'=>'Venezuela','pt'=>'Venezuela','fr'=>'Venezuela','de'=>'Venezuela',
                'tr'=>'Venezuela','id'=>'Venezuela','ar'=>'فنزويلا','ja'=>'ベネズエラ','hi'=>'वेनेजुएला',
            ],
            'Vietnam' => [
                'es'=>'Vietnam','pt'=>'Vietnã','fr'=>'Vietnam','de'=>'Vietnam',
                'tr'=>'Vietnam','id'=>'Vietnam','ar'=>'فيتنام','ja'=>'ベトナム','hi'=>'वियतनाम',
            ],
            'Wales' => [
                'es'=>'Gales','pt'=>'País de Gales','fr'=>'Pays de Galles','de'=>'Wales',
                'tr'=>'Galler','id'=>'Wales','ar'=>'ويلز','ja'=>'ウェールズ','hi'=>'वेल्स',
            ],

            // ── TOP CLUBS (ja/ar/hi chủ yếu, thêm khi tên thật sự khác) ──
            'Arsenal'                   => ['ja'=>'アーセナル','ar'=>'أرسنال','hi'=>'आर्सेनल'],
            'Aston Villa'               => ['ja'=>'アストン・ヴィラ','ar'=>'أستون فيلا','hi'=>'एस्टन विला'],
            'Chelsea'                   => ['ja'=>'チェルシー','ar'=>'تشيلسي','hi'=>'चेल्सी'],
            'Everton'                   => ['ja'=>'エバートン','ar'=>'إيفرتون','hi'=>'एवर्टन'],
            'Liverpool'                 => ['ja'=>'リバプール','ar'=>'ليفربول','hi'=>'लिवरपूल'],
            'Manchester City'           => ['ja'=>'マンチェスター・シティ','ar'=>'مانشستر سيتي','hi'=>'मैनचेस्टर सिटी'],
            'Manchester United'         => ['ja'=>'マンチェスター・ユナイテッド','ar'=>'مانشستر يونايتد','hi'=>'मैनचेस्टर यूनाइटेड'],
            'Newcastle United'          => ['ja'=>'ニューカッスル','ar'=>'نيوكاسل','hi'=>'न्यूकैसल'],
            'Tottenham'                 => ['ja'=>'トッテナム','ar'=>'توتنهام','hi'=>'टोटेनहम'],
            'Coventry'                  => ['ja'=>'コベントリー','ar'=>'كوفنتري','hi'=>'कोवेंट्री'],
            'West Ham'                  => ['ja'=>'ウェストハム','ar'=>'ويست هام','hi'=>'वेस्ट हैम'],
            'Barcelona'                 => ['ja'=>'バルセロナ','ar'=>'برشلونة','hi'=>'बार्सिलोना'],
            'Real Madrid'               => ['ja'=>'レアル・マドリード','ar'=>'ريال مدريد','hi'=>'रियल मैड्रिड'],
            'Atletico Madrid'           => ['ja'=>'アトレティコ・マドリード','ar'=>'أتليتيكو مدريد','hi'=>'अटलेटिको मैड्रिड','es'=>'Atlético de Madrid'],
            'Espanyol'                  => ['ja'=>'エスパニョール','ar'=>'إسبانيول','hi'=>'एस्पान्योल'],
            'Real Betis'                => ['ja'=>'レアル・ベティス','ar'=>'ريال بيتيس','hi'=>'रियल बेटिस'],
            'Real Sociedad'             => ['ja'=>'レアル・ソシエダ','ar'=>'ريال سوسيداد','hi'=>'रियल सोसिएदाद'],
            'Sevilla FC'                => ['ja'=>'セビージャ','ar'=>'إشبيلية','hi'=>'सेविला'],
            'Valencia'                  => ['ja'=>'バレンシア','ar'=>'فالنسيا','hi'=>'वेलेंसिया'],
            'Villarreal'                => ['ja'=>'ビジャレアル','ar'=>'فياريال','hi'=>'विलारियल'],
            'Girona'                    => ['ja'=>'ジローナ','ar'=>'جيرونا','hi'=>'जिरोना'],
            'Alavés'                    => ['ja'=>'アラベス','ar'=>'ألافيس','hi'=>'अलावेस'],
            'Mallorca'                  => ['ja'=>'マジョルカ','ar'=>'مايوركا','hi'=>'मैयोर्का'],
            'Rayo Vallecano'            => ['ja'=>'ラジョ・バジェカーノ','ar'=>'رايو فاليكانو','hi'=>'रायो वाएयेकानो'],
            'Bayern Munich'             => ['ja'=>'バイエルン・ミュンヘン','ar'=>'بايرن ميونخ','hi'=>'बायर्न म्यूनिख'],
            'Borussia Dortmund'         => ['ja'=>'ボルシア・ドルトムント','ar'=>'بوروسيا دورتموند','hi'=>'बोरुसिया डॉर्टमुंड'],
            'Bayer Leverkusen'          => ['ja'=>'バイヤー・レバークーゼン','ar'=>'باير ليفركوزن','hi'=>'बायर लेवरकुजेन'],
            'RB Leipzig'                => ['ja'=>'RBライプツィヒ','ar'=>'آر بي لايبزيغ','hi'=>'आरबी लाइपज़िग'],
            'Eintracht Frankfurt'       => ['ja'=>'アイントラハト・フランクフルト','ar'=>'آينتراخت فرانكفورت','hi'=>'आइंट्राख्ट फ्रैंकफर्ट'],
            'VfB Stuttgart'             => ['ja'=>'シュトゥットガルト','ar'=>'شتوتغارت','hi'=>'VfB स्टटगार्ट'],
            'Borussia Mönchengladbach'  => ['ja'=>'ボルシア・メンヒェングラートバッハ','ar'=>'بوروسيا مونشنغلادباخ','hi'=>'बोरुसिया मोंशेनग्लादबाख'],
            'VfL Wolfsburg'             => ['ja'=>'ヴォルフスブルク','ar'=>'فولفسبورغ','hi'=>'वोल्फ़्सबर्ग'],
            'SC Freiburg'               => ['ja'=>'フライブルク','ar'=>'فرايبورغ','hi'=>'फ्रीबर्ग'],
            '1899 Hoffenheim'           => ['ja'=>'ホッフェンハイム','ar'=>'هوفنهايم','hi'=>'हॉफ़ेनहाइम'],
            'Werder Bremen'             => ['ja'=>'ヴェルダー・ブレーメン','ar'=>'فيردر بريمن','hi'=>'वेर्डर ब्रेमन'],
            'Hamburger SV'              => ['ja'=>'ハンブルガーSV','ar'=>'هامبورغ','hi'=>'हैम्बर्गर SV'],
            'Union Berlin'              => ['ja'=>'ウニオン・ベルリン','ar'=>'يونيون برلين','hi'=>'यूनियन बर्लिन'],
            'Paris Saint Germain'       => ['ja'=>'パリ・サンジェルマン','ar'=>'باريس سان جيرمان','hi'=>'पेरिस सेंट-जर्मेन','fr'=>'Paris Saint-Germain'],
            'Marseille'                 => ['ja'=>'マルセイユ','ar'=>'مرسيليا','hi'=>'मार्सेय'],
            'Lyon'                      => ['ja'=>'リヨン','ar'=>'ليون','hi'=>'ल्योन'],
            'Monaco'                    => ['ja'=>'モナコ','ar'=>'موناكو','hi'=>'मोनाको'],
            'Lille'                     => ['ja'=>'リール','ar'=>'ليل','hi'=>'लिले'],
            'Nice'                      => ['ja'=>'ニース','ar'=>'نيس','hi'=>'नीस'],
            'Lens'                      => ['ja'=>'ランス','ar'=>'لانس','hi'=>'लेंस'],
            'Rennes FC'                 => ['ja'=>'レンヌ','ar'=>'رين','hi'=>'रेन'],
            'Toulouse'                  => ['ja'=>'トゥールーズ','ar'=>'تولوز','hi'=>'तूलूज़'],
            'Strasbourg'                => ['ja'=>'ストラスブール','ar'=>'ستراسبورغ','hi'=>'स्ट्रासबर्ग'],
            'Stade Brestois 29'         => ['ja'=>'ブレスト','ar'=>'برست','hi'=>'ब्रेस्ट'],
            'Auxerre'                   => ['ja'=>'オセール','ar'=>'أوكسير','hi'=>'ऑक्सेर'],
            'Juventus'                  => ['ja'=>'ユベントス','ar'=>'يوفنتوس','hi'=>'युवेंटस'],
            'Inter Milan'               => ['ja'=>'インテル・ミラノ','ar'=>'إنتر ميلان','hi'=>'इंटर मिलान'],
            'AC Milan'                  => ['ja'=>'ACミラン','ar'=>'ميلان','hi'=>'एसी मिलान'],
            'Napoli'                    => ['ja'=>'ナポリ','ar'=>'نابولي','hi'=>'नापोली'],
            'AS Roma'                   => ['ja'=>'ローマ','ar'=>'روما','hi'=>'रोमा'],
            'Lazio'                     => ['ja'=>'ラツィオ','ar'=>'لاتسيو','hi'=>'लाज़ियो'],
            'Atalanta'                  => ['ja'=>'アタランタ','ar'=>'أتالانتا','hi'=>'अटलांटा'],
            'Fiorentina'                => ['ja'=>'フィオレンティーナ','ar'=>'فيورنتينا','hi'=>'फियोरेंटीना'],
            'Ajax'                      => ['ja'=>'アヤックス','ar'=>'أياكس','hi'=>'अजाक्स'],
            'FC Porto'                  => ['ja'=>'ポルト','ar'=>'بورتو','hi'=>'पोर्टो'],
            'Benfica'                   => ['ja'=>'ベンフィカ','ar'=>'بنفيكا','hi'=>'बेनफिका'],
            'Sporting CP'               => ['ja'=>'スポルティング','ar'=>'سبورتينغ','hi'=>'स्पोर्टिंग CP'],
            'Galatasaray'               => ['ja'=>'ガラタサライ','ar'=>'غلطة سراي','hi'=>'गलातासाराय'],
            'Fenerbahce'                => ['ja'=>'フェネルバフチェ','ar'=>'فنربخشة','hi'=>'फेनरबाहसे'],
            'Besiktas'                  => ['ja'=>'ベシクタシュ','ar'=>'بشيكتاش','hi'=>'बेशिकताश'],
            'Flamengo'                  => ['ja'=>'フラメンゴ','ar'=>'فلامنغو','hi'=>'फ्लामेंगो'],
            'Palmeiras'                 => ['ja'=>'パルメイラス','ar'=>'بالميراس','hi'=>'पालमेइरास'],
            'Fluminense'                => ['ja'=>'フルミネンセ','ar'=>'فلومينينسي','hi'=>'फ्लुमिनेंसे'],
            'Corinthians'               => ['ja'=>'コリンチャンス','ar'=>'كورينثيانز','hi'=>'कोरिंथियंस'],
            'São Paulo FC'              => ['ja'=>'サンパウロFC','ar'=>'ساو باولو','hi'=>'साओ पाउलो FC'],
            'Atletico-MG'               => ['ja'=>'アトレティコ・ミネイロ','ar'=>'أتليتيكو مينيرو','hi'=>'एटलेटिको मिनेइरो'],
            'Botafogo'                  => ['ja'=>'ボタフォゴ','ar'=>'بوتافوغو','hi'=>'बोटाफोगो'],
            'Vasco DA Gama'             => ['ja'=>'ヴァスコ・ダ・ガマ','ar'=>'فاسكو دا غاما','hi'=>'वास्को डा गामा'],
            'Internacional'             => ['ja'=>'インテルナシオナル','ar'=>'إنترناسيونال','hi'=>'इंटरनेशिओनाल'],
            'Gremio'                    => ['ja'=>'グレミオ','ar'=>'غريميو','hi'=>'ग्रेमियो'],
            'RB Bragantino'             => ['ja'=>'ブラガンチーノ','ar'=>'براغانتينو','hi'=>'ब्रागांटिनो'],
            'Inter Miami'               => ['ja'=>'インテル・マイアミ','ar'=>'إنتر ميامي','hi'=>'इंटर मियामी'],
            'Los Angeles Galaxy'        => ['ja'=>'LAギャラクシー','ar'=>'لوس أنجلوس غالاكسي','hi'=>'LA गैलेक्सी'],
            'Seattle Sounders'          => ['ja'=>'シアトル・サウンダーズ','ar'=>'سياتل ساوندرز','hi'=>'सिएटल साउंडर्स'],
            'New York City FC'          => ['ja'=>'ニューヨーク・シティFC','ar'=>'نيويورك سيتي','hi'=>'न्यूयॉर्क सिटी FC'],
            'New York Red Bulls'        => ['ja'=>'ニューヨーク・レッドブルズ','ar'=>'نيويورك ريد بولز','hi'=>'न्यूयॉर्क रेड बुल्स'],
            'Toronto FC'                => ['ja'=>'トロントFC','ar'=>'تورنتو','hi'=>'टोरंटो FC'],
            'Atlanta United FC'         => ['ja'=>'アトランタ・ユナイテッド','ar'=>'أتلانتا يونايتد','hi'=>'अटलांटा यूनाइटेड'],
            'Chicago Fire'              => ['ja'=>'シカゴ・ファイアー','ar'=>'شيكاغو فاير','hi'=>'शिकागो फायर'],
            'Sporting Kansas City'      => ['ja'=>'スポーティングKC','ar'=>'سبورتينغ كانساس سيتي','hi'=>'स्पोर्टिंग कैनसस सिटी'],
            'DC United'                 => ['ja'=>'DCユナイテッド','ar'=>'دي سي يونايتد','hi'=>'DC यूनाइटेड'],
            'Montréal Impact'           => ['ja'=>'モントリオール','ar'=>'مونتريال','hi'=>'मॉन्ट्रियल'],
            'Real Salt Lake'            => ['ja'=>'リアル・ソルトレイク','ar'=>'ريال سولت ليك','hi'=>'रियल साल्ट लेक'],
            'Al-Ittihad FC'             => ['ja'=>'アル・イティハド','ar'=>'الاتحاد','hi'=>'अल-इत्तिहाद'],
        ];

        $locales  = ['es','pt','ar','id','ja','fr','de','tr','hi'];
        $inserted = 0;
        $skipped  = 0;

        foreach ($translations as $teamName => $localeMap) {
            $team = Team::where('name', $teamName)->first();
            if (!$team) {
                $this->command->warn("  Team not found: {$teamName}");
                continue;
            }

            foreach ($localeMap as $locale => $translatedName) {
                if (!in_array($locale, $locales)) continue;

                $exists = DB::table('team_translations')
                    ->where('team_id', $team->id)
                    ->where('locale', $locale)
                    ->exists();

                if ($exists) { $skipped++; continue; }

                DB::table('team_translations')->insert([
                    'team_id'    => $team->id,
                    'locale'     => $locale,
                    'name'       => $translatedName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $inserted++;
            }
        }

        $this->command->info("Team translations: {$inserted} inserted, {$skipped} skipped.");
    }
}
