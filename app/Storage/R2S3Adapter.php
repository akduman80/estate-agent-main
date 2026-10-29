<?php

namespace App\Storage;

use Aws\Exception\MultipartUploadException;
use Aws\S3\MultipartUploader;
use Aws\S3\S3ClientInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Config;
use League\Flysystem\UnableToWriteFile;

class R2S3Adapter extends AwsS3V3Adapter
{
    public function __construct(private S3ClientInterface $sdk, string $bucket)
    {
        parent::__construct($sdk, $bucket, 'zydemlak');
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->uploadSafely($path, $contents, $config, false);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->uploadSafely($path, $contents, $config, true);
    }

    private function uploadSafely(string $path, mixed $contents, Config $config, bool $stream): void
    {
        try {
            if ($stream) {
                parent::writeStream($path, $contents, $config);
            } else {
                parent::write($path, $contents, $config);
            }

            return;
        } catch (\Throwable $error) {
            $multipart = $error;
            while ($multipart && ! $multipart instanceof MultipartUploadException) {
                $multipart = $multipart->getPrevious();
            }
            if (! $multipart) {
                throw UnableToWriteFile::atLocation($path, 'R2 yüklemesi tamamlanamadı.');
            }
        }
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                (new MultipartUploader($this->sdk, $contents, ['state' => $multipart->getState(), 'concurrency' => 2]))->upload();

                return;
            } catch (MultipartUploadException $retry) {
                $multipart = $retry;
            } catch (\Throwable) {
                break;
            }
        }
        try {
            $this->sdk->abortMultipartUpload($multipart->getState()->getId());
        } catch (\Throwable) {
            // No SDK details in logs. A generic failure makes the need for cleanup visible.
            throw UnableToWriteFile::atLocation($path, 'R2 yüklemesi ve yarım kalan parça temizliği tamamlanamadı.');
        }
        throw UnableToWriteFile::atLocation($path, 'R2 yüklemesi tamamlanamadı; yarım kalan parçalar temizlendi.');
    }
}
