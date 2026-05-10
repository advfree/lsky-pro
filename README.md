# Lsky Pro Docker NAS WebP Fork

这是 [lsky-org/lsky-pro](https://github.com/lsky-org/lsky-pro) 的个人 fork，维护者为 `advfree`。本 fork 继续遵循上游项目的 GPL-3.0 许可证。

这个版本面向个人自建图床、VPS Docker 部署、NAS/NFS 图片存储、MySQL 备份迁移和第三方工具接入做了优化。

## 主要差异

- 支持 Docker Compose 一键部署。
- GitHub Actions 自动构建镜像：`ghcr.io/advfree/lsky-pro:latest`。
- 部署到 VPS 时不需要 `git clone`，也不需要本地编译镜像。
- 单容器镜像内同时运行 Nginx + PHP-FPM。
- 内置 GD、WebP、`cwebp`、MySQL 客户端等依赖。
- 默认使用 GD，修复 Docker 环境安装时 Imagick 检测问题。
- 支持保留原图，同时生成分享用 WebP 优化图。
- 分享链接默认使用短路径：`/i/{key}.webp`。
- 新增 NAS 存储与 MySQL 备份功能。
- 只填写一个存储总路径，系统自动使用：
  - 图片目录：`{存储总路径}/uploads`
  - MySQL 备份目录：`{存储总路径}/backups/mysql`
  - NAS 图片导入目录：`{存储总路径}/imports`
- 新增 NAS 图片导入入口。
- 优化“我的图片”相册展示，移动相册不会影响图片外链。
- 接口页集中展示 API Token、相册 ID、存储策略 ID，方便 Halo 等第三方插件配置。
- 修复默认上传策略为空导致首次上传失败的问题。

NAS 存储与 MySQL 备份的详细说明见：

[docs/nas-storage-and-mysql-backup.md](docs/nas-storage-and-mysql-backup.md)

## 推荐安装方式：直接使用镜像

这种方式适合 VPS 部署。你不需要把源码 clone 到服务器，也不需要在 VPS 上本地 build。

### 1. 准备目录

```bash
mkdir -p /root/data/docker_data/lsky-pro
cd /root/data/docker_data/lsky-pro
```

如果你使用 NAS/NFS，请先把 NAS 挂载到服务器上的某个目录。示例里为了简单，先使用本机目录：

```text
/root/data/docker_data/lsky-pro/nas
```

以后你也可以改成：

```text
/mnt/nas/lskypro
```

### 2. 创建 docker-compose.yml

```bash
vim docker-compose.yml
```

写入下面内容：

```yaml
services:
  lsky-pro:
    image: ghcr.io/advfree/lsky-pro:latest
    container_name: lsky-pro
    restart: unless-stopped
    ports:
      - "7791:80"
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      APP_URL: "http://你的服务器IP:7791"
      APP_KEY: "base64:请替换为你生成的APP_KEY"
      DB_CONNECTION: mysql
      DB_HOST: mysql
      DB_PORT: 3306
      DB_DATABASE: lsky-pro
      DB_USERNAME: lsky-pro
      DB_PASSWORD: "请替换为强密码"
      CACHE_DRIVER: file
      SESSION_DRIVER: file
      QUEUE_CONNECTION: sync
      IMAGE_DRIVER: gd
      LSKY_STORAGE_BASE_PATH: /mnt/nas/lskypro
    volumes:
      - /root/data/docker_data/lsky-pro/storage:/var/www/html/storage
      - /root/data/docker_data/lsky-pro/thumbnails:/var/www/html/public/thumbnails
      - /root/data/docker_data/lsky-pro/nas:/mnt/nas/lskypro
    depends_on:
      mysql:
        condition: service_healthy

  mysql:
    image: mysql:8.4
    container_name: lsky-pro-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: lsky-pro
      MYSQL_USER: lsky-pro
      MYSQL_PASSWORD: "请替换为强密码"
      MYSQL_ROOT_PASSWORD: "请替换为另一个强密码"
    volumes:
      - /root/data/docker_data/lsky-pro/db:/var/lib/mysql
    healthcheck:
      test: ["CMD-SHELL", "mysqladmin ping -h 127.0.0.1 -u root -p$${MYSQL_ROOT_PASSWORD}"]
      interval: 10s
      timeout: 5s
      retries: 10
```

注意：

- `DB_PASSWORD` 必须和 `mysql.environment.MYSQL_PASSWORD` 一致。
- `APP_URL` 应改成你的真实访问地址。
- 如果你用域名和反向代理，建议写成 `https://你的域名`。
- `LSKY_STORAGE_BASE_PATH` 是容器内路径，示例固定为 `/mnt/nas/lskypro`。
- 第三个 volume 把宿主机目录映射到容器内 `/mnt/nas/lskypro`。
- 不要把 MySQL 的 `/var/lib/mysql` 放到 NAS/NFS 上，推荐放 VPS 本地 SSD。

### 3. 生成 APP_KEY

可以在 VPS 上执行：

```bash
docker run --rm php:8.2-cli-alpine php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

把输出填到 `APP_KEY`。

### 4. 启动

```bash
docker compose up -d
```

查看状态：

```bash
docker compose ps
```

查看日志：

```bash
docker compose logs -f lsky-pro
```

### 5. 首次安装

浏览器打开：

```text
http://你的服务器IP:7791
```

安装页面中数据库填写：

```text
数据库类型：MySQL 5.7+
数据库连接地址：mysql
数据库连接端口：3306
数据库名称：lsky-pro
数据库用户名：lsky-pro
数据库密码：填写 docker-compose.yml 里的 DB_PASSWORD
```

然后设置超级管理员邮箱和密码。

安装完成后，`installed.lock` 会保存到持久化的 `storage` 目录中，后续升级镜像或重建容器不会丢失安装状态。

## 使用 NAS 路径

如果你的 NAS 已经挂载到宿主机：

```text
/mnt/nas/lskypro
```

可以把 compose 里的第三个 volume 改成：

```yaml
      - /mnt/nas/lskypro:/mnt/nas/lskypro
```

后台填写：

```text
存储总路径：/mnt/nas/lskypro
```

系统会自动使用：

```text
图片目录：/mnt/nas/lskypro/uploads
MySQL 备份目录：/mnt/nas/lskypro/backups/mysql
NAS 图片导入目录：/mnt/nas/lskypro/imports
```

## 升级

```bash
cd /root/data/docker_data/lsky-pro
docker compose pull
docker compose up -d
```

如需清理 Laravel 缓存：

```bash
docker compose exec -T lsky-pro php artisan optimize:clear
```

如需手动执行迁移：

```bash
docker compose exec -T lsky-pro php artisan migrate --force
```

## 备份

手动执行 MySQL 备份：

```bash
docker compose exec -T lsky-pro php artisan lsky:mysql-backup
```

备份文件默认保存到：

```text
{存储总路径}/backups/mysql
```

## NAS 图片导入

把要导入的图片放到：

```text
{存储总路径}/imports
```

然后进入前台侧边栏：

```text
NAS 图片导入
```

导入会创建数据库记录，并生成缩略图和 WebP 分享优化图。直接把文件复制进 `uploads` 不会自动出现在 Lsky Pro 中。

## 开发者本地构建

如果你需要改源码或本地调试，可以 clone 仓库后使用源码版 Compose：

```bash
git clone https://github.com/advfree/lsky-pro.git
cd lsky-pro
cp .env.docker.example .env
docker compose build
docker compose up -d
```

源码版 `docker-compose.yml` 使用 app/nginx 分离结构；发布镜像使用单容器 standalone 结构。

## GitHub Actions 镜像

每次推送到 `master` 后，GitHub Actions 会构建并发布：

```text
ghcr.io/advfree/lsky-pro:latest
ghcr.io/advfree/lsky-pro:{commit_sha}
```

如果首次使用 GHCR 镜像遇到无权限拉取，请到 GitHub 仓库的 Packages 页面，把 package visibility 设置为 Public。

## 第三方工具接入

接口页会显示：

- API Token
- 相册 ID
- 存储策略 ID
- 接口说明

配合 Halo Lsky Pro 插件时，可以直接在接口页复制相关信息：

[https://github.com/ichenhe/halo-lsky-pro](https://github.com/ichenhe/halo-lsky-pro)

## 上游项目

- 上游仓库：[https://github.com/lsky-org/lsky-pro](https://github.com/lsky-org/lsky-pro)
- 官方网站：[https://www.lsky.pro](https://www.lsky.pro)
- 官方文档：[https://docs.lsky.pro](https://docs.lsky.pro)

## License

GPL-3.0，详见 [LICENSE](LICENSE)。
