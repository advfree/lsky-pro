<?php

namespace App\Services;

use App\Enums\Strategy\LocalOption;
use App\Enums\StrategyKey;
use App\Models\Strategy;
use App\Models\Config;
use App\Enums\ConfigKey;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class StorageBasePathService
{
    public function __construct(private ?Filesystem $files = null)
    {
        $this->files = $files ?: new Filesystem();
    }

    public function normalizeBasePath(?string $path): string
    {
        $path = trim((string) $path);
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path) ?: '';

        if ($path === '') {
            return '';
        }

        return rtrim($path, '/');
    }

    public function uploadsPath(?string $basePath): string
    {
        return $this->normalizeBasePath($basePath).'/uploads';
    }

    public function mysqlBackupsPath(?string $basePath): string
    {
        return $this->normalizeBasePath($basePath).'/backups/mysql';
    }

    public function importsPath(?string $basePath): string
    {
        return $this->normalizeBasePath($basePath).'/imports';
    }

    public function ensureDirectories(?string $basePath): void
    {
        $basePath = $this->normalizeBasePath($basePath);
        if ($basePath === '') {
            return;
        }

        if (! $this->isAbsolutePath($basePath)) {
            throw new RuntimeException('存储总路径必须是绝对路径，例如 /mnt/nas/lskypro');
        }

        foreach ([$basePath, $this->uploadsPath($basePath), $this->mysqlBackupsPath($basePath), $this->importsPath($basePath)] as $path) {
            if (! $this->files->isDirectory($path) && ! $this->files->makeDirectory($path, 0755, true)) {
                throw new RuntimeException("目录创建失败：{$path}");
            }

            if (! is_writable($path)) {
                throw new RuntimeException("目录不可写：{$path}");
            }
        }
    }

    public function applyLocalStrategy(?string $basePath, ?Strategy $strategy = null): ?Strategy
    {
        $basePath = $this->normalizeBasePath($basePath);
        if ($basePath === '') {
            return null;
        }

        $this->ensureDirectories($basePath);

        /** @var Strategy|null $strategy */
        $strategy = $strategy ?: Strategy::query()
            ->where('key', StrategyKey::Local)
            ->oldest('id')
            ->first();

        if (is_null($strategy)) {
            throw new RuntimeException('未找到本地存储策略，无法自动应用图片目录');
        }

        $configs = $strategy->configs;
        $configs[LocalOption::Root] = $this->uploadsPath($basePath);
        $configs[LocalOption::Url] = rtrim(config('app.url'), '/').'/i';
        $strategy->configs = $configs;
        $strategy->save();

        Config::query()->updateOrInsert(
            ['name' => ConfigKey::StorageBasePath],
            [
                'value' => $basePath,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        Cache::flush();

        $this->syncPublicSymlink($configs[LocalOption::Root], 'i');

        return $strategy;
    }

    public function apply(?string $basePath, ?Strategy $strategy = null): ?Strategy
    {
        return DB::transaction(fn () => $this->applyLocalStrategy($basePath, $strategy));
    }

    private function syncPublicSymlink(string $target, string $linkName): void
    {
        $link = public_path($linkName);

        if (is_link($link) || is_file($link)) {
            @unlink($link);
        }

        if (! file_exists($link)) {
            try {
                $this->files->link($target, $link);
            } catch (\Throwable) {
                // Nginx may provide its own /i mapping; strategy root is the source of truth.
            }
        }
    }

    private function isAbsolutePath(string $path): bool
    {
        return Str::startsWith($path, '/') || (bool) preg_match('/^[A-Za-z]:\//', $path);
    }
}
