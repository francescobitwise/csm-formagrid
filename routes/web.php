<?php

use App\Http\Controllers\Central\CentralAuthController;
use App\Http\Controllers\Central\CentralDashboardController;
use App\Http\Controllers\Central\CentralTenantsController;
use App\Http\Controllers\Central\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::domain(config('app.central_domain'))->group(function () {

    Route::get('/', [CentralDashboardController::class, 'index'])->name('central.home');
    Route::view('/privacy', 'central.legal.privacy')->name('central.legal.privacy');
    Route::view('/cookie', 'central.legal.cookies')->name('central.legal.cookies');
    Route::view('/termini', 'central.legal.terms')->name('central.legal.terms');
    Route::view('/dpa', 'central.legal.dpa')->name('central.legal.dpa');
    Route::view('/sub-responsabili', 'central.legal.subprocessors')->name('central.legal.subprocessors');
    Route::view('/register', 'central.register')->name('central.register');
    Route::post('/register', [RegistrationController::class, 'store'])->name('central.register.store');
    Route::get('/register/checkout-success', [RegistrationController::class, 'checkoutSuccess'])->name('central.register.checkout-success');

    Route::middleware('guest:central')->group(function () {
        Route::get('/login', [CentralAuthController::class, 'create'])->name('central.login');
        Route::post('/login', [CentralAuthController::class, 'store'])
            ->middleware('throttle:12,1')
            ->name('central.login.store');
    });

    Route::middleware('auth:central')->group(function () {
        Route::get('/dashboard', [CentralDashboardController::class, 'dashboard'])->name('central.dashboard');
        Route::get('/tenants', [CentralTenantsController::class, 'index'])->name('central.tenants.index');
        Route::get('/tenants/{tenant}/invoices', [CentralTenantsController::class, 'invoices'])->name('central.tenants.invoices');
        Route::get('/tenants/{tenant}/invoices/{invoice}/pdf', [CentralTenantsController::class, 'downloadInvoice'])
            ->where('invoice', 'in_[a-zA-Z0-9]+')
            ->middleware('throttle:60,1')
            ->name('central.tenants.invoices.pdf');
        Route::post('/tenants/{tenant}/plan', [CentralTenantsController::class, 'updatePlan'])->name('central.tenants.update-plan');
        Route::post('/tenants/{tenant}/billing-portal', [CentralTenantsController::class, 'billingPortal'])->name('central.tenants.billing-portal');
        Route::post('/tenants/{tenant}/saas-login', [CentralTenantsController::class, 'saasLogin'])
            ->middleware('throttle:30,1')
            ->name('central.tenants.saas-login');
        Route::post('/logout', [CentralAuthController::class, 'destroy'])->name('central.logout');
    });
});
