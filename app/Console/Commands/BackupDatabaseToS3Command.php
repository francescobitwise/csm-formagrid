<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

final class BackupDatabaseToS3Command extends Command
{
    protected $signature = 'backup:database-to-s3
                            {--dry-run : Esegue solo i dump locali, senza upload su S3}';

    protected $description = 'Dump MySQL database, gzip, upload su disco S3 (vedi config/backup.php).';

    public function handle(): int
    {
        if (! config('backup.database_enabled') && ! $this->option('dry-run')) {
            $this->error('Backup disattivato. Imposta BACKUP_DATABASE_ENABLED=true in .env oppure usa --dry-run.');

            return self::FAILURE;
        }

        $defaultConnectionName = (string) config('database.default', 'mysql');
        $connection = config("database.connections.{$defaultConnectionName}");
        if (! is_array($connection) || ($connection['driver'] ?? '') !== 'mysql') {
            $this->error('Il backup supporta solo MySQL/MariaDB sulla connessione di default.');

            return self::FAILURE;
        }

        $database = (string) ($connection['database'] ?? '');
        if ($database === '') {
            $this->error("database.connections.{$defaultConnectionName}.database non configurato.");

            return self::FAILURE;
        }

        $diskName = (string) config('backup.disk', 's3_backups');
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun) {
            $diskConfig = config("filesystems.disks.{$diskName}");
            if (! is_array($diskConfig) || ($diskConfig['driver'] ?? '') !== 's3') {
                $this->error("Disco \"{$diskName}\" non è S3 o non esiste in config/filesystems.php.");

                return self::FAILURE;
            }
            if (empty($diskConfig['bucket'])) {
                $this->error('Bucket S3 non configurato (AWS_BUCKET / AWS_BACKUP_BUCKET).');

                return self::FAILURE;
            }
        }

        $mysqldump = (string) config('backup.mysqldump_path', 'mysqldump');
        $tmpRoot = storage_path('app/backup-tmp');
        if (! is_dir($tmpRoot) && ! @mkdir($tmpRoot, 0750, true) && ! is_dir($tmpRoot)) {
            $this->error("Impossibile creare la directory temporanea: {$tmpRoot}");

            return self::FAILURE;
        }

        $runFolder = now()->utc()->format('Y-m-d').'/'.now()->utc()->format('His');
        $prefix = trim((string) config('backup.s3_prefix', 'backups/database'), '/');
        $remoteBase = $prefix !== '' ? "{$prefix}/{$runFolder}" : $runFolder;

        $workDir = $tmpRoot.DIRECTORY_SEPARATOR.uniqid('run_', true);
        if (! @mkdir($workDir, 0750, true) && ! is_dir($workDir)) {
            $this->error("Impossibile creare work dir: {$workDir}");

            return self::FAILURE;
        }

        $cnfPath = $workDir.DIRECTORY_SEPARATOR.'mysql.cnf';
        $this->writeMysqlClientCnf($cnfPath, $connection);

        $toUpload = [];
        $failed = false;

        try {
            $landlordSql = $workDir.DIRECTORY_SEPARATOR.'landlord.sql';
            $landlordGz = $landlordSql.'.gz';
            $this->info("Dump landlord: {$database}");
            if (! $this->runMysqldump($mysqldump, $cnfPath, $database, $landlordSql)) {
                $failed = true;
            } else {
                $this->gzipFile($landlordSql, $landlordGz);
                $toUpload[] = ['local' => $landlordGz, 'remote' => "{$remoteBase}/landlord.sql.gz"];
            }

            // single-client: no per-tenant databases
        } finally {
            if (is_file($cnfPath)) {
                @unlink($cnfPath);
            }
        }

        if ($failed && $toUpload === []) {
            $this->cleanupWorkdir($workDir);

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('--dry-run: file lasciati in '.$workDir.' (upload saltato).');

            return $failed ? self::FAILURE : self::SUCCESS;
        }

        $disk = Storage::disk($diskName);
        $bucket = (string) config("filesystems.disks.{$diskName}.bucket", '');
        foreach ($toUpload as $item) {
            $stream = fopen($item['local'], 'rb');
            if ($stream === false) {
                $this->error('Lettura fallita: '.$item['local']);
                $failed = true;

                continue;
            }
            try {
                $disk->put($item['remote'], $stream);
                $this->info('Caricato s3://'.$bucket.'/'.$item['remote']);
            } catch (\Throwable $e) {
                $this->error('Upload fallito '.$item['remote'].': '.$e->getMessage());
                $failed = true;
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }

        $this->cleanupWorkdir($workDir);

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function writeMysqlClientCnf(string $path, array $connection): void
    {
        $user = (string) ($connection['username'] ?? 'root');
        $password = (string) ($connection['password'] ?? '');
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '3306');
        $socket = isset($connection['unix_socket']) ? (string) $connection['unix_socket'] : '';

        $lines = [
            '[client]',
            'user='.$user,
            'password='.str_replace(["\n", "\r"], '', $password),
        ];
        if ($socket !== '') {
            $lines[] = 'socket='.$socket;
        } else {
            $lines[] = 'host='.$host;
            $lines[] = 'port='.$port;
        }

        file_put_contents($path, implode("\n", $lines)."\n", LOCK_EX);
        if (PHP_OS_FAMILY !== 'Windows') {
            @chmod($path, 0600);
        }
    }

    private function runMysqldump(string $mysqldump, string $cnfPath, string $database, string $outputSqlPath): bool
    {
        $timeout = (int) config('backup.dump_timeout', 0);
        $process = new Process([
            $mysqldump,
            '--defaults-extra-file='.$cnfPath,
            '--single-transaction',
            '--skip-lock-tables',
            '--routines',
            '--no-tablespaces',
            $database,
        ]);
        if ($timeout > 0) {
            $process->setTimeout($timeout);
        } else {
            $process->setTimeout(null);
        }

        $out = fopen($outputSqlPath, 'wb');
        if ($out === false) {
            $this->error("Impossibile scrivere {$outputSqlPath}");

            return false;
        }

        try {
            $stderr = '';
            $process->run(function (string $type, string $buffer) use ($out, &$stderr): void {
                if ($type === Process::OUT) {
                    fwrite($out, $buffer);
                } else {
                    $stderr .= $buffer;
                }
            });

            if (! $process->isSuccessful()) {
                $this->error("mysqldump {$database} fallito: ".trim($stderr ?: $process->getErrorOutput()));

                return false;
            }
        } finally {
            fclose($out);
        }

        return true;
    }

    private function gzipFile(string $sourceSql, string $destGz): void
    {
        $in = fopen($sourceSql, 'rb');
        if ($in === false) {
            throw new \RuntimeException("Cannot read {$sourceSql}");
        }
        $gz = gzopen($destGz, 'wb9');
        if ($gz === false) {
            fclose($in);
            throw new \RuntimeException("Cannot write {$destGz}");
        }
        try {
            while (! feof($in)) {
                $chunk = fread($in, 1024 * 1024);
                if ($chunk === false) {
                    break;
                }
                gzwrite($gz, $chunk);
            }
        } finally {
            fclose($in);
            gzclose($gz);
            @unlink($sourceSql);
        }
    }

    private function cleanupWorkdir(string $workDir): void
    {
        if (! is_dir($workDir)) {
            return;
        }
        $files = glob($workDir.DIRECTORY_SEPARATOR.'*') ?: [];
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($workDir);
    }
}
