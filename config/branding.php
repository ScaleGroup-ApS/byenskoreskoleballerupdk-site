<?php

return [

    'name' => env('BRAND_NAME', config('app.name')),

    'logo_path' => env('BRAND_LOGO_PATH'),

    'colors' => [
        'primary' => env('BRAND_COLOR_PRIMARY'),
        'sidebar' => env('BRAND_COLOR_SIDEBAR'),
        'accent' => env('BRAND_COLOR_ACCENT'),
    ],

];
