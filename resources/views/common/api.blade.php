@section('title', '接口')

<x-app-layout>
    <div class="my-6 md:my-9">
        <p class="text-xl mb-2 text-gray-800 font-semibold">API Token</p>
        <div class="space-y-4 bg-white p-3 rounded-md mb-10 shadow-custom">
            <div class="rounded bg-blue-50 p-3 text-sm text-blue-800 space-y-1">
                <p>这里生成的是给第三方客户端使用的 API Token，比如 Halo 的 Lsky Pro 插件。</p>
                <p>Token 只会在创建成功时显示一次。已有 Token 的明文无法再次查看，丢失后请重新生成。</p>
                <p>接口地址：<span class="font-mono">{{ url('/api/v1') }}</span></p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                <div>
                    <label for="api_token_name" class="block text-sm font-medium text-gray-700">Token 名称</label>
                    <x-input type="text" id="api_token_name" value="Halo" placeholder="例如：Halo、PicGo、Obsidian" />
                </div>
                <div>
                    <label for="api_token_expiry" class="block text-sm font-medium text-gray-700">有效期</label>
                    <x-select id="api_token_expiry" class="mt-1">
                        <option value="1_month">1个月</option>
                        <option value="6_months">半年</option>
                        <option value="1_year">1年</option>
                        <option value="3_years">3年</option>
                        <option value="5_years">5年</option>
                        <option value="never" selected>无限</option>
                        <option value="custom_date">具体日期</option>
                    </x-select>
                </div>
                <div id="api-token-custom-date-wrap" class="hidden">
                    <label for="api_token_expiry_date" class="block text-sm font-medium text-gray-700">具体日期</label>
                    <x-input type="date" id="api_token_expiry_date" />
                </div>
            </div>

            <div class="text-right">
                <x-button type="button" id="create-api-token" class="w-full sm:w-auto">生成 API Token</x-button>
            </div>

            <div id="api-token-result" class="hidden rounded bg-green-50 p-3 text-sm text-green-700 space-y-2">
                <p id="api-token-message"></p>
                <p class="text-sm text-green-700">备注：该 Token 只会展示一次，之后只能重新申请。</p>
                <x-code id="api-token-value" class="my-0"></x-code>
                <div class="flex flex-wrap gap-2">
                    <x-button type="button" id="copy-api-token" class="bg-blue-500">复制 Token</x-button>
                    <x-button type="button" id="close-api-token-result" class="bg-gray-500">关闭</x-button>
                </div>
            </div>

            <div class="rounded border border-gray-100 overflow-hidden">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 font-medium">名称</th>
                        <th class="px-3 py-2 font-medium">有效期</th>
                        <th class="px-3 py-2 font-medium">创建时间</th>
                        <th class="px-3 py-2 font-medium">最近使用</th>
                        <th class="px-3 py-2 font-medium">备注用户</th>
                    </tr>
                    </thead>
                    <tbody id="api-token-table-body" class="divide-y divide-gray-100">
                    @forelse($apiTokens as $token)
                        <tr>
                            <td class="px-3 py-2">{{ $token->name }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $token->expires_at ? $token->expires_at->format('Y-m-d H:i:s') : '无限' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ optional($token->created_at)->format('Y-m-d H:i:s') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ optional($token->last_used_at)->format('Y-m-d H:i:s') ?: '未使用' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $user->name }} / {{ $currentUserNote }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-gray-500 text-center">还没有 API Token</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="text-right">
                <x-button type="button" id="clear-api-tokens" class="bg-red-500">清空全部 API Token</x-button>
            </div>
        </div>

        <p class="text-xl mb-2 text-gray-800 font-semibold">相册 ID</p>
        <div class="space-y-4 bg-white p-3 rounded-md mb-10 shadow-custom">
            <div class="rounded bg-gray-50 p-3 text-sm text-gray-700 space-y-1">
                <p>这里列出你当前账号下可见的相册 ID。新增、修改或删除相册后，这个表会在你下次打开接口页时自动按最新数据显示。</p>
                <p>移动图片到不同相册不会影响图片外链。</p>
                <p>当前查看用户：{{ $user->name }} / {{ $currentUserNote }}</p>
            </div>
            <div class="rounded border border-gray-100 overflow-hidden">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 font-medium">相册 ID</th>
                        <th class="px-3 py-2 font-medium">名称</th>
                        <th class="px-3 py-2 font-medium">图片数</th>
                        <th class="px-3 py-2 font-medium">简介</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($albums as $album)
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap font-mono">{{ $album->id }}</td>
                            <td class="px-3 py-2">{{ $album->name }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $album->image_num }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $album->intro ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-4 text-gray-500 text-center">还没有相册</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-xl mb-2 text-gray-800 font-semibold">存储策略 ID</p>
        <div class="space-y-4 bg-white p-3 rounded-md mb-10 shadow-custom">
            <div class="rounded bg-gray-50 p-3 text-sm text-gray-700 space-y-1">
                <p>这里列出你当前角色组可用的存储策略 ID。新增、修改或调整角色组绑定关系后，这个表会在你下次打开接口页时自动按最新数据显示。</p>
                <p>当前查看用户：{{ $user->name }} / {{ $currentUserNote }}</p>
            </div>
            <div class="rounded border border-gray-100 overflow-hidden">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 font-medium">策略 ID</th>
                        <th class="px-3 py-2 font-medium">名称</th>
                        <th class="px-3 py-2 font-medium">类型</th>
                        <th class="px-3 py-2 font-medium">简介</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($strategies as $strategy)
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap font-mono">{{ $strategy->id }}</td>
                            <td class="px-3 py-2">{{ $strategy->name }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ \App\Models\Strategy::DRIVERS[$strategy->key] ?? $strategy->key }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $strategy->intro ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-4 text-gray-500 text-center">当前角色组还没有可用存储策略</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-xl mb-2 text-gray-800 font-semibold">接口说明</p>
        <div class="space-y-4 bg-white p-3 rounded-md mb-10 shadow-custom">
            <div>
                <p class="text-lg text-gray-700 font-semibold">接口URL</p>
                <x-code>{{ request()->getSchemeAndHttpHost() }}/api/v1</x-code>
            </div>

            <div>
                <p class="text-lg text-gray-700 font-semibold">验证方式</p>
                <div class="my-2 text-sm bg-white rounded-md p-4 overflow-x-auto">
                    当前版本接口采用 「HTTP 基本验证」的方式验证授权，获取到 token 后，通过设置请求 header 标头来验证请求(Bearer Token)，例如：
                    <b class="block my-2 text-gray-600 text-sm">"Authorization": "Bearer 1|1bJbwlqBfnggmOMEZqXT5XusaIwqiZjCDs7r1Ob5"</b>
                    <p class="text-sm">如果未设置 Authorization 的情况下请求上传接口，将会被视为游客上传。</p>
                </div>
            </div>

            <div class="my-4 overflow-x-auto">
                <p class="text-sm mb-2">公共请求 headers 说明</p>
                <table class="min-w-full">
                    <thead class="bg-white border">
                    <tr>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                            字段
                        </th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                            类型
                        </th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                            说明
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white border divide-y text-sm">
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap">Authorization</td>
                        <td class="px-3 py-2 whitespace-nowrap">String</td>
                        <td class="px-3 py-2 whitespace-nowrap">授权 Token，例如：Bearer 1|1bJbwlqBfnggmOMEZqXT5XusaIwqiZjCDs7r1Ob5</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap"><span class="text-red-500">*</span>Accept</td>
                        <td class="px-3 py-2 whitespace-nowrap">String</td>
                        <td class="px-3 py-2 whitespace-nowrap">必须设置为 application/json</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="my-4 overflow-x-auto">
                <p class="text-sm mb-2">公共响应 headers 说明</p>
                <table class="min-w-full">
                    <thead class="bg-white border">
                    <tr>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                            字段
                        </th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                            类型
                        </th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                            说明
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white border divide-y text-sm">
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap">X-RateLimit-Limit</td>
                        <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                        <td class="px-3 py-2 whitespace-nowrap">当前客户端一分钟内请求配额</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap">X-RateLimit-Remaining</td>
                        <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                        <td class="px-3 py-2 whitespace-nowrap">当前客户端剩余请求配额</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="my-4 overflow-x-auto">
                <p class="text-sm mb-2">响应状态码 HTTP Status Code 说明</p>
                <table class="min-w-full">
                    <thead class="bg-white border">
                    <tr>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                            状态码
                        </th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                            说明
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white border divide-y text-sm">
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap">401</td>
                        <td class="px-3 py-2 whitespace-nowrap">未登录或授权失败</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap">403</td>
                        <td class="px-3 py-2 whitespace-nowrap">管理员关闭了接口功能</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap">429</td>
                        <td class="px-3 py-2 whitespace-nowrap">超出请求配额，请求受限</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap">500</td>
                        <td class="px-3 py-2 whitespace-nowrap">服务端出现异常</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <p class="text-sm my-2 text-red-500">文档中接口的请求参数，使用红色「*」符号标注，则表示为必传项。</p>
        </div>

        <p class="text-xl mb-2 text-gray-800 font-semibold">授权相关</p>
        <div class="space-y-4 bg-white p-3 rounded-md mb-10 shadow-custom">
            <div>
                <p class="text-lg text-gray-700 font-semibold">生成 Token</p>
                <x-code><span class="text-green-500 select-none">POST </span>/tokens</x-code>
                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">请求参数(Body)</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap"><span class="text-red-500">*</span>email</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">邮箱</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap"><span class="text-red-500">*</span>password</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">密码</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">返回参数</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">status</td>
                            <td class="px-3 py-2 whitespace-nowrap">Boolean</td>
                            <td class="px-3 py-2 whitespace-nowrap">状态，true 或 false</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">message</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">描述信息</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">data</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object</td>
                            <td class="px-3 py-2 whitespace-nowrap">数据</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">token</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">Token</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <p class="text-lg text-gray-700 font-semibold">清空 Token</p>
                <x-code><span class="text-red-300 select-none">DELETE </span>/tokens</x-code>
                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">返回参数</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">status</td>
                            <td class="px-3 py-2 whitespace-nowrap">Boolean</td>
                            <td class="px-3 py-2 whitespace-nowrap">状态，true 或 false</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">message</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">描述信息</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">data</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object</td>
                            <td class="px-3 py-2 whitespace-nowrap">数据</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <p class="text-lg text-gray-700 font-semibold">用户资料</p>
                <x-code><span class="text-sky-500 select-none">GET </span>/profile</x-code>
                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">返回参数</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">status</td>
                            <td class="px-3 py-2 whitespace-nowrap">Boolean</td>
                            <td class="px-3 py-2 whitespace-nowrap">状态，true 或 false</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">message</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">描述信息</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">data</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object</td>
                            <td class="px-3 py-2 whitespace-nowrap">数据</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">name</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">用户名</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">avatar</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">头像地址</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">email</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">邮箱地址</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">capacity</td>
                            <td class="px-3 py-2 whitespace-nowrap">Float</td>
                            <td class="px-3 py-2 whitespace-nowrap">总容量</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">used_capacity</td>
                            <td class="px-3 py-2 whitespace-nowrap">Float</td>
                            <td class="px-3 py-2 whitespace-nowrap">已使用容量</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">url</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">个人主页地址</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">image_num</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片数量</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">album_num</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">相册数量</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">registered_ip</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">注册 IP</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <p class="text-xl mb-2 text-gray-800 font-semibold">策略相关</p>
        <div class="space-y-4 bg-white p-3 rounded-md mb-10 shadow-custom">
            <div>
                <p class="text-lg text-gray-700 font-semibold">策略列表</p>
                <x-code><span class="text-sky-500 select-none">GET </span>/strategies</x-code>

                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">请求参数(Query)</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">keyword</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">筛选关键字</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">返回参数</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">status</td>
                            <td class="px-3 py-2 whitespace-nowrap">Boolean</td>
                            <td class="px-3 py-2 whitespace-nowrap">状态，true 或 false</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">message</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">描述信息</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">data</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object</td>
                            <td class="px-3 py-2 whitespace-nowrap">数据</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">strategies</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object[]</td>
                            <td class="px-3 py-2 whitespace-nowrap">策略数据</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">id</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">策略 ID</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">name</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">策略名称</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <p class="text-xl mb-2 text-gray-800 font-semibold">图片相关</p>
        <div class="space-y-4 bg-white p-3 rounded-md mb-10 shadow-custom">
            <div>
                <p class="text-lg text-gray-700 font-semibold">上传图片</p>
                <x-code><span class="text-green-500 select-none">POST </span>/upload</x-code>

                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">Headers</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap"><span class="text-red-500">*</span>Content-Type</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">需要设置为 multipart/form-data</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">请求参数(Body)</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap"><span class="text-red-500">*</span>file</td>
                            <td class="px-3 py-2 whitespace-nowrap">File</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片文件</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">strategy_id</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">储存策略ID</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">返回参数</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">status</td>
                            <td class="px-3 py-2 whitespace-nowrap">Boolean</td>
                            <td class="px-3 py-2 whitespace-nowrap">状态，true 或 false</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">message</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">描述信息</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">data</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object</td>
                            <td class="px-3 py-2 whitespace-nowrap">数据</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">key</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片唯一密钥</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">name</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片名称</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">pathname</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片路径名</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">origin_name</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片原始名</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">size</td>
                            <td class="px-3 py-2 whitespace-nowrap">Float</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片大小，单位 KB</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">mimetype</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片类型</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">extension</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片拓展名</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">md5</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片 md5 值</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">sha1</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片 sha1 值</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">links</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object</td>
                            <td class="px-3 py-2 whitespace-nowrap">链接</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">url</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片访问 url</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">html</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">-</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">bbcode</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">-</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">markdown</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">-</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">markdown_with_link</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">-</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">thumbnail_url</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">缩略图 url</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <p class="text-lg text-gray-700 font-semibold">图片列表</p>
                <x-code><span class="text-sky-500 select-none">GET </span>/images</x-code>

                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">请求参数(Query)</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">page</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">页码</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">order</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">排序方式，newest=最新，earliest=最早，utmost=最大，least=最小</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">permission</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">权限，public=公开的，private=私有的</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">album_id</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">相册 ID</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">keyword</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">筛选关键字</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">返回参数</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">status</td>
                            <td class="px-3 py-2 whitespace-nowrap">Boolean</td>
                            <td class="px-3 py-2 whitespace-nowrap">状态，true 或 false</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">message</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">描述信息</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">data</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object</td>
                            <td class="px-3 py-2 whitespace-nowrap">数据</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">current_page</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">当前所在页页码</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">last_page</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">最后一页页码</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">per_page</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">每页展示数据数量</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">total</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片总数量</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">data</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object[]</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片列表</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">key</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片唯一密钥</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">name</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片名称</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">origin_name</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片原始名称</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">pathname</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片路径名</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">size</td>
                            <td class="px-3 py-2 whitespace-nowrap">Float</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片大小，单位 KB</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">width</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片宽度</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">height</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片高度</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">md5</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片 md5 值</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">sha1</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片 sha1 值</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">human_date</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">上传时间(友好格式)</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">date</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">上传日期(yyyy-MM-dd HH:mm:ss)</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">links</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object</td>
                            <td class="px-3 py-2 whitespace-nowrap">链接，与上传接口返回参数中的 links 相同</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <p class="text-lg text-gray-700 font-semibold">删除图片</p>
                <x-code><span class="text-red-300 select-none">DELETE </span>/images/:key</x-code>

                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">请求参数(Params)</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap"><span class="text-red-500">*</span>key</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片密钥</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">返回参数</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">status</td>
                            <td class="px-3 py-2 whitespace-nowrap">Boolean</td>
                            <td class="px-3 py-2 whitespace-nowrap">状态，true 或 false</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">message</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">描述信息</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">data</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object</td>
                            <td class="px-3 py-2 whitespace-nowrap">数据</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <p class="text-xl mb-2 text-gray-800 font-semibold">相册相关</p>
        <div class="space-y-4 bg-white p-3 rounded-md mb-10 shadow-custom">
            <div>
                <p class="text-lg text-gray-700 font-semibold">相册列表</p>
                <x-code><span class="text-sky-500 select-none">GET </span>/albums</x-code>

                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">请求参数(Query)</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">page</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">页码</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">order</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">排序方式，newest=最新，earliest=最早，most=图片最多，least=图片最少</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">keyword</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">筛选关键字</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">返回参数</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">status</td>
                            <td class="px-3 py-2 whitespace-nowrap">Boolean</td>
                            <td class="px-3 py-2 whitespace-nowrap">状态，true 或 false</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">message</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">描述信息</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">data</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object</td>
                            <td class="px-3 py-2 whitespace-nowrap">数据</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">current_page</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">当前所在页页码</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">last_page</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">最后一页页码</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">per_page</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">每页展示数据数量</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">total</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">图片总数量</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-6">data</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object[]</td>
                            <td class="px-3 py-2 whitespace-nowrap">相册列表</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">id</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">相册自增 ID</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">name</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">相册名称</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">intro</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">相册简介</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap pl-10">image_num</td>
                            <td class="px-3 py-2 whitespace-nowrap">Integer</td>
                            <td class="px-3 py-2 whitespace-nowrap">相册图片数量</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <p class="text-lg text-gray-700 font-semibold">删除相册</p>
                <x-code><span class="text-red-300 select-none">DELETE </span>/albums/:id</x-code>

                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">请求参数(Params)</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap"><span class="text-red-500">*</span>id</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">相册自增 ID</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="my-4 overflow-x-auto">
                    <p class="text-sm mb-2">返回参数</p>
                    <table class="min-w-full">
                        <thead class="bg-white border">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                字段
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                类型
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                说明
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white border divide-y text-sm">
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">status</td>
                            <td class="px-3 py-2 whitespace-nowrap">Boolean</td>
                            <td class="px-3 py-2 whitespace-nowrap">状态，true 或 false</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">message</td>
                            <td class="px-3 py-2 whitespace-nowrap">String</td>
                            <td class="px-3 py-2 whitespace-nowrap">描述信息</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap">data</td>
                            <td class="px-3 py-2 whitespace-nowrap">Object</td>
                            <td class="px-3 py-2 whitespace-nowrap">数据</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            let toggleApiTokenCustomDate = function () {
                $('#api-token-custom-date-wrap')[$('#api_token_expiry').val() === 'custom_date' ? 'removeClass' : 'addClass']('hidden');
            };

            toggleApiTokenCustomDate();

            $('#api_token_expiry').change(function () {
                toggleApiTokenCustomDate();
            });

            $('#create-api-token').click(function () {
                let $button = $(this);
                let $result = $('#api-token-result');
                let name = $('#api_token_name').val();
                let expiresType = $('#api_token_expiry').val();
                let expiresAtDate = $('#api_token_expiry_date').val();

                $button.attr('disabled', true);
                axios.post('{{ route('settings.tokens.create') }}', {
                    name: name,
                    expires_type: expiresType,
                    expires_at_date: expiresAtDate,
                }).then(response => {
                    if (! response.data.status) {
                        toastr.error(response.data.message);
                        return;
                    }

                    let token = response.data.data.token;
                    let expiresAt = response.data.data.expires_at || '无限';
                    $('#api-token-message').text(`Token 名称：${response.data.data.name}，有效期：${expiresAt}`);
                    $('#api-token-value').text(token);
                    $result.show();
                    $('#api-token-table-body').find('td[colspan="5"]').closest('tr').remove();
                    $('#api-token-table-body').prepend(`
                        <tr>
                            <td class="px-3 py-2">${response.data.data.name}</td>
                            <td class="px-3 py-2 whitespace-nowrap">${expiresAt}</td>
                            <td class="px-3 py-2 whitespace-nowrap">${new Date().toLocaleString('sv-SE').replace('T', ' ')}</td>
                            <td class="px-3 py-2 whitespace-nowrap">未使用</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $user->name }} / {{ $currentUserNote }}</td>
                        </tr>
                    `);
                    toastr.success(response.data.message);
                }).catch(error => {
                    toastr.error(error.message || '生成 Token 失败');
                }).finally(() => {
                    $button.attr('disabled', false);
                });
            });

            $('#copy-api-token').click(function () {
                let token = $('#api-token-value').text().trim();
                navigator.clipboard.writeText(token).then(() => {
                    toastr.success('Token 已复制');
                }).catch(() => {
                    toastr.error('复制失败，请手动复制');
                });
            });

            $('#close-api-token-result').click(function () {
                $('#api-token-result').hide();
            });

            $('#clear-api-tokens').click(function () {
                Swal.fire({
                    title: '确认清空全部 API Token？',
                    text: '清空后，所有第三方客户端都会立即失效，需要重新生成并重新配置。',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '确认清空',
                    cancelButtonText: '取消',
                }).then(result => {
                    if (! result.isConfirmed) {
                        return;
                    }

                    axios.delete('{{ route('settings.tokens.clear') }}').then(response => {
                        if (response.data.status) {
                            toastr.success(response.data.message);
                            window.location.reload();
                        } else {
                            toastr.error(response.data.message);
                        }
                    }).catch(error => {
                        toastr.error(error.message || '清空失败');
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
