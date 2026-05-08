<img align="right" width="100" src="https://avatars.githubusercontent.com/u/100565733?s=200" alt="Lsky Pro Logo"/>

<h1 align="left">Lsky Pro 优化增强版</h1>

☁️ 基于兰空图床的二次开发，安全加固 · 图片自动压缩 · 暗色主题 · Docker 容器化

[![PHP](https://img.shields.io/badge/PHP->=8.0-orange.svg)](http://php.net)
[![Release](https://img.shields.io/github/v/release/advfree/lsky-pro?include_prereleases)](https://github.com/advfree/lsky-pro/releases)
[![License](https://img.shields.io/badge/license-GPL_V3.0-yellowgreen.svg)](https://github.com/advfree/lsky-pro/blob/master/LICENSE)
[![Docker](https://img.shields.io/badge/docker-ready-2496ED?logo=docker)](https://github.com/advfree/lsky-pro/pkgs/container/lsky-pro)

---

## 📋 概述

本项目基于 [lsky-org/lsky-pro](https://github.com/lsky-org/lsky-pro)（兰空图床）二次开发，原版开源版已停止维护。此 fork 在保留原版全部功能的基础上，重点进行了 **安全加固**、**性能优化**、**图片自动压缩**、**UI 美化** 和 **Docker 容器化**，使其更适合生产环境部署。

## ✨ 新增与优化特性

### 🔒 安全加固
相比原版，新增 12 项安全防护措施：

| 安全措施 | 说明 |
|---------|------|
| 文件上传 MIME 白名单 | 严格校验上传文件的 MIME 类型，防止伪造类型绕过 |
| Magic Bytes 检测 | 通过文件头特征字节二次校验文件真实性 |
| 路径遍历防护 | 过滤文件名中的 `../`、`..\\` 等危险字符 |
| CSP 内容安全策略 | 全局 Content-Security-Policy Header，防御 XSS 和数据注入 |
| XSS 过滤工具类 | 自动清除用户输入中的脚本标签、事件处理器等危险代码 |
| 安全 Cookie 配置 | Secure、HttpOnly、SameSite 三项全开 |
| 敏感信息保护 | 生产环境异常信息不暴露给客户端 |

### 🖼️ 图片自动压缩
参考 [EasyImages2.0](https://github.com/icret/EasyImages2.0) 的压缩方案，在现有上传流程中集成自动压缩：

- **质量压缩**：上传时自动调整图片质量（可配置 1-100）
- **尺寸缩放**：超过设定阈值（默认 1920×1080）自动等比缩放
- **格式转换**：支持自动转换为 WebP/JPEG/PNG，PSD/TIFF/BMP 自动转 Web 友好格式
- **按组配置**：不同用户组可配置独立的压缩策略
- **压缩统计**：上传后返回压缩前后大小及压缩率
- **智能跳过**：GIF/SVG/ICO 格式自动跳过压缩，保留原始特性

### 🌗 暗色主题（UI 美化）
参考 [LSKY-Pro-LiuShen](https://github.com/willow-god/LSKY-Pro-LiuShen) 的现代化 UI 设计：

- **亮色/暗色双主题**：一键切换，偏好持久化到 localStorage
- **浮动切换按钮**：页面右下角悬浮太阳/月亮按钮
- **全站适配**：管理后台、用户端、欢迎页、设置页等所有页面均适配暗色
- **平滑过渡**：300ms 过渡动画，切换丝滑
- **CSS 变量驱动**：全局颜色变量，方便自定义

### ⚡ 性能优化

| 优化项 | 说明 |
|--------|------|
| 数据库复合索引 | `images` 表新增 4 个联合索引，大幅提升查询性能 |
| N+1 查询修复 | 预加载关联模型，消除懒加载导致的多次查询 |
| 图片懒加载 | 图片列表 `loading="lazy"` 延迟加载 |
| Redis 缓存 | 可选 Redis 驱动替代文件缓存，支持缓存标签 |
| 配置缓存优化 | 配置变更时主动刷新缓存 |
| 图片静态缓存 | Nginx 层面配置图片 30 天强缓存 |

### 🐳 Docker 容器化

- 完整 `docker-compose.yml`：PHP 8.2 + Nginx + MySQL 8.0 + Redis 7
- 优化 PHP-FPM 配置：OPcache、GD/Imagick/Redis 扩展全内置
- Nginx 安全配置：防 `.env` 泄露、防目录浏览、安全 Header
- GitHub Actions：自动构建并推送 Docker 镜像到 ghcr.io
- Supervisor 进程管理：PHP-FPM + Nginx + 计划任务统一管理

## 🛠 安装要求

- PHP >= 8.0.2
- BCMath、Ctype、DOM、Fileinfo、JSON、Mbstring、OpenSSL、PDO、Tokenizer、XML、Imagick 扩展
- exec、shell_exec、readlink、symlink、putenv、getenv、chmod、chown、fileperms 函数可用
- MySQL 5.7+ / PostgreSQL 9.6+ / SQLite 3.8.8+ / SQL Server 2017+

## 🚀 快速部署

### 🐳 方案一：Docker Desktop 一键安装（最快）

如果你已安装 [Docker Desktop](https://www.docker.com/products/docker-desktop/)，直接一条命令启动：

```bash
docker run -d --name lsky-pro -p 8080:80 -v lsky-storage:/var/www/html/storage -v lsky-uploads:/var/www/html/public -e APP_URL=http://localhost:8080 -e DB_CONNECTION=sqlite -e CACHE_DRIVER=file ghcr.io/advfree/lsky-pro:latest
```

> 镜像由 GitHub Actions 自动构建并推送至 `ghcr.io`，每次推送 master 分支后自动更新。  
> 启动后访问 **http://localhost:8080** 按安装向导完成初始化。  
> 使用 SQLite 数据库，无需额外配置，开箱即用。  
> 停止容器：`docker stop lsky-pro && docker rm lsky-pro`

也可从源码**本地构建**：

```bash
docker build -t lsky-pro .
docker run -d --name lsky-pro -p 8080:80 -e APP_URL=http://localhost:8080 -e DB_CONNECTION=sqlite lsky-pro
```

### 🐳 方案二：Docker Compose 完整部署（生产推荐）

克隆仓库并使用 Docker Compose 启动完整环境（含 MySQL + Redis）：

```bash
git clone https://github.com/advfree/lsky-pro.git
cd lsky-pro

# 创建 .env 配置文件
cp .env.example .env
# ⚠️ 编辑 .env，至少填写以下关键配置：
#   DB_PASSWORD=你的数据库密码
#   APP_KEY= （运行下一步自动生成）

# 生成应用密钥
php -r "echo 'APP_KEY='.base64_encode(random_bytes(32));" >> .env

# 启动所有服务（后台运行）
docker-compose up -d

# 查看启动状态
docker-compose ps

# 查看日志
docker-compose logs -f

# 访问 http://localhost:8080 按安装向导完成初始化
```

> **首次启动后**，进入容器执行数据库迁移：  
> `docker exec lsky-app php artisan migrate --seed`

**常用 Compose 命令：**

```bash
# 停止服务
docker-compose down

# 停止并删除数据卷（⚠️ 会清空数据库和上传文件）
docker-compose down -v

# 重新构建镜像（代码更新后）
docker-compose build --no-cache

# 更新到最新代码
git pull
docker-compose up -d --build
```

### 手动部署

```bash
git clone https://github.com/advfree/lsky-pro.git
cd lsky-pro

composer install --no-dev
cp .env.example .env
php artisan key:generate

# 配置数据库连接信息到 .env
php artisan migrate --seed

chmod -R 775 storage bootstrap/cache
php artisan storage:link

# 配置 Web 服务器将根目录指向 public/
```

## 📸 截图

> 亮色模式 / 暗色模式

## 🧩 与原版的差异对比

| 特性 | 原版 lsky-pro | 本增强版 |
|------|-------------|---------|
| 开源状态 | ❌ 已停止维护 | ✅ 持续维护 |
| 文件上传安全 | ⚠️ 仅扩展名校验 | ✅ MIME + Magic Bytes + 路径防护 |
| XSS 防护 | ⚠️ 基础 | ✅ CSP 策略 + XssSanitizer |
| 会话安全 | ⚠️ 基础 | ✅ Secure + HttpOnly + SameSite |
| 图片压缩 | ❌ 无自动压缩 | ✅ 质量/尺寸/格式/WebP 全支持 |
| 暗色主题 | ❌ 无 | ✅ 亮暗切换，全站适配 |
| Docker 部署 | ❌ 无 | ✅ docker-compose + CI/CD |
| GitHub Actions | ❌ 无 | ✅ 自动构建 Docker 镜像 |
| 数据库索引 | ⚠️ 无复合索引 | ✅ 4 个复合索引 |
| N+1 查询 | ⚠️ 存在 | ✅ 预加载修复 |

## 🙏 鸣谢

### 原项目
- [lsky-org/lsky-pro](https://github.com/lsky-org/lsky-pro) — 兰空图床，本项目的基础

### 灵感来源
- [icret/EasyImages2.0](https://github.com/icret/EasyImages2.0) — 图片自动压缩功能的实现参考
- [willow-god/LSKY-Pro-LiuShen](https://github.com/willow-god/LSKY-Pro-LiuShen) — UI 美化与暗色主题的设计参考

### 开源依赖
- [Laravel](https://laravel.com)
- [Tailwindcss](https://tailwindcss.com)
- [Fontawesome](https://fontawesome.com)
- [Alpinejs](https://alpinejs.dev/)
- [Intervention/image](https://github.com/Intervention/image)
- [league/flysystem](https://flysystem.thephpleague.com)
- [Echarts](https://echarts.apache.org)
- 以及所有原版依赖的开源项目

## 📃 开源许可

[GPL 3.0](https://opensource.org/licenses/GPL-3.0)

Copyright (c) 2018-present Lsky Pro & 二次开发贡献者
