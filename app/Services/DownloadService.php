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

    // Một response chỉ là playlist khi mở đầu bằng #EXTM3U — nếu không thì
    // đó là trang lỗi, và parse tiếp sẽ sinh ra "segment" từ thẻ HTML.
    private function isPlaylist(string $content): bool
    {
        return str_starts_with(ltrim($content), '#EXTM3U');
    }

    public function getStreams(string $masterUrl): array
    {
        $content = $this->curlGet($masterUrl);
        if (!$this->isPlaylist($content)) return [];

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

    private function resolutionHeight(string $resolution): int
    {
        return preg_match('/\d+x(\d+)/', $resolution, $m) ? (int) $m[1] : 0;
    }

    // 720p → rendition cao nhất dưới 720p → thấp nhất.
    // Giống hệt pickQuality() trong scripts/download-highlight.ts để hai nhánh
    // Deno/curl cho ra cùng một kết quả.
    private function pickQuality(array $streams): array
    {
        foreach ($streams as $s) {
            if ($s['quality'] === '720p' || $this->resolutionHeight($s['resolution']) === 720) {
                return $s;
            }
        }

        // $streams đã sort bandwidth giảm dần → phần tử đầu là cao nhất còn dưới 720p
        foreach ($streams as $s) {
            if ($this->resolutionHeight($s['resolution']) < 720) return $s;
        }

        return end($streams);
    }

    private function downloadHlsWithCurl(MatchVideo $video, string $masterUrl, string $outDir, string $relBase): bool
    {
        $streams = $this->getStreams($masterUrl);
        if (empty($streams)) {
            $streams = [['quality' => 'default', 'url' => $masterUrl, 'bandwidth' => 0, 'resolution' => 'unknown']];
        }

        // Chỉ giữ 1 rendition — tải hết mọi chất lượng tốn 2-3x dung lượng SX65
        $streams = [$this->pickQuality($streams)];
        Log::info('HLS: chọn rendition', [
            'video_id'   => $video->id,
            'quality'    => $streams[0]['quality'],
            'resolution' => $streams[0]['resolution'],
        ]);

        $totalSize     = 0;
        $totalDuration = 0;
        $okStreams     = [];

        foreach ($streams as $stream) {
            $quality    = $stream['quality'];
            $segmentDir = "{$outDir}/{$quality}";
            if (!is_dir($segmentDir)) mkdir($segmentDir, 0755, true);

            $playlist = $this->curlGet($stream['url']);
            if (!$this->isPlaylist($playlist)) {
                Log::warning('HLS: response không phải playlist', [
                    'video_id' => $video->id,
                    'url'      => $stream['url'],
                ]);
                continue;
            }

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

            $gotSegments = 0;
            foreach ($segments as $idx => $segUrl) {
                $segName = basename(parse_url($segUrl, PHP_URL_PATH)) ?: "seg_{$idx}.ts";
                $segPath = "{$segmentDir}/{$segName}";
                if (file_exists($segPath) && filesize($segPath) > 0) {
                    $totalSize += filesize($segPath);
                    $gotSegments++;
                    continue;
                }
                if ($this->curlDownload($segUrl, $segPath)) {
                    $totalSize += filesize($segPath);
                    $gotSegments++;
                }
            }

            // Stream không tải được segment nào thì bỏ hẳn, đừng đưa vào master
            if ($gotSegments === 0) {
                Log::warning('HLS: stream có 0 segment', ['video_id' => $video->id, 'quality' => $quality]);
                continue;
            }

            $localPlaylist = $this->buildLocalPlaylist($playlist);
            file_put_contents("{$segmentDir}/index.m3u8", $localPlaylist);
            $totalDuration = max($totalDuration, (int) $duration);
            $okStreams[]   = $stream;
        }

        // Không có stream nào tải được → fail thật, đừng markReady 0 MB
        if (empty($okStreams)) {
            Log::error('HLS download failed: không tải được segment nào', [
                'video_id' => $video->id,
                'master'   => $masterUrl,
            ]);
            return false;
        }

        file_put_contents("{$outDir}/master.m3u8", $this->buildLocalMaster($okStreams));

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

    // ─── Download tất cả pending videos (mọi source) ─────────────
    public function downloadAllPending(): int
    {
        // Row kẹt ở 'downloading' vì lần chạy trước bị crash/timeout —
        // không reset thì chúng không bao giờ được thử lại.
        MatchVideo::where('status', 'downloading')
            ->where('updated_at', '<', now()->subHour())
            ->update(['status' => 'pending']);

        $videos = MatchVideo::where('status', 'pending')
            ->whereNotNull('embed_url')
            ->whereNull('local_path')
            ->with('match')
            ->get();

        $count = 0;
        foreach ($videos as $video) {
            try {
                if ($this->downloadOne($video)) $count++;
            } catch (\Throwable $e) {
                // Một video hỏng không được làm chết cả lượt crawl
                Log::error('downloadAllPending: exception', [
                    'video_id' => $video->id,
                    'error'    => $e->getMessage(),
                ]);
                // markError() cũng có thể ném (vd. enum 'error' chưa migrate)
                try {
                    $video->markError();
                } catch (\Throwable $inner) {
                    Log::error('downloadAllPending: markError failed', [
                        'video_id' => $video->id,
                        'error'    => $inner->getMessage(),
                    ]);
                }
            }
        }

        return $count;
    }

    // Download 1 video rồi đẩy lên storage. Thất bại thì đánh dấu 'error' —
    // findAndMapVideos() dựa vào status này để biết khi nào thử nguồn fallback.
    private function downloadOne(MatchVideo $video): bool
    {
        $url = $video->embed_url;
        $video->markDownloading();

        // Thứ tự quan trọng: nhận diện host trước khi fetch trang embed,
        // tránh curl vô ích lên YouTube và tránh bắt nhầm .m3u8 của live stream.
        if (str_contains($url, '.m3u8')) {
            $ok = $this->downloadHls($video, $url);
        } elseif (
            str_contains($url, 'youtube.com') ||
            str_contains($url, 'youtu.be') ||
            str_contains($url, 'streamable.com')
        ) {
            $ok = $this->downloadYoutube($video, $url);
        } elseif ($hlsUrl = $this->getHlsUrl($url)) {
            // Trang embed kiểu videas.fr — HLS nằm trong HTML
            $ok = $this->downloadHls($video, $hlsUrl);
        } else {
            Log::warning("downloadAllPending: no playable source for video {$video->id}: {$url}");
            $video->markError();
            return false;
        }

        if (!$ok) {
            Log::warning("downloadAllPending: download failed for video {$video->id}: {$url}");
            $video->markError();
            return false;
        }

        $this->syncToStorage($video);
        return true;
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

    private function refererFor(string $url): string
    {
        preg_match('/https?:\/\/([^\/]+)/', $url, $m);
        return isset($m[1]) ? "https://{$m[1]}/" : 'https://hoofoot.com/';
    }

    // -f: curl thoát khác 0 và không in body khi HTTP >= 400.
    // Thiếu cờ này thì trang 404 HTML bị coi là nội dung hợp lệ.
    private function curlGet(string $url): string
    {
        $cmd = sprintf(
            'curl -sfL --max-time 30 -H "User-Agent: %s" -H "Accept: */*" -H "Referer: %s" %s',
            $this->ua,
            $this->refererFor($url),
            escapeshellarg($url)
        );

        $out  = [];
        $code = 0;
        exec($cmd, $out, $code);

        if ($code !== 0) {
            Log::warning('curlGet failed', ['url' => $url, 'exit' => $code]);
            return '';
        }

        return implode("\n", $out);
    }

    private function curlDownload(string $url, string $outPath): bool
    {
        $cmd = sprintf(
            'curl -sfL --max-time 60 -H "User-Agent: %s" -H "Accept: */*" -H "Referer: %s" -o %s %s',
            $this->ua,
            $this->refererFor($url),
            escapeshellarg($outPath),
            escapeshellarg($url)
        );

        $out  = [];
        $code = 0;
        exec($cmd, $out, $code);

        if ($code !== 0 || !file_exists($outPath) || filesize($outPath) === 0) {
            // curl có thể đã tạo file rỗng/dở dang — xoá để lần sau không tưởng là đã tải xong
            @unlink($outPath);
            return false;
        }

        return true;
    }
}
