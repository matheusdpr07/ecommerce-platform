<?php

return [
    'name' => env('STORE_NAME', 'Aurea'),
    'eyebrow' => env('STORE_EYEBROW', 'Curadoria contemporanea'),
    'tagline' => env('STORE_TAGLINE', 'Escolhas que transformam o cotidiano.'),
    'support_email' => env('STORE_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS')),
];
