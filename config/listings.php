<?php

return [
    'photos' => [
        'max_files' => 30,
        'max_size_kb' => 15360,
    ],
    'video' => [
        // Reuse the existing upload ceiling; deployments may lower this per listing.
        'max_size_kb' => (int) env('LISTING_VIDEO_MAX_SIZE_KB', env('PROJECT_VIDEO_MAX_SIZE_KB', 204800)),
        'max_duration_seconds' => 60,
        'mime_types' => ['video/mp4', 'video/webm'],
        'ffprobe_binary' => env('LISTING_FFPROBE_BINARY', 'ffprobe'),
    ],
];
