# Lsky Pro 架构设计文档

> 基于 PRD 对 Lsky Pro 图床项目进行架构设计，涵盖安全优化、图片自动压缩、性能优化、UI 美化、Docker 部署。

---

## 1. 实现方案 + 框架选型

### 1.1 安全加固方案

采用分层防御策略，在现有中间件链和业务逻辑中嵌入安全控制：

| 层级 | 策略 | 实现方式 |
|------|------|----------|
| **输入层** | 文件上传安全 | 新增 `CheckUploadSecurity` 中间件, 在请求到达 Controller 前校验 MIME 类型、文件头 magic bytes、文件名路径遍历、文件大小二次校验 |
| **存储层** | 文件路径安全 | `ImageService::replacePathname()` 中过滤 `../` 路径穿越，使用 `basename()` 剥离目录 |
| **输出层** | XSS 防护 | Blade 模板中所有用户可控输出使用 `{{ }}` 自动转义，已有 `{!! !!}` 的位置改为 `{{ }}` + 白名单过滤 |
| **CSRF** | Token 验证 | 已有 `VerifyCsrfToken` 中间件，API 路由组使用 Sanctum；确认 `api.php` 中所有写操作已配 token |
| **会话安全** | Cookie 加固 | `session.php` 中 `http_only` 已为 `true`，确认 `secure` 在生产环境启用、`same_site` 保持 `lax` |
| **速率限制** | 上传限流 | 已有 `ImageService::rateLimiter()`，确认 `throttle:api` 中间件在 API 路由组已启用 |
| **权限校验** | 资源隔离 | 确认 `ImageController::destroy()` 和 `UserService::deleteImages()` 做了用户归属校验 |

#### 需要新增/修改的安全点

- **SEC-01**: 新建 `app/Http/Middleware/CheckUploadSecurity.php` — 文件 MIME 白名单校验、文件名净化
- **SEC-02**: 修改 `app/Services/ImageService.php` — `replacePathname()` 中增加路径遍历防护
- **SEC-03**: 在所有用户可写文本输入位置（如相册名、别名）输出前做 XSS 过滤，新增 `app/Security/XssSanitizer.php`
- **SEC-04**: 确认 API token 刷新机制，延长 Sanctum token 过期时间可配置
- **SEC-05**: 新增 `app/Http/Middleware/ContentSecurityPolicy.php` — 设置 CSP header（`script-src 'self'`, `img-src 'self' *` 等）
- **SEC-06**: 确认管理员路由组 `auth.admin` 中间件有完整的权限校验
- **SEC-07**: `config/session.php` 中增加 `secure` 和 `same_site` 的生产环境强制覆盖
- **SEC-08**: 图片外链防盗链 — 可选实现 Referer 校验中间件
- **SEC-09**: 文件上传大小和类型检查下沉至请求层面，使用 FormRequest 类
- **SEC-10**: 敏感信息泄漏防护 — 异常返回时隐藏详细错误信息（已有部分，确认全面）
- **SEC-11**: 对 SVG 文件上传做安全清洗，移除 script 标签和事件处理器
- **SEC-12**: SQL 注入防护 — 已有 Eloquent ORM 参数绑定，确认所有 raw query 已使用参数化查询

### 1.2 图片自动压缩架构

在现有 `ImageService::store()` 流程中，在图片处理阶段（`// 图片处理，跳过 ico gif svg` 代码块后）插入压缩模块：

```
store() 流程插入点:
  ┌─ 1. 校验权限/频率
  ├─ 2. 检查文件类型/大小
  ├─ 3. 现有图片处理（质量/格式/水印）
  ├─ 4. ★ 新增: 自动压缩模块 (compress)
  │     ├─ 质量压缩 (quality)
  │     ├─ 尺寸缩放 (resize)
  │     ├─ 格式转换 (format -> WebP 等)
  │     └─ 按组配置驱动 (GroupConfigKey)
  ├─ 5. 计算哈希、填充Image记录
  ├─ 6. 写入存储策略
  └─ 7. 图片审核、缩略图
```

