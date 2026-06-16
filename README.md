# 3D 相册与图片管理系统 (v5.01)

[cite_start]这是一个轻量级、高效且功能丰富的 PHP 相册管理系统 [cite: 1, 4][cite_start]。支持 3D 相册生成、多相册管理、防盗链及图片访问统计等功能 [cite: 1, 4][cite_start]。系统不依赖数据库，极易部署 。

## 🚀 项目特性

* [cite_start]**运行环境**：支持 **PHP 7.0 - 8.5** 。
* [cite_start]**零数据库**：未使用任何数据库，支持虚拟机搭建，即传即用 。
* [cite_start]**3D 相册生成**：优化了 3D 相册生成步骤，支持配置多个带独立后台的 3D 相册 [cite: 1, 4]。
* **图片处理**：
  * [cite_start]支持常见格式图片上传，自动转换为 **WebP** 格式并生成自定义缩略图 。
  * [cite_start]导入 3D 相册时支持多种格式自动转换（*注意：3D 相册手动上传仅支持 `.jpg` 格式*） [cite: 1, 4]。
* **安全与隐私**：
  * [cite_start]支持设置**私密相册**（多相册/分类独立后台） 。
  * [cite_start]全局支持**防盗链**与**白名单**访问，可禁用第三方工具下载 。
* [cite_start]**数据统计**：支持图片外链分享及完善的图片访问统计（访问日志） 。

---

## 🛠️ 后台管理与初始配置

### 1. 超级管理员后台
* [cite_start]**登录地址**：`/admin/login.php` 或从首页右上角菜单进入 。
* [cite_start]**修改初始密码**：打开根目录 `/index_src/ini/seting.php`，将默认密码 `admin` 修改为强密码 。

### 2. 默认模板配置说明
[cite_start]所有模板核心文件位于 `/index_src/defaul/` 目录下 ：

* **相册后台 (`login.php`)**：
  * [cite_start]默认账号/密码：`ximi` / `【随机值】` 。
  * [cite_start]*注：为避免粗心忘记修改，初始密码已设为随机值，请务必手动修改 。*
* **相册访问日志 (`log.php`)**：
  * [cite_start]默认初始密码：`admin`（请按需修改或关闭） 。
  * [cite_start]**彻底关闭日志**：打开 `img.php` 文件，删除日志记录核心函数后续的所有代码即可 。
* **相册白名单 (`functions.php`)**：
  * [cite_start]用于添加和修改你的白名单域名（亦可在管理后台直接修改） 。
* **默认配置文件 (`ini.php`)**：
  * [cite_start]相册的核心配置文件，支持手动修改 。

---

## ⚙️ 配置文件说明 (`ini.php`)

```php
$config = array (
  'min'         => '../../update/user/cheng/class/min_image/',  // 小缩略图路径
  'max'         => '../../update/user/cheng/class/max_image/',  // 大缩略图路径
  'tu_1'        => '../../update/user/cheng/class/img/',        // 原图路径
  'login_admin' => 'login_defaul',                             // 登录标识
  'list_read'   => '1',                                         // 是否为私密相册：0 = 私密，1 = 公开
  'title'       => '城',                                         // 相册名称标题
  'cover'       => '',                                          // 相册封面图地址
  'list_home'   => '1',                                         // 是否在首页展示：0 = 隐藏，1 = 展示
  'txt'         => '',                                          // 相册介绍（在相册底部展示）
);