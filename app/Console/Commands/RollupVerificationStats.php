<?php

namespace App\Console\Commands;

use App\Models\RiskAlert;
use App\Models\VerificationDailyStat;
use App\Models\VerificationLog;
use Illuminate\Console\Command;

class RollupVerificationStats extends Command
{
    protected $signature = 'anti-counterfeit:rollup {--days=14 : Number of days back to (re)build}';

    protected $description = 'Rebuild daily verification rollups so dashboards avoid scanning raw logs.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $from = now()->subDays($days - 1)->startOfDay();

        // One grouped scan over the recent window (bounded by the created_at index).
        $logRows = VerificationLog::selectRaw(
            'DATE(created_at) d,
             COUNT(*) total,
             SUM(result = "genuine") genuine,
             SUM(result = "suspicious") suspicious,
             SUM(result IN ("invalid","expired","recalled","locked","blocked")) invalid'
        )->where('created_at', '>=', $from)->groupBy('d')->get()->keyBy('d');

        $alertRows = RiskAlert::selectRaw('DATE(created_at) d, COUNT(*) c')
            ->where('created_at', '>=', $from)->groupBy('d')->pluck('c', 'd');

        $built = 0;
        for ($i = 0; $i < $days; $i++) {
            $day = now()->subDays($i)->toDateString();
            $r   = $logRows->get($day);

            VerificationDailyStat::updateOrCreate(['day' => $day], [
                'total'      => (int) ($r->total ?? 0),
                'genuine'    => (int) ($r->genuine ?? 0),
                'suspicious' => (int) ($r->suspicious ?? 0),
                'invalid'    => (int) ($r->invalid ?? 0),
                'alerts'     => (int) ($alertRows[$day] ?? 0),
            ]);
            $built++;
        }

        $this->info("Rebuilt {$built} day(s) of verification rollups.");
        return self::SUCCESS;
    }
}
