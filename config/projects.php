<?php

return [
    'video' => [
        'max_size_kb' => (int) env('PROJECT_VIDEO_MAX_SIZE_KB', 51200),
        'mime_types' => ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-m4v'],
    ],
];
