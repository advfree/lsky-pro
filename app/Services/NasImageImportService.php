<?php

namespace App\Services;

use App\Exceptions\UploadException;
use App\Models\User;
use App\Utils;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Finder\Finder;

class NasImageImportService
{
    private array $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg'];

    public function __construct(
        private ?MysqlBackupService $backupService = null,
        private ?StorageBasePathService $pathService = null,
        private ?ImageService $imageService = null,
    ) {
        $this->backupService = $backupService ?: new MysqlBackupService();
        $this->pathService = $pathService ?: new StorageBasePathService();
        $this->imageService = $imageService ?: new ImageService();
    }

    public function importPath(): string
    {
        $basePath = $this->backupService->basePath();

        return $basePath === '' ? '' : $this->pathService->importsPath($basePath);
    }

    public function ensureImportPath(): string
    {
        $path = $this->importPath();
        if ($path === '') {
            throw new \RuntimeException('请先应用存储总路径');
        }

        if (! is_dir($path) && ! mkdir($path, 0755, true)) {
            throw new \RuntimeException("导入目录创建失败：{$path}");
        }

        if (! is_readable($path) || ! is_writable($path)) {
            throw new \RuntimeException("导入目录需要可读写权限：{$path}");
        }

        return $path;
    }

    public function listPending(int $limit = 200): array
    {
        $path = $this->importPath();
        if ($path === '' || ! is_dir($path)) {
            return [];
        }

        $finder = Finder::create()
            ->files()
            ->in($path)
            ->depth('< 5')
            ->filter(fn (\SplFileInfo $file) => in_array(strtolower($file->getExtension()), $this->extensions));

        $files = [];
        foreach ($finder as $file) {
            $files[] = [
                'pathname' => $file->getPathname(),
                'filename' => $file->getFilename(),
                'size' => $file->getSize(),
                'human_size' => Utils::formatSize($file->getSize()),
            ];

            if (count($files) >= $limit) {
                break;
            }
        }

        return $files;
    }

    public function import(User $user, int $limit = 50): array
    {
        $this->ensureImportPath();
        $files = array_slice($this->listPending($limit), 0, $limit);
        $success = [];
        $failed = [];

        foreach ($files as $file) {
            try {
                $success[] = $this->importFile($user, $file['pathname']);
                $this->removeEmptyDirectories(dirname($file['pathname']));
            } catch (\Throwable $e) {
                $failed[] = [
                    'filename' => $file['filename'],
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'import_path' => $this->importPath(),
            'total' => count($files),
            'success_count' => count($success),
            'failed_count' => count($failed),
            'success' => $success,
            'failed' => $failed,
        ];
    }

    private function importFile(User $user, string $path): array
    {
        $uploadedFile = new UploadedFile(
            $path,
            basename($path),
            mime_content_type($path) ?: null,
            null,
            true,
        );

        $request = Request::create('/nas-image-import', 'POST', [
            'force_optimized_share' => 1,
        ], [], [
            'file' => $uploadedFile,
        ], [
            'REMOTE_ADDR' => request()->ip() ?: '127.0.0.1',
        ]);
        $request->setUserResolver(fn () => $user);

        try {
            $image = $this->imageService->store($request);
        } catch (UploadException $e) {
            throw $e;
        }

        return [
            'id' => $image->id,
            'filename' => $image->origin_name,
            'url' => $image->url,
            'optimized_url' => $image->optimized_url,
        ];
    }

    private function removeEmptyDirectories(string $path): void
    {
        $root = $this->importPath();
        while ($path && $root && str_starts_with($path, $root) && $path !== $root && is_dir($path)) {
            if (count(scandir($path) ?: []) > 2) {
                return;
            }
            @rmdir($path);
            $path = dirname($path);
        }
    }
}
