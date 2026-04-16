<?php

namespace App\Console\Commands;

use App\Services\TenantProvisioningService;
use Illuminate\Console\Command;

class ProvisionTenant extends Command
{
    protected $signature = 'tenant:provision
        {tenant_id : Tenant id (es. acmecorp)}
        {company_name : Nome azienda}
        {plan=pro : basic|pro|enterprise}';

    protected $description = 'Provisiona un tenant (DB + dominio + migrate tenant)';

    public function handle(TenantProvisioningService $provisioning): int
    {
        $tenant = $provisioning->provision([
            'tenant_id' => (string) $this->argument('tenant_id'),
            'company_name' => (string) $this->argument('company_name'),
            'plan' => (string) $this->argument('plan'),
        ]);

        $this->info('Tenant creato: ' . $tenant->id);
        $this->info('Dominio: ' . ($tenant->domains->first()?->domain ?? '(nessuno)'));

        return self::SUCCESS;
    }
}

