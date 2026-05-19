# Cloud 文件云存储系统

使用教程： https://www.bilibili.com/video/BV1pe9FBBEBy/ 一定要观看后再部署，里面有配置文件需要下载

## 项目介绍

Cloud 是一个基于 彩虹外链云盘，使用PHP + MySQL 的网盘/文件托管系统，支持文件上传、分享、外链、在线预览（图片/视频/音频）等功能。

### 主要特性

- 支持图片、视频、音频在线预览（CKPlayer 播放器）
- 多种存储后端：本地存储、阿里云 OSS、腾讯云 COS、七牛云、又拍云、华为 OBS
- 用户注册/登录系统，支持浏览器缓存登录状态
- API 上传接口（支持 JSON/JSONP/表单 回调格式）
- 文件分享、密码保护、下载次数统计
- 后台管理面板（文件管理、用户管理、系统设置、存储设置、检查更新等）
- 内置内容安全审核（阿里云绿网/腾讯云内容安全）
- 上传验证码、文件类型/大小限制、并发上传
- 在线检查更新，一键自动升级系统

## 运行环境

| 组件 | 要求 |
|------|------|
| PHP | >= 7.4（推荐 7.4 或 8.0） |
| MySQL | >= 5.6（推荐 5.7 或 MariaDB 10.x） |
| Web 服务器 | Nginx / Apache / 小皮面板（phpStudy） |
| PHP 扩展 | pdo_mysql、mbstring、json、gd、fileinfo、openssl |
| 操作系统 | Linux（推荐）/ Windows（开发测试） |

## 目录结构

```
cloud/
├── index.php              # 主页 / 文件列表
├── config.php             # 数据库配置（部署时修改）
├── api.php                # 上传 API 接口
├── upload.php             # 上传页面
├── file.php               # 文件详情页
├── view.php               # 文件预览页
├── player.php             # 视频播放页
├── login.php              # 用户登录
├── down.php               # 下载处理
├── ajax.php               # 前端 AJAX 接口
├── install.php            # 安装引导入口
├── favicon.ico
├── LICENSE                # 许可证
├── admin/                 # 后台管理面板
│   ├── index.php          # 管理首页
│   ├── login.php          # 管理员登录
│   ├── file.php           # 文件管理
│   ├── user.php           # 用户管理
│   ├── set.php            # 系统设置
│   ├── set_stor.php       # 存储设置
│   ├── update.php         # 检查更新
│   └── ...
├── assets/                # 前端静态资源
│   ├── css/               # 样式文件
│   └── js/                # JavaScript
├── includes/              # 核心库
│   ├── common.php         # 公共初始化
│   ├── functions.php      # 函数库
│   ├── header.php         # 页面头部
│   ├── footer.php         # 页面底部
│   ├── lib/               # 核心类库
│   │   ├── PdoHelper.php  # 数据库操作
│   │   ├── Oauth.php      # OAuth 认证
│   │   ├── Cache.php      # 缓存
│   │   └── Storage/       # 存储驱动
│   │       ├── Local.php  # 本地存储
│   │       ├── Oss.php    # 阿里云 OSS
│   │       ├── Qcloud.php # 腾讯云 COS
│   │       ├── Qiniu.php  # 七牛云
│   │       ├── Upyun.php  # 又拍云
│   │       └── Obs.php    # 华为 OBS
│   ├── OSS/               # 阿里云 OSS SDK
│   ├── Obs/               # 华为 OBS SDK
│   ├── Qcloud/            # 腾讯云 COS SDK
│   ├── Qiniu/             # 七牛云 SDK
│   ├── Upyun/             # 又拍云 SDK
│   └── vendor/            # Composer 依赖
├── file/                  # 本地存储目录
├── upload/                # 上传临时目录
└── install/               # 安装程序
    ├── index.php          # 安装入口
    ├── install.sql        # 数据库初始化 SQL
    └── install.lock       # 安装锁
```

## 部署步骤

### 方式一：小皮面板（phpStudy）部署

1. **启动环境**
   - 打开小皮面板，启动 Nginx（或 Apache）+ MySQL
   - 确认 PHP 版本 >= 7.4

2. **放置文件**
   - 将 cloud 整个文件夹复制到小皮面板的网站根目录
   - 小皮面板默认网站目录：`C:\phpstudy_pro\WWW\`
   - 放好后路径应为：`C:\phpstudy_pro\WWW\cloud\`

3. **创建数据库**
   - 打开小皮面板 → 数据库 → 创建数据库
   - 数据库名：cloud（或自定义）
   - 记下数据库用户名和密码

4. **访问安装**
   - 浏览器打开：http://localhost/cloud/install/
   - 按照向导步骤填写数据库信息和管理员账号
   - 点击安装，等待完成

5. **安装完成**
   - 前台地址：http://localhost/cloud/
   - 后台地址：http://localhost/cloud/admin/
   - 安装完成后删除或重命名 install 文件夹（建议重命名）

### 方式二：Linux 服务器部署

```bash
# 1. 上传文件
scp -r cloud/ root@服务器IP:/var/www/html/

