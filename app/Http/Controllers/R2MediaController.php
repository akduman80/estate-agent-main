<?php

namespace App\Http\Controllers;

use App\Services\R2Connection;
use App\Storage\R2MediaAdapter;
use App\Storage\R2MediaFilesystem;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class R2MediaController
{
    public function __invoke(Request $request, string $path)
    {
        abort_unless(config('cloudflare_r2.enabled') && R2MediaAdapter::eligible($path), 404);
        $disk = config('filesystems.disks.r2_public');
        try {
            R2Connection::validate($disk, (string) config('cloudflare_r2.account_id'));
            $client = app(\Aws\S3\S3Client::class);
            $arguments = ['Bucket' => $disk['bucket'], 'Key' => 'zydemlak/'.$path, '@http' => ['stream' => true]];
            if ($range = $request->header('Range')) {
                abort_unless(preg_match('/^bytes=(?:[0-9]+-[0-9]*|-[0-9]+)$/D', $range), 416);
                $arguments['Range'] = $range;
            }
            $object = $client->getObject($arguments);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable) {
            abort(404, 'Medya dosyasına erişilemiyor.');
        }
        $body = $object['Body'];
        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
            'mp4', 'm4v' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime',
            'ts' => 'video/mp2t', 'vtt' => 'text/vtt', 'm3u8' => 'application/vnd.apple.mpegurl',
            default => 'application/octet-stream',
        };
        $headers = ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=300', 'Accept-Ranges' => 'bytes'];
        if (str_ends_with($path, '.m3u8')) {
            abort_if((int) $object['ContentLength'] > 262144 || $request->hasHeader('Range'), 416);
            $manifest = (string) $body;
            $lines = explode("\n", $manifest);
            foreach ($lines as &$line) {
                $line = rtrim($line, "\r");
                if ($line !== '' && $line[0] !== '#') {
                    abort_unless(preg_match('/^[a-zA-Z0-9_-]+\.(?:m3u8|ts)$/D', $line), 404);
                    $line = R2MediaFilesystem::mediaUrl(dirname($path).'/'.$line);
                }
            }

            return response(implode("\n", $lines), 200, $headers);
        }
        $headers['Content-Length'] = (string) $object['ContentLength'];
        if (isset($object['ContentRange'])) {
            $headers['Content-Range'] = $object['ContentRange'];
        }

        return response()->stream(static function () use ($body): void {
            try {
                while (! $body->eof()) {
                    echo $body->read(65536);
                }
            } finally {
                $body->close();
            }
        }, isset($object['ContentRange']) ? 206 : 200, $headers);
    }
}
