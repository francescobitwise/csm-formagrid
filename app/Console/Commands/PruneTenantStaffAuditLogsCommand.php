<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Models\Tenant\StaffAuditLog;
use Illuminate\Console\Command;

final class PruneTenantStaffAuditLogsCommand extends Command
{
    protected $signature = 'tenants:prune-staff-audit-logs
                            {--days= : Giorni di retention (override rispetto a config)}';

    protected $description = 'Elimina dal registro attività staff le righe più vecchie della retention (ogni DB tenant).';

    public function handle(): int
    {
        $days = $this->option('days');
        $retentionDays = is_string($days) && $days !== '' ? (int) $days : (int) config('audit.staff_log_retention_days', 365);

        if ($retentionDays <= 0) {
            $this->warn('Retention disattivata (staff_log_retention_days ≤ 0). Nessuna eliminazione.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($retentionDays);
        $tenantsProcessed = 0;
        $rowsDeleted = 0;

        foreach (Tenant::query()->cursor() as $tenant) {
            try {
                $deleted = 0;
                $tenant->run(function () use ($cutoff, &$deleted): void {
                    $deleted = StaffAuditLog::query()
                        ->where('created_at', '<', $cutoff)
                        ->delete();
                });
                $rowsDeleted += $deleted;
                $tenantsProcessed++;
                if ($deleted > 0) {
                    $this->line("Tenant {$tenant->id}: eliminate {$deleted} righe.");
                }
            } catch (\Throwable $e) {
                $this->warn("Tenant {$tenant->id}: saltato ({$e->getMessage()}).");
            }
        }

        $this->info("Completato. Tenant elaborati: {$tenantsProcessed}, righe eliminate: {$rowsDeleted}, cutoff: {$cutoff->toIso8601String()}.");

        return self::SUCCESS;
    }
}