**关键设计决策**:
- 压缩发生在水印之后，确保水印图片也被压缩
- 跳过格式已在 `ico/gif/svg` 的基础上配置可扩展
- 压缩配置存储在 `Group.configs`（JSON 字段），新增 `GroupConfigKey` 常量
- 使用 `Intervention Image` 库进行压缩处理（项目中已依赖）
- WebP 转换条件：目标格式设为 `webp` 且 PHP 支持（GD 需编译 `libwebp`，Imagick 需支持 WebP）

### 1.3 暗色主题实施方案

**技术选型**: Tailwind CSS `darkMode: 'class'` + CSS 变量

**实现步骤**:
1. 修改 `tailwind.config.js`，启用 `darkMode: 'class'`
2. 新增 `resources/css/dark-theme.css`，定义 CSS 变量覆盖
3. 修改 `layouts/app.blade.php`，在 `<html>` 上添加 `x-data` 控制 `dark` class
4. 新增 `components/theme-toggle.blade.php`，切换按钮
5. 所有 Blade 组件新增 `dark:` 前缀的 Tailwind 类
6. 使用 Alpine.js store 持久化主题偏好到 `localStorage`
7. 前端的 `dark` class 可同步到后端渲染（通过 cookie 或 blade 判断）

**CSS 变量方案**:
```css
/* light (default) */
:root {
  --bg-primary: #ffffff;
  --bg-secondary: #f3f4f6;
  --text-primary: #1f2937;
  --text-secondary: #6b7280;
  --border-color: #e5e7eb;
}

/* dark */
.dark {
  --bg-primary: #1f2937;
  --bg-secondary: #111827;
  --text-primary: #f9fafb;
  --text-secondary: #9ca3af;
  --border-color: #374151;
}
```

### 1.4 性能优化方案

#### 缓存层级

| 缓存层级 | 存储 | 用途 | TTL |
|----------|------|------|-----|
| **L1 - 应用缓存** | Redis/File | 系统配置、路由缓存、视图缓存 | 永久/按需 |
| **L2 - HTTP 缓存** | 浏览器/CDN | 图片输出 (Cache-Control header) | 30天 |
| **L3 - 数据库查询缓存** | Redis | 热门图片列表、用户统计 | 5分钟 |
| **L4 - Octane 常驻内存** | Swoole/ RoadRunner | 应用状态、服务实例 | 请求间 |

#### 索引设计

| 表名 | 新增/修改索引 | 覆盖查询 |
|------|-------------|----------|
| `images` | `INDEX key_extension (key, extension)` | 图片输出查询 (output) |
| `images` | `INDEX strategy_md5_sha1 (strategy_id, md5, sha1)` | 重复图片检测 |
| `images` | `INDEX user_list (user_id, created_at)` | 用户图片列表 + 排序 |
| `images` | `INDEX group_cache (group_id, created_at)` | 组配置缓存查询 |
| `images` | `INDEX uploaded_ip (uploaded_ip)` | 游客上传频率限制 |

#### 具体优化项

- **PERF-01**: 路由缓存 — `route:cache`
- **PERF-02**: 配置缓存 — `config:cache`
- **PERF-03**: 视图缓存 — `view:cache`
- **PERF-04**: 事件缓存 — `event:cache`
- **PERF-05**: 新增 `images` 表索引（如上表）
- **PERF-06**: Redis 缓存驱动 — 环境变量 `CACHE_DRIVER=redis`，配置 `database.redis`
- **PERF-07**: N+1 查询修复 — 检查 `ImageController::images()` 等列表查询，预加载关联模型
- **PERF-08**: 懒加载图片列表 — 前端无限滚动改为 Intersection Observer + 分页
- **PERF-09**: Octane 配置 — 优化 `config/octane.php`，设置 `max_requests` 和 `worker count`
- **PERF-10**: 数据库连接池 — Octane 模式下使用持久连接

### 1.5 Docker 容器化方案

**文件结构**:
```
docker/
  php/
    Dockerfile        # PHP 8.2 + FPM + 扩展
  nginx/
    default.conf      # Nginx 配置
docker-compose.yml    # 编排服务
Dockerfile             # 根目录构建镜像
```

**服务组成**:
| 服务 | 镜像/基础 | 说明 |
|------|----------|------|
| `app` | `php:8.2-fpm-alpine` | PHP-FPM + Composer + 扩展 (gd/imagick/pdo/redis/zip/bcmath) |
| `web` | `nginx:1.25-alpine` | Nginx 静态文件服务 + PHP 代理 |
| `db` | `mysql:8.0` | 数据库（可选外部） |
| `redis` | `redis:7-alpine` | 缓存/会话（可选） |

