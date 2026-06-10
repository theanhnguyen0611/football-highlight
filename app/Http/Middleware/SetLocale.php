<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private array $supported = ['en', 'es', 'pt', 'ar', 'id', 'bn', 'ja', 'fr', 'de', 'tr', 'sw', 'hi'];

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Route prefix là source of truth
        $locale = $request->route('locale');

        // Nếu không có prefix → EN (không đọc cookie)
        if (!$locale) {
            $locale = 'en';
        }

        // Validate
        if (!in_array($locale, $this->supported)) {
            $locale = 'en';
        }

        App::setLocale($locale);

        $response = $next($request);

        // Lưu cookie để AppLayout biết locale hiện tại
        return $response->cookie('lang', $locale, 60 * 24 * 365);
    }
}
