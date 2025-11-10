<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CleanupSecurityLogs extends Command
{
    protected $signature = 'security:cleanup {--days=30 : Number of days to keep logs}';
    protected $description = 'Clean up old security logs and revoked tokens';

    public function handle()
    {
        $days = $this->option('days');
        $timestamp = now()->subDays($days)->timestamp;

        $this->info("Cleaning up security logs older than {$days} days...");

        // Limpa tokens revogados antigos
        $revokedKeys = Redis::keys('revoked:*');
        $countRevoked = 0;
        
        foreach ($revokedKeys as $key) {
            $keyTimestamp = Redis::ttl($key);
            if ($keyTimestamp < 0) {
                Redis::del($key);
                $countRevoked++;
            }
        }

        // Limpa logs de atividade antigos
        $activityKeys = Redis::keys('user_activity:*');
        $countActivities = 0;

        foreach ($activityKeys as $key) {
            // Mantém apenas últimos 30 dias
            Redis::expire($key, 60 * 60 * 24 * 30);
            $countActivities++;
        }

        $this->info("Cleanup completed!");
        $this->info("Removed {$countRevoked} expired revoked tokens");
        $this->info("Updated expiration for {$countActivities} activity logs");

        return Command::SUCCESS;
    }
}