**关键配置**:
- `APP_RUN_IN_DOCKER=true` 环境变量区分运行环境
- 使用 Docker volumes 持久化上传文件和数据库
- `.env` 通过 Docker 环境变量注入，支持 `docker-compose.env`

---

## 2. 文件列表及相对路径

### 2.1 新增文件

| # | 相对路径 | 所属模块 | 说明 |
|---|---------|----------|------|
| 1 | `app/Http/Middleware/CheckUploadSecurity.php` | SEC | 文件上传安全中间件 |
| 2 | `app/Http/Middleware/ContentSecurityPolicy.php` | SEC | CSP Header 中间件 |
| 3 | `app/Security/XssSanitizer.php` | SEC | XSS 过滤工具类 |
| 4 | `app/Http/Middleware/HotlinkProtection.php` | SEC | 防盗链中间件(可选) |
| 5 | `app/Services/ImageCompressionService.php` | CMP | 图片压缩服务类 |
| 6 | `app/Enums/Compression/DriverOption.php` | CMP | 压缩驱动配置枚举 |
| 7 | `app/Enums/Compression/Mode.php` | CMP | 压缩模式枚举 |
| 8 | `resources/css/dark-theme.css` | UI | 暗色主题 CSS |
| 9 | `resources/views/components/theme-toggle.blade.php` | UI | 主题切换按钮组件 |
| 10 | `app/Http/Requests/UploadRequest.php` | SEC | 上传请求表单校验类 |
| 11 | `docker/php/Dockerfile` | DOCKER | PHP 镜像构建 |
| 12 | `docker/nginx/default.conf` | DOCKER | Nginx 配置 |
| 13 | `docker-compose.yml` | DOCKER | Docker 编排 |
| 14 | `Dockerfile` | DOCKER | 根目录构建入口 |
| 15 | `.dockerignore` | DOCKER | Docker 忽略文件 |
| 16 | `app/Console/Commands/ClearImageCache.php` | PERF | 缓存清理命令 |
| 17 | `app/Http/Middleware/CheckPermission.php` | SEC | 统一权限校验中间件 |

### 2.2 修改文件

| # | 相对路径 | 所属模块 | 修改内容 |
|---|---------|----------|----------|
| 1 | `app/Services/ImageService.php` | SEC/CMP | 新增 `compress()`，`store()` 中集成压缩调用，路径穿越防护 |
| 2 | `app/Models/Image.php` | CMP/PERF | 新增压缩相关字段 cast、fillable；新增索引定义注释 |
| 3 | `app/Models/Group.php` | CMP | `getDefaultConfigs()` 增加压缩配置默认值 |
| 4 | `app/Enums/GroupConfigKey.php` | CMP | 新增压缩相关常量 |
| 5 | `app/Http/Kernel.php` | SEC | 注册新增中间件 |
| 6 | `config/convention.php` | CMP | `group` 段新增压缩默认配置 |
| 7 | `config/image.php` | CMP | 可选增加 WebP 压缩配置 |
| 8 | `config/cache.php` | PERF | Redis 配置启用注释完善 |
| 9 | `resources/views/layouts/app.blade.php` | UI | 暗色主题 <html> 标签、CSS/JS 引入 |
| 10 | `resources/views/layouts/header.blade.php` | UI | 加入主题切换按钮 |
| 11 | `resources/views/layouts/sidebar.blade.php` | UI | 添加 `dark:` 样式类 |
| 12 | `resources/views/components/*.blade.php` | UI | 所有组件加 `dark:` 前缀样式 |
| 13 | `resources/views/user/*.blade.php` | UI | 页面级暗色适配 |
| 14 | `resources/views/admin/**/*.blade.php` | UI | 管理后台暗色适配 |
| 15 | `resources/views/common/*.blade.php` | UI | 公共页面暗色适配 |
| 16 | `resources/css/common.less` | UI | 暗色变量覆盖 |
| 17 | `resources/css/app.css` | UI | 引入 dark-theme.css |
| 18 | `resources/js/app.js` | UI | 主题切换逻辑 |
| 19 | `tailwind.config.js` | UI | 启用 `darkMode: 'class'` |
| 20 | `webpack.mix.js` | UI | 若有额外编译配置 |
| 21 | `database/migrations/*_add_compression_fields_to_images_table.php` | CMP | 新增压缩字段迁移 |
| 22 | `composer.json` | PERF/CMP | 新增依赖包（如有） |
| 23 | `package.json` | UI | 新增前端依赖（如有） |
| 24 | `app/Utils.php` | PERF | 新增缓存工具方法（可选） |
| 25 | `app/Http/Controllers/Controller.php` | PERF/SEC | 视图间全局变量共享 |
| 26 | `app/Http/Controllers/Api/V1/ImageController.php` | SEC | API 上传改用 FormRequest |
| 27 | `routes/web.php` | SEC | 新增中间件路由组 |
| 28 | `.env.example` | DOCKER | 新增 Docker 环境变量 |

