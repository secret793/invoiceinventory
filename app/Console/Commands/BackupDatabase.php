<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature   = 'db:backup';
    protected $description = 'Create a mysqldump backup of the database and store it in storage/backups/';

    public function handle(): int
    {
        $host     = config('database.connections.mysql.host');
        $port     = config('database.connections.mysql.port', 3306);
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        if (!$database) {
            $this->error('No database configured.');
            return self::FAILURE;
        }

        // Backups folder inside storage/app/backups
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0750, true);
        }

        $filename  = 'backup_' . $database . '_' . now()->format('Y-m-d_His') . '.sql.gz';
        $filepath  = $backupDir . DIRECTORY_SEPARATOR . $filename;

        // Build mysqldump command — password passed via env var to avoid shell history exposure
        $env    = "MYSQL_PWD=" . escapeshellarg($password);
        $dump   = "mysqldump"
            . " --host=" . escapeshellarg($host)
            . " --port=" . escapeshellarg($port)
            . " --user=" . escapeshellarg($username)
            . " --single-transaction"
            . " --routines"
            . " --triggers"
            . " " . escapeshellarg($database);

        $command = $env . " " . $dump . " | gzip > " . escapeshellarg($filepath);

        $this->info("Starting backup: {$filename}");

        exec($command . " 2>&1", $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error("mysqldump failed (exit {$exitCode}):");
            foreach ($output as $line) {
                $this->error("  " . $line);
            }
            return self::FAILURE;
        }

        $sizeMb = round(filesize($filepath) / 1024 / 1024, 2);
        $this->info("Backup saved: {$filepath} ({$sizeMb} MB)");

        // Delete backups older than 30 days
        $this->pruneOldBackups($backupDir);

        return self::SUCCESS;
    }

    private function pruneOldBackups(string $backupDir): void
    {
        $cutoff = now()->subDays(30)->getTimestamp();
        $pruned = 0;

        foreach (glob($backupDir . '/backup_*.sql.gz') as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $pruned++;
            }
        }

        if ($pruned > 0) {
            $this->info("Pruned {$pruned} backup(s) older than 30 days.");
        }
    }
}
