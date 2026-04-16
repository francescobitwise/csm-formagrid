<?php

namespace App\Providers;

use App\Enums\TenantPermission;
use App\Support\TenantPermissions;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Single-client fork: include "tenant" migrations in default migrate commands
        // until the schema is fully consolidated.
        $this->app->afterResolving(Migrator::class, function (Migrator $migrator): void {
            $migrator->path(database_path('migrations/tenant'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Authenticate::redirectUsing(function (Request $request) {
            return route('tenant.login');
        });

        Blade::if('tenantcan', function (string $permission): bool {
            if (! Auth::check()) {
                return false;
            }
            $p = TenantPermission::tryFrom($permission);

            return $p !== null && TenantPermissions::allows(Auth::user(), $p);
        });
    }
}
