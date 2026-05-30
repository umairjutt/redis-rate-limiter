<?php

return [
    'default_algorithm' => env('RATE_LIMITER_ALGO', 'sliding'),
    'default_max' => env('RATE_LIMITER_MAX', 60),
    'default_window' => env('RATE_LIMITER_WINDOW', 60),
];
