<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Console\Command;

final class RepairTenantLandlordMetadataCommand extends Command
{
    protected $signature = 'tenant:repair-landlord-metadata
        {tenant_id : ID tenant (es. bitwise)}
        {--plan= : basic|pro|enterprise — se omesso usa il default da config}
        {--company= : Nome organizzazione — se omesso usa il valore attuale o l\'id tenant}';

    protected $description = 'Ripristina plan, company_name e limits nel record landlord se il JSON `data` è incompleto';

    public function handle(TenantProvisioningService $provisioning): int
    {
        $id = (string) $this->argument('tenant_id');
        $tenant = Tenant::query()->find($id);
        if ($tenant === null) {
            $this->error("Tenant non trovato: {$id}");

            return self::FAILURE;
        }

        $plan = (string) ($this->option('plan') ?: config('tenant_plans.default', 'pro'));
        if (! array_key_exists($plan, config('tenant_plans.plans', []))) {
            $this->error("Piano non valido: {$plan}");

            return self::FAILURE;
        }

        $tenant = $tenant->fresh();
        $company = (string) ($this->option('company') ?: $tenant->company_name ?: $id);

        $provisioning->ensureLandlordTenantPayload(
            $tenant,
            $plan,
            $company,
            is_string($tenant->billing_email) ? $tenant->billing_email : null,
        );

        $this->info("Tenant {$id}: metadati landlord consolidati.");

        return self::SUCCESS;
    }
}
