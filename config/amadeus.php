<?php

return [
    'client_id' => env('AMADEUS_CLIENT_ID'),
    'client_secret' => env('AMADEUS_CLIENT_SECRET'),
    'base_url' => env('AMADEUS_BASE_URL', 'https://test.api.amadeus.com'),
    'cache_ttl' => env('AMADEUS_CACHE_TTL', 30), // minutes
];
