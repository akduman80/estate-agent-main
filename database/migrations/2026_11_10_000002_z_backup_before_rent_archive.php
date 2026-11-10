<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing') || DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $database = DB::connection()->getDatabaseName();
        $settings = config('database.connections.'.config('database.default'));
        $dumpBinary = (new ExecutableFinder)->find('mysqldump')
            ?? (new ExecutableFinder)->find('mariadb-dump');

        if (! is_string($database) || $database === '' || ! $dumpBinary) {
            throw new RuntimeException('A verified database backup could not be started; rent-rate migration stopped.');
        }

        $backupRoot = storage_path('app/private/deploy-backups');
        if (! is_dir($backupRoot) && ! mkdir($backupRoot, 0700, true) && ! is_dir($backupRoot)) {
            throw new RuntimeException('A private database backup directory could not be created; rent-rate migration stopped.');
        }
        @chmod($backupRoot, 0700);

        $directory = $backupRoot.'/'.now('UTC')->format('Ymd\THis\Z').'-'.bin2hex(random_bytes(4));
        if (! mkdir($directory, 0700)) {
            throw new RuntimeException('A unique private database backup could not be created; rent-rate migration stopped.');
        }

        $temporaryPath = $directory.'/database.sql.part';
        $backupPath = $directory.'/database.sql';
        $stream = fopen($temporaryPath, 'xb');

        if (! is_resource($stream)) {
            throw new RuntimeException('A private database backup file could not be opened; rent-rate migration stopped.');
        }

        @chmod($temporaryPath, 0600);
        $command = [
            $dumpBinary,
            '--single-transaction', '--quick', '--hex-blob', '--routines', '--triggers', '--events',
            '--default-character-set=utf8mb4', '--no-tablespaces', '--skip-lock-tables',
            '--host='.($settings['host'] ?? '127.0.0.1'),
            '--port='.($settings['port'] ?? 3306),
            '--user='.($settings['username'] ?? ''),
        ];
        if (! empty($settings['unix_socket'])) {
            $command[] = '--socket='.$settings['unix_socket'];
        }
        $command[] = $database;

        $process = new Process($command, base_path(), [
            'MYSQL_PWD' => (string) ($settings['password'] ?? ''),
            'MYSQL_HOST' => (string) ($settings['host'] ?? '127.0.0.1'),
            'MYSQL_TCP_PORT' => (string) ($settings['port'] ?? 3306),
            'MYSQL_UNIX_PORT' => (string) ($settings['unix_socket'] ?? ''),
            'MYSQL_USER' => (string) ($settings['username'] ?? ''),
            'MYSQL_DATABASE' => $database,
        ]);
        $process->setTimeout(900);

        try {
            $process->run(function (string $type, string $buffer) use ($stream): void {
                if ($type === Process::OUT && fwrite($stream, $buffer) !== strlen($buffer)) {
                    throw new RuntimeException('The private database backup could not be written completely.');
                }
            });
        } catch (Throwable) {
            fclose($stream);
            @unlink($temporaryPath);

            throw new RuntimeException('The private database backup failed; rent-rate migration stopped.');
        }

        fclose($stream);

        if (! $process->isSuccessful() || ! is_file($temporaryPath) || filesize($temporaryPath) < 64) {
            @unlink($temporaryPath);

            throw new RuntimeException('The private database backup did not pass verification; rent-rate migration stopped.');
        }

        $sha256 = hash_file('sha256', $temporaryPath);
        if (! is_string($sha256) || ! rename($temporaryPath, $backupPath) || ! hash_equals($sha256, (string) hash_file('sha256', $backupPath))) {
            @unlink($temporaryPath);
            @unlink($backupPath);

            throw new RuntimeException('The private database backup checksum failed; rent-rate migration stopped.');
        }

        @chmod($backupPath, 0600);
        $manifest = json_encode([
            'created_at_utc' => now('UTC')->toIso8601String(),
            'database' => $database,
            'dump_file' => 'database.sql',
            'bytes' => filesize($backupPath),
            'sha256' => $sha256,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        if (file_put_contents($directory.'/manifest.json', $manifest."\n", LOCK_EX) === false) {
            throw new RuntimeException('The private database backup manifest could not be written; rent-rate migration stopped.');
        }
        @chmod($directory.'/manifest.json', 0600);

        logger()->info('Verified pre-migration database backup created.', [
            'path' => $backupPath,
            'bytes' => filesize($backupPath),
            'sha256' => $sha256,
        ]);
    }

    public function down(): void
    {
        // Keep the backup for recovery even if this migration is rolled back.
    }
};
