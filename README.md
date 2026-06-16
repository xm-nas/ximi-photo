# 🖼️ Ximi Gallery (希米相册管理系统)

<p align="center">
  <img src="https://img.shields.io/badge/Version-v5.01-blue.svg?style=flat-square" alt="Version">
  <img src="https://img.shields.io/badge/Environment-PHP%207.0%20--%208.5-orange.svg?style=flat-square" alt="PHP">
  <img src="https://img.shields.io/badge/Database-None%20(File%20Based)-green.svg?style=flat-square" alt="Database">
  <img src="https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square" alt="License">
</p>

<p align="center">
  <strong>一款轻量级、零数据库、极易部署的 3D 相册与图片分享管理系统</strong>
</p>

<p align="center">
  🌐 <a href="https://img.ximi.me/">在线演示预览</a> | 📝 <a href="https://www.ximi.me/post-6027.html">官方博客发布页</a>
</p>

---

## 📖 目录
- [🚀 项目特性](#-项目特性)
- [🛠️ 后台管理与初始配置](#️-后台管理与初始配置)
- [⚙️ 配置文件说明 (ini.php)](#️-配置文件说明-iniphp)
- [📦 部署与安装](#-部署与安装)
- [📅 版本与关于](#-版本与关于)

---

## 🚀 项目特性

| 特性分类 | 功能描述 |
| :--- | :--- |
| **⚡ 极简架构** | 原生支持 **PHP 7.0 - 8.5**；完全不依赖任何数据库，支持虚拟主机即传即用。 |
| **🎨 视觉动效** | 深度优化 **3D 相册** 生成步骤，支持一键配置多个带有独立后台的 3D 相册。 |
| **🖼️ 智能图像** | 自动将上传图片转换为高性能 **WebP** 格式并生成缩略图；导入 3D 相册支持多格式自动转换。 |
| **🔒 安全隐私** | 支持**私密相册**权限隔离；内置**全局防盗链与域名白名单**，有效防止第三方恶意爬取。 |
| **📊 数据统计** | 内置完善的图片外链分享机制与精细化的**图片访问日志（Log）**统计分析。 |

> ⚠️ **注意**：3D 相册批量导入支持多种格式转换，但手动单张上传时仅支持 `.jpg` 格式。

---

## 🛠️ 后台管理与初始配置

### 🔑 超级管理员后台
* **登录地址**：`/admin/login.php` （或通过后台首页直接更改）
* **核心安全**：部署后请立刻打开 `/index_src/ini/setting.php`，将默认密码 `admin` 修改为强密码。

### 📂 默认模板核心文件 (`/index_src/default/`)
<details>
<summary>📂 点击展开查看核心文件配置详情</summary>

* **相册单独管理后台 (`login.php`)**
  * 默认账号：`ximi`
  * 默认密码：`【随机值】` (*系统默认生成安全随机值，防止意外泄露*)
* **相册访问日志 (`log.php`)**
  * 默认密码：`admin` (*请按需修改*)
  * *彻底关闭日志方法*：打开 `img.php`，删除日志记录核心函数后续的所有代码。
* **防盗链白名单 (`functions.php`)**
  * 用于手动维护允许外链的域名白名单（亦可在超级管理后台可视化修改）。
* **独立配置文件 (`ini.php`)**
  * 各个独立相册的核心配置文件。
</details>

---

## ⚙️ 配置文件说明 (`ini.php`)

在自定义或迁移相册时，可以通过修改单个相册目录下的 `ini.php` 调整参数。标准的配置结构如下：

```php
$config = array (
  'title'       => '城',                                        // 相册名称/标题
  'list_read'   => '1',                                         // 权限控制：0 = 私密，1 = 公开
  'list_home'   => '1',                                         // 首页可见性：0 = 隐藏，1 = 展现
  'login_admin' => 'login_default',                             // 该相册的登录隔离标识
  
  // --- 路径配置 ---
  'tu_1'        => '../../update/user/cheng/class/img/',        // 原图存放路径
  'min'         => '../../update/user/cheng/class/min_image/',  // 小缩略图存放路径
  'max'         => '../../update/user/cheng/class/max_image/',  // 大缩略图存放路径
  
  // --- 界面展示 ---
  'cover'       => '',                                          // 封面图 URL（留空则随机获取）
  'txt'         => '',                                          // 相册底部文字介绍
);
```

---

## 📦 部署与安装

1. 上传源码
将本项目源码整个目录结构完整上传至服务器或虚拟主机的 Web 根目录。

2. 设置权限
请确保以下目录及其子目录具备自读写与修改权限（通常设置为 755 或 777）：

3. 安全初始化
参照 超级管理员后台 说明，修改管理员密码，即可开始创建你的 3D 相册！

```Bash
chmod -R 777 update/
chmod -R 777 user/
```

---

## 📅 版本与关于

* 当前版本：V5.01 (Stable)

* 更新日期：2026.05.18

* 作者：ximi

* 项目主页：[Ximi's Blog](https://www.ximi.me/post-6027.html)