# 2. 设置目录权限
chmod -R 755 /var/www/html/cloud/
chmod -R 777 /var/www/html/cloud/upload/
chmod -R 777 /var/www/html/cloud/file/

# 3. Nginx 配置示例
# 编辑 /etc/nginx/conf.d/cloud.conf

# 4. 浏览器访问安装
# http://your-domain.com/install/
```

## 配置说明

### 数据库配置

编辑 `config.php`：

```php
$dbconfig = array(
    'host'   => 'localhost',    // 数据库地址
    'port'   => 3306,           // 端口
    'user'   => 'root',         // 用户名
    'pwd'    => '123456',       // 密码
    'dbname' => 'cloud',        // 数据库名
);
```

 **安装后请修改管理员密码，数据库配置中的密码也建议改强密码。**

### 存储后端配置

安装后在 **后台管理 → 存储设置** 中配置：

| 存储方式 | 需要填写的配置 |
|----------|----------------|
| 本地存储 | 无需配置（默认） |
| 阿里云 OSS | AccessKey ID、AccessKey Secret、Bucket、Endpoint、Domain |
| 腾讯云 COS | SecretId、SecretKey、Bucket、Region、Domain |
| 七牛云 | AccessKey、SecretKey、Bucket、Domain |
| 又拍云 | 操作员名、密码、Bucket、Domain |
| 华为 OBS | AK、SK、Bucket、Endpoint |

### 文件类型限制

系统内置允许的文件类型：

- **图片**：png, jpg, jpeg, gif, bmp, webp, ico, svg, tif, tiff, heic
- **音频**：mp3, wav, ogg, m4a, flac, aac
- **视频**：mp4, webm, flv, mov, 3gp, avi, mkv, m3u8

可在后台管理中自定义扩展名黑白名单。

## 后台管理

| 功能 | 说明 |
|------|------|
| 控制面板 | 系统概览、快捷操作入口 |
| API 开关 | 开启/关闭 API 上传接口 |
| 文件管理 | 查看/搜索/删除所有上传的文件 |
| 用户管理 | 管理注册用户、查看用户文件 |
| 网站信息 | 站点名称、关键词、描述、公告 |
| 上传设置 | 文件大小限制、类型限制、验证码、并发数 |
| 用户设置 | 用户注册、强制登录开关 |
| 管理员设置 | 管理员账号和密码修改 |
| 存储设置 | 切换存储后端（本地/OSS/COS/七牛/又拍/OBS） |
| 检查更新 | 在线检查并一键升级系统 |

后台登录地址：`http://你的域名/admin/`

## API 接口

### 上传接口

**POST** `/api.php`

Content-Type: `multipart/form-data`

**参数：**
- `file`: 文件（必填）
- `upload_code`: 验证码（如开启了上传验证）
- `format`: json / jsonp / html（默认 json）
- `callback`: JSONP 回调函数名（format=jsonp 时生效）

**成功响应示例：**
```json
{
  "code": 0,
  "name": "example.png",
  "type": "image",
  "size": 102400,
  "hash": "abc123...",
  "downurl": "https://your-domain.com/file/abc123.png",
  "url": "https://your-domain.com/file/abc123.png"
}
```

**错误响应示例：**
```json
{
  "code": -1,
  "msg": "上传失败：文件类型不允许"
}
```

**错误码：**

| code | 含义 |
|------|------|
| 0 | 成功 |
| -1 | 一般错误 |
| -2 | 文件类型不允许 |
| -3 | 文件太大 |
| -4 | API 未开启 / 验证码错误 |
| -5 | 文件被安全拦截 |

## 常见问题

**1. 安装时提示"未检测到数据库扩展"**
- 确保 PHP 安装了 pdo_mysql 扩展
- 小皮面板中：PHP → 扩展管理 → 勾选 pdo_mysql

**2. 上传文件提示"权限不足"**
```bash
# Linux
chmod -R 777 cloud/upload/ cloud/file/

# Windows
确保 upload 和 file 目录对 IIS/IUSR 可写
```

**3. 页面显示 500 错误**
- 检查 PHP 版本是否 >= 7.1
- 查看 Nginx/Apache 错误日志
- 确认 config.php 数据库配置正确

**4. 视频无法播放**
- 检查视频格式是否在允许列表中
- 确保视频文件 URL 可直接访问（不是防盗链的）
- CKPlayer 需要浏览器支持 HTML5

**5. 如何重置管理员密码**
```sql
-- 直接在数据库中执行
UPDATE pre_config SET v = '新密码MD5值' WHERE k = 'admin_pwd';
-- 例如：echo -n "123456" | md5sum
```

## 版本信息

- **程序版本**：v2.1
- **数据库版本**：8.0.41
- **最低 PHP**：7.4
- **默认管理员**：admin / 123456（注意：首次使用请修改）

---

**联系作者**：https://space.bilibili.com/521205099
