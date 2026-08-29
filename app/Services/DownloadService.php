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

    // ─── Tải HLS: chọn 720p rồi để ffmpeg kéo + cắt segment ──────────
    // ffmpeg tải cả stream trong một tiến trình, tái sử dụng kết nối. Bản cũ
    // lặp curl từng segment (một process mỗi segment) mất 436s cho video mà
    // ffmpeg làm trong 150s.
    public function downloadHls(MatchVideo $video, string $masterUrl): bool
    {
        $slug    = $video->match->slug;
        $subdir  = $this->highlightSubdir($video);
        $outDir  = storage_path("app/public/highlights/{$slug}/{$subdir}");
        $relBase = "highlights/{$slug}/{$subdir}";
        if (!is_dir($outDir)) mkdir($outDir, 0755, true);

        $streams = $this->getStreams($masterUrl);
        if (empty($streams)) {
            // Playlist một tầng — không có master, dùng thẳng
            $streams = [['quality' => 'default', 'url' => $masterUrl, 'bandwidth' => 0, 'resolution' => 'unknown']];
        }

        $stream  = $this->pickQuality($streams);
        $quality = $stream['quality'];
        $segDir  = "{$outDir}/{$quality}";
        if (!is_dir($segDir)) mkdir($segDir, 0755, true);

        // Dọn segment lần trước: lần đó dài hơn thì phần thừa nằm lại và bị
        // rsync lên SX65 dù playlist không tham chiếu.
        foreach (glob("{$segDir}/*.ts") ?: [] as $stale) @unlink($stale);

        Log::info('HLS: chọn rendition', [
            'video_id'   => $video->id,
            'quality'    => $quality,
            'resolution' => $stream['resolution'],
        ]);

        $m3u8 = "{$segDir}/index.m3u8";
        $cmd  = sprintf(
            'ffmpeg -y -loglevel error -user_agent %s -headers %s -i %s -c copy '
            . '-hls_time 10 -hls_list_size 0 -hls_segment_filename %s/seg%%05d.ts %s 2>&1',
            escapeshellarg($this->ua),
            escapeshellarg("Referer: {$this->refererFor($stream['url'])}\r\n"),
            escapeshellarg($stream['url']),
            escapeshellarg($segDir),
            escapeshellarg($m3u8)
        );

        $out  = [];
        $code = 0;
        exec($cmd, $out, $code);

        $segments = glob("{$segDir}/*.ts") ?: [];
        if ($code !== 0 || !file_exists($m3u8) || empty($segments)) {
            Log::error('HLS download lỗi', [
                'video_id' => $video->id,
                'master'   => $masterUrl,
                'output'   => implode("\n", array_slice($out, -5)),
            ]);
            return false;
        }

        $res = $stream['resolution'] !== 'unknown'
            ? $stream['resolution']
            : $this->probeResolution($segments[0]);

        file_put_contents(
            "{$outDir}/master.m3u8",
            "#EXTM3U\n#EXT-X-VERSION:3\n"
            . "#EXT-X-STREAM-INF:BANDWIDTH={$stream['bandwidth']},RESOLUTION={$res}\n"
            . "{$quality}/index.m3u8\n"
        );

        $size = array_sum(array_map('filesize', $segments));
        $video->markReady("{$relBase}/master.m3u8", round($size / 1024 / 1024, 2), $this->getHlsDuration($outDir));

        Log::info('HLS download xong', ['video_id' => $video->id, 'size_mb' => round($size / 1024 / 1024, 2)]);
        return true;
    }

    private function resolutionHeight(string $resolution): int
    {
        return preg_match('/\d+x(\d+)/', $resolution, $m) ? (int) $m[1] : 0;
    }

    // 720p → rendition cao nhất dưới 720p → thấp nhất.
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

    // Thư mục đích của một video: full match và highlight nằm khác nhánh.
    private function outputPaths(MatchVideo $video): array
    {
        $slug = $video->match->slug;

        if ($video->video_type === 'full_match') {
            return [storage_path("app/public/full-matches/{$slug}"), "full-matches/{$slug}"];
        }

        $subdir = $this->highlightSubdir($video);
        return [storage_path("app/public/highlights/{$slug}/{$subdir}"), "highlights/{$slug}/{$subdir}"];
    }

    // Một file MP4 → HLS 720p. Dùng chung cho nguồn yt-dlp và nguồn MP4 trực tiếp.
    // Ép H.264 + AAC để segment MPEG-TS chạy được trên mọi trình duyệt, và hạ
    // xuống tối đa 720p (nguồn nhỏ hơn thì giữ nguyên, không upscale).
    private function convertMp4ToHls(MatchVideo $video, string $mp4Path, string $outDir, string $relBase): bool
    {
        $segDir = "{$outDir}/720p";
        if (!is_dir($segDir)) mkdir($segDir, 0755, true);

        // Dọn segment của lần convert trước: nếu lần đó dài hơn, phần thừa sẽ nằm
        // lại trên đĩa và bị rsync lên SX65 dù index.m3u8 không hề tham chiếu.
        foreach (glob("{$segDir}/*.ts") ?: [] as $stale) @unlink($stale);

        // Nguồn đã H.264/AAC và <=720p thì chỉ cần remux (-c copy) — nhanh hơn
        // re-encode hàng chục lần. File MP4 của DasFootball có thể tới 800MB, encode
        // lại trên 2 vCPU dễ vượt timeout 30 phút của DownloadVideosJob.
        $info    = $this->probeVideo($mp4Path);
        $canCopy = $info['vcodec'] === 'h264'
            && $info['acodec'] === 'aac'
            && $info['height'] > 0
            && $info['height'] <= 720;

        $codecArgs = $canCopy
            ? '-c copy'
            : sprintf('-vf %s -c:v libx264 -preset fast -crf 23 -c:a aac', escapeshellarg("scale=-2:'min(720,ih)'"));

        Log::info('MP4→HLS', [
            'video_id' => $video->id,
            'mode'     => $canCopy ? 'remux (-c copy)' : 're-encode',
            'source'   => $info,
        ]);

        $m3u8 = "{$segDir}/index.m3u8";
        $cmd  = sprintf(
            'ffmpeg -y -loglevel error -i %s %s -hls_time 10 -hls_list_size 0 '
            . '-hls_segment_filename %s/seg%%05d.ts %s 2>&1',
            escapeshellarg($mp4Path),
            $codecArgs,
            escapeshellarg($segDir),
            escapeshellarg($m3u8)
        );
        exec($cmd, $out, $code);
        @unlink($mp4Path);

        $segments = glob("{$segDir}/*.ts") ?: [];
        if ($code !== 0 || !file_exists($m3u8) || empty($segments)) {
            Log::error('ffmpeg MP4→HLS lỗi', [
                'video_id' => $video->id,
                'output'   => implode("\n", array_slice($out, -5)),
            ]);
            return false;
        }

        // Probe segment .ts đầu tiên, không probe .m3u8 — ffprobe không đọc được
        // playlist nên sẽ trả rỗng và ghi nhầm RESOLUTION mặc định vào master.
        $res = $this->probeResolution($segments[0]);
        file_put_contents(
            "{$outDir}/master.m3u8",
            "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-STREAM-INF:BANDWIDTH=2500000,RESOLUTION={$res}\n720p/index.m3u8\n"
        );

        $size = array_sum(array_map('filesize', $segments));
        $video->markReady("{$relBase}/master.m3u8", round($size / 1024 / 1024, 2), $this->getHlsDuration($outDir));

        return true;
    }

    // ffprobe trên MPEG-TS in ra một dòng cho mỗi program, nên luôn chỉ lấy dòng đầu.
    private function ffprobeFirstLine(string $stream, string $entries, string $path, string $sep = ','): string
    {
        $out = (string) shell_exec(sprintf(
            'ffprobe -v error -select_streams %s -show_entries stream=%s -of csv=s=%s:p=0 %s 2>/dev/null',
            $stream,
            $entries,
            $sep,
            escapeshellarg($path)
        ));

        return trim(strtok($out, "\n") ?: '');
    }

    private function probeResolution(string $path): string
    {
        $out = $this->ffprobeFirstLine('v:0', 'width,height', $path, 'x');

        return preg_match('/^\d+x\d+$/', $out) ? $out : '1280x720';
    }

    private function probeVideo(string $path): array
    {
        $v = explode(',', $this->ffprobeFirstLine('v:0', 'codec_name,height', $path));

        return [
            'vcodec' => trim($v[0] ?? ''),
            'height' => (int) trim($v[1] ?? '0'),
            'acodec' => $this->ffprobeFirstLine('a:0', 'codec_name', $path),
        ];
    }

    // Chỉ tính phần path — query string có thể chứa ".mp4" mà không phải file MP4.
    private function isMp4Url(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        return str_ends_with(strtolower($path), '.mp4');
    }

    // ─── Nguồn MP4 trực tiếp (DasFootball hay trả cdn.videas.fr / cdn.streamain.com) ─
    public function downloadMp4(MatchVideo $video, string $mp4Url): bool
    {
        [$outDir, $relBase] = $this->outputPaths($video);
        if (!is_dir($outDir)) mkdir($outDir, 0755, true);

        $tmpMp4 = "{$outDir}/source.mp4";
        if (!$this->curlDownload($mp4Url, $tmpMp4, maxTime: 900)) {
            Log::error('Tải MP4 lỗi', ['video_id' => $video->id, 'url' => $mp4Url]);
            return false;
        }

        return $this->convertMp4ToHls($video, $tmpMp4, $outDir, $relBase);
    }

    // yt-dlp báo geo-block bằng các câu này — chỉ khi đó mới đáng thử lại qua proxy.
    private function isGeoBlocked(string $output): bool
    {
        foreach ([
            'not available in your country',
            'not made this video available in your country',
            'blocked it in your country',
            'blocked it on copyright grounds',
            'geo restriction',
            'geo-restricted',
            'available in your location',
        ] as $needle) {
            if (stripos($output, $needle) !== false) return true;
        }

        return false;
    }

    public function downloadYoutube(MatchVideo $video, string $ytUrl): bool
    {
        [$outDir, $relBase] = $this->outputPaths($video);
        if (!is_dir($outDir)) mkdir($outDir, 0755, true);

        $tmpMp4 = "{$outDir}/source.mp4";

        // Force H.264+AAC so MPEG-TS segments work in all browsers
        $ytdlp = 'yt-dlp -f "bestvideo[height<=720][vcodec^=avc1]+bestaudio[ext=m4a]/bestvideo[height<=720][ext=mp4]+bestaudio/best[height<=720]"'
            . ' --merge-output-format mp4 --no-playlist --remote-components ejs:github';

        // Thử IP thật trước. Proxy chỉ dùng khi YouTube báo đúng lỗi chặn vùng —
        // IP datacenter (kể cả proxy UK) hay bị YouTube bắt "confirm you're not a
        // bot", nên gắn proxy vô điều kiện là tự làm hỏng mọi lượt tải.
        $cmd = sprintf('%s -o %s %s 2>&1', $ytdlp, escapeshellarg($tmpMp4), escapeshellarg($ytUrl));
        exec($cmd, $out, $code);

        $proxy = env('UK_PROXY');
        if ($code !== 0 && $proxy && $this->isGeoBlocked(implode("\n", $out))) {
            Log::info('yt-dlp: bị chặn vùng, thử lại qua UK proxy', ['video_id' => $video->id]);
            @unlink($tmpMp4);
            $out = [];
            $cmd = sprintf(
                '%s --proxy %s -o %s %s 2>&1',
                $ytdlp,
                escapeshellarg($proxy),
                escapeshellarg($tmpMp4),
                escapeshellarg($ytUrl)
            );
            exec($cmd, $out, $code);
        }

        if ($code !== 0 || !file_exists($tmpMp4) || filesize($tmpMp4) === 0) {
            Log::error("yt-dlp failed for {$ytUrl}", ['output' => implode("\n", array_slice($out, -5))]);
            @unlink($tmpMp4);
            return false;
        }

        return $this->convertMp4ToHls($video, $tmpMp4, $outDir, $relBase);
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

    // BunnyCDN cache 30 ngày (cache-control: max-age=2592000). Khi ghi đè video
    // đã từng sync trước đó (vd: hoofoot:fix-short-videos reset lại bản EXTENDED),
    // origin đổi nhưng edge CDN khác không tự biết — phải purge thủ công, không
    // thì user ở edge chưa refresh vẫn thấy bản cũ dù origin đã đúng.
    public function purgeCdnCache(): void
    {
        $apiKey = config('services.cdn.api_key');
        $zoneId = config('services.cdn.pullzone_id');
        if (!$apiKey || !$zoneId) {
            Log::warning('purgeCdnCache bỏ qua: thiếu BUNNY_API_KEY/BUNNY_PULLZONE_ID');
            return;
        }

        $cmd = sprintf(
            'curl -s -o /dev/null -w "%%{http_code}" -X POST -H %s "https://api.bunny.net/pullzone/%s/purgeCache"',
            escapeshellarg("AccessKey: {$apiKey}"),
            $zoneId
        );
        $code = trim((string) shell_exec($cmd));

        if ($code !== '204') {
            Log::error('purgeCdnCache thất bại', ['http_code' => $code]);
        } else {
            Log::info('purgeCdnCache thành công');
        }
    }

    // Xoá một thư mục trên SX65. Video nằm trên CX23 rồi mới rsync sang, nên khi
    // dọn phải xoá cả hai nơi — bản local có thể còn nếu rsync trước đó thất bại.
    public function deleteFromStorage(string $relDir): bool
    {
        $ssh  = config('services.cdn.sx65_ssh');
        $base = config('services.cdn.sx65_path');
        if (!$ssh || !$base) return false;

        // Lệnh này chạy rm -rf trên storage thật → chỉ nhận đúng dạng đã biết:
        // highlights/{slug}[/{subdir}] hoặc full-matches/{slug}[/{subdir}].
        // Whitelist chặt hơn blacklist: khoảng trắng, '..', ký tự lạ đều bị loại.
        $relDir = trim($relDir, " \t\n\r\0\x0B/");

        if (!preg_match('#^(highlights|full-matches)/[A-Za-z0-9._-]+(/[A-Za-z0-9._-]+)?$#', $relDir)
            || str_contains($relDir, '..')
        ) {
            Log::error('deleteFromStorage: relDir không hợp lệ', ['relDir' => $relDir]);
            return false;
        }

        $cmd = sprintf(
            'ssh -o BatchMode=yes -o ConnectTimeout=10 %s %s 2>&1',
            escapeshellarg($ssh),
            escapeshellarg('rm -rf ' . escapeshellarg("{$base}/{$relDir}"))
        );

        $out  = [];
        $code = 0;
        exec($cmd, $out, $code);

        if ($code !== 0) {
            Log::error('deleteFromStorage lỗi', [
                'relDir' => $relDir,
                'output' => implode("\n", $out),
            ]);
            return false;
        }

        Log::info('deleteFromStorage done', ['relDir' => $relDir]);
        return true;
    }

    // Kiểm tra hàng loạt local_path trên SX65 trong MỘT lần ssh.
    // Trả về [path => 'ok'|'missing'|'empty'].
    public function checkOnStorage(array $relPaths): array
    {
        $ssh  = config('services.cdn.sx65_ssh');
        $base = config('services.cdn.sx65_path');
        if (!$ssh || !$base || empty($relPaths)) return [];

        // Remote đọc path từ stdin: thiếu master.m3u8 → missing;
        // có master nhưng thư mục không còn segment nào → empty.
        $remote = 'cd ' . escapeshellarg($base) . ' || exit 1; '
            . 'while IFS= read -r p; do '
            . '  if [ ! -s "$p" ]; then echo "missing|$p"; continue; fi; '
            . '  d=$(dirname "$p"); '
            . '  n=$(find "$d" -name "*.ts" -o -name "*.m4s" 2>/dev/null | head -1); '
            . '  if [ -z "$n" ]; then echo "empty|$p"; else echo "ok|$p"; fi; '
            . 'done';

        $cmd = sprintf(
            'printf %%s %s | ssh -o BatchMode=yes -o ConnectTimeout=15 %s %s 2>/dev/null',
            escapeshellarg(implode("\n", $relPaths) . "\n"),
            escapeshellarg($ssh),
            escapeshellarg($remote)
        );

        $out  = [];
        $code = 0;
        exec($cmd, $out, $code);
        if ($code !== 0) {
            Log::error('checkOnStorage: ssh lỗi', ['exit' => $code]);
            return [];
        }

        $result = [];
        foreach ($out as $line) {
            [$state, $path] = array_pad(explode('|', $line, 2), 2, null);
            if ($path !== null) $result[$path] = $state;
        }

        return $result;
    }

    // Liệt kê thư mục rendition trên SX65 (highlights/{slug}/{subdir}) để tìm rác
    // không còn row nào trong DB trỏ tới.
    public function listStorageDirs(): array
    {
        $ssh  = config('services.cdn.sx65_ssh');
        $base = config('services.cdn.sx65_path');
        if (!$ssh || !$base) return [];

        $remote = 'cd ' . escapeshellarg($base) . ' || exit 1; '
            . 'find highlights full-matches -mindepth 1 -maxdepth 2 -type d 2>/dev/null';

        $cmd = sprintf(
            'ssh -o BatchMode=yes -o ConnectTimeout=15 %s %s 2>/dev/null',
            escapeshellarg($ssh),
            escapeshellarg($remote)
        );

        $out = [];
        exec($cmd, $out);

        return array_values(array_filter(array_map('trim', $out)));
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
    public function downloadOne(MatchVideo $video): bool
    {
        $url = $video->embed_url;
        $video->markDownloading();

        // Thứ tự quan trọng: nhận diện host trước khi fetch trang embed,
        // tránh curl vô ích lên YouTube và tránh bắt nhầm .m3u8 của live stream.
        if (str_contains($url, '.m3u8')) {
            $ok = $this->downloadHls($video, $url);
        } elseif ($this->isMp4Url($url)) {
            // DasFootball hay trả thẳng file MP4 (cdn.videas.fr, cdn.streamain.com)
            $ok = $this->downloadMp4($video, $url);
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

    // $maxTime mặc định hợp cho segment HLS; file MP4 nguyên trận cần nới rộng.
    private function curlDownload(string $url, string $outPath, int $maxTime = 60): bool
    {
        $cmd = sprintf(
            'curl -sfL --max-time %d -H "User-Agent: %s" -H "Accept: */*" -H "Referer: %s" -o %s %s',
            $maxTime,
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
