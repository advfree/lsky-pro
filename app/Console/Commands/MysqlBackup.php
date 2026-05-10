<?php

namespace App\Console\Commands;

use App\Services\MysqlBackupService;
use App\Utils;
use Illuminate\Console\Command;

class MysqlBackup extends Command
{
    protected $signature = 'lsky:mysql-backup {--force : 忽略自动备份间隔，立即执行备份}';

    protected $description = 'Backup Lsky Pro MySQL metadata database to the configured storage backup directory.';

    public function handle(MysqlBackupService $service): int
    {
        if (! $this->option('force') && ! $service->shouldRun()) {
            $this->info('未到自动备份时间，跳过。');
            return self::SUCCESS;
        }

        try {
            $backup = $service->backupNow();
        } catch (\Throwable $e) {
            Utils::e($e, 'MySQL 备份失败');
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("备份完成：{$backup['path']}");
        $this->info('文件大小：'.Utils::formatSize($backup['size']));

        if ($backup['deleted']) {
            $this->info('已清理旧备份：'.implode(', ', $backup['deleted']));
        }

        return self::SUCCESS;
    }
}
