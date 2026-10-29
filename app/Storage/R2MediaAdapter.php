<?php

namespace App\Storage;

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

/** Existing local files win. Only new media in the allowlist is written to R2. */
class R2MediaAdapter implements FilesystemAdapter
{
    public const PREFIXES = ['listings/images/', 'listings/videos/', 'posts/', 'news-video/streams/', 'news-video/captions/'];

    public function __construct(public FilesystemAdapter $local, private FilesystemAdapter $remote, private bool $writeRemote = true) {}

    public static function eligible(string $path): bool
    {
        if (str_contains($path, '..') || str_contains($path, '\\') || str_contains($path, "\0") || str_starts_with($path, '/')) {
            return false;
        }
        foreach (self::PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix) && strlen($path) > strlen($prefix)) {
                return (bool) preg_match('/\.(?:jpe?g|png|webp|mp4|mov|webm|mkv|m4v|3gp|m3u8|ts|vtt)$/iD', $path);
            }
        }

        return false;
    }

    public function isRemote(string $path): bool
    {
        return self::eligible($path) && ! $this->local->fileExists($path);
    }

    private function target(string $path): FilesystemAdapter
    {
        return $this->isRemote($path) ? $this->remote : $this->local;
    }

    private function run(string $path, \Closure $operation): mixed
    {
        try {
            return $operation();
        } catch (\Throwable) {
            // Never chain SDK exceptions: they can contain signed URLs and credentials.
            throw UnableToReadFile::fromLocation($path, 'Medya depolama işlemi tamamlanamadı.');
        }
    }

    private function privateConfig(Config $config): Config
    {
        return new Config(array_merge($config->toArray(), ['visibility' => 'private', 'ACL' => 'private', 'mup_threshold' => 16777216, 'part_size' => 8388608, 'concurrency' => 2]));
    }

    public function fileExists(string $path): bool
    {
        return $this->run($path, fn () => $this->local->fileExists($path) || (self::eligible($path) && $this->remote->fileExists($path)));
    }

    public function directoryExists(string $path): bool
    {
        return $this->local->directoryExists($path) || iterator_count($this->listContents($path, false)) > 0;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->writeStreamOrString($path, $contents, $config, false);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->writeStreamOrString($path, $contents, $config, true);
    }

    private function writeStreamOrString(string $path, mixed $contents, Config $config, bool $stream): void
    {
        try {
            $target = $this->writeRemote ? $this->target($path) : $this->local;
            $options = $target === $this->remote ? $this->privateConfig($config) : $config;
            $method = $stream ? 'writeStream' : 'write';
            $target->$method($path, $contents, $options);
        } catch (\Throwable) {
            throw UnableToWriteFile::atLocation($path, 'Medya yüklenemedi. Tekrar deneyin.');
        }
    }

    public function read(string $path): string
    {
        return $this->run($path, fn () => $this->target($path)->read($path));
    }

    public function readStream(string $path)
    {
        return $this->run($path, fn () => $this->target($path)->readStream($path));
    }

    public function delete(string $path): void
    {
        $this->run($path, fn () => $this->target($path)->delete($path));
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->local->createDirectory($path, $config);
    }

    public function deleteDirectory(string $path): void
    {
        if (trim($path, '/') === '' || str_contains($path, '..')) {
            throw UnableToReadFile::fromLocation($path, 'Toplu kök silme engellendi.');
        }
        // Never bulk-delete a bucket or an unrelated prefix.
        foreach ($this->listContents($path, true) as $entry) {
            if ($entry->isFile()) {
                $this->delete($entry->path());
            }
        }
        $this->local->deleteDirectory($path);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        if (! $this->isRemote($path)) {
            $this->local->setVisibility($path, $visibility);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->isRemote($path) ? new FileAttributes($path, visibility: 'private') : $this->local->visibility($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->run($path, fn () => $this->target($path)->mimeType($path));
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->run($path, fn () => $this->target($path)->lastModified($path));
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->run($path, fn () => $this->target($path)->fileSize($path));
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $seen = [];
        foreach ($this->local->listContents($path, $deep) as $entry) {
            $seen[$entry->path()] = true;
            yield $entry;
        }
        $directory = trim($path, '/');
        foreach (self::PREFIXES as $prefix) {
            $base = rtrim($prefix, '/');
            if ($directory !== '' && ! str_starts_with($directory.'/', $prefix) && ! str_starts_with($prefix, $directory.'/')) {
                continue;
            }
            $start = str_starts_with($directory.'/', $prefix) ? $directory : $base;
            try {
                foreach ($this->remote->listContents($start, $deep) as $entry) {
                    if (! isset($seen[$entry->path()]) && ($entry->isDir() || self::eligible($entry->path()))) {
                        $seen[$entry->path()] = true;
                        yield $entry;
                    }
                }
            } catch (\Throwable) {
                throw UnableToReadFile::fromLocation($path, 'Medya listesi alınamadı.');
            }
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $stream = $this->readStream($source);
        try {
            $this->writeStream($destination, $stream, $config);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->copy($source, $destination, $config);
        $this->delete($source);
    }
}