---

## 3. 数据结构和接口（类图）

### 3.1 ImageService 增强

```
ImageService (修改)
├── store(Request): Image                      // 已有，增强
│   ├── ... 原有校验和处理逻辑
│   └── 新增: $this->compress($file, $configs) // 在现有图片处理后、哈希计算前调用
├── compress(UploadedFile $file, Collection $groupConfigs): UploadedFile  // ★ 新增
│   ├── 读取组配置中的压缩策略
│   ├── 根据模式执行压缩:
│   │   ├── quality: 调整质量参数
│   │   ├── resize:  按最大宽高缩放
│   │   ├── format:  格式转换 (含 WebP)
│   │   └── auto:    智能组合模式
│   └── 返回压缩后的 UploadedFile 实例
├── rateLimiter(...)                            // 已有
├── scan(...)                                   // 已有
├── stickWatermark(...)                         // 已有
├── makeThumbnail(...)                          // 已有
├── getAdapter(...)                             // 已有
└── getWatermark(...)                           // 已有
```

**`compress()` 方法签名**:
```php
/**
 * 对上传图片执行自动压缩
 *
 * @param UploadedFile $file         原始文件
 * @param Collection   $groupConfigs 组配置
 * @return UploadedFile              压缩后的文件
 * @throws UploadException
 */
public function compress(UploadedFile $file, Collection $groupConfigs): UploadedFile;
```

### 3.2 ImageCompressionService（新增）

```
ImageCompressionService
├── compress(UploadedFile $file, Collection $configs, string $extension): UploadedFile
├── compressByQuality(UploadedFile $file, int $quality): UploadedFile
├── compressByMaxSize(UploadedFile $file, int $maxWidth, int $maxHeight): UploadedFile
├── convertFormat(UploadedFile $file, string $targetFormat, int $quality): UploadedFile
└── compressToWebP(UploadedFile $file, int $quality): UploadedFile
```

### 3.3 Group 模型增强（JSON 字段扩展）

`Group.configs` JSON 结构新增段:

```php
GroupConfigKey::IsEnableCompress     => 'is_enable_compress'   // bool, 默认 false
GroupConfigKey::CompressConfigs      => 'compress_configs'     => [
    'mode' => 'quality',              // quality | resize | format | auto
    'quality' => 80,                  // 1-100, 仅 quality/auto 模式
    'max_width' => 1920,              // 仅 resize/auto 模式
    'max_height' => 1080,             // 仅 resize/auto 模式
    'target_format' => '',            // '' (不变) | webp | jpg | png
    'min_file_size' => 10240,         // 低于此大小的文件跳过压缩（字节）
    'skip_extensions' => ['gif','svg','ico'], // 跳过的格式
]
```

### 3.4 Image 模型新增字段

`images` 表新增字段（迁移文件）：

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| `compress_before_size` | `float` | `0` | 压缩前文件大小 (KB) |
| `compress_after_size` | `float` | `0` | 压缩后文件大小 (KB) |
| `compress_ratio` | `float` | `0` | 压缩率 (百分比) |
| `compress_mode` | `string` | `null` | 采用的压缩模式 (nullable) |

### 3.5 GroupConfigKey 新增常量

```php
// 压缩相关
const IsEnableCompress    = 'is_enable_compress';
const CompressConfigs     = 'compress_configs';
```

---

## 4. 程序调用流程（时序图）

### 4.1 上传 + 自动压缩流程

