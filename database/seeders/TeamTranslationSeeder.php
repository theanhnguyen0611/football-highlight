<?php
namespace Database\Seeders;

use App\Models\Team;
use App\Models\TeamTranslation;
use Illuminate\Database\Seeder;

class TeamTranslationSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            'france'           => ['vi' => 'Pháp',          'es' => 'Francia',          'ar' => 'فرنسا'],
            'england'          => ['vi' => 'Anh',            'es' => 'Inglaterra',        'ar' => 'إنجلترا'],
            'germany'          => ['vi' => 'Đức',            'es' => 'Alemania',          'ar' => 'ألمانيا'],
            'spain'            => ['vi' => 'Tây Ban Nha',    'es' => 'España',            'ar' => 'إسبانيا'],
            'italy'            => ['vi' => 'Ý',              'es' => 'Italia',            'ar' => 'إيطاليا'],
            'brazil'           => ['vi' => 'Brazil',         'es' => 'Brasil',            'ar' => 'البرازيل'],
            'argentina'        => ['vi' => 'Argentina',      'es' => 'Argentina',         'ar' => 'الأرجنتين'],
            'portugal'         => ['vi' => 'Bồ Đào Nha',     'es' => 'Portugal',          'ar' => 'البرتغال'],
            'netherlands'      => ['vi' => 'Hà Lan',         'es' => 'Países Bajos',      'ar' => 'هولندا'],
            'belgium'          => ['vi' => 'Bỉ',             'es' => 'Bélgica',           'ar' => 'بلجيكا'],
            'croatia'          => ['vi' => 'Croatia',        'es' => 'Croacia',           'ar' => 'كرواتيا'],
            'morocco'          => ['vi' => 'Ma Rốc',         'es' => 'Marruecos',         'ar' => 'المغرب'],
            'usa'              => ['vi' => 'Mỹ',             'es' => 'Estados Unidos',    'ar' => 'الولايات المتحدة'],
            'norway'           => ['vi' => 'Na Uy',          'es' => 'Noruega',           'ar' => 'النرويج'],
            'sweden'           => ['vi' => 'Thụy Điển',      'es' => 'Suecia',            'ar' => 'السويد'],
            'denmark'          => ['vi' => 'Đan Mạch',       'es' => 'Dinamarca',         'ar' => 'الدنمارك'],
            'japan'            => ['vi' => 'Nhật Bản',       'es' => 'Japón',             'ar' => 'اليابان'],
            'south-korea'      => ['vi' => 'Hàn Quốc',       'es' => 'Corea del Sur',     'ar' => 'كوريا الجنوبية'],
            'nigeria'          => ['vi' => 'Nigeria',        'es' => 'Nigeria',           'ar' => 'نيجيريا'],
            'senegal'          => ['vi' => 'Senegal',        'es' => 'Senegal',           'ar' => 'السنغال'],
            'egypt'            => ['vi' => 'Ai Cập',         'es' => 'Egipto',            'ar' => 'مصر'],
            'mexico'           => ['vi' => 'Mexico',         'es' => 'México',            'ar' => 'المكسيك'],
            'colombia'         => ['vi' => 'Colombia',       'es' => 'Colombia',          'ar' => 'كولومبيا'],
            'chile'            => ['vi' => 'Chile',          'es' => 'Chile',             'ar' => 'تشيلي'],
            'uruguay'          => ['vi' => 'Uruguay',        'es' => 'Uruguay',           'ar' => 'أوروغواي'],
            'poland'           => ['vi' => 'Ba Lan',         'es' => 'Polonia',           'ar' => 'بولندا'],
            'ukraine'          => ['vi' => 'Ukraine',        'es' => 'Ucrania',           'ar' => 'أوكرانيا'],
            'switzerland'      => ['vi' => 'Thụy Sĩ',        'es' => 'Suiza',             'ar' => 'سويسرا'],
            'austria'          => ['vi' => 'Áo',             'es' => 'Austria',           'ar' => 'النمسا'],
            'turkey'           => ['vi' => 'Thổ Nhĩ Kỳ',     'es' => 'Turquía',           'ar' => 'تركيا'],
            'northern-ireland' => ['vi' => 'Bắc Ireland',    'es' => 'Irlanda del Norte', 'ar' => 'أيرلندا الشمالية'],
            'scotland'         => ['vi' => 'Scotland',       'es' => 'Escocia',           'ar' => 'اسكتلندا'],
            'wales'            => ['vi' => 'Wales',          'es' => 'Gales',             'ar' => 'ويلز'],
            'greece'           => ['vi' => 'Hy Lạp',         'es' => 'Grecia',            'ar' => 'اليونان'],
            'ivory-coast'      => ['vi' => 'Bờ Biển Ngà',    'es' => 'Costa de Marfil',   'ar' => 'ساحل العاج'],
            'honduras'         => ['vi' => 'Honduras',       'es' => 'Honduras',          'ar' => 'هندوراس'],
            'panama'           => ['vi' => 'Panama',         'es' => 'Panamá',            'ar' => 'بنما'],
            'new-zealand'      => ['vi' => 'New Zealand',    'es' => 'Nueva Zelanda',     'ar' => 'نيوزيلندا'],
            'iraq'             => ['vi' => 'Iraq',           'es' => 'Irak',              'ar' => 'العراق'],
            'luxembourg'       => ['vi' => 'Luxembourg',     'es' => 'Luxemburgo',        'ar' => 'لوكسمبورغ'],
            'algeria'          => ['vi' => 'Algeria',        'es' => 'Argelia',           'ar' => 'الجزائر'],
            'saudi-arabia'     => ['vi' => 'Ả Rập Xê Út',    'es' => 'Arabia Saudita',    'ar' => 'المملكة العربية السعودية'],
            'iran'             => ['vi' => 'Iran',           'es' => 'Irán',              'ar' => 'إيران'],
            'qatar'            => ['vi' => 'Qatar',          'es' => 'Catar',             'ar' => 'قطر'],
            'australia'        => ['vi' => 'Úc',             'es' => 'Australia',         'ar' => 'أستراليا'],
            'china'            => ['vi' => 'Trung Quốc',     'es' => 'China',             'ar' => 'الصين'],
            'uzbekistan'       => ['vi' => 'Uzbekistan',     'es' => 'Uzbekistán',        'ar' => 'أوزبكستان'],
            'finland'          => ['vi' => 'Phần Lan',       'es' => 'Finlandia',         'ar' => 'فنلندا'],
            'slovenia'         => ['vi' => 'Slovenia',       'es' => 'Eslovenia',         'ar' => 'سلوفينيا'],
            'tunisia'          => ['vi' => 'Tunisia',        'es' => 'Túnez',             'ar' => 'تونس'],
        ];

        foreach ($translations as $slug => $locales) {
            $team = Team::where('slug', $slug)->first();
            if (!$team) continue;
            foreach ($locales as $locale => $name) {
                TeamTranslation::updateOrCreate(
                    ['team_id' => $team->id, 'locale' => $locale],
                    ['name' => $name]
                );
            }
        }

        $this->command->info('Team translations seeded!');
    }
}
