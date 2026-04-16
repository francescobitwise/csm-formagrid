<?php

return [
    // Identifier for storage prefixes, exports, etc.
    'instance_id' => 'single',

    // Public disk path for the organization logo used in UI/PDFs.
    'logo_path' => env('BRANDING_LOGO_PATH', 'brand/logo.png'),

    // Accent color used in PDFs.
    'accent' => env('BRANDING_ACCENT', '#1a6dbf'),
];

