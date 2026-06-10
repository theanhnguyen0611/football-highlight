<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use App\Models\MatchVideo;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        $storageSize = 0;
        $highlightsPath = storage_path('app/public/highlights');
        if (is_dir($highlightsPath)) {
            $storageSize = $this->dirSize($highlightsPath);
        }

        return response()->json([
            'matches' => [
                'total'   => FootballMatch::count(),
                'pending' => FootballMatch::where('status', 'pending')->count(),
                'ready'   => FootballMatch::where('status', 'ready')->count(),
            ],
            'videos' => [
                'total'       => MatchVideo::count(),
                'pending'     => MatchVideo::where('status', 'pending')->count(),
                'downloading' => MatchVideo::where('status', 'downloading')->count(),
                'ready'       => MatchVideo::where('status', 'ready')->count(),
                'error'       => MatchVideo::where('status', 'error')->count(),
            ],
            'storage_gb' => round($storageSize / 1024 / 1024 / 1024, 2),
        ]);
    }

    private function dirSize(string $path): int
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        return $size;
    }
}
