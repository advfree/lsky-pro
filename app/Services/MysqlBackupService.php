<?php

namespace App\Services;

use App\Enums\ConfigKey;
use App\Enums\Strategy\LocalOption;
use App\Enums\StrategyKey;
use App\Models\Strategy;
use App\Utils;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class MysqlBackupService
{
    public function __construct(private ?StorageBasePathService $pathService = null)
    {
        $this->pathService = $pathService ?: new StorageBasePathService();
    }

    public function backupNow(): array
    {
        if (config('database.default') !== 'mysql') {
            throw new RuntimeException('第一版数据库备份仅支持 MySQL');
        }

        $basePath = $this->basePath();
        if ($basePath === '') {
            throw new RuntimeException('请先配置存储总路径');
        }

        $this->pathService->ensureDirectories($basePath);
        $backupDir = $this->pathService->mysqlBackupsPath($basePath);
        $filename = 'lsky-'.Carbon::now()->format('Y-m-d-His').'.sql.gz';
        $pathname = $backupDir.'/'.$filename;

        $sql = $this->dumpMysql();
        $encoded = gzencode($sql, 6);
        if ($encoded === false || file_put_contents($pathname, $encoded) === false) {
            throw new RuntimeException("备份文件写入失败：{$pathname}");
        }

        $this->updateLastRanAt();
        $deleted = $this->pruneOldBackups((int) Utils::config(ConfigKey::MysqlBackupRetentionCount, 5));

        return [
            'filename' => $filename,
            'path' => $pathname,
            'size' => filesize($pathname) ?: 0,
            'deleted' => $deleted,
        ];
    }

    public function shouldRun(): bool
    {
        if (! Utils::config(ConfigKey::IsEnableMysqlBackup, false)) {
            return false;
        }

        $lastRanAt = Utils::config(ConfigKey::MysqlBackupLastRanAt, '');
        if (! $lastRanAt) {
            return true;
        }

        $intervalDays = max(1, (int) Utils::config(ConfigKey::MysqlBackupIntervalDays, 1));

        return Carbon::parse($lastRanAt)->addDays($intervalDays)->isPast();
    }

    public function listBackups(): array
    {
        $basePath = $this->basePath();
        if ($basePath === '') {
            return [];
        }

        $backupDir = $this->pathService->mysqlBackupsPath($basePath);
        if (! is_dir($backupDir)) {
            return [];
        }

        $files = glob($backupDir.'/lsky-*.sql.gz') ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return array_map(fn ($path) => [
            'filename' => basename($path),
            'path' => $path,
            'size' => filesize($path) ?: 0,
            'created_at' => Carbon::createFromTimestamp(filemtime($path))->toDateTimeString(),
        ], $files);
    }

    public function basePath(): string
    {
        $basePath = $this->pathService->normalizeBasePath(Utils::config(ConfigKey::StorageBasePath, ''));
        if ($basePath !== '') {
            return $basePath;
        }

        $strategy = Strategy::query()
            ->where('key', StrategyKey::Local)
            ->oldest('id')
            ->first();

        if (is_null($strategy)) {
            return '';
        }

        $root = $this->pathService->normalizeBasePath($strategy->configs->get(LocalOption::Root, ''));
        if ($root === '') {
            return '';
        }

        return str_ends_with($root, '/uploads') ? substr($root, 0, -8) : $root;
    }

    public function backupDirectory(): string
    {
        $basePath = $this->basePath();

        return $basePath === '' ? '' : $this->pathService->mysqlBackupsPath($basePath);
    }

    public function backupPath(string $filename): string
    {
        $filename = basename($filename);
        foreach ($this->listBackups() as $backup) {
            if ($backup['filename'] === $filename) {
                return $backup['path'];
            }
        }

        throw new RuntimeException('备份文件不存在');
    }

    public function importBackup(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw new RuntimeException('上传文件无效');
        }

        if (! preg_match('/\.sql\.gz$/i', $file->getClientOriginalName())) {
            throw new RuntimeException('只允许上传 .sql.gz 备份文件');
        }

        $basePath = $this->basePath();
        if ($basePath === '') {
            throw new RuntimeException('请先应用存储总路径');
        }

        $this->pathService->ensureDirectories($basePath);
        $backupDir = $this->pathService->mysqlBackupsPath($basePath);
        $filename = 'lsky-upload-'.Carbon::now()->format('Y-m-d-His').'.sql.gz';
        $pathname = $backupDir.'/'.$filename;

        if (! $file->move($backupDir, $filename)) {
            throw new RuntimeException('备份文件保存失败');
        }

        $deleted = $this->pruneOldBackups((int) Utils::config(ConfigKey::MysqlBackupRetentionCount, 5));

        return [
            'filename' => $filename,
            'path' => $pathname,
            'size' => filesize($pathname) ?: 0,
            'created_at' => Carbon::createFromTimestamp(filemtime($pathname))->toDateTimeString(),
            'deleted' => $deleted,
        ];
    }

    public function pruneOldBackups(int $retention): array
    {
        $retention = max(1, $retention);
        $backups = $this->listBackups();
        $deleted = [];

        foreach (array_slice($backups, $retention) as $backup) {
            if (@unlink($backup['path'])) {
                $deleted[] = $backup['filename'];
            }
        }

        return $deleted;
    }

    private function dumpMysql(): string
    {
        $connection = config('database.connections.mysql');
        $command = [
            'mysqldump',
            '--host='.$connection['host'],
            '--port='.(string) $connection['port'],
            '--user='.$connection['username'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            $connection['database'],
        ];

        $process = new Process($command, null, [
            'MYSQL_PWD' => (string) $connection['password'],
        ]);
        $process->setTimeout(null);
        $process->run();

        if (! $process->isSuccessful()) {
            return $this->dumpMysqlWithPdo($process);
        }

        return $process->getOutput();
    }

    private function dumpMysqlWithPdo(Process $failedProcess): string
    {
        $error = $failedProcess->getErrorOutput();
        if (! str_contains($error, 'caching_sha2_password') && ! str_contains($error, 'not found')) {
            throw new ProcessFailedException($failedProcess);
        }

        $pdo = DB::connection('mysql')->getPdo();
        $database = config('database.connections.mysql.database');
        $sql = [
            '-- Lsky Pro MySQL backup',
            '-- Generated at '.Carbon::now()->toDateTimeString(),
            '-- mysqldump unavailable, generated by PDO fallback',
            'SET FOREIGN_KEY_CHECKS=0;',
            "CREATE DATABASE IF NOT EXISTS `{$this->escapeIdentifier($database)}`;",
            "USE `{$this->escapeIdentifier($database)}`;",
            '',
        ];

        $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
        foreach ($tables as $tableRow) {
            $table = $tableRow[0];
            $quotedTable = '`'.$this->escapeIdentifier($table).'`';

            $create = $pdo->query("SHOW CREATE TABLE {$quotedTable}")->fetch(PDO::FETCH_ASSOC);
            $createSql = $create['Create Table'] ?? array_values($create)[1] ?? '';

            $sql[] = "DROP TABLE IF EXISTS {$quotedTable};";
            $sql[] = $createSql.';';

            $rows = $pdo->query("SELECT * FROM {$quotedTable}");
            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                $columns = array_map(fn ($column) => '`'.$this->escapeIdentifier($column).'`', array_keys($row));
                $values = array_map(fn ($value) => $this->quoteValue($pdo, $value), array_values($row));
                $sql[] = "INSERT INTO {$quotedTable} (".implode(', ', $columns).') VALUES ('.implode(', ', $values).');';
            }

            $sql[] = '';
        }

        $sql[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $sql[] = '';

        return implode(PHP_EOL, $sql);
    }

    private function quoteValue(PDO $pdo, mixed $value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        return $pdo->quote((string) $value);
    }

    private function escapeIdentifier(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }

    private function updateLastRanAt(): void
    {
        $now = Carbon::now();
        DB::table('configs')->updateOrInsert(
            ['name' => ConfigKey::MysqlBackupLastRanAt],
            [
                'value' => $now->toDateTimeString(),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        cache()->forget('configs');
    }
}
