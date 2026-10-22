<?php

return [
    // Set to '*' only behind the deployment platform's reverse proxy.
    'proxies' => env('TRUSTED_PROXIES'),
];
