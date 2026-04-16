<?php

namespace App\Providers;

use App\Enums\TenantPermission;
use App\Listeners\EnsureTenantFirstAdminAfterStripeWebhook;
use App\Models\Landlord\Tenant;
use App\Services\TenantBillingSyncService;
use App\Support\TenantPermissions;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Cashier\Subscription as CashierSubscription;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(Tenant::class);

        Event::listen(WebhookReceived::class, EnsureTenantFirstAdminAfterStripeWebhook::class);

        $priceToPlan = [];
        foreach (config('billing.stripe_prices', []) as $planKey => $intervals) {
            foreach ($intervals as $priceId) {
                if (filled($priceId)) {
                    $priceToPlan[$priceId] = $planKey;
                }
            }
        }
        Config::set('billing.stripe_price_to_plan', $priceToPlan);

        CashierSubscription::saved(function (CashierSubscription $subscription): void {
            app(TenantBillingSyncService::class)->syncFromSubscription($subscription);
        });

        Authenticate::redirectUsing(function (Request $request) {
            if ($request->getHost() === config('app.central_domain')) {
                return Route::has('central.login') ? route('central.login') : '/login';
            }

            return Route::has('tenant.login') ? route('tenant.login') : '/login';
        });

        Blade::if('tenantcan', function (string $permission): bool {
            if (! auth()->check()) {
                return false;
            }
            $p = TenantPermission::tryFrom($permission);

            return $p !== null && TenantPermissions::allows(auth()->user(), $p);
        });
    }
}
