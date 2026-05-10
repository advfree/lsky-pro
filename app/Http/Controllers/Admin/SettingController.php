<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConfigKey;
use App\Http\Controllers\Controller;
use App\Mail\Test;
use App\Models\Config;
use App\Services\MysqlBackupService;
use App\Services\StorageBasePathService;
use App\Services\UpgradeService;
use App\Utils;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SettingController extends Controller
{
    public function index(MysqlBackupService $backupService): View
    {
        $configs = Utils::config();
        $storageBasePath = $backupService->basePath();
        $mysqlBackupDirectory = $backupService->backupDirectory();
        $mysqlBackups = collect($backupService->listBackups())->map(function ($backup) {
            $backup['human_size'] = Utils::formatSize($backup['size']);
            $backup['download_url'] = route('admin.settings.mysql.backup.download', ['filename' => $backup['filename']]);

            return $backup;
        });

        return view('admin.setting.index', compact('configs', 'storageBasePath', 'mysqlBackupDirectory', 'mysqlBackups'));
    }

    public function save(Request $request): Response
    {
        try {
            $payload = $request->all();
            $now = Carbon::now();
            foreach ($payload as $key => $value) {
                $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
                Config::query()->updateOrInsert(
                    ['name' => $key],
                    [
                        'value' => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        Cache::flush();
        return $this->success('保存成功');
    }

    public function mysqlBackup(MysqlBackupService $service): Response
    {
        try {
            $backup = $service->backupNow();
            $backup['human_size'] = Utils::formatSize($backup['size']);
            $backup['download_url'] = route('admin.settings.mysql.backup.download', ['filename' => $backup['filename']]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success('备份成功', compact('backup'));
    }

    public function applyStorageBasePath(Request $request, StorageBasePathService $service): Response
    {
        try {
            $strategy = $service->apply($request->input('storage_base_path'));
            $backupDirectory = $service->mysqlBackupsPath($request->input('storage_base_path'));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success('存储路径已应用到本地储存策略', [
            'strategy_id' => $strategy?->id,
            'backup_directory' => $backupDirectory,
        ]);
    }

    public function mysqlBackupUpload(Request $request, MysqlBackupService $service): Response
    {
        try {
            $file = $request->file('backup');
            if (! $file) {
                throw new \RuntimeException('请选择要上传的 .sql.gz 备份文件');
            }

            $backup = $service->importBackup($file);
            $backup['human_size'] = Utils::formatSize($backup['size']);
            $backup['download_url'] = route('admin.settings.mysql.backup.download', ['filename' => $backup['filename']]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success('备份文件上传成功', compact('backup'));
    }

    public function mysqlBackupDownload(string $filename, MysqlBackupService $service): BinaryFileResponse
    {
        $filename = basename($filename);
        $path = $service->backupPath($filename);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/gzip',
        ]);
    }

    public function mailTest(Request $request): Response
    {
        try {
            Mail::to($request->post('email'))->send(new Test());
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('发送成功');
    }

    public function checkUpdate(): Response
    {
        $version = Utils::config(ConfigKey::AppVersion);
        $service = new UpgradeService($version);
        try {
            $data = [
                'is_update' => $service->check(),
            ];
            if ($data['is_update']) {
                $data['version'] = $service->getVersions()->first();
            }
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success('success', $data);
    }

    public function upgrade()
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $version = Utils::config(ConfigKey::AppVersion);
        $service = new UpgradeService($version);
        $this->success()->send();
        $service->upgrade();
        flush();
    }

    public function upgradeProgress(): Response
    {
        return $this->success('success', Cache::get('upgrade_progress'));
    }
}
