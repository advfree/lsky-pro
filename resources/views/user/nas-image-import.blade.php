@section('title', 'NAS 图片导入')

<x-app-layout>
    <div class="my-6 md:my-9">
        <div class="relative p-4 rounded-md bg-white mb-8 space-y-4 shadow-custom">
            <div>
                <p class="font-semibold text-lg text-gray-700">NAS 图片导入</p>
                <p class="mt-2 text-sm text-gray-600">
                    这里用于接管你手动复制到 NAS 的图片。请把图片放到存储总路径下的 imports 目录，导入后系统会按正常上传流程写入数据库，归属当前管理员，生成缩略图和分享 WebP。
                </p>
            </div>

            <div class="rounded bg-yellow-50 p-3 text-sm text-yellow-800 space-y-1">
                <p>不要直接把图片放进 uploads 后期待系统自动识别，Lsky Pro 需要数据库记录才能在“我的图片”里管理图片。</p>
                <p>成功导入的源文件会从 imports 目录移除；失败的文件会保留，并在结果中显示原因。</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">导入目录</label>
                    <x-input type="text" readonly value="{{ $importPath ?: '请先在储存策略中应用存储总路径' }}" />
                    <small class="text-gray-500"><i class="fas fa-exclamation-circle"></i> 固定目录为「存储总路径/imports」。系统不会扫描整个 NAS，也不会扫描 uploads。</small>
                </div>
                <div>
                    <label for="nas_import_limit" class="block text-sm font-medium text-gray-700">本次最多导入</label>
                    <x-input type="number" id="nas_import_limit" min="1" max="200" step="1" value="50" />
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="font-semibold text-gray-700">待导入图片</p>
                    <span class="text-sm text-gray-500">{{ $pendingFiles->count() }} 个</span>
                </div>

                @if($pendingFiles->isNotEmpty())
                    <div class="rounded border border-gray-100 overflow-hidden">
                        <table class="min-w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-3 py-2 font-medium">文件名</th>
                                <th class="px-3 py-2 font-medium">大小</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach($pendingFiles as $file)
                                <tr>
                                    <td class="px-3 py-2 break-all">{{ $file['filename'] }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $file['human_size'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded bg-gray-50 p-4 text-sm text-gray-500">当前没有待导入图片。</div>
                @endif
            </div>

            <div id="nas-image-import-result" class="hidden rounded bg-green-50 p-3 text-sm text-green-700"></div>

            <div class="text-right">
                <x-button type="button" id="nas-image-import-now" class="bg-blue-500">开始导入 NAS 图片</x-button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const appendLine = function ($target, label, value) {
                $('<div>').text(`${label}${value}`).appendTo($target);
            };

            $('#nas-image-import-now').click(function () {
                let $button = $(this);
                let $result = $('#nas-image-import-result').hide().html('');
                let limit = $('#nas_import_limit').val() || 50;

                Swal.fire({
                    title: '确认导入 NAS 图片？',
                    text: '成功导入的文件会从 imports 目录移除，并归属当前管理员。系统会生成缩略图和分享 WebP。',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '确认导入',
                    cancelButtonText: '取消',
                }).then(result => {
                    if (! result.isConfirmed) {
                        return;
                    }

                    $button.attr('disabled', true).addClass('cursor-not-allowed bg-gray-400').removeClass('bg-blue-500').text('导入中...');
                    axios.post('{{ route('nas-image-import.import') }}', {
                        limit: limit,
                    }).then(response => {
                        if (! response.data.status) {
                            toastr.error(response.data.message);
                            return;
                        }

                        let data = response.data.data.result;
                        $result.empty();
                        appendLine($result, '扫描文件：', data.total);
                        appendLine($result, '成功：', data.success_count);
                        appendLine($result, '失败：', data.failed_count);
                        if (data.failed && data.failed.length) {
                            $('<div>').text('失败文件：').appendTo($result);
                            data.failed.forEach(item => appendLine($result, '', `${item.filename}: ${item.message}`));
                        }
                        $result.show();
                        toastr.success(response.data.message);
                        setTimeout(() => window.location.reload(), 1200);
                    }).catch(error => {
                        toastr.error(error.message || '导入失败');
                    }).finally(() => {
                        $button.attr('disabled', false).removeClass('cursor-not-allowed bg-gray-400').addClass('bg-blue-500').text('开始导入 NAS 图片');
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
