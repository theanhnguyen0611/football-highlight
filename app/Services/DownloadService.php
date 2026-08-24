<?php
namespace App\Services;

use App\Models\MatchVideo;
use Illuminate\Support\Facades\Log;

class DownloadService
{
    private string $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    public function getHlsUrl(string $embedUrl): ?string
    {
        $result = shell_exec(sprintf(
            'curl -s -L --max-time 30 -H "User-Agent: %s" -H "Referer: https://hoofoot.com/" -H "Origin: https://hoofoot.com" "%s"',
            $this->ua, $embedUrl
        ));
        if (!$result) return null;

        if (preg_match("/hls\s*:\s*'(\/\/[^']+\.m3u8[^']*)'/", $result, $m)) {
            return 'https:' . $m[1];
        }
        if (preg_match('/https?:\/\/[^\s"\'\\\\]+\.m3u8[^\s"\'\\\\]*/', $result, $m)) {
            return $m[0];
        }
        return null;
    }

    public function getStreams(string $masterUrl): array
    {
        $content = $this->curlGet($masterUrl);
        if (!$content) return [];

        $lines   = explode("\n", trim($content));
        $streams = [];
        $base    = substr($masterUrl, 0, strrpos($masterUrl, '/') + 1);

        for ($i = 0; $i < count($lines); $i++) {
            if (str_starts_with(trim($lines[$i]), '#EXT-X-STREAM-INF')) {
                preg_match('/BANDWIDTH=(\d+)/', $lines[$i], $bw);
                preg_match('/RESOLUTION=(\d+x\d+)/', $lines[$i], $res);
                $uri = trim($lines[$i + 1] ?? '');
                if (!$uri) continue;
                $url = str_starts_with($uri, 'http') ? $uri : $base . $uri;
                preg_match('/(\d+p)/', $uri, $q);
                $streams[] = [
                    'bandwidth'  => (int) ($bw[1] ?? 0),
                    'resolution' => $res[1] ?? 'unknown',
                    'quality'    => $q[1] ?? 'default',
                    'url'        => $url,
                ];
                $i++;
            }
        }
        usort($streams, fn($a, $b) => $b['bandwidth'] - $a['bandwidth']);
        return $streams;
    }

    public function downloadHls(MatchVideo $video, string $masterUrl): bool
    {
        $slug    = $video->match->slug;
        $subdir  = $this->highlightSubdir($video);
        $outDir  = storage_path("app/public/highlights/{$slug}/{$subdir}");
        $relBase = "highlights/{$slug}/{$subdir}";
        if (!is_dir($outDir)) mkdir($outDir, 0755, true);

        $denoScript = base_path('scripts/download-highlight.ts');
        if (is_file($denoScript) && $this->denoAvailable()) {
            return $this->downloadHlsWithDeno($video, $masterUrl, $outDir, $relBase);
        }

        return $this->downloadHlsWithCurl($video, $masterUrl, $outDir, $relBase);
    }

    private function denoAvailable(): bool
    {
        exec('deno --version 2>&1', $out, $code);
        return $code === 0;
    }

    private function downloadHlsWithDeno(MatchVideo $video, string $masterUrl, string $outDir, string $relBase): bool
    {
        $script = base_path('scripts/download-highlight.ts');
        $cmd    = sprintf(
            'deno run --allow-net --allow-write --allow-read --allow-run=ffmpeg %s %s %s 2>&1',
            escapeshellarg($script),
            escapeshellarg($masterUrl),
            escapeshellarg($outDir)
        );

        $output   = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists("{$outDir}/master.m3u8")) {
            Log::error('Deno HLS download failed', ['output' => implode("\n", $output)]);
            return false;
        }

        // Parse output for size ("Done! X MB saved to:")
        $sizeMb = 0;
        foreach ($output as $line) {
            if (preg_match('/Done!\s+([\d.]+)\s+MB/', $line, $m)) {
                $sizeMb = (float) $m[1];
                break;
            }
        }

        $duration = $this->getHlsDuration($outDir);
        $video->markReady("{$relBase}/master.m3u8", $sizeMb, $duration);

