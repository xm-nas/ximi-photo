<?php
//页眉

// 获取当前请求的主域名
$host = $_SERVER['HTTP_HOST'];

// 获取当前协议（http 或 https）
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

// 构建完整的域名
$full_domain = $protocol . '://' . $host;

// 打印主域名
//echo "主域名: " . $full_domain;
//============================================

?>
<!-- header.php -->
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? $pageTitle : '欢迎访问希米的图册'; ?></title>
 <link rel="stylesheet" href="https://fonts-api.wp.com/css?family=Noto+Serif+SC:900%7CNoto+Serif+SC:r,i,b,bi&subset=latin,latin-ext,latin,latin-ext">
  <link rel="stylesheet" href="/admin/css/themes.css">
</header>
 <style>
.header {
        width: 100%;
        background-color: #333;
        color: white;
        padding: 10px 0;
        text-align: center;
    }
    .header-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: center;
        gap: 20px;
    }
    .header-container a {
        color: white;
        text-decoration: none;
        padding: 5px 10px;
    }
    .header-container a:hover {
        background-color: #555;
        border-radius: 4px;
    }
    .container {
        width: 100%;
        max-width: 900px; /* Adjust max-width for better layout */
        text-align: center;
        margin: 36px auto;
        padding: 0 15px; /* Add some padding on smaller screens */
    }
    h2 {
        color: #333;
        margin-bottom: 20px;
    }

        body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-top: 30px;
    }
    
    </style>   
<body style="font-family: Arial, sans-serif;">
<div class="header">
<div class="header-container">
    <a href="./">主 页</a>
    <a href="./upload.php">上 传</a>
    <a href="./list-1.php">列 表</a>
   <a href="./list-2.php">平 铺</a> | 
     <a href="./admin.php">管 理</a>
 </div>
</div>  
<!-- 其他菜单项 -->
<!-- 页面内容开始 -->
<main>