```
User/Browser                    Controller          ImageService        ImageCompressionSvc   Filesystem
    |                               |                     |                      |                 |
    |--- POST /upload (file) ------>|                     |                      |                 |
    |                               |-- store(request) ->|                      |                 |
    |                               |                     |-- 校验权限/频率       |                 |
    |                               |                     |-- 检查文件类型/大小    |                 |
    |                               |                     |-- 图片处理(质量/格式)  |                 |
    |                               |                     |-- 水印(如有)          |                 |
    |                               |                     |                      |                 |
    |                               |                     |-- compress(file) --->|                 |
    |                               |                     |                      |-- 读取组压缩配置  |
    |                               |                     |                      |-- 质量/尺寸/格式  |
    |                               |                     |                      |-- WebP转换(如有) |
    |                               |                     |<-- 返回压缩后文件 ----|                 |
    |                               |                     |                      |                 |
    |                               |                     |-- 计算 md5/sha1      |                 |
    |                               |                     |-- 填充 Image 记录     |                 |
    |                               |                     |-- 写入存储 --------->|                 |
    |                               |                     |-- 图片审核            |                 |
    |                               |                     |-- 生成缩略图          |                 |
    |                               |<-- return Image ----|                      |                 |
    |<-- JSON Response -------------|                     |                      |                 |
```

### 4.2 安全隐患修复后请求流程

```
Request
  │
  ├─ [Global Middleware Stack]
  │   ├─ TrustProxies
  │   ├─ HandleCors (CORS)
  │   ├─ PreventRequestsDuringMaintenance
  │   ├─ ValidatePostSize
  │   ├─ TrimStrings
  │   └─ ConvertEmptyStringsToNull
  │
  ├─ [Web Middleware Group]
  │   ├─ EncryptCookies
  │   ├─ AddQueuedCookiesToResponse
  │   ├─ StartSession
  │   ├─ ShareErrorsFromSession
  │   ├─ VerifyCsrfToken                        ← SEC: CSRF保护
  │   ├─ CheckIsInstalled
  │   └─ SubstituteBindings
  │
  ├─ [Route-specific Middleware]
  │   ├─ **CheckUploadSecurity** (新增)         ← SEC: 文件MIME/路径校验
  │   ├─ **ContentSecurityPolicy** (新增)        ← SEC: CSP Header
  │   ├─ auth / auth.admin                      ← SEC: 身份认证
  │   └─ throttle:api                           ← SEC: 速率限制
  │
  ├─ [Controller]
  │   ├─ **UploadRequest** (新增)               ← SEC: FormRequest校验
  │   └─ Service层调用
  │       ├─ XssSanitizer::sanitize()           ← SEC: 用户输入过滤
  │       ├─ ImageService::store()
  │       │   ├─ 路径遍历防护                   ← SEC: replacePathname净化
  │       │   ├─ 文件内容安全(非ico/svg跳过)     ← SEC: SVG安全清洗
  │       │   └─ 压缩 / 水印 / 缩略图
  │       └─ 响应返回
  │
  └─ [Response]
      └─ CSP Headers applied                    ← SEC: 浏览器安全策略
```

---

## 5. 任务列表

任务按实现顺序排列，前置任务完成后才能开始后续任务。

### Phase 1: 基础设施与性能优化（基础环境）

| # | 任务ID | 任务名 | 依赖 | 说明 |
|---|--------|--------|------|------|
| 1 | PERF-01 | 执行 `route:cache` | 无 | 路由缓存，提升路由解析性能 |
| 2 | PERF-02 | 执行 `config:cache` | 无 | 配置缓存 |
| 3 | PERF-03 | 执行 `view:cache` | 无 | 视图缓存 |
| 4 | PERF-04 | 执行 `event:cache` | 无 | 事件缓存 |
| 5 | PERF-05 | 新增数据库索引迁移 | 无 | `images` 表新增 4 个联合索引 |
| 6 | PERF-06 | Redis 缓存驱动配置 | 无 | 修改 `.env` 和 `config/cache.php` |
| 7 | PERF-07 | 修复 N+1 查询问题 | 5 | `ImageController::images()` 等列表查询预加载关联模型 |
| 8 | PERF-08 | 前端懒加载改造 | 7 | 图片列表改为 Intersection Observer 分页加载 |
| 9 | PERF-09 | Octane 配置优化 | 6 | 配置 `max_requests`、worker 数量 |
| 10 | PERF-10 | 新增缓存清理命令 Artisan | 6 | `php artisan cache:clear-image` |

