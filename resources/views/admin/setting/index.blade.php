@section('title', '系统设置')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/markdown-css/github-markdown-light.css') }}">
@endpush

<x-app-layout>
    <div class="my-6 md:my-9">
        <p class="mb-3 font-semibold text-lg text-gray-700">通用</p>
        <form action="{{ route('admin.settings.save') }}">
            <div class="relative p-4 rounded-md bg-white mb-8 space-y-4 shadow-custom">
                <div>
                    <label for="app_name" class="block text-sm font-medium text-gray-700"><span class="text-red-600">*</span>应用名称</label>
                    <x-input type="text" name="app_name" id="app_name" value="{{ $configs->get('app_name') }}" placeholder="请输入应用名称"/>
                </div>
                <div>
                    <label for="site_keywords" class="block text-sm font-medium text-gray-700">网站关键字</label>
                    <x-textarea type="text" name="site_keywords" id="site_keywords" placeholder="请输入网站关键字">{{ $configs->get('site_keywords') }}</x-textarea>
                </div>
                <div>
                    <label for="site_description" class="block text-sm font-medium text-gray-700">网站描述</label>
                    <x-textarea type="text" name="site_description" id="site_description" placeholder="请输入网站描述">{{ $configs->get('site_description') }}</x-textarea>
                </div>
                <div>
                    <label for="icp_no" class="block text-sm font-medium text-gray-700">备案号</label>
                    <x-input type="text" name="icp_no" id="icp_no" value="{{ $configs->get('icp_no') }}" placeholder="请输入备案号"/>
                </div>
                <div>
                    <label for="site_notice" class="block text-sm font-medium text-gray-700">网站公告</label>
                    <x-textarea type="text" name="site_notice" id="site_notice" placeholder="首页弹出公告，支持 Markdown，不设置请留空。" rows="7">{{ $configs->get('site_notice') }}</x-textarea>
                </div>

                <div class="text-right">
                    <x-button type="submit">保存更改</x-button>
                </div>
            </div>
        </form>

        <p class="mb-3 font-semibold text-lg text-gray-700">控制</p>
        <form action="{{ route('admin.settings.save') }}">
            <div class="relative p-4 rounded-md bg-white mb-8 space-y-4 shadow-custom">
                <x-fieldset title="是否启用注册" faq="启用或关闭系统注册功能">
                    <x-switch name="is_enable_registration" value="1" :checked="(bool) $configs->get('is_enable_registration')" />
                </x-fieldset>
                <x-fieldset title="是否启用画廊" faq="启用或关闭画廊功能，画廊只有已登录的用户可见，画廊中的图片均为所有用户公开的图片。">
                    <x-switch name="is_enable_gallery" value="1" :checked="(bool) $configs->get('is_enable_gallery')" />
                </x-fieldset>
                <x-fieldset title="是否启用接口" faq="启用或关闭接口功能，关闭后将无法通过接口上传图片、管理图片等操作。">
                    <x-switch name="is_enable_api" value="1" :checked="(bool) $configs->get('is_enable_api')" />
                </x-fieldset>
                <x-fieldset title="是否允许游客上传" faq="启用或关闭游客上传功能，游客上传受「系统默认组」控制。">
                    <x-switch name="is_allow_guest_upload" value="1" :checked="(bool) $configs->get('is_allow_guest_upload')" />
                </x-fieldset>
                <x-fieldset title="账号验证" faq="是否强制用户验证邮箱，开启后用户必须经过验证邮箱后才能上传图片，请确保邮件配置正常。">
                    <x-switch name="is_user_need_verify" value="1" :checked="(bool) $configs->get('is_user_need_verify')" />
                </x-fieldset>
                <div class="text-right">
                    <x-button type="submit">保存更改</x-button>
                </div>
            </div>
        </form>

        <p class="mb-3 font-semibold text-lg text-gray-700">用户</p>
        <form action="{{ route('admin.settings.save') }}">
            <div class="relative p-4 rounded-md bg-white mb-8 space-y-4 shadow-custom">
                <div>
                    <label for="user_initial_capacity" class="block text-sm font-medium text-gray-700">用户初始容量(kb)</label>
                    <x-input type="number" name="user_initial_capacity" id="user_initial_capacity" step="0.01" value="{{ $configs->get('user_initial_capacity') }}" placeholder="请输入用户初始容量(kb)"/>
                </div>

                <div class="text-right">
                    <x-button type="submit">保存更改</x-button>
                </div>
            </div>
        </form>

        <p class="mb-3 font-semibold text-lg text-gray-700">NAS 存储与备份</p>
        <form action="{{ route('admin.settings.save') }}">
            <div class="relative p-4 rounded-md bg-white mb-8 space-y-4 shadow-custom">
                <div class="rounded bg-blue-50 p-3 text-sm text-blue-800 space-y-1">
                    <p class="font-semibold">这里备份的是 MySQL 元数据，不是图片文件。</p>
                    <p>图片文件由本地储存策略写入存储总路径下的 uploads，适合放 NAS/NFS。MySQL 实时数据仍建议放 VPS 本地 SSD，这里只把完整 SQL 压缩备份保存到 backups/mysql。</p>
                    <p>迁移时通常先在旧 VPS 立即备份 MySQL，再在新 VPS 挂载同一个 NAS，应用新的存储总路径，然后上传或下载对应的 .sql.gz 备份文件。应用路径只更新 Lsky Pro 的本地储存策略和备份目录，不需要重启 MySQL。</p>
                </div>
                <x-fieldset title="数据库自动备份" faq="开启后可通过 Laravel scheduler 每天检查一次，到达间隔天数后自动执行 MySQL 完整备份。">
                    <input type="hidden" name="is_enable_mysql_backup" value="0">
                    <x-switch name="is_enable_mysql_backup" value="1" :checked="(bool) $configs->get('is_enable_mysql_backup')" />
                </x-fieldset>
                <div id="mysql-backup-options" class="space-y-4">
                <div class="border-t border-gray-100 pt-4 space-y-4">
                    <p class="font-semibold text-gray-700">MySQL 备份频率与保留</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="mysql_backup_interval_days" class="block text-sm font-medium text-gray-700">备份频率(天)</label>
                        <x-input type="number" name="mysql_backup_interval_days" id="mysql_backup_interval_days" min="1" step="1" value="{{ $configs->get('mysql_backup_interval_days', 1) }}" placeholder="每 N 天备份一次"/>
                    </div>
                    <div>
                        <label for="mysql_backup_retention_count" class="block text-sm font-medium text-gray-700">备份保留份数</label>
                        <x-input type="number" name="mysql_backup_retention_count" id="mysql_backup_retention_count" min="1" step="1" value="{{ $configs->get('mysql_backup_retention_count', 5) }}" placeholder="默认保留 5 份"/>
                    </div>
                </div>
                <div class="rounded bg-gray-50 p-3 text-sm text-gray-600 space-y-1">
                    <p>自动备份会按频率生成完整 MySQL SQL 压缩包，并只在当前备份目录内保留最近 N 份。</p>
                    <p>当前 MySQL 备份目录：<span class="font-mono">{{ $mysqlBackupDirectory ?: '未配置' }}</span></p>
                    <p>最近备份：{{ $configs->get('mysql_backup_last_ran_at') ?: '暂无' }}</p>
                </div>
                <div class="text-right">
                    <x-button type="submit">保存备份设置</x-button>
                    <x-button type="button" id="mysql-backup-now" class="bg-yellow-500">立即备份 MySQL 数据库</x-button>
                </div>
                </div>

                <div class="border-t border-gray-100 pt-4 space-y-4">
                    <p class="font-semibold text-gray-700">备份文件与迁移应用</p>
                    <div class="rounded bg-yellow-50 p-3 text-sm text-yellow-800 space-y-1">
                        <p>上传备份文件只会把 .sql.gz 保存到当前备份目录，便于迁移保存和下载，不会自动恢复 MySQL。</p>
                        <p>应用迁移总路径会修改本地储存策略的图片目录和访问地址。配置写入数据库后立即生效，通常不需要重启 Lsky Pro 或 MySQL；如果你同时修改了 Docker 挂载路径，则需要重启 Docker 服务让新挂载进入容器。</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div class="md:col-span-2">
                            <label for="migration_storage_base_path" class="block text-sm font-medium text-gray-700">迁移后存储总路径</label>
                            <x-input type="text" id="migration_storage_base_path" value="{{ $storageBasePath }}" placeholder="例如：/mnt/nas/lskypro"/>
                        </div>
                        <x-button type="button" id="storage-base-path-apply" class="bg-blue-500">应用迁移总路径</x-button>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="file" id="mysql-backup-file" accept=".gz,.sql.gz" class="block w-full text-sm text-gray-600">
                        <x-button type="button" id="mysql-backup-upload" class="bg-blue-500 whitespace-nowrap">上传 MySQL 备份文件</x-button>
                    </div>
                </div>

                @if($mysqlBackups->isNotEmpty())
                    <div class="rounded border border-gray-100 overflow-hidden">
                        <table class="min-w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-3 py-2 font-medium">备份文件</th>
                                <th class="px-3 py-2 font-medium">大小</th>
                                <th class="px-3 py-2 font-medium">时间</th>
                                <th class="px-3 py-2 font-medium text-right">操作</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach($mysqlBackups as $backup)
                                <tr>
                                    <td class="px-3 py-2 font-mono text-xs break-all">{{ $backup['filename'] }}</td>
                                    <td class="px-3 py-2">{{ $backup['human_size'] }}</td>
                                    <td class="px-3 py-2">{{ $backup['created_at'] }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <a class="text-blue-600 hover:text-blue-700" href="{{ $backup['download_url'] }}">下载</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <div id="mysql-backup-result" class="hidden rounded bg-green-50 p-3 text-sm text-green-700"></div>
                </div>
            </div>
        </form>

        <p class="mb-3 font-semibold text-lg text-gray-700">邮件配置</p>
        <div class="relative p-4 rounded-md bg-white mb-8 space-y-4 shadow-custom">
            <x-fieldset title="发信驱动">
                <x-fieldset-radio id="mail[default]" name="mail[default]" data-select="mailer" value="smtp" checked>SMTP</x-fieldset-radio>
            </x-fieldset>

            <div class="mb-4 hidden" data-mailer-driver="smtp">
                <form action="{{ route('admin.settings.save') }}" class="space-y-4">
                    <div>
                        <label for="mail[mailers][smtp][host]" class="block text-sm font-medium text-gray-700"><span class="text-red-600">*</span>主机地址</label>
                        <x-input type="text" name="mail[mailers][smtp][host]" id="mail[mailers][smtp][host]" value="{{ $configs['mail']['mailers']['smtp']['host'] ?? '' }}" placeholder="请输入 SMTP 主机地址"/>
                    </div>
                    <div>
                        <label for="mail[mailers][smtp][port]" class="block text-sm font-medium text-gray-700"><span class="text-red-600">*</span>连接端口</label>
                        <x-input type="number" name="mail[mailers][smtp][port]" id="mail[mailers][smtp][port]" value="{{ $configs['mail']['mailers']['smtp']['port'] ?? 587 }}" placeholder="请输入 SMTP 主机连接端口"/>
                    </div>
                    <div>
                        <label for="mail[mailers][smtp][username]" class="block text-sm font-medium text-gray-700"><span class="text-red-600">*</span>用户名</label>
                        <x-input type="text" name="mail[mailers][smtp][username]" id="mail[mailers][smtp][username]" value="{{ $configs['mail']['mailers']['smtp']['username'] ?? '' }}" placeholder="请输入用户名"/>
                    </div>
                    <div>
                        <label for="mail[mailers][smtp][password]" class="block text-sm font-medium text-gray-700"><span class="text-red-600">*</span>密码</label>
                        <x-input type="password" name="mail[mailers][smtp][password]" id="mail[mailers][smtp][password]" value="{{ $configs['mail']['mailers']['smtp']['password'] ?? '' }}" placeholder="请输入密码"/>
                    </div>
                    <div>
                        <label for="mail[mailers][smtp][encryption]" class="block text-sm font-medium text-gray-700">加密方式</label>
                        <x-input type="text" name="mail[mailers][smtp][encryption]" id="mail[mailers][smtp][encryption]" value="{{ $configs['mail']['mailers']['smtp']['encryption'] ?? '' }}" placeholder="请输入加密方式(ssl, tls)"/>
                    </div>
                    <div>
                        <label for="mail[mailers][smtp][timeout]" class="block text-sm font-medium text-gray-700">连接超时时间(秒)</label>
                        <x-input type="number" name="mail[mailers][smtp][timeout]" id="mail[mailers][smtp][timeout]" value="{{ $configs['mail']['mailers']['smtp']['timeout'] ?? 10 }}" placeholder="请输入连接超时时间(秒)"/>
                    </div>
                    <div>
                        <label for="mail[mailers][smtp][from_address]" class="block text-sm font-medium text-gray-700">发件人地址</label>
                        <x-input type="email" name="mail[from][address]" id="mail[from][address]" value="{{ $configs['mail']['from']['address'] ?? '' }}" placeholder="请输入发件人邮箱地址"/>
                    </div>
                    <div>
                        <label for="mail[mailers][smtp][from_name]" class="block text-sm font-medium text-gray-700">发件人名称</label>
                        <x-input type="text" name="mail[from][name]" id="mail[from][name]" value="{{ $configs['mail']['from']['name'] ?? '' }}" placeholder="请输入发件人名称"/>
                    </div>

                    <input type="hidden" name="mail[default]" value="smtp">
                    <input type="hidden" name="mail[mailers][smtp][transport]" value="smtp">

                    <div class="text-right">
                        <x-button type="button" id="mail-test" class="bg-yellow-500">测试</x-button>
                        <x-button type="submit">保存更改</x-button>
                    </div>
                </form>
            </div>
        </div>

        <p class="mb-3 font-semibold text-lg text-gray-700">系统升级</p>
        <div class="relative p-4 rounded-md bg-white mb-8 shadow-custom">
            <p id="check-update" class="text-gray-600 text-center p-4" style="display: none">
                <i class="fas fa-cog animate-spin"></i> 正在检查更新...
            </p>
            <p id="not-update" class="text-center p-6" style="display: none">
                <span class="text-gray-700">{{ \App\Utils::config(\App\Enums\ConfigKey::AppVersion) }}</span>
                <span class="text-gray-500">已是最新版本</span>
            </p>
            <div id="have-update" class="break-words" style="display: none"></div>
        </div>
    </div>

    <script type="text/html" id="update-tpl">
        <div class="flex items-center">
            <img id="icon" src="__icon__" alt="icon" class="rounded-full w-16" style="animation-duration: 5s">
            <div class="flex flex-col text-gray-700 ml-4">
                <p class="font-semibold">Lsky Pro __name__</p>
                <p class="text-sm">__size__</p>
                <p class="text-sm">发布于 __pushed_at__</p>
            </div>
        </div>
        <p id="upgrade-message" class="mt-4 text-sm text-gray-500"></p>
        <div class="mt-4 text-sm markdown-body">
            __changelog__
        </div>
        <div class="mt-6 text-right">
            <a href="javascript:void(0)" id="install" class="rounded-md px-4 py-2 bg-blue-500 text-white">立即安装</a>
        </div>
    </script>

    @push('scripts')
        <script>
            // 设置选中驱动
            let setSelected = function () {
                $('[data-select]').each(function () {
                    $(`[data-${$(this).data('select')}-driver=${$(this).val()}]`)[this.checked ? 'show' : 'hide']();
                });
            };

            let setMysqlBackupVisible = function () {
                $('#mysql-backup-options')[$('[name="is_enable_mysql_backup"][value="1"]').is(':checked') ? 'show' : 'hide']();
            };

            setSelected();
            setMysqlBackupVisible();

            $('[data-select]').click(function () {
                setSelected();
            });

            $('[name="is_enable_mysql_backup"]').change(function () {
                setMysqlBackupVisible();
            });

            $('form').submit(function (e) {
                e.preventDefault();
                axios.put(this.action, $(this).serialize()).then(function (response) {
                    toastr[response.data.status ? 'success' : 'error'](response.data.message)
                });
            });

            $('#storage-base-path-apply').click(function () {
                let $button = $(this);
                let basePath = $('#migration_storage_base_path').val();

                Swal.fire({
                    title: '确认应用新的存储总路径？',
                    text: '这会修改本地储存策略的图片目录和访问地址。迁移期间请确保旧站已停止写入，且新路径已经挂载可写。',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '确认应用',
                    cancelButtonText: '取消',
                }).then(result => {
                    if (! result.isConfirmed) {
                        return;
                    }

                    $button.attr('disabled', true).addClass('cursor-not-allowed bg-gray-400').removeClass('bg-blue-500').text('应用中...');
                    axios.post('{{ route('admin.settings.storage-base-path.apply') }}', {
                        storage_base_path: basePath,
                    }).then(response => {
                        if (response.data.status) {
                            toastr.success(response.data.message);
                            setTimeout(() => window.location.reload(), 800);
                        } else {
                            toastr.error(response.data.message);
                        }
                    }).catch(error => {
                        toastr.error(error.message || '应用失败');
                    }).finally(() => {
                        $button.attr('disabled', false).removeClass('cursor-not-allowed bg-gray-400').addClass('bg-blue-500').text('应用到本地储存策略');
                    });
                });
            });

            $('#mysql-backup-upload').click(function () {
                let file = $('#mysql-backup-file')[0].files[0];
                if (! file) {
                    toastr.error('请选择要上传的 .sql.gz 备份文件');
                    return;
                }

                let $button = $(this);
                let formData = new FormData();
                formData.append('backup', file);

                $button.attr('disabled', true).addClass('cursor-not-allowed bg-gray-400').removeClass('bg-blue-500').text('上传中...');
                axios.post('{{ route('admin.settings.mysql.backup.upload') }}', formData, {
                    headers: {'Content-Type': 'multipart/form-data'},
                }).then(response => {
                    if (response.data.status) {
                        toastr.success(response.data.message);
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        toastr.error(response.data.message);
                    }
                }).catch(error => {
                    toastr.error(error.message || '上传失败');
                }).finally(() => {
                    $button.attr('disabled', false).removeClass('cursor-not-allowed bg-gray-400').addClass('bg-blue-500').text('上传备份文件');
                });
            });

            $('#mysql-backup-now').click(function () {
                let $button = $(this);
                let $form = $button.closest('form');
                let $result = $('#mysql-backup-result').hide().html('');
                $button.attr('disabled', true).addClass('cursor-not-allowed bg-gray-400').removeClass('bg-yellow-500').text('备份中...');
                axios.put($form.attr('action'), $form.serialize()).then(response => {
                    if (! response.data.status) {
                        throw new Error(response.data.message);
                    }
                    return axios.post('{{ route('admin.settings.mysql.backup') }}');
                }).then(response => {
                    if (response.data.status) {
                        let backup = response.data.data.backup;
                        $result.html(`备份文件：${backup.filename}<br>保存路径：${backup.path}<br>文件大小：${backup.human_size}<br><a class="text-blue-600 underline" href="${backup.download_url}">下载备份文件</a>`).show();
                        toastr.success(response.data.message);
                    } else {
                        toastr.error(response.data.message);
                    }
                }).catch(error => {
                    toastr.error(error.message || '备份失败');
                }).finally(() => {
                    $button.attr('disabled', false).removeClass('cursor-not-allowed bg-gray-400').addClass('bg-yellow-500').text('立即备份数据库');
                });
            });

            $('#mail-test').click(function () {
                Swal.fire({
                    title: '请输入接收测试邮件的邮箱',
                    input: 'text',
                    inputValue: '',
                    inputAttributes: {
                        type: 'email',
                        autocapitalize: 'off'
                    },
                    showCancelButton: true,
                    confirmButtonText: '确认',
                    showLoaderOnConfirm: true,
                    preConfirm: (value) => {
                        return axios.post('{{ route('admin.settings.mail.test') }}', {
                            email: value,
                        }).then(response => {
                            if (! response.data.status) {
                                throw new Error(response.data.message)
                            }
                            return response.data;
                        }).catch(error => Swal.showValidationMessage(error));
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        toastr[result.value.status ? 'success' : 'warning'](result.value.message);
                    }
                })
            });

            let timer;
            let upgrade = function () {
                return {
                    start: function () {
                        $('#icon').addClass('animate-spin')
                        $('#install').attr('disabled', true).removeClass('bg-blue-500').addClass('cursor-not-allowed bg-gray-400').text('执行升级中...')
                        $('#upgrade-message').text('准备升级...').removeClass('text-red-500').addClass('text-gray-500');

                        timer = setInterval(getProgress, 1500);
                        axios.post('{{ route('admin.settings.upgrade') }}');
                    },
                    stop: function () {
                        $('#icon').removeClass('animate-spin')
                        $('#install').attr('disabled', false).removeClass('cursor-not-allowed bg-gray-400').addClass('bg-blue-500').text('立即安装')
                        clearInterval(timer);
                    }
                };
            };

            let getVersion = function (callback) {
                $('#check-update').show();
                axios.get('{{ route('admin.settings.check.update') }}').then(response => {
                    if (response.data.status && response.data.data.is_update) {
                        $('#check-update').hide();
                        let version = response.data.data.version;
                        let html = $('#update-tpl').html()
                            .replace(/__icon__/g, version.icon)
                            .replace(/__name__/g, version.name)
                            .replace(/__size__/g, version.size)
                            .replace(/__pushed_at__/g, version.pushed_at)
                            .replace(/__changelog__/g, version.changelog);
                        $('#have-update').html(html).show();
                        $('.markdown-body a').attr('target', '_blank');
                        callback && callback(version);
                    } else {
                        $('#not-update').show();
                        $('#check-update').hide();
                    }
                });
            }

            let getProgress = function () {
                axios.get('{{ route('admin.settings.upgrade.progress') }}').then(response => {
                    $('#upgrade-message').text(response.data.data.message);
                    if (response.data.data.status === 'success') {
                        $('#upgrade-message').removeClass('text-gray-500').addClass('text-green-500');
                        $('#install').hide();
                    }
                    if (response.data.data.status === 'fail') {
                        $('#upgrade-message').removeClass('text-gray-500').addClass('text-red-500');
                    }
                    if (response.data.data.status !== 'installing') {
                        upgrade().stop();
                    }
                });
            };

            $(document).on('click', '#install', function () {
                if ($(this).attr('disabled')) {
                    return;
                }
                upgrade().start();
            });

            @if(cache()->has('upgrade_progress'))
                getVersion(() => {
                    $('#icon').addClass('animate-spin')
                    $('#install').attr('disabled', true).removeClass('bg-blue-500').addClass('cursor-not-allowed bg-gray-400').text('正在升级...')
                    $('#upgrade-message').text('请稍等...').removeClass('text-red-500').addClass('text-gray-500');

                    timer = setInterval(getProgress, 1500);
                });
                @else
                getVersion();
            @endif
        </script>
    @endpush

</x-app-layout>
