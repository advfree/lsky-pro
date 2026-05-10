# Lsky Pro Docker NAS WebP Fork

这是 [lsky-org/lsky-pro](https://github.com/lsky-org/lsky-pro) 的个人 fork，维护者为 `advfree`。本 fork 继续遵循上游项目的 GPL-3.0 许可证。

这个版本主要面向个人自建图床、VPS Docker 部署、NAS/NFS 图片存储、MySQL 备份迁移和第三方工具接入做了优化。

## 主要差异

- GitHub Actions 自动构建镜像：`ghcr.io/advfree/lsky-pro:latest`。
- VPS 部署不需要 `git clone`，不需要本地编译镜像。
- 单容器镜像内同时运行 Nginx + PHP-FPM。
- 内置 GD、WebP、`cwebp`、MySQL 客户端等依赖。
- 默认使用 GD，修复 Docker 环境安装时 Imagick 检测问题。
- 支持保留原图，同时生成分享用 WebP 优化图。
- 分享链接默认使用短路径：`/i/{key}.webp`。
- 新增 NAS 存储与 MySQL 备份功能。
- 新增 NAS 图片导入入口。
- 优化“我的图片”相册展示，移动相册不会影响图片外链。
- 接口页集中展示 API Token、相册 ID、存储策略 ID。
- 修复默认上传策略为空导致首次上传失败的问题。

NAS 存储与 MySQL 备份的详细说明见：

[docs/nas-storage-and-mysql-backup.md](docs/nas-storage-and-mysql-backup.md)

## Docker Compose 安装

推荐普通用户使用这种方式。只需要在 VPS 上写一个 `docker-compose.yml`，然后启动容器。

### 1. 创建目录

```bash
mkdir -p /root/data/docker_data/lsky-pro
cd /root/data/docker_data/lsky-pro
```

### 2. 创建 docker-compose.yml

```bash
vim docker-compose.yml
```

写入：

```yaml
services:
  lsky-pro:
    container_name: lsky-pro
    image: ghcr.io/advfree/lsky-pro:latest
    restart: always
    volumes:
      - /root/data/docker_data/lsky-pro/lsky-pro-data:/var/www/html/storage
    ports:
      - 7791:80
    environment:
      - MYSQL_HOST=mysql
      - MYSQL_DATABASE=lsky-pro
      - MYSQL_USER=lsky-pro
      - MYSQL_PASSWORD=lsky-pro
    depends_on:
      - mysql

  mysql:
    image: mysql:8.4
    container_name: lsky-pro-db
    restart: always
    environment:
      - MYSQL_DATABASE=lsky-pro
      - MYSQL_USER=lsky-pro
      - MYSQL_PASSWORD=lsky-pro
      - MYSQL_ROOT_PASSWORD=lsky-pro
    volumes:
      - /root/data/docker_data/lsky-pro/db:/var/lib/mysql
```

然后启动：

```bash
docker compose up -d
```

访问：

```text
http://你的服务器IP:7791
```

### 3. 首次安装数据库填写

安装页面中数据库填写：

```text
数据库类型：MySQL 5.7+
数据库连接地址：mysql
数据库连接端口：3306
数据库名称：lsky-pro
数据库用户名：lsky-pro
数据库密码：lsky-pro
```

然后设置超级管理员邮箱和密码。

## 持久化说明

Compose 示例只持久化两个目录：

```text
/root/data/docker_data/lsky-pro/lsky-pro-data
/root/data/docker_data/lsky-pro/db
```

其中：

- `lsky-pro-data` 映射到容器内 `/var/www/html/storage`
- `db` 映射到 MySQL 的 `/var/lib/mysql`

程序会自动把这些内容放到持久化目录中：

- `.env`
- `APP_KEY`
- `installed.lock`
- 上传图片
- 缩略图
- Laravel 缓存、日志、session
- 默认本地储存策略的图片文件

不要把宿主机空目录直接挂载到 `/var/www/html`。预构建镜像里的程序代码就在 `/var/www/html`，如果把空目录挂上去，会把代码盖住，容器会无法运行。

## NAS 怎么用

默认 Compose 不写 NAS 路径，先让程序简单跑起来。

如果你习惯把 NAS/NFS 挂载到 Ubuntu 的：

```text
/home/user/lskypro
```

那么有两种做法。

### 做法 A：Docker 仍用默认目录，后台改存储总路径

把 NAS 挂载到宿主机后，在 compose 中额外加一行挂载：

```yaml
    volumes:
      - /root/data/docker_data/lsky-pro/lsky-pro-data:/var/www/html/storage
      - /home/user/lskypro:/home/user/lskypro
```

然后进入 Lsky Pro 后台，在储存策略或 NAS 存储与备份中填写：

```text
存储总路径：/home/user/lskypro
```

系统会自动使用：

```text
图片目录：/home/user/lskypro/uploads
MySQL 备份目录：/home/user/lskypro/backups/mysql
NAS 图片导入目录：/home/user/lskypro/imports
```

### 做法 B：直接把默认 storage 放到 NAS

如果你想让 Lsky Pro 的默认持久化目录就在 NAS 上，可以把第一条 volume 改成：

```yaml
      - /home/user/lskypro/lsky-pro-data:/var/www/html/storage
```

MySQL 数据目录仍然建议放 VPS 本地 SSD，不建议放 NAS/NFS。

## 常用命令

查看状态：

```bash
docker compose ps
```

查看日志：

```bash
docker compose logs -f lsky-pro
```

停止：

```bash
docker compose down
```

升级镜像：

```bash
docker compose pull
docker compose up -d
```

清理 Laravel 缓存：

```bash
docker compose exec -T lsky-pro php artisan optimize:clear
```

手动执行数据库迁移：

```bash
docker compose exec -T lsky-pro php artisan migrate --force
```

手动备份 MySQL：

```bash
docker compose exec -T lsky-pro php artisan lsky:mysql-backup
```

## 第三方工具接入

接口页会显示：

- API Token
- 相册 ID
- 存储策略 ID
- 接口说明

配合 Halo Lsky Pro 插件时，可以直接在接口页复制相关信息：

[https://github.com/ichenhe/halo-lsky-pro](https://github.com/ichenhe/halo-lsky-pro)

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

## 上游项目

- 上游仓库：[https://github.com/lsky-org/lsky-pro](https://github.com/lsky-org/lsky-pro)
- 官方网站：[https://www.lsky.pro](https://www.lsky.pro)
- 官方文档：[https://docs.lsky.pro](https://docs.lsky.pro)

## License

GPL-3.0，详见 [LICENSE](LICENSE)。
