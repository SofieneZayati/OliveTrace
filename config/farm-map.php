<?php

return [
    // Browser-loaded tiles only: no proxy, prefetching, geocoding or offline downloads.
    'tile_url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
    'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
    'center' => [34.0, 9.5],
    'zoom' => 6,
    'max_zoom' => 19,
];
