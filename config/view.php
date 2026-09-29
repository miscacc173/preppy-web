<?php

$compiledPath = env('VIEW_COMPILED_PATH');

return [
    'paths' => [
        resource_path('views'),
    ],

    'compiled' => is_string($compiledPath) && trim($compiledPath) !== ''
        ? $compiledPath
        : storage_path('framework/views'),
];
