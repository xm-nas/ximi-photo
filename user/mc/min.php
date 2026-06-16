<?php
//加载小缩略图
include("./ini.php");
include("./functions.php");

// 设置 Access-Control-Allow-Origin 头部
//header('Access-Control-Allow-Origin: https://img.cd'); // 仅允许特定域访问

// 允许的来源域名列表
//$allowedHosts = geturls();
//['www.ximi.me', 'ximi.me', 'img.cd', 'ip.img.cd','dalao.net','www.dalao.net'];

// 图片存储目录
//$imagePath = '../img/update/img/min_image/';
$imagePath = $config['min'];

// 设置 MIME 类型
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'gif' => 'image/gif',
    'ico' => 'image/x-icon'
];

// 函数：发送默认图片
function sendDefaultImage($ext) {
    global $mimeTypes;
    $defaultImgPath = '110.svg'; // 默认图片路径
    
    if (file_exists($defaultImgPath)) {
        header('Content-Type: ' . $mimeTypes[$ext]);
        readfile($defaultImgPath);
    } else {
        header('HTTP/1.0 404 Not Found');
        echo 'Default image not found.';
    }
    exit; // 停止后续脚本执行
}

// 检查 Referer
//$referer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '';

// 如果 Referer 为空或者不在允许的域名列表中，发送默认图片
//if (empty($referer) || !in_array($referer, $allowedHosts)) {
//    sendDefaultImage('svg'); // 默认发送 SVG 图片
//}



// 从查询字符串获取图片名称
$imgName = ltrim($_SERVER['QUERY_STRING'], '=');
$ext = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));

// 构造图片路径
$filePath = $imagePath . $imgName;

// 检查文件是否存在且为有效格式
if (file_exists($filePath) && isset($mimeTypes[$ext])) {
    header('Content-Type: ' . $mimeTypes[$ext]);
    readfile($filePath);
} else {
    // 如果文件不存在或者不支持的格式，发送默认图片
    sendDefaultImage('svg'); // 默认发送 SVG 图片
}

?>
