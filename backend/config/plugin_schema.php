<?php
// config/plugin_schema.php

return [
    'required' => [
        'name',
        'slug',
        'version',
        'author',
        'description',
    ],
    'optional' => [
        'requires',
        'settings',
        'hooks',
        'routes',
    ],
];
