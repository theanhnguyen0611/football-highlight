<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Danh sách tất cả quốc gia
$nationals = [
    'France','England','Germany','Spain','Italy','Brazil','Argentina',
    'Portugal','Netherlands','Belgium','Croatia','Morocco','Senegal',
    'USA','Mexico','Japan','South Korea','Norway','Sweden','Denmark',
    'Switzerland','Poland','Ukraine','Tunisia','Algeria','Egypt',
    'Honduras','Panama','Chile','Ivory Coast','Slovenia','Luxembourg',
    'Finland','Uzbekistan','New Zealand','Iraq','Greece','Northern Ireland',
    'Scotland','Wales','Ireland','Turkey','Türkiye','Romania','Serbia',
    'Austria','Czech Republic','Hungary','Slovakia','Russia','Colombia',
    'Peru','Ecuador','Uruguay','Paraguay','Bolivia','Venezuela',
    'Costa Rica','Nigeria','Ghana','Cameroon','Mali','South Africa',
    'Kenya','Ethiopia','Saudi Arabia','Iran','Qatar','UAE','China',
    'India','Indonesia','Thailand','Vietnam','Australia','Georgia',
    'Albania','North Macedonia','Montenegro','Bosnia','Iceland',
    'Bulgaria','Lithuania','Latvia','Estonia','Belarus','Moldova',
    'Armenia','Azerbaijan','Kazakhstan','Kyrgyzstan','Tajikistan',
    'Turkmenistan','Afghanistan','Pakistan','Bangladesh','Sri Lanka',
    'Nepal','Myanmar','Cambodia','Laos','Philippines','Singapore',
    'Malaysia','Brunei','East Timor','Papua New Guinea','Fiji',
    'Jamaica','Trinidad and Tobago','Haiti','Cuba','Guatemala',
    'El Salvador','Nicaragua','Belize','Bahamas','Barbados',
    'Antigua and Barbuda','Dominica','Grenada','Saint Kitts and Nevis',
    'Saint Lucia','Saint Vincent','Suriname','Guyana','Libya',
    'Sudan','South Sudan','Ethiopia','Eritrea','Djibouti','Somalia',
    'Rwanda','Burundi','Tanzania','Uganda','Democratic Republic of Congo',
    'Congo','Gabon','Equatorial Guinea','Cameroon','Central African Republic',
    'Chad','Niger','Mali','Burkina Faso','Guinea','Guinea-Bissau',
    'Sierra Leone','Liberia','Ivory Coast','Ghana','Togo','Benin',
    'Mauritania','Cabo Verde','Gambia','Senegal','Zambia','Zimbabwe',
    'Mozambique','Madagascar','Malawi','Botswana','Namibia','Angola',
    'Lesotho','Eswatini','Comoros','Mauritius','Seychelles',
];

$slugs = array_map(fn($n) => \Illuminate\Support\Str::slug($n), $nationals);

$updated = \App\Models\Team::whereIn('slug', $slugs)
    ->where('type', 'club')
    ->update(['type' => 'national']);

echo "Fixed {$updated} teams to national type\n";

// Verify
$nationals_count = \App\Models\Team::where('type', 'national')->count();
$clubs_count = \App\Models\Team::where('type', 'club')->count();
echo "National: {$nationals_count} | Club: {$clubs_count}\n";
