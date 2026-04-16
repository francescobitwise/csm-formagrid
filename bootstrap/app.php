<?php

use App\Http\Middleware\EnsureTenantPermission;
use App\Http\Middleware\EnsureTenantStaff;
use App\Http\Middleware\EnsureTenantSubscriptionActive;
use App\Http\Middleware\RedirectIfTenantMustChangePassword;
use App\Http\Middleware\RoleMiddleware;
use App\Support\TenantPermissions;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('tenants:prune-staff-audit-logs')->dailyAt('03:15');
        $schedule->command('backup:database-to-s3')
            ->dailyAt('02:00')
            ->when(fn (): bool => (bool) config('backup.database_enabled'))
            ->withoutOverlapping(180);
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'guest' => RedirectIfAuthenticated::class,
            'role' => RoleMiddleware::class,
            'tenant.staff' => EnsureTenantStaff::class,
            'tenant.permission' => EnsureTenantPermission::class,
            'tenant.must_change_password' => RedirectIfTenantMustChangePassword::class,
            'tenant.billing.subscription' => EnsureTenantSubscriptionActive::class,
        ]);

        $middleware->redirectUsersTo(function () {
            if (request()->getHost() === config('app.central_domain')) {
                return Route::has('central.dashboard') ? route('central.dashboard') : '/';
            }

            $user = auth()->user();
            if ($user === null) {
                return Route::has('tenant.login') ? route('tenant.login') : '/login';
            }
            if (TenantPermissions::staff($user) && Route::has('tenant.admin.dashboard')) {
                return route('tenant.admin.dashboard');
            }

            return Route::has('tenant.dashboard') ? route('tenant.dashboard') : '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
