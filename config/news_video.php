<?php

return [
    // Dedicated queue: never run an expensive conversion in the HTTP request.
    'enabled' => (bool) env('VIDEO_TRANSCODING_ENABLED', false),
    'max_upload_mb' => (int) env('MAX_VIDEO_UPLOAD_MB', 100),
    'max_duration' => (int) env('MAX_VIDEO_DURATION_SECONDS', 600),
    'max_resolution' => (int) env('MAX_VIDEO_RESOLUTION', 1080),
    'max_output_resolution' => (int) env('VIDEO_MAX_OUTPUT_RESOLUTION', 1080),
    'enable_8k' => (bool) env('VIDEO_8K_ENABLED', false),
    'disk' => env('VIDEO_STORAGE_PROVIDER', 'public'),
    'max_concurrent_jobs' => max(1, (int) env('VIDEO_MAX_CONCURRENT_JOBS', 1)),
    'ffmpeg' => env('VIDEO_FFMPEG_BINARY', 'ffmpeg'),
    'ffprobe' => env('VIDEO_FFPROBE_BINARY', 'ffprobe'),
    'timeout' => 3600,
    'caption_languages' => ['tr' => 'Türkçe', 'en' => 'English'],
    'mime_types' => ['video/mp4', 'video/quicktime', 'video/webm', 'video/x-matroska', 'video/x-m4v'],
    'profiles' => [144 => 160, 240 => 350, 360 => 700, 480 => 1200, 720 => 2500, 1080 => 5000, 1440 => 9000, 2160 => 16000, 4320 => 45000],
];