### Phase 2: 安全加固

| # | 任务ID | 任务名 | 依赖 | 说明 |
|---|--------|--------|------|------|
| 11 | SEC-01 | 新增 `CheckUploadSecurity` 中间件 | 无 | MIME 白名单、文件头 magic bytes 校验 |
| 12 | SEC-02 | 路径穿越防护 | 11 | `replacePathname()` 增加 `basename()`、过滤 `../` |
| 13 | SEC-03 | 新增 XSS 过滤工具类 | 无 | `XssSanitizer::sanitize()` 净化用户输入 |
| 14 | SEC-04 | SVG 上传安全清洗 | 11 | 去除 SVG 中的 script、on* 事件处理器 |
| 15 | SEC-05 | 新增 CSP 中间件 | 无 | `ContentSecurityPolicy` 设置 CSP header |
| 16 | SEC-06 | 新增 `UploadRequest` FormRequest | 11 | 上传请求下沉到 Request 层校验 |
| 17 | SEC-07 | 确认并加固 CSRF/会话安全 | 无 | 检查 VerifyCsrfToken、session.php 配置 |
| 18 | SEC-08 | 防盗链中间件(可选) | 无 | Referer 校验 |
| 19 | SEC-09 | API 权限校验加固 | 16 | 检查所有 API 端点 token 验证 |
| 10 | SEC-10 | Blade 模板 XSS 扫描 | 13 | 检查所有 `{!! !!}` 改为 `{{ }}` + 白名单 |
| 21 | SEC-11 | 注册新中间件到 Kernel | 1-20 | 在 `$routeMiddleware` 和路由组中注册 |
| 22 | SEC-12 | 管理员路由权限全覆盖 | 21 | 确认 `auth.admin` 中间件覆盖所有管理端点 |

### Phase 3: 图片自动压缩

| # | 任务ID | 任务名 | 依赖 | 说明 |
|---|--------|--------|------|------|
| 23 | CMP-01 | GroupConfigKey 新增压缩常量 | 无 | `IsEnableCompress`、`CompressConfigs` |
| 24 | CMP-02 | `convention.php` 增加压缩默认配置 | 23 | `group` 段增加 `compress_configs` 默认值 |
| 25 | CMP-03 | Group 模型 `getDefaultConfigs()` 增加压缩 | 24 | 确保压缩配置被合并 |
| 26 | CMP-04 | 新增数据库迁移：Image 表压缩字段 | 23 | `compress_before_size` 等字段 |
| 27 | CMP-05 | Image 模型新增压缩字段 cast/fillable | 26 | 字段映射到模型 |
| 28 | CMP-06 | 新增 `ImageCompressionService` | 24 | 核心压缩服务 |
| 29 | CMP-07 | ImageService 新增 `compress()` 方法 | 28 | 调用 ImageCompressionService |
| 30 | CMP-08 | `store()` 集成压缩调用 | 29 | 在图片处理段后插入压缩 |

### Phase 4: Docker 容器化

| # | 任务ID | 任务名 | 依赖 | 说明 |
|---|--------|--------|------|------|
| 31 | DOCK-01 | 创建 `docker/php/Dockerfile` | 无 | PHP 8.2 FPM Alpine + 扩展 |
| 32 | DOCK-02 | 创建 `docker/nginx/default.conf` | 无 | Nginx 配置 |
| 33 | DOCK-03 | 创建 `docker-compose.yml` | 31,32 | 编排 app/web/db/redis |
| 34 | DOCK-04 | 创建根目录 `Dockerfile` | 31 | 构建入口 |
| 35 | DOCK-05 | 创建 `.dockerignore` | 无 | 避免复制无用文件 |
| 36 | DOCK-06 | 更新 `.env.example` | 33 | Docker 环境变量 |

### Phase 5: UI 美化（暗色主题 + 组件美化）

