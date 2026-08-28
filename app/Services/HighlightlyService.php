<?php
namespace App\Services;

use App\Exceptions\HighlightlyQuotaException;
use App\Models\Team;
use App\Models\League;
use App\Models\FootballMatch;
use App\Models\MatchEvent;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class HighlightlyService
{
    private string $baseUrl = 'https://soccer.highlightly.net';

    private const TOP_LEAGUE_IDS = [
        33973, 119924, 67162, 61205, 52695, 2486, 3337,
        39079, 41632, 122477, 69715, 117371, 56950,
        1635, 4188, 8443, 5039, 9294, 14400,
        19506, 75672, 80778, 173537,
        216087, 34824,
        262041, // Saudi Pro League

        // World Cup Qualifiers (tất cả châu lục gộp chung)
        25463, 26314, 27165, 28016, 28867, 29718, 32271,
    ];

    // WCQ logo: dùng chung logo WCQ Europe (đẹp nhất, phổ biến nhất)
    private const WCQ_LOGO = 'https://highlightly.net/soccer/images/leagues/28016.png';

    // Highlightly đặt tên chung chung "Pro League" cho nhiều nước (Bỉ, Iran, Ả
    // Rập...) không phân biệt được — đè tên rõ ràng theo id đã biết.
    private const LEAGUE_NAME_OVERRIDES = [
        262041 => 'Saudi Pro League',
    ];

    // Các league ID được gộp thành 1 league ảo trong DB
    private const LEAGUE_GROUP_MAP = [
        25463 => ['id' => 99999, 'name' => 'World Cup Qualifier', 'logo' => self::WCQ_LOGO],
        26314 => ['id' => 99999, 'name' => 'World Cup Qualifier', 'logo' => self::WCQ_LOGO],
        27165 => ['id' => 99999, 'name' => 'World Cup Qualifier', 'logo' => self::WCQ_LOGO],
        28016 => ['id' => 99999, 'name' => 'World Cup Qualifier', 'logo' => self::WCQ_LOGO],
        28867 => ['id' => 99999, 'name' => 'World Cup Qualifier', 'logo' => self::WCQ_LOGO],
        29718 => ['id' => 99999, 'name' => 'World Cup Qualifier', 'logo' => self::WCQ_LOGO],
        32271 => ['id' => 99999, 'name' => 'World Cup Qualifier', 'logo' => self::WCQ_LOGO],
    ];

    // Highlightly gộp giao hữu ĐTQG + CLB chung 1 ID → tách ra theo team type
    private const FRIENDLIES_HIGHLIGHTLY_ID    = 9294;
    private const INTL_FRIENDLIES_VIRTUAL_ID   = 9294;   // giữ nguyên highlightly_id cũ
    private const CLUB_FRIENDLIES_VIRTUAL_ID   = 99998;

    private string $apiKey;

    // Đọc từ header x-ratelimit-* của response gần nhất
    public ?int $quotaRemaining = null;
    public ?int $quotaLimit     = null;

    // Dừng trước khi chạm 0 để cron vẫn còn ngân sách chạy tiếp
    public int $quotaFloor = 100;

    public function __construct()
    {
        $this->apiKey = config('services.highlightly.key');
    }

    private function readQuotaHeaders(array $headers): void
    {
        foreach ($headers as $h) {
            if (preg_match('/^x-ratelimit-requests-remaining:\s*(\d+)/i', $h, $m)) {
                $this->quotaRemaining = (int) $m[1];
            } elseif (preg_match('/^x-ratelimit-requests-limit:\s*(\d+)/i', $h, $m)) {
                $this->quotaLimit = (int) $m[1];
            }
        }
    }

    private function get(string $endpoint, array $params = []): ?array
    {
        $url = $this->baseUrl . $endpoint;
        if ($params) $url .= '?' . http_build_query($params);

        $ctx = stream_context_create([
            'http' => [
                'header'  => "x-rapidapi-key: {$this->apiKey}\r\n",
                'timeout' => 30,
            ]
        ]);

        $res = @file_get_contents($url, false, $ctx);

        // $http_response_header do file_get_contents set, có cả khi HTTP 4xx/5xx
        $headers = $http_response_header ?? [];
        $this->readQuotaHeaders($headers);

        if (!$res) {
            $status = $headers[0] ?? 'no response';

            // 429 = hết quota. Phải ném để backfill dừng hẳn thay vì chạy tiếp
            // hàng trăm ngày với kết quả rỗng.
            if (str_contains($status, '429')) {
                throw new HighlightlyQuotaException($this->quotaRemaining, $this->quotaLimit);
            }

            Log::warning('Highlightly: request failed', ['endpoint' => $endpoint, 'status' => $status]);
            return null;
        }

        return json_decode($res, true);
    }

    // Mỗi endpoint có trần `limit` riêng — vượt trần thì API trả HTTP 400.
    private const PAGE_LIMITS = [
        '/matches'    => 100,
        '/highlights' => 40,
        '/leagues'    => 100,
    ];

    // ─── Lấy hết các trang của một endpoint ──────────────────
    // API trả tối đa PAGE_LIMITS[endpoint] rows/request kèm pagination.totalCount.
    // Không phân trang thì các league lớn nằm ngoài trang đầu bị bỏ sót.
    private function getAll(string $endpoint, array $params = [], int $maxPages = 15): array
    {
        $rows  = [];
        $limit = self::PAGE_LIMITS[$endpoint] ?? 100;

        for ($page = 0; $page < $maxPages; $page++) {
            // Chặn trước khi gửi: quota còn dưới sàn thì dừng, đừng để cron chết theo
            if ($this->quotaRemaining !== null && $this->quotaRemaining <= $this->quotaFloor) {
                throw new HighlightlyQuotaException($this->quotaRemaining, $this->quotaLimit);
            }

            $res   = $this->get($endpoint, $params + ['limit' => $limit, 'offset' => $page * $limit]);
            $batch = $res['data'] ?? [];
            if (!$batch) break;

            $rows = array_merge($rows, $batch);

            $total = $res['pagination']['totalCount'] ?? null;
            if (count($batch) < $limit) break;
            if ($total !== null && count($rows) >= $total) break;

            sleep(1);
        }

        return $rows;
    }

    // ─── Sync một ngày: /matches + /highlights trong 1 lần lặp ──
    // Thay cho syncMatches() + syncHighlights() riêng lẻ.
    // Hai endpoint trả về cùng league filter nên gọi liên tiếp cho 1 date.
    public function syncDate(string $date): array
    {
        $matchCount = 0;
        $thumbCount = 0;

        // Bước A: lấy matches theo ngày — chỉ lưu khi finished + có score
        foreach ($this->getAll('/matches', ['date' => $date]) as $m) {
            if (!in_array($m['league']['id'] ?? null, self::TOP_LEAGUE_IDS, true)) continue;
            if ($this->mapStatus($m['state']['description'] ?? null) !== 'finished') continue;
            if (!$this->hasScore($m)) continue;
            if ($this->upsertMatch($m)) $matchCount++;
        }

        sleep(1);

        // Bước B: lấy thumbnail từ /highlights.
        //
        // Trước đây bước này lọc `state.description` + score rồi mới upsert match,
        // nhưng object `match` của /highlights KHÔNG có hai field đó (chỉ có id,
        // date, round, country, teams, league) — nên mọi highlight đều bị loại và
        // bước B chưa bao giờ làm được gì. Giờ chỉ gắn thumbnail vào match đã được
        // bước A xác thực, không tạo match mới từ payload thiếu dữ liệu.
        foreach ($this->getAll('/highlights', ['date' => $date]) as $h) {
            $md = $h['match'] ?? null;
            if (!$md || empty($h['imgUrl'])) continue;
            if (!in_array($md['league']['id'] ?? null, self::TOP_LEAGUE_IDS, true)) continue;

            // 'goal-clip' / 'other' là clip lẻ — ảnh không đại diện cho trận
            if (($h['category'] ?? null) !== 'match-highlights') continue;

            $match = FootballMatch::where('highlightly_id', $md['id'])->first();
            if (!$match) continue;

            // Chỉ set lần đầu; getRawOriginal() để không đụng accessor
            if ($match->getRawOriginal('thumbnail_url')) continue;

            $match->update(['thumbnail_url' => $h['imgUrl']]);
            $thumbCount++;
        }

        Log::info("Highlightly: syncDate {$date} → {$matchCount} matches, {$thumbCount} thumbnails");
        return ['matches' => $matchCount, 'thumbnails' => $thumbCount];
    }

    // Debug: tìm tên thật của league trên Highlightly khi slug không khớp
    // danh sách menu (vd đổi tên thương mại, viết khác đi).
    public function searchLeagueNames(string $keyword): array
    {
        $keyword = mb_strtolower($keyword);
        return collect($this->getAll('/leagues', [], maxPages: 50))
            ->filter(fn ($l) => str_contains(mb_strtolower($l['name'] ?? ''), $keyword))
            ->map(fn ($l) => ['id' => $l['id'] ?? null, 'name' => $l['name'] ?? null])
            ->values()
            ->all();
    }

    // ─── Nạp toàn bộ danh sách league từ Highlightly, không phụ thuộc match ───
    // Bình thường League chỉ được tạo khi có match đi qua syncDate() (bước A ở
    // trên) — nên giải đấu không có trận trong khoảng ngày crawl (vd Euro giữa
    // 2 kỳ) sẽ không tồn tại trong DB, trang /league/{slug} bị 404. Gọi thẳng
    // /leagues để có League row (tên, logo, highlightly_id) trước, không cần chờ
    // match.
    //
    // KHÔNG dùng upsertLeague() (khớp theo highlightly_id): endpoint /leagues
    // trả id "master" khác với id gắn trong object league của /matches, /highlights
    // — league đã có sẵn (tạo qua match) sẽ bị coi là league mới, đụng slug unique.
    // Nên khớp theo slug: đã có thì bỏ qua, chỉ tạo league thực sự còn thiếu.
    public function syncAllLeagues(): int
    {
        $count = 0;
        foreach ($this->getAll('/leagues', [], maxPages: 50) as $l) {
            if (empty($l['name'])) continue;
            $name = self::LEAGUE_NAME_OVERRIDES[$l['id'] ?? null] ?? $l['name'];
            $slug = Str::slug($name);

            // League đã có sẵn theo id (vd tạo từ trước với tên chung chung của
            // Highlightly) nhưng chưa khớp override → sửa tên/slug luôn.
            $byId = isset($l['id']) ? League::where('highlightly_id', $l['id'])->first() : null;
            if ($byId) {
                if ($byId->slug !== $slug) $byId->update(['name' => $name, 'slug' => $slug]);
                continue;
            }

            if (League::where('slug', $slug)->exists()) continue;

            try {
                League::create([
                    'name'           => $name,
                    'slug'           => $slug,
                    'logo_path'      => $l['logo'] ?? null,
                    'highlightly_id' => $l['id'] ?? null,
                ]);
                $count++;
            } catch (\Illuminate\Database\QueryException $e) {
                // vd highlightly_id trùng với 1 league đã tồn tại dưới slug khác
                Log::warning('syncAllLeagues: skip league lỗi insert', ['name' => $l['name'], 'error' => $e->getMessage()]);
            }
        }
        return $count;
    }

    // ─── Sync venue + events cho finished matches chưa được detail ───
    // Retry nếu score vẫn NULL dù đã sync (API chậm hơn trận kết thúc).
    public function syncFinishedMatchDetails(int $limit = 30): int
    {
        $matches = FootballMatch::where('match_status', 'finished')
            ->whereNotNull('highlightly_id')
            ->where(function ($q) {
                $q->whereNull('details_synced_at')
                  ->orWhere(function ($q) {
                      // Re-sync nếu score vẫn NULL và trận < 7 ngày trước
                      $q->whereNull('home_score')
                        ->where('match_date', '>=', now()->subDays(7));
                  });
            })
            ->limit($limit)
            ->get();

        $count = 0;
        foreach ($matches as $match) {
            // Một match lỗi không được kéo sập cả batch: trước đây payload dị
            // dạng của Highlightly làm văng giữa vòng lặp, những match phía sau
            // không bao giờ được sync.
            try {
                $result = $this->syncMatchDetails($match->id, $match->highlightly_id);
                if ($result['events'] > 0 || $result['venue'] || $result['score']) $count++;
            } catch (HighlightlyQuotaException $e) {
                throw $e; // hết quota là lỗi toàn cục — phải dừng, không nuốt
            } catch (\Throwable $e) {
                Log::warning('Highlightly: syncMatchDetails thất bại', [
                    'match_id'       => $match->id,
                    'highlightly_id' => $match->highlightly_id,
                    'error'          => $e->getMessage(),
                ]);
            }
            sleep(1);
        }

        Log::info("Highlightly: synced details for {$count} matches");
        return $count;
    }

    // ─── Sync detail một match cụ thể ────────────────────────────
    public function syncMatchDetails(int $matchId, int $highlightlyMatchId): array
    {
        $res    = $this->get("/matches/{$highlightlyMatchId}");
        $detail = $res[0] ?? null;
        if (!$detail) return ['venue' => false, 'events' => 0, 'score' => false];

        $match = FootballMatch::with(['homeTeam', 'awayTeam'])->find($matchId);
        if (!$match) return ['venue' => false, 'events' => 0, 'score' => false];

        $updates = [];
        $scoreUpdated = false;

        // Chỉ update field nếu còn trống — không ghi đè data hiện có
        if (!$match->venue) {
            $venueName = $detail['venue']['name'] ?? null;
            if ($venueName) $updates['venue'] = $venueName;
        }

        if (blank($match->home_score)) {
            $scoreStr = $detail['state']['score']['current'] ?? null;
            if ($scoreStr && preg_match('/(\d+)\s*-\s*(\d+)/', $scoreStr, $sm)) {
                $updates['home_score'] = (int) $sm[1];
                $updates['away_score'] = (int) $sm[2];
                $scoreUpdated = true;
            }
        } else {
            $scoreUpdated = true; // score đã có từ trước
        }

        if (!$match->score_penalties) {
            $penalties = $detail['state']['score']['penalties'] ?? null;
            if ($penalties) $updates['score_penalties'] = $penalties;
        }

        if (!$match->referee) {
            $refereeName = $detail['referee']['name'] ?? null;
            if ($refereeName) $updates['referee'] = $refereeName;
        }

        if (!$match->statistics) {
            $statistics = $detail['statistics'] ?? null;
            if ($statistics) $updates['statistics'] = $statistics;
        }

        // Chỉ đánh dấu synced khi đã có score — tránh lock match khỏi retry
        if ($scoreUpdated) {
            $updates['details_synced_at'] = now();
        }

        if ($updates) $match->update($updates);
        $venueUpdated = isset($updates['venue']);

        $eventsCount = $this->saveEvents($match, $detail['events'] ?? []);

        return ['venue' => $venueUpdated, 'events' => $eventsCount, 'score' => $scoreUpdated];
    }

    private const EVENT_TYPE_MAP = [
        'Goal'            => 'goal',
        'Own Goal'        => 'own_goal',
        'Yellow Card'     => 'yellow_card',
        'Red Card'        => 'red_card',
        'Yellow Red Card' => 'yellow_red_card',
        'Substitution'    => 'subst',
        'Penalty'         => 'penalty',
    ];

    // ─── Lưu events — bỏ qua nếu đã có (tránh ghi đè fix thủ công) ──
    private function saveEvents(FootballMatch $match, array $events): int
    {
        if (!$events) return 0;

        // Đã có events → giữ nguyên, không ghi đè
        if ($match->events()->exists()) {
            return $match->events()->count();
        }

        $count = 0;
        foreach ($events as $e) {
            $apiType = $e['type'] ?? '';
            $type    = self::EVENT_TYPE_MAP[$apiType] ?? null;
            if (!$type) continue;

            // Ternary cũ chỉ có 2 nhánh: mọi event không khớp home — kể cả khi
            // payload thiếu hẳn field `team` — đều rơi vào awayTeam, tức bàn
            // thắng bị gán nhầm đội. Không khớp đội nào thì bỏ qua, đừng đoán.
            $teamHighlightlyId = $e['team']['id'] ?? null;
            $team = match (true) {
                $match->homeTeam?->highlightly_id == $teamHighlightlyId => $match->homeTeam,
                $match->awayTeam?->highlightly_id == $teamHighlightlyId => $match->awayTeam,
                default => null,
            };
            if (!$team) {
                Log::warning('Highlightly: event không khớp đội nào, bỏ qua', [
                    'match_id' => $match->id,
                    'team_id'  => $teamHighlightlyId,
                    'type'     => $apiType,
                ]);
                continue;
            }

            MatchEvent::create([
                'match_id'    => $match->id,
                'team_id'     => $team->id,
                'minute'      => (int) ($e['time'] ?? 0),
                'type'        => $type,
                'player_name' => $e['player'] ?? '',
                'assist_name' => $e['assist'] ?? $e['substituted'] ?? null,
            ]);
            $count++;
        }

        Log::info("Highlightly: saved {$count} events for match {$match->id}");
        return $count;
    }

    // ─── Private helpers ──────────────────────────────────────
    private function upsertMatch(array $m): ?FootballMatch
    {
        try {
            $homeTeam = $this->upsertTeam($m['homeTeam'] ?? null);
            $awayTeam = $this->upsertTeam($m['awayTeam'] ?? null);
            if (!$homeTeam || !$awayTeam) return null;

            $leagueData = $m['league'] ?? null;
            $leagueId   = $leagueData['id'] ?? null;
            if ($leagueId && isset(self::LEAGUE_GROUP_MAP[$leagueId])) {
                $leagueData = self::LEAGUE_GROUP_MAP[$leagueId];
            } elseif ($leagueId === self::FRIENDLIES_HIGHLIGHTLY_ID) {
                $logo = $leagueData['logo'] ?? null;
                $isNational = $homeTeam->type === 'national' && $awayTeam->type === 'national';
                if ($isNational) {
                    $leagueData = ['id' => self::INTL_FRIENDLIES_VIRTUAL_ID, 'name' => 'International Friendlies', 'logo' => $logo];
                } else {
                    $leagueData = ['id' => self::CLUB_FRIENDLIES_VIRTUAL_ID, 'name' => 'Club Friendlies', 'logo' => $logo];
                }
            }
            $league = $this->upsertLeague($leagueData);
            $date   = substr($m['date'], 0, 10);
            // $m['date'] là datetime đầy đủ dạng "2026-08-27T23:10:00.000Z" (UTC) —
            // match_date chỉ giữ phần NGÀY để lọc theo ngày ở nhiều chỗ khác nhau
            // (sitemap, cleanup, MapHoofootVideosJob...), kick_off_time giữ phần GIỜ
            // riêng để hiển thị "x giờ trước" chính xác thay vì tính từ nửa đêm.
            $kickOffTime = strpos($m['date'], 'T') !== false ? substr($m['date'], 11, 8) : null;
            $slug   = Str::slug("{$homeTeam->slug}-vs-{$awayTeam->slug}-{$date}");

            return FootballMatch::updateOrCreate(
                ['highlightly_id' => $m['id']],
                [
                    'slug'           => $slug,
                    'home_team_id'   => $homeTeam->id,
                    'away_team_id'   => $awayTeam->id,
                    'league_id'      => $league?->id,
                    'home_score'     => $this->parseScore($m, 'home'),
                    'away_score'     => $this->parseScore($m, 'away'),
                    'match_date'     => $date,
                    'kick_off_time'  => $kickOffTime,
                    'round'          => $m['round'] ?? null,
                    'match_status'   => $this->mapStatus($m['state']['description'] ?? null),
                    'highlightly_id' => $m['id'],
                ]
            );
        } catch (\Exception $e) {
            Log::error("Highlightly upsertMatch error: " . $e->getMessage());
            return null;
        }
    }

    private function hasScore(array $m): bool
    {
        if (isset($m['homeScore'])) return true;
        $current = $m['state']['score']['current'] ?? null;
        return $current !== null && str_contains($current, ' - ');
    }

    private function parseScore(array $m, string $side): ?int
    {
        if (isset($m['homeScore'])) {
            return $side === 'home' ? (int) $m['homeScore'] : (int) $m['awayScore'];
        }
        $current = $m['state']['score']['current'] ?? null;
        if (!$current || !str_contains($current, ' - ')) return null;
        [$home, $away] = explode(' - ', $current, 2);
        return $side === 'home' ? (int) trim($home) : (int) trim($away);
    }

    private function mapStatus(?string $description): string
    {
        $live  = ['First Half', 'Second Half', 'Half Time', 'Extra Time', 'Extra Time Half Time', 'Penalty Shootout', 'Break Time'];
        $ended = ['Finished', 'Finished AET', 'Finished AP', 'Finished After Extra Time', 'Finished After Penalties'];

        if (!$description) return 'scheduled';
        if (in_array($description, $ended)) return 'finished';
        if (in_array($description, $live)) return 'live';
        if ($description === 'Not started') return 'scheduled';
        return 'other';
    }

    private function upsertTeam(?array $t): ?Team
    {
        if (!$t) return null;
        return Team::updateOrCreate(
            ['highlightly_id' => $t['id']],
            [
                'name'           => $t['name'],
                'slug'           => Str::slug($t['name']),
                'type'           => Team::guessTypePublic($t['name']),
                'logo_path'      => $t['logo'] ?? null,
                'highlightly_id' => $t['id'],
            ]
        );
    }

    private function upsertLeague(?array $l): ?League
    {
        if (!$l) return null;

        $name   = self::LEAGUE_NAME_OVERRIDES[$l['id']] ?? $l['name'];
        $league = League::where('highlightly_id', $l['id'])->first();

        // Id gắn trong object league của /matches, /highlights có thể khác id
        // "master" ở /leagues (đã gặp với premier-league) — không match theo
        // highlightly_id thì thử theo slug trước khi insert mới, tránh đụng
        // slug unique và crash cả vòng sync.
        if (!$league) {
            $league = League::where('slug', Str::slug($name))->first();
        }

        if ($league) {
            $league->update([
                'name'           => $name,
                'logo_path'      => $l['logo'] ?? null,
                'highlightly_id' => $l['id'],
            ]);
            return $league;
        }

        return League::create([
            'name'           => $name,
            'slug'           => Str::slug($name),
            'logo_path'      => $l['logo'] ?? null,
            'highlightly_id' => $l['id'],
        ]);
    }
}
