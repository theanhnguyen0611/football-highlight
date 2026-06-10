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
        $slug   = $video->match->slug;
        $outDir = storage_path("app/public/highlights/{$slug}");
        if (!is_dir($outDir)) mkdir($outDir, 0755, true);

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
                if ($line && !str_starts_with($line, '#')) {
                    $segments[] = str_starts_with($line, 'http') ? $line : $base . $line;
                }
            }

            foreach ($segments as $idx => $segUrl) {
                preg_match('/\/([^\/\?]+\.ts)/', $segUrl, $m);
                $segName = $m[1] ?? "seg_{$idx}.ts";
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
            "highlights/{$slug}/master.m3u8",
            round($totalSize / 1024 / 1024, 2),
            $totalDuration
        );

        Log::info('DownloadService: ready', ['video_id' => $video->id]);
        return true;
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
            if ($line && !str_starts_with($line, '#')) {
                preg_match('/\/([^\/\?]+\.ts)/', $line, $m);
                $result[] = $m[1] ?? basename(parse_url($line, PHP_URL_PATH));
            } else {
                $result[] = $line;
            }
        }
        return implode("\n", $result) . "\n";
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