        Log::info('Deno HLS download complete', ['video_id' => $video->id, 'size_mb' => $sizeMb]);
        return true;
    }

    private function downloadHlsWithCurl(MatchVideo $video, string $masterUrl, string $outDir, string $relBase): bool
    {
        $streams = $this->getStreams($masterUrl);
        if (empty($streams)) {
            $streams = [['quality' => 'default', 'url' => $masterUrl, 'bandwidth' => 0, 'resolution' => 'unknown']];
        }

        $totalSize     = 0;
        $totalDuration = 0;

        foreach ($streams as $stream) {
            $quality    = $stream['quality'];
            $segmentDir = "{$outDir}/{$quality}";
            if (!is_dir($segmentDir)) mkdir($segmentDir, 0755, true);

            $playlist = $this->curlGet($stream['url']);
            if (!$playlist) continue;

            file_put_contents("{$segmentDir}/playlist.m3u8", $playlist);

            $lines    = explode("\n", trim($playlist));
            $base     = substr($stream['url'], 0, strrpos($stream['url'], '/') + 1);
            $segments = [];
            $duration = 0;

            foreach ($lines as $line) {
                $line = trim($line);
                if (str_starts_with($line, '#EXTINF:')) {
                    preg_match('/#EXTINF:([\d.]+)/', $line, $m);
                    $duration += (float) ($m[1] ?? 0);
                }
                // Download init segment từ #EXT-X-MAP (CMAF/fMP4 format)
                if (str_starts_with($line, '#EXT-X-MAP:')) {
                    preg_match('/#EXT-X-MAP:URI="([^"]+)"/', $line, $m);
                    if (!empty($m[1])) {
                        $initUri  = $m[1];
                        $initUrl  = str_starts_with($initUri, 'http') ? $initUri : $base . $initUri;
                        $initName = basename(parse_url($initUrl, PHP_URL_PATH));
                        if ($initName) $this->curlDownload($initUrl, "{$segmentDir}/{$initName}");
                    }
                }
                if ($line && !str_starts_with($line, '#')) {
                    $segments[] = str_starts_with($line, 'http') ? $line : $base . $line;
                }
            }

            foreach ($segments as $idx => $segUrl) {
                $segName = basename(parse_url($segUrl, PHP_URL_PATH)) ?: "seg_{$idx}.ts";
                $segPath = "{$segmentDir}/{$segName}";
                if (file_exists($segPath) && filesize($segPath) > 0) {
                    $totalSize += filesize($segPath);
                    continue;
                }
                $this->curlDownload($segUrl, $segPath);
                $totalSize += file_exists($segPath) ? filesize($segPath) : 0;
            }

            $localPlaylist = $this->buildLocalPlaylist($playlist);
            file_put_contents("{$segmentDir}/index.m3u8", $localPlaylist);
            $totalDuration = max($totalDuration, (int) $duration);
        }

        $localMaster = $this->buildLocalMaster($streams);
        file_put_contents("{$outDir}/master.m3u8", $localMaster);

        if (!file_exists("{$outDir}/master.m3u8")) return false;

        $video->markReady(
            "{$relBase}/master.m3u8",
            round($totalSize / 1024 / 1024, 2),
            $totalDuration
        );

        Log::info('DownloadService: ready', ['video_id' => $video->id]);
        return true;
    }

    private function getHlsDuration(string $outDir): int
    {
        $playlists = glob("{$outDir}/*/index.m3u8");
        if (empty($playlists)) return 0;

        $duration = 0;
        $content  = file_get_contents($playlists[0]) ?: '';
        preg_match_all('/#EXTINF:([\d.]+)/', $content, $m);
        foreach ($m[1] as $d) {
            $duration += (float) $d;
        }
        return (int) $duration;
    }

    public function downloadYoutube(MatchVideo $video, string $ytUrl): bool
    {
        $slug = $video->match->slug;
        if ($video->video_type === 'full_match') {
            $outDir  = storage_path("app/public/full-matches/{$slug}");
            $relBase = "full-matches/{$slug}";
        } else {
            $subdir  = $this->highlightSubdir($video);
            $outDir  = storage_path("app/public/highlights/{$slug}/{$subdir}");
            $relBase = "highlights/{$slug}/{$subdir}";
        }
        if (!is_dir($outDir)) mkdir($outDir, 0755, true);

        $tmpMp4 = "{$outDir}/source.mp4";

        // Force H.264+AAC so MPEG-TS segments work in all browsers
        $proxy    = env('UK_PROXY');
        $proxyArg = $proxy ? '--proxy ' . escapeshellarg($proxy) . ' ' : '';
        $cmd = sprintf(
            'yt-dlp -f "bestvideo[height<=720][vcodec^=avc1]+bestaudio[ext=m4a]/bestvideo[height<=720][ext=mp4]+bestaudio/best[height<=720]" --merge-output-format mp4 --no-playlist --remote-components ejs:github %s-o %s %s 2>&1',
            $proxyArg,
            escapeshellarg($tmpMp4),
            escapeshellarg($ytUrl)
        );
        exec($cmd, $out, $code);

        if ($code !== 0 || !file_exists($tmpMp4) || filesize($tmpMp4) === 0) {
            Log::error("yt-dlp failed for {$ytUrl}", ['output' => implode("\n", $out)]);
            return false;
        }

        // Convert MP4 → HLS, re-encode video to H.264 nếu cần
        $segDir = "{$outDir}/720p";
        if (!is_dir($segDir)) mkdir($segDir, 0755, true);

        $m3u8 = "{$segDir}/index.m3u8";
        $cmd  = sprintf(
            'ffmpeg -y -loglevel error -i %s -c:v libx264 -preset fast -crf 23 -c:a aac -hls_time 10 -hls_list_size 0 -hls_segment_filename %s/seg%%05d.ts %s 2>&1',
            escapeshellarg($tmpMp4),
            escapeshellarg($segDir),
            escapeshellarg($m3u8)
        );
        exec($cmd, $out, $code);
        @unlink($tmpMp4);

        if ($code !== 0 || !file_exists($m3u8)) {
            Log::error("ffmpeg HLS conversion failed for match {$slug}");
            return false;
        }

        file_put_contents("{$outDir}/master.m3u8", "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-STREAM-INF:BANDWIDTH=2500000,RESOLUTION=1280x720\n720p/index.m3u8\n");

        $size     = array_sum(array_map('filesize', glob("{$segDir}/*.ts") ?: []));
        $duration = $this->getHlsDuration($outDir);
        $video->markReady("{$relBase}/master.m3u8", round($size / 1024 / 1024, 2), $duration);

        Log::info("yt-dlp: downloaded YouTube for match {$slug}");
        return true;
    }

    public function downloadPendingYoutubeVideos(): int
    {
        $videos = MatchVideo::where('status', 'pending')
            ->whereNull('local_path')
            ->where(function ($q) {
                $q->where('source_url', 'like', '%youtube.com%')
                  ->orWhere('source_url', 'like', '%youtu.be%');
            })
            ->with('match')
            ->get();

        $count = 0;
        foreach ($videos as $video) {
            if ($this->downloadYoutube($video, $video->source_url)) {
                $count++;
            }
        }
        return $count;
    }

    // ─── Download tất cả pending videos (mọi source, không tách Hoofoot/DasFootball) ─
    public function syncToStorage(MatchVideo $video): void
    {
        $ssh  = config('services.cdn.sx65_ssh');
        $base = config('services.cdn.sx65_path');
        if (!$ssh || !$base) return;

        // local_path = highlights/{slug}/extended/master.m3u8
        // remote     = /storage/bolareel/highlights/{slug}/extended/
        $relDir   = dirname($video->local_path);
        $localDir = storage_path("app/public/{$relDir}");

        $rsync = sprintf(
            'rsync -az --mkpath --no-perms %s/ %s:%s/%s/ 2>&1',
            escapeshellarg($localDir),
            $ssh,
            $base,
            $relDir
        );
        $output   = [];
        $exitCode = 0;
        exec($rsync, $output, $exitCode);

        if ($exitCode !== 0) {
            Log::error('syncToStorage rsync failed', [
                'video_id' => $video->id,
                'local'    => $localDir,
                'remote'   => "{$ssh}:{$base}/{$relDir}",
                'output'   => implode("\n", $output),
            ]);
            return;
        }

        shell_exec('rm -rf ' . escapeshellarg($localDir));
        Log::info('syncToStorage done', ['video_id' => $video->id, 'path' => $relDir]);
    }

    public function downloadAllPending(): int
    {
        $videos = MatchVideo::where('status', 'pending')
            ->whereNotNull('embed_url')
            ->whereNull('local_path')
            ->with('match')
            ->get();

        $count = 0;
        foreach ($videos as $video) {
            $url = $video->embed_url;
            $ok  = false;

            // HLS extracted from embed page (hoofoot/videas style)
            $hlsUrl = $this->getHlsUrl($url);
            if ($hlsUrl) {
                $ok = $this->downloadHls($video, $hlsUrl);
            } elseif (str_contains($url, '.m3u8')) {
                $ok = $this->downloadHls($video, $url);
            } elseif (
                str_contains($url, 'youtube.com') ||
                str_contains($url, 'youtu.be') ||
                str_contains($url, 'streamable.com')
            ) {
                $ok = $this->downloadYoutube($video, $url);
            } else {
                Log::warning("downloadAllPending: unknown embed type for video {$video->id}: {$url}");
                $video->markError();
            }

            if ($ok) {
                $count++;
                $this->syncToStorage($video);
            }
        }

        return $count;
    }

    // ─── Download pending dasfootball videos: m3u8 → YouTube → Streamable ─
    public function downloadPendingDasFootballVideos(): int
    {
        $videos = MatchVideo::where('source', 'dasfootball')
            ->where('status', 'pending')
            ->whereNull('local_path')
            ->whereNotNull('embed_url')
            ->with('match')
            ->get();

        $count = 0;
        foreach ($videos as $video) {
            $url = $video->embed_url;
            $ok  = false;

            if (str_contains($url, '.m3u8')) {
                $ok = $this->downloadHls($video, $url);
            } elseif (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be') || str_contains($url, 'streamable.com')) {
                $ok = $this->downloadYoutube($video, $url);
            } else {
                Log::warning("DasFootball: unknown embed type for video {$video->id} → {$url}");
                $video->markError();
            }

            if ($ok) $count++;
        }
        return $count;
    }

    public function downloadFullMatchFromFile(MatchVideo $video, string $storedFilePath): bool
    {
        $slug   = $video->match->slug;
        $outDir = storage_path("app/public/full-matches/{$slug}");
        if (!is_dir($outDir)) mkdir($outDir, 0755, true);

        $segDir = "{$outDir}/default";
        if (!is_dir($segDir)) mkdir($segDir, 0755, true);

        $m3u8 = "{$segDir}/index.m3u8";
        $cmd  = sprintf(
            'ffmpeg -y -loglevel error -i %s -c copy -hls_time 10 -hls_list_size 0 -hls_segment_filename %s/seg%%04d.ts %s 2>&1',
            escapeshellarg($storedFilePath),
            escapeshellarg($segDir),
            escapeshellarg($m3u8)
        );
        exec($cmd, $out, $code);
        @unlink($storedFilePath);

        if ($code !== 0 || !file_exists($m3u8)) {
            Log::error("ffmpeg HLS conversion failed for full match {$slug}", ['output' => implode("\n", $out)]);
            return false;
        }

        file_put_contents("{$outDir}/master.m3u8", "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-STREAM-INF:BANDWIDTH=0,RESOLUTION=unknown\ndefault/index.m3u8\n");

        $size     = array_sum(array_map('filesize', glob("{$segDir}/*.ts") ?: []));
        $duration = $this->getVideoDuration($m3u8);
        $video->markReady("full-matches/{$slug}/master.m3u8", round($size / 1024 / 1024, 2), $duration);

        Log::info("Full match uploaded and converted for {$slug}");
        return true;
    }

    private function getVideoDuration(string $m3u8Path): int
    {
        $content  = file_get_contents($m3u8Path) ?: '';
        $duration = 0;
        preg_match_all('/#EXTINF:([\d.]+)/', $content, $m);
        foreach ($m[1] as $d) {
            $duration += (float) $d;
        }
        return (int) $duration;
    }

    private function buildLocalMaster(array $streams): string
    {
        $lines = ["#EXTM3U", "#EXT-X-VERSION:3"];
        foreach ($streams as $s) {
            $lines[] = "#EXT-X-STREAM-INF:BANDWIDTH={$s['bandwidth']},RESOLUTION={$s['resolution']}";
            $lines[] = "{$s['quality']}/index.m3u8";
        }
        return implode("\n", $lines) . "\n";
    }

    private function buildLocalPlaylist(string $original): string
    {
        $lines  = explode("\n", trim($original));
        $result = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#EXT-X-MAP:')) {
                // Rewrite init segment URI to local filename
                $rewritten = preg_replace_callback(
                    '/#EXT-X-MAP:URI="([^"]+)"/',
                    fn($m) => '#EXT-X-MAP:URI="' . basename(parse_url($m[1], PHP_URL_PATH)) . '"',
                    $line
                );
                $result[] = $rewritten;
            } elseif ($line && !str_starts_with($line, '#')) {
                preg_match('/\/([^\/\?]+\.ts)/', $line, $m);
                $result[] = $m[1] ?? basename(parse_url($line, PHP_URL_PATH));
            } else {
                $result[] = $line;
            }
        }
        return implode("\n", $result) . "\n";
    }

    private function highlightSubdir(MatchVideo $video): string
    {
        return match($video->source) {
            'hoofoot'     => 'extended',
            'dasfootball' => 'alt_highlight',
            default       => 'highlight',
        };
    }

    private function curlGet(string $url): string
    {
        preg_match('/https?:\/\/([^\/]+)/', $url, $m);
        $referer = isset($m[1]) ? "https://{$m[1]}/" : 'https://hoofoot.com/';
        $result  = shell_exec(sprintf(
            'curl -s -L --max-time 30 -H "User-Agent: %s" -H "Accept: */*" -H "Referer: %s" "%s"',
            $this->ua, $referer, $url
        ));
        return $result ?? '';
    }

    private function curlDownload(string $url, string $outPath): bool
    {
        preg_match('/https?:\/\/([^\/]+)/', $url, $m);
        $referer = isset($m[1]) ? "https://{$m[1]}/" : 'https://hoofoot.com/';
        shell_exec(sprintf(
            'curl -s -L --max-time 60 -H "User-Agent: %s" -H "Accept: */*" -H "Referer: %s" -o "%s" "%s"',
            $this->ua, $referer, $outPath, $url
        ));
        return file_exists($outPath) && filesize($outPath) > 0;
    }
}
