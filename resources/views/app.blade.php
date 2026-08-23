@php
    $locale   = app()->getLocale();
    $htmlLang = $locale === 'pt' ? 'pt-BR' : $locale;
    $dir      = $locale === 'ar' ? 'rtl' : 'ltr';
    $seo      = $seo ?? [];
    $title    = $seo['title']       ?? 'BolaReel';
    $desc     = $seo['description'] ?? '';
    $canon    = $seo['canonical']   ?? '';
    $image    = $seo['image']       ?? '';
    $noindex  = $seo['noindex']     ?? false;
    $ogType   = $seo['ogType']      ?? 'website';
    $ogLocale = ['en'=>'en_US','es'=>'es_ES','pt'=>'pt_BR','ar'=>'ar_AR','id'=>'id_ID','ja'=>'ja_JP','fr'=>'fr_FR','de'=>'de_DE','tr'=>'tr_TR','hi'=>'hi_IN'][$locale] ?? 'en_US';
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title data-blade-seo>{{ $title }}</title>
    <meta data-blade-seo name="description" content="{{ $desc }}">
    @if($canon)
    <link data-blade-seo rel="canonical" href="{{ $canon }}">
    @endif
    <meta name="robots" content="{{ $noindex ? 'noindex,nofollow' : 'index,follow' }}">

    {{-- Open Graph --}}
    <meta data-blade-seo property="og:site_name" content="BolaReel">
    <meta data-blade-seo property="og:type" content="{{ $ogType }}">
    <meta data-blade-seo property="og:locale" content="{{ $ogLocale }}">
    <meta data-blade-seo property="og:title" content="{{ $title }}">
    <meta data-blade-seo property="og:description" content="{{ $desc }}">
    @if($canon)<meta data-blade-seo property="og:url" content="{{ $canon }}">@endif
    @if($image)
    <meta data-blade-seo property="og:image" content="{{ $image }}">
    <meta data-blade-seo property="og:image:alt" content="{{ $title }}">
    @endif

    {{-- Twitter --}}
    <meta data-blade-seo name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
    <meta data-blade-seo name="twitter:title" content="{{ $title }}">
    <meta data-blade-seo name="twitter:description" content="{{ $desc }}">
    @if($image)<meta data-blade-seo name="twitter:image" content="{{ $image }}">@endif

    {{-- hreflang --}}
    @foreach($seo['alternates'] ?? [] as $alt)
    <link data-blade-seo rel="alternate" hreflang="{{ $alt['hreflang'] }}" href="{{ $alt['href'] }}">
    @endforeach

    {{-- JSON-LD --}}
    @if(!empty($seo['jsonLd']))
    <script data-blade-seo type="application/ld+json">{!! json_encode($seo['jsonLd'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