| # | 任务ID | 任务名 | 依赖 | 说明 |
|---|--------|--------|------|------|
| 37 | UI-01 | 修改 `tailwind.config.js` 启用 `darkMode: 'class'` | 无 |
| 38 | UI-02 | 新增 CSS 变量和 `dark-theme.css` | 37 | 定义暗色变量 |
| 39 | UI-03 | 修改 `app.blade.php` 布局 | 38 | 添加 `x-data` 和 dark class 控制 |
| 40 | UI-04 | 新增 `theme-toggle` 组件 | 39 | 主题切换按钮 |
| 41 | UI-05 | 修改 `header.blade.php` 嵌入切换按钮 | 40 |
| 42 | UI-06 | 修改 `sidebar.blade.php` 暗色样式 | 38 |
| 43 | UI-07 | 所有组件文件加 `dark:` 前缀 | 42 | 逐个检查 components 目录 |
| 44 | UI-08 | 用户页面暗色适配 | 43 | `user/*.blade.php` |
| 45 | UI-09 | 管理后台暗色适配 | 44 | `admin/**/*.blade.php` |
| 46 | UI-10 | 公共页面暗色适配 | 45 | `common/*.blade.php` |
| 47 | UI-11 | `app.js` 新增主题切换逻辑 + localStorage 持久化 | 40 | Alpine.js store |

---

## 6. 依赖包列表

### 6.1 Composer 依赖（PHP）

| 包名 | 版本 | 用途 | 新增/已有 |
|------|------|------|-----------|
| `intervention/image` | ^2.7 | 图片处理核心 | 已有 |
| `intervention/imagecache` | ^2.5 | 图片缓存 | 已有 |
| `laravel/octane` | ^1.2 | 性能优化 (Swoole/RoadRunner) | 已有 |
| `predis/predis` | ^2.0 | **新增** — Redis 客户端（可选，若使用 `phpredis` 扩展可跳过） |
| `spatie/laravel-image-optimizer` | ^1.7 | **新增** — 图片压缩优化（可选替代方案，使用外部工具如 jpegoptim/optipng/webp） |
| `laravel/framework` | ^9.0 | 框架 | 已有 |

### 6.2 npm 依赖（前端）

| 包名 | 版本 | 用途 | 新增/已有 |
|------|------|------|-----------|
| `tailwindcss` | ^3.0 | CSS 框架 | 已有 |
| `alpinejs` | ^3.4 | 前端交互 | 已有 |
| `@tailwindcss/forms` | ^0.4 | 表单样式 | 已有 |
| `postcss` | ^8.4 | CSS 处理 | 已有 |
| `autoprefixer` | ^10.1 | CSS 兼容 | 已有 |
| `sweetalert2` | ^11.3 | 弹窗组件 | 已有 |
| `toastr` | ^2.1 | 通知提示 | 已有 |
| `postcss-import` | ^14.0 | CSS 导入 | 已有 |
| `less` / `less-loader` | ^4.1 | CSS 预处理器 | 已有 |

无需新增 npm 包，现有依赖已满足暗色主题需求。

### 6.3 系统工具（Docker 镜像）

| 工具 | 用途 |
|------|------|
| `jpegoptim` | JPEG 压缩 |
| `optipng` | PNG 压缩 |
| `libwebp` | WebP 支持 |

---

## 7. 共享知识（跨文件约定）

### 7.1 命名规范

| 类别 | 规范 | 示例 |
|------|------|------|
| **类名** | PascalCase, 名词性 | `ImageCompressionService`, `CheckUploadSecurity` |
| **方法名** | camelCase, 动词开头 | `compress()`, `sanitize()` |
| **变量/属性** | camelCase | `$compressConfigs`, `$beforeSize` |
| **数据库字段** | snake_case | `compress_before_size`, `is_enable_compress` |
| **配置键** | snake_case, 与字段对应 | `is_enable_compress`, `compress_configs` |
| **枚举常量** | PascalCase | `GroupConfigKey::IsEnableCompress` |
| **路由名** | kebab-case | `admin.groups`, `user.images` |
| **中间件** | 描述性 PascalCase | `CheckUploadSecurity`, `ContentSecurityPolicy` |
| **缓存键** | snake_case, `{type}_{key}` | `image_{key}`, `compress_config_{group_id}` |

### 7.2 配置约定

