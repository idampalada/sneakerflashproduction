<?php

return [
    'temporary_file_upload' => [
        'disk' => 'local',
        'rules' => ['required', 'file', 'max:12288'], // 12MB Max
        'directory' => 'livewire-tmp',
        'middleware' => null, // Remove throttle middleware
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
    ],
    'asset_url' => null,
    'app_url' => env('APP_URL', 'https://sneaker.meltedcloud.cloud'),
    'middleware_group' => 'web',
    'manifest_path' => null,
];