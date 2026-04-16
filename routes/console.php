<?php

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Landlord\Tenant;
use App\Models\Tenant\Course;
use App\Models\Tenant\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Stancl\Tenancy\Jobs\DeleteDatabase;
use Stancl\Tenancy\Jobs\MigrateDatabase;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('landlord:tenants', function () {
    $tenants = Tenant::with('domains')->get();

    if ($tenants->isEmpty()) {
        $this->warn('No tenants found.');

        return self::SUCCESS;
    }

    foreach ($tenants as $tenant) {
        $this->line($tenant->id);
        foreach ($tenant->domains as $domain) {
            $this->line('  - '.$domain->domain);
        }
    }

    return self::SUCCESS;
})->purpose('List tenants + domains (landlord DB)');

Artisan::command('tenant:add-domain {tenant_id} {domain}', function () {
    $tenantId = (string) $this->argument('tenant_id');
    $domain = Str::lower(trim((string) $this->argument('domain')));

    $tenant = Tenant::find($tenantId);
    if (! $tenant) {
        $this->error("Tenant not found: {$tenantId}");

        return self::FAILURE;
    }

    $existingCount = $tenant->domains()->count();
    $customAllowed = (bool) data_get($tenant->limits, 'custom_domain', false);

    if ($existingCount >= 1 && ! $customAllowed) {
        $this->error('Piano Basic: è incluso solo il sottodominio. Passa a Pro o Enterprise per dominio personalizzato (o aggiorna `limits.custom_domain` su landlord).');

        return self::FAILURE;
    }

    $tenant->domains()->firstOrCreate(['domain' => $domain]);
    $this->info("Domain added: {$domain}");

    return self::SUCCESS;
})->purpose('Add a domain to an existing tenant');

Artisan::command('tenant:rebuild-db {tenant_id} {--migrate-only : Esegue solo le migrazioni sul DB esistente (nessun drop/create)}', function () {
    $tenantId = (string) $this->argument('tenant_id');
    /** @var Tenant|null $tenant */
    $tenant = Tenant::find($tenantId);

    if (! $tenant) {
        $this->error("Tenant not found: {$tenantId}");

        return self::FAILURE;
    }

    $migrate = new MigrateDatabase($tenant);

    if ($this->option('migrate-only')) {
        app()->call([$migrate, 'handle']);
        $this->info("Migrations run for tenant: {$tenantId}");

        return self::SUCCESS;
    }

    $dbName = $tenant->database()->getName();
    $manager = $tenant->database()->manager();

    if ($manager->databaseExists($dbName)) {
        $this->warn("Database già presente ({$dbName}): eliminazione prima del rebuild.");
        $delete = new DeleteDatabase($tenant);
        app()->call([$delete, 'handle']);
    }

    $create = new CreateDatabase($tenant);
    app()->call([$create, 'handle']);
    app()->call([$migrate, 'handle']);

    $this->info("DB ricreato e migrato per tenant: {$tenantId}");

    return self::SUCCESS;
})->purpose('Elimina (se esiste), crea il DB tenant ed esegue le migrazioni; oppure --migrate-only');

Artisan::command('tenant:make-admin {tenant_id} {email} {password}', function () {
    $tenantId = (string) $this->argument('tenant_id');
    $email = (string) $this->argument('email');
    $password = (string) $this->argument('password');

    /** @var Tenant|null $tenant */
    $tenant = Tenant::find($tenantId);
    if (! $tenant) {
        $this->error("Tenant not found: {$tenantId}");

        return self::FAILURE;
    }

    $tenant->run(function () use ($email, $password) {
        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'password' => Hash::make($password),
                'role' => UserRole::Admin,
                'must_change_password' => false,
            ],
        );
    });

    $this->info("Admin created/updated: {$email}");

    return self::SUCCESS;
})->purpose('Create an admin user in a tenant DB');

Artisan::command('tenant:debug-user {tenant_id} {email}', function () {
    $tenantId = (string) $this->argument('tenant_id');
    $email = (string) $this->argument('email');

    /** @var Tenant|null $tenant */
    $tenant = Tenant::find($tenantId);
    if (! $tenant) {
        $this->error("Tenant not found: {$tenantId}");

        return self::FAILURE;
    }

    $tenant->run(function () use ($email) {
        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->warn('User not found in tenant DB.');

            return;
        }

        $this->line('Found user in tenant DB:');
        $this->line('  id: '.$user->id);
        $this->line('  email: '.$user->email);
        $this->line('  role: '.((string) ($user->role?->value ?? $user->role)));
        $this->line('  password_hash_prefix: '.substr((string) $user->password, 0, 7));
    });

    return self::SUCCESS;
})->purpose('Debug a tenant user record');

Artisan::command('tenant:seed-demo {tenant_id}', function () {
    $tenantId = (string) $this->argument('tenant_id');
    /** @var Tenant|null $tenant */
    $tenant = Tenant::find($tenantId);

    if (! $tenant) {
        $this->error("Tenant not found: {$tenantId}");

        return self::FAILURE;
    }

    $tenant->run(function () {
        Course::firstOrCreate(
            ['slug' => 'cybersecurity-awareness-2024'],
            [
                'title' => 'Cybersecurity Awareness 2024',
                'status' => CourseStatus::Published,
                'description' => 'Fondamenti di sicurezza per tutti i dipendenti.',
                'total_hours' => 4,
            ],
        );

        Course::firstOrCreate(
            ['slug' => 'onboarding-aziendale'],
            [
                'title' => 'Onboarding Aziendale',
                'status' => CourseStatus::Published,
                'description' => 'Benvenuto: valori, cultura e processi essenziali.',
                'total_hours' => 2,
            ],
        );

        Course::firstOrCreate(
            ['slug' => 'leadership-avanzata'],
            [
                'title' => 'Leadership Avanzata',
                'status' => CourseStatus::Draft,
                'description' => 'Percorso per manager e team leader.',
            ],
        );
    });

    $this->info('Demo courses created (if missing).');

    return self::SUCCESS;
})->purpose('Seed demo courses into a tenant DB');
