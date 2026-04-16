<?php

declare(strict_types=1);

/**
 * Billing SaaS: Stripe Price ID per piano (Dashboard Stripe → Products → Prices).
 * Mappare ogni price_id al piano interno per sincronizzare `tenants.data.plan` e `limits`.
 */
return [

    'enforce_subscription' => env('BILLING_ENFORCE_SUBSCRIPTION', false),

    'require_checkout_on_register' => env('BILLING_REQUIRE_CHECKOUT_ON_REGISTER', true),

    /*
    | Rotte tenant (nome route) accessibili senza abbonamento attivo quando enforce è true.
    | Tipicamente dashboard + profilo così l’admin vede il messaggio e può uscire.
    */
    'middleware_excluded_route_names' => [
        'tenant.admin.dashboard',
        'tenant.admin.profile.edit',
        'tenant.admin.profile.update',
        'tenant.admin.profile.logo.update',
        'tenant.admin.billing.invoices',
        'tenant.admin.billing.invoices.pdf',
    ],

    'stripe_prices' => [
        'basic' => [
            'monthly' => env('STRIPE_PRICE_BASIC_MONTHLY'),
            'yearly' => env('STRIPE_PRICE_BASIC_YEARLY'),
        ],
        'pro' => [
            'monthly' => env('STRIPE_PRICE_PRO_MONTHLY'),
            'yearly' => env('STRIPE_PRICE_PRO_YEARLY'),
        ],
        'enterprise' => [
            'monthly' => env('STRIPE_PRICE_ENTERPRISE_MONTHLY'),
            'yearly' => env('STRIPE_PRICE_ENTERPRISE_YEARLY'),
        ],
    ],

    /*
    | Price ID Stripe → chiave piano (basic|pro|enterprise). Generato automaticamente in AppServiceProvider.
    */
    'stripe_price_to_plan' => [],

];
