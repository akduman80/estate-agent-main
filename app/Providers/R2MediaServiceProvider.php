<?php

namespace App\Providers;

use App\Services\R2Connection;
use App\Storage\R2MediaAdapter;
use App\Storage\R2MediaFilesystem;
use App\Storage\R2S3Adapter;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class R2MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(S3Client::class, function ($app) {
            $disk = $app['config']['filesystems.disks.r2_public'];
            R2Connection::validate($disk, (string) $app['config']['cloudflare_r2.account_id']);

            return R2Connection::client($disk);
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/r2-media.php'));
        Storage::extend('r2_media', function ($app, array $config) {
            $disk = $app['config']['filesystems.disks.r2_public'];
            R2Connection::validate($disk, (string) $app['config']['cloudflare_r2.account_id']);
            $remote = new R2S3Adapter($app->make(S3Client::class), $disk['bucket']);
            $local = new LocalFilesystemAdapter($config['root']);
            $adapter = new R2MediaAdapter($local, $remote, (bool) $app['config']['cloudflare_r2.writes_enabled']);

            return new R2MediaFilesystem(new Filesystem($adapter, ['visibility' => 'public']), $adapter, $config);
        });
    }
}
