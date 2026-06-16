<?php
// about.php - 关于我们 / 说明文件查看页
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title = "关于 / 使用说明"; 

session_start();

// 1. 引入公共页眉（自动包含您的左侧栏和主体布局等）
if (file_exists("header.php")) {
    include("header.php");
}

// 2. 定义说明文件的路径
$readme_file = "../使用说明.txt";
$file_content = "";

// 3. 读取文件内容
if (file_exists($readme_file)) {
    // 读取文件并进行 HTML 转义，防止文本里含有的特殊字符破坏网页排版
    $file_content = htmlspecialchars(file_get_contents($readme_file), ENT_QUOTES, 'UTF-8');
} else {
    $file_content = "未找到说明文件。请检查服务器上级目录下是否存在 “说明文件.txt”。";
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <style>
        /* 复用您后台系统的通用精美样式 */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f0f2f5; color: #333; }
        .about-container { max-width: 960px; margin: 20px auto; background-color: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        h1 { color: #2c3e50; border-bottom: 2px solid #e0e0e0; padding-bottom: 12px; margin-bottom: 25px; font-weight: 600; font-size: 2.2em; }
        .nav-links { margin-bottom: 20px; font-size: 1.1em; }
        .nav-links a { color: #007bff; text-decoration: none; margin-right: 15px; transition: color 0.2s; }
        .nav-links a:hover { color: #0056b3; text-decoration: underline; }
        
        /* 说明文本展示的核心样式 */
        .readme-content-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 20px;
            font-size: 15px;
            line-height: 1.8;
            color: #495057;
            /* CRITICAL: white-space: pre-wrap 能完美保留 txt 文件中的换行、空格和缩进 */
            white-space: pre-wrap; 
            word-wrap: break-word;
            font-family: Consolas, "Liberation Mono", Menlo, Courier, monospace, "Microsoft YaHei";
        }
    </style>
</head>
<body>

<div class="admin-main-wrapper" style="padding: 20px;">
    <div class="about-container">
        <h1>📖 系统使用说明</h1>
        
        <div class="nav-links">
            <a href="admin.php">后台首页</a>
            <a href="./">网站首页</a>
        </div>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin-top: 15px; margin-bottom: 25px;">

        <div class="readme-content-box"><?php echo $file_content; ?></div>
    </div>
</div>

</main> 
</body>
</html>