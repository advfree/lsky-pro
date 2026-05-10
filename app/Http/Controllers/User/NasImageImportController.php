<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\NasImageImportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class NasImageImportController extends Controller
{
    public function index(NasImageImportService $service): View
    {
        $importPath = $service->importPath();
        $pendingFiles = collect($service->listPending(100));

        return view('user.nas-image-import', compact('importPath', 'pendingFiles'));
    }

    public function import(Request $request, NasImageImportService $service): Response
    {
        try {
            $limit = max(1, min(200, (int) $request->input('limit', 50)));
            $result = $service->import($request->user(), $limit);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success('NAS 图片导入完成', compact('result'));
    }
}
