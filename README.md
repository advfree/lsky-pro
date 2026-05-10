# Lsky Pro Docker NAS WebP Fork

这是 [lsky-org/lsky-pro](https://github.com/lsky-org/lsky-pro) 的个人 fork，主要面向自建图床、Docker Compose 部署、NAS/NFS 图片存储、MySQL 备份迁移和第三方工具接入做了优化。

原项目版权、贡献者和许可证归上游项目所有。本 fork 继续遵循 GPL-3.0 许可证。

## 主要差异

- 新增完整 Docker / Docker Compose 部署配置。
- Docker 服务包含 `app`、`nginx`、`mysql`、`redis`、`scheduler`。
- PHP 镜像内置 GD、WebP、`cwebp`、MySQL 客户端等依赖。
- 修复 Docker 环境安装时 Imagick 检测问题，默认使用 GD。
- 支持保留原图，同时生成分享用 WebP 优化图。
- 分享链接默认使用短路径：`/i/{key}.webp`。
- 后台角色组支持配置分享优化图格式、质量、最长边。
- 新增 NAS 存储与 MySQL 备份功能。
- 只填写一个存储总路径，系统自动使用：
  - 图片目录：`{存储总路径}/uploads`
  - MySQL 备份目录：`{存储总路径}/backups/mysql`
  - NAS 图片导入目录：`{存储总路径}/imports`
- 支持 MySQL `.sql.gz` 备份、下载、上传和路径迁移应用。
- 新增“NAS 图片导入”，可从 NAS 扫描图片并入库。
- 优化“我的图片”相册展示，区分相册与未归位图片。
- 移动相册不会改变图片外链。
- 接口页集中展示 API Token、相册 ID、存储策略 ID，方便 Halo 等第三方插件配置。
- API Token 支持有效期选择，并提示 Token 只展示一次。
- 修复默认上传策略为空导致首次上传失败的问题，默认使用本地策略。
- 修复 Markdown 链接复制、部分后台按钮样式等交互问题。

NAS 存储与 MySQL 备份的详细说明见：

[docs/nas-storage-and-mysql-backup.md](docs/nas-storage-and-mysql-backup.md)

## Docker Compose 安装

### 1. 准备环境

服务器需要安装：

- Docker
- Docker Compose v2

推荐结构：

- VPS 本地 SSD：运行 Docker 和 MySQL 数据目录
- NAS/NFS/对象存储挂载目录：保存图片文件和 MySQL 备份文件

不要把 MySQL 实时数据目录放到 NAS/NFS 上。MySQL 对延迟、锁、fsync 和一致性很敏感，推荐只把 MySQL 备份文件保存到 NAS。

### 2. 克隆仓库

```bash
git clone https://github.com/advfree/lsky-pro.git
cd lsky-pro
```

### 3. 创建 `.env`

```bash
cp .env.docker.example .env
```

编辑 `.env`：

```env
APP_URL=https://your-domain.example
APP_KEY=base64:请替换为随机密钥

DB_DATABASE=lsky
DB_USERNAME=lsky
DB_PASSWORD=请替换为强密码
MYSQL_ROOT_PASSWORD=请替换为强密码

HTTP_PORT=8080

LSKY_STORAGE_BASE_PATH=/mnt/nas/lskypro

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

生成 `APP_KEY` 的一种方式：

```bash
docker run --rm php:8.2-cli-alpine php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

如果暂时不使用 NAS，可以把 `LSKY_STORAGE_BASE_PATH` 改成本机路径，例如：

```env
LSKY_STORAGE_BASE_PATH=./docker-data/lskypro
```

如果使用 NAS/NFS，请先在 VPS 上挂载好目录，例如：

```bash
sudo mkdir -p /mnt/nas/lskypro
```

并确保 Docker 容器内运行用户有写入权限。

### 4. 启动服务

```bash
docker compose build
docker compose up -d
```

查看服务状态：

```bash
docker compose ps
```

默认访问地址：

```text
http://服务器IP:8080
```

如果你在 `.env` 中把 `HTTP_PORT` 改成 `80`，则访问：

```text
http://服务器IP
```

### 5. 首次安装

浏览器打开站点后进入安装向导。

数据库参数填写：

```text
数据库类型：MySQL 5.7+
数据库连接地址：mysql
数据库连接端口：3306
数据库名称：lsky
数据库用户名：lsky
数据库密码：填写 .env 里的 DB_PASSWORD
```

然后设置超级管理员邮箱和密码。

### 6. 安装后建议配置

登录后台后建议检查：

- 储存策略中的本地策略是否指向正确路径。
- 角色组是否已开启“生成分享优化图”。
- 分享优化图格式建议使用 `webp`。
- 分享优化图质量建议 `75`。
- 分享优化图最长边建议 `2560`。
- 默认上传策略应为“默认本地策略”。

如果你使用 NAS，进入后台的 NAS 存储与备份相关区域，填写：

```text
存储总路径：/mnt/nas/lskypro
```

系统会自动使用：

```text
图片目录：/mnt/nas/lskypro/uploads
MySQL 备份目录：/mnt/nas/lskypro/backups/mysql
NAS 图片导入目录：/mnt/nas/lskypro/imports
```

## 常用 Docker 命令

启动：

```bash
docker compose up -d
```

停止：

```bash
docker compose down
```

查看日志：

```bash
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f mysql
```

清理 Laravel 缓存：

```bash
docker compose exec -T app php artisan optimize:clear
```

执行数据库迁移：

```bash
docker compose exec -T app php artisan migrate --force
```

手动执行 MySQL 备份：

```bash
docker compose exec -T app php artisan lsky:mysql-backup
```

重新构建并重启：

```bash
docker compose build app nginx
docker compose up -d app scheduler nginx
docker compose exec -T app php artisan optimize:clear
```

## NAS 图片导入

如果你已经有一批图片放在 NAS 上，可以把图片放入：

```text
{存储总路径}/imports
```

然后进入前台侧边栏：

```text
NAS 图片导入
```

导入时会走正常图片入库流程，并生成缩略图和分享优化图。导入后的图片归属于当前超级管理员用户。

注意：直接把文件复制进 `uploads` 目录不会自动出现在 Lsky Pro 中。Lsky Pro 需要数据库记录、缩略图和优化图信息，所以应使用“NAS 图片导入”功能。

## MySQL 备份与迁移建议

推荐迁移流程：

1. 老 VPS 开启维护模式，避免迁移期间继续上传、删除或改名。
2. 在后台点击立即备份 MySQL，确认 `.sql.gz` 备份已写入 NAS。
3. 新 VPS 挂载同一个 NAS 到相同或新的路径。
4. 新 VPS 部署本项目 Docker Compose。
5. 在后台填写新的存储总路径并应用到本地储存策略。
6. 上传或选择最新 MySQL 备份恢复。
7. 测试图片访问、上传和分享链接。
8. 切换 DNS。
9. 确认无误后下线老 VPS。

迁移期间不要让两个 Lsky Pro 实例同时写入同一个 NAS 图片目录。

## 第三方工具接入

本 fork 优化了“接口”页面：

- API Token
- 相册 ID
- 存储策略 ID
- 接口说明

配合 Halo 等第三方工具时，可以在接口页直接查看需要填写的 Token、相册 ID 和存储策略 ID。

例如 Halo Lsky Pro 插件：

[https://github.com/ichenhe/halo-lsky-pro](https://github.com/ichenhe/halo-lsky-pro)

## 注意事项

- Notion 等云服务无法访问本机 `localhost` 链接。测试外链嵌入时，需要使用公网域名。
- 移动图片所属相册不会改变图片外链。
- MySQL 数据目录默认在 Docker volume 中，适合放在 VPS 本地 SSD。
- NAS/NFS 更适合保存图片文件和 MySQL 备份文件。
- `.env`、Docker 数据目录、上传图片和测试图片不会提交到 GitHub。

## 上游项目

- 官方仓库：[https://github.com/lsky-org/lsky-pro](https://github.com/lsky-org/lsky-pro)
- 官方网站：[https://www.lsky.pro](https://www.lsky.pro)
- 官方文档：[https://docs.lsky.pro](https://docs.lsky.pro)

## License

GPL-3.0。详见 [LICENSE](LICENSE)。
