<?php

declare(strict_types=1);

/**
 * Contenuti marketing sulla landing centrale (welcome).
 * Modifica testi e card senza toccare il markup della welcome.
 */
return [

    /*
    | (Legacy SaaS) Piano di cui mostrare i giorni di prova in hero.
    | Nel fork single-client non esistono piani/quote; la landing centrale è rimossa.
    */
    'trial_highlight_plan' => env('MARKETING_TRIAL_HIGHLIGHT_PLAN', 'basic'),

    /*
    | Card nella sezione “social proof” (tre colonne sotto l’hero).
    */
    'social_proof_cards' => [
        [
            'title' => 'Attivazione rapida',
            'body' => 'Setup in autonomia: account, piano e spazio dedicato senza call.',
        ],
        [
            'title' => 'Contenuti SCORM e video',
            'body' => 'Caricamento e tracciamento in un unico posto, con report chiari.',
        ],
        [
            'title' => 'Dati isolati',
            'body' => 'Un database per organizzazione: separazione reale, non “solo logica”.',
        ],
    ],

];
