<?php

$s3 = [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'bucket' => env('AWS_BUCKET', 'gestorjob'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'visibility' => 'private',
    'throw' => true,
    'report' => false,
    'options' => [
        'ServerSideEncryption' => 'AES256',
    ],
];

$anexosLocal = env('ANEXOS_DISK', 's3') === 'local';

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'anexos' => $anexosLocal
            ? [
                'driver' => 'local',
                'root' => storage_path('app/private/anexos'),
                'visibility' => 'private',
                'throw' => true,
                'report' => false,
            ]
            : $s3 + ['root' => 'anexos'],

        'backups' => $anexosLocal
            ? [
                'driver' => 'local',
                'root' => storage_path('app/private/backups'),
                'visibility' => 'private',
                'throw' => true,
                'report' => false,
            ]
            : $s3 + ['root' => 'postgres'],

        's3' => $s3,

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