| 配置方式 | 位置 | 说明 |
|----------|------|------|
| 系统级配置 | `config/*.php` | Laravel 配置文件，不可热更新 |
| 应用配置（数据库） | `App\Utils::config()` 通过 `configs` 表 | 管理员后台设置的配置 |
| 组配置（数据库） | `Group.configs` JSON 字段 | 角色组级别配置，含压缩、水印、审核等 |
| 用户配置（数据库） | `User.configs` JSON 字段 | 用户个性化配置 |
| 存储策略配置（数据库） | `Strategy.configs` JSON 字段 | 存储驱动参数 |
| 环境变量 | `.env` | 敏感信息和环境特异值 |

**压缩配置层级**: 系统级默认值 (`convention.php`) → 组级覆盖 (`Group.configs`) → 无用户级覆盖

### 7.3 路由约定

| 前缀/路由 | 中间件 | 说明 |
|-----------|--------|------|
| `/install` | 无 | 安装向导 |
| `/upload` | `web`, `CheckIsInstalled`, `CheckIsEnableGuestUpload` | Web 上传入口 |
| `/dashboard` | `web`, `auth` | 用户仪表盘 |
| `/admin/*` | `web`, `auth.admin` | 管理后台 |
| `/{key}.{extension}` | `cache.headers` | 受保护图片输出 |
| `/api/v1/*` | `api`, `auth:sanctum` | API 路由（定义在 `routes/api.php`） |

### 7.4 缓存键约定

| 缓存键 | 类型 | TTL | 说明 |
|--------|------|-----|------|
| `configs` | `Cache::forever` | 永久 | 系统配置，配置更新时 `forget()` |
| `image_{key}` | `Cache::remember` | 组配置 `image_cache_ttl` | 受保护图片内容 |
| `compress_config_{group_id}` | `Cache::remember` | 3600 秒 | 组压缩配置缓存 |
| `user_usage_{user_id}` | `Cache::remember` | 300 秒 | 用户容量使用统计 |
| `image_count` | `Cache::remember` | 60 秒 | 全局图片总数 |

**命名规则**: `{resource_type}_{identifier}`，使用 snake_case

---

## 8. 待明确事项

以下事项需要与团队负责人或产品经理确认：

### 8.1 压缩策略

- [ ] **CMP-Q1**: 自动压缩模式中，当质量压缩 + 尺寸缩放同时启用时，优先级如何？先缩后压还是先压后缩？
- [ ] **CMP-Q2**: WebP 转换是否作为独立的"格式转换"选项，还是合并到压缩配置中？是否需要回退机制（浏览器不支持 WebP 时输出原格式）？
- [ ] **CMP-Q3**: 压缩失败时，是返回原始未压缩文件还是抛出异常中断上传？
- [ ] **CMP-Q4**: 是否支持上传后手动触发压缩（已有图片的批量压缩）？

### 8.2 安全策略

- [ ] **SEC-Q1**: SVG 清洗策略采用白名单（保留允许的标签）还是黑名单（删除已知危险标签）？
- [ ] **SEC-Q2**: CSP Header 的 `script-src` 策略，是否允许 `'unsafe-inline'` ？（项目使用了 Alpine.js 内联 `x-data` 属性）
- [ ] **SEC-Q3**: 防盗链功能是否需要？允许的 Referer 域名列表如何管理？

### 8.3 前端设计

- [ ] **UI-Q1**: 暗色主题切换默认值：跟随系统 (`prefers-color-scheme`)，用户手动选择，还是默认浅色？
- [ ] **UI-Q2**: 主题切换是否需要后端同步（已登录用户跨设备保持）？
- [ ] **UI-Q3**: 主题切换动画？过渡效果时长和缓动函数？

### 8.4 部署运维

- [ ] **DOCK-Q1**: 是否需要支持使用 `php-swoole` 扩展的 Octane 模式运行？
- [ ] **DOCK-Q2**: 数据库是否需要 Docker 化，或者使用外部 MySQL 实例？
- [ ] **DOCK-Q3**: 是否需要在 Docker 中集成定时任务（计划任务清理缓存/临时文件）？

### 8.5 性能

- [ ] **PERF-Q1**: 缓存驱动使用 Redis 还是 File？File 驱动在多容器部署下会有缓存不一致问题。
- [ ] **PERF-Q2**: Octane 的 worker 数量和 `max_requests` 如何配置（取决于服务器内存规格）？

---

> **文档版本**: v1.0  
> **作者**: 高见远 (Architect)  
> **日期**: 2026-05-08  
> **状态**: 待审批
