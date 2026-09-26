<?php

return [
    'video' => [
        'max_size_kb' => (int) env('PROJECT_VIDEO_MAX_SIZE_KB', 204800),
        'mime_types' => ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-m4v', 'video/3gpp', 'video/3gpp2'],
        'mime_type_map' => [
            'mp4' => 'video/mp4',
            'm4v' => 'video/x-m4v',
            'mov' => 'video/quicktime',
            'webm' => 'video/webm',
            '3gp' => 'video/3gpp',
            '3gpp' => 'video/3gpp',
            '3g2' => 'video/3gpp2',
            '3gpp2' => 'video/3gpp2',
        ],
    ],
];
