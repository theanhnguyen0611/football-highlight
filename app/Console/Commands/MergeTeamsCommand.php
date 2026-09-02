<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeTeamsCommand extends Command
{
    // Team trùng thường xảy ra khi upsertTeam() tạo record mới vì slug/tên đội
    // lệch giữa các lần Highlightly trả về (vd "Inter" vs "Inter Milan"). Lệnh
    // này gộp 2 team_id thành 1: chuyển hết matches/match_events/team_translations
    // trỏ sang team giữ lại, rồi xoá team thừa.
    protected $signature = 'teams:merge
                            {keep : ID của team giữ lại}
                            {remove : ID của team sẽ bị xoá, dữ liệu chuyển hết sang --keep}
                            {--dry-run : Chỉ xem trước, không thay đổi gì}';

    protected $description = 'Gộp 2 team trùng nhau (cùng 1 đội nhưng bị tạo 2 record)';

    public function handle(): void
    {
        $keepId   = (int) $this->argument('keep');
        $removeId = (int) $this->argument('remove');
        $dryRun   = (bool) $this->option('dry-run');

        if ($keepId === $removeId) {
            $this->error('keep và remove phải khác nhau.');
            return;
        }

        $keep   = Team::find($keepId);
        $remove = Team::find($removeId);

        if (!$keep || !$remove) {
            $this->error('Không tìm thấy team với id đã cho.');
            return;
        }

        $this->info("Giữ:  #{$keep->id} {$keep->name} (slug={$keep->slug})");
        $this->info("Xoá:  #{$remove->id} {$remove->name} (slug={$remove->slug})");

        $homeCount  = DB::table('matches')->where('home_team_id', $removeId)->count();
        $awayCount  = DB::table('matches')->where('away_team_id', $removeId)->count();
        $eventCount = DB::table('match_events')->where('team_id', $removeId)->count();
        $transCount = DB::table('team_translations')->where('team_id', $removeId)->count();

        $this->line("  matches.home_team_id: {$homeCount}");
        $this->line("  matches.away_team_id: {$awayCount}");
        $this->line("  match_events.team_id: {$eventCount}");
        $this->line("  team_translations: {$transCount}");

        if ($dryRun) {
            $this->warn('[DRY RUN] Không thay đổi gì. Bỏ --dry-run để thực hiện.');
            return;
        }

        DB::transaction(function () use ($keepId, $removeId) {
            DB::table('matches')->where('home_team_id', $removeId)->update(['home_team_id' => $keepId]);
            DB::table('matches')->where('away_team_id', $removeId)->update(['away_team_id' => $keepId]);
            DB::table('match_events')->where('team_id', $removeId)->update(['team_id' => $keepId]);

            // team_translations có unique(team_id, locale) — nếu team giữ lại đã
            // có bản dịch cho locale đó thì xoá bản của team thừa, không update đè
            // (update sẽ vi phạm unique constraint).
            $translations = DB::table('team_translations')->where('team_id', $removeId)->get();
            foreach ($translations as $t) {
                $exists = DB::table('team_translations')
                    ->where('team_id', $keepId)
                    ->where('locale', $t->locale)
                    ->exists();

                if ($exists) {
                    DB::table('team_translations')->where('id', $t->id)->delete();
                } else {
                    DB::table('team_translations')->where('id', $t->id)->update(['team_id' => $keepId]);
                }
            }

            Team::destroy($removeId);
        });

        $this->info('Gộp xong.');
    }
}
