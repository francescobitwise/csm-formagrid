<?php

declare(strict_types=1);

/**
 * Piani SaaS per tenant (landlord). Salvati in `tenants.data.limits` al provisioning.
 *
 * - courses: max corsi; -1 = illimitato.
 * - learners_max: max utenti con ruolo learner “attivi” (account); -1 = illimitato.
 * - storage_gb: quota storage media indicativa (GB).
 * - custom_domain: se true, il tenant può aggiungere un dominio proprio (oltre al sottodominio *.central).
 *
 * Prezzi pubblici (EUR, IVA esclusa se applicabile): mensile e annuale fatturato anticipato.
 */
return [

    'default' => env('TENANT_DEFAULT_PLAN', 'pro'),

    'plans' => [
        'basic' => [
            'label' => 'Basic',
            'price_monthly_eur' => 59,
            'price_yearly_eur' => 590,
            'courses' => 10,
            'learners_max' => 50,
            'storage_gb' => 5,
            'custom_domain' => false,
            'trial_days' => 14,
            'contact_only' => false,
        ],
        'pro' => [
            'label' => 'Pro',
            'price_monthly_eur' => 199,
            'price_yearly_eur' => 1990,
            'courses' => 100,
            'learners_max' => 500,
            'storage_gb' => 50,
            'custom_domain' => true,
            'trial_days' => 0,
            'contact_only' => false,
        ],
        'enterprise' => [
            'label' => 'Enterprise',
            'price_monthly_eur' => 0,
            'price_yearly_eur' => 0,
            'courses' => -1,
            'learners_max' => -1,
            'storage_gb' => 500,
            'custom_domain' => true,
            'trial_days' => 0,
            'contact_only' => true,
        ],
    ],
];
