<?php

namespace App\Http\Middleware;

use App\Exceptions\UploadException;
use Closure;
use Illuminate\Http\Request;

class CheckUploadSecurity
{
    /**
     * Allowed MIME types.
     */
    protected array $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/bmp',
        'image/tiff',
        'image/x-icon',
        'image/vnd.microsoft.icon',
        'image/psd',
    ];

    /**
     * Magic bytes mapping for common image types.
     */
    protected array $magicBytes = [
        'image/jpeg' => ["\xFF\xD8\xFF"],
        'image/png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
        'image/gif' => ["\x47\x49\x46\x38\x37\x61", "\x47\x49\x46\x38\x39\x61"],
        'image/webp' => ["\x52\x49\x46\x46"],
        'image/bmp' => ["\x42\x4D"],
        'image/tiff' => ["\x49\x49\x2A\x00", "\x4D\x4D\x00\x2A"],
    ];

    /**
     * Maximum filename length in bytes.
     */
    protected int $maxFilenameLength = 255;

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  \Closure  $next
     * @return mixed
     * @throws UploadException
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mimeType = $file->getMimeType();
            $originalName = $file->getClientOriginalName();

            // Check MIME type whitelist
            if (!in_array($mimeType, $this->allowedMimes, true)) {
                throw new UploadException('不支持的文件类型');
            }

            // Check magic bytes for non-SVG files
            if ($mimeType !== 'image/svg+xml' && isset($this->magicBytes[$mimeType])) {
                $fileHandle = fopen($file->getRealPath(), 'rb');
                $header = fread($fileHandle, 16);
                fclose($fileHandle);

                $isValid = false;
                foreach ($this->magicBytes[$mimeType] as $magic) {
                    if (str_starts_with($header, $magic)) {
                        $isValid = true;
                        break;
                    }
                }

                if (!$isValid) {
                    throw new UploadException('文件格式不合法');
                }
            }

            // Check path traversal in filename
            if (preg_match('/\.\.(\/|\\\\)/', $originalName)) {
                throw new UploadException('文件名不合法');
            }

            // Check filename length
            if (strlen($originalName) > $this->maxFilenameLength) {
                throw new UploadException('文件名过长');
            }
        }

        return $next($request);
    }
}
