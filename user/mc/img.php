<?php
// img.php - 智能白名单动态防盗链与日志集成版
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("./ini.php");
if (file_exists("./functions.php")) {
    include("./functions.php");
}

// 1. 检查是否获取到参数
if (empty($_SERVER['QUERY_STRING'])) {
    header('Location: 404.php');    
    exit;
}

// 2. 获取并解析请求的图片名和文件扩展名
$imgName = ltrim($_SERVER['QUERY_STRING'], '=');
$ext = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));

// 设置支持的 MIME 类型
$mimeTypes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
    'gif'  => 'image/gif',
    'ico'  => 'image/x-icon'
];

// 函数：发送默认防盗链提示图片
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
}

// 3. 动态白名单开关鉴权校验
// 只有当 $config_whitelist 存在且 admin_whitelist 等于 '1' 时才开启过滤
$is_whitelist_enabled = isset($config_whitelist['admin_whitelist']) && $config_whitelist['admin_whitelist'] === '1';

if ($is_whitelist_enabled) {
    // 获取允许的来源域名列表
    $allowedHosts = function_exists('geturls') ? geturls() : [];
    
    // 检查 Referer 头部
    $referer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '';
    
    // 如果 Referer 为空或者不在允许的域名列表中，直接发送防盗链提示图并退出
    if (empty($referer) || !in_array($referer, $allowedHosts)) {
        sendDefaultImage('svg');
        // 虽然被拦截，但依然为其记录日志
        writeRequestLog($imgName, '【被拦截】防盗链触发');
        exit;
    }
}

// 4. 构造图片真实存储路径
$imagePath = $config['tu_1'];
$filePath = $imagePath . $imgName;

// 5. 检查物理文件是否存在且扩展名有效，输出图片流
if (file_exists($filePath) && isset($mimeTypes[$ext])) {
    header('Content-Type: ' . $mimeTypes[$ext]);
    readfile($filePath);
    
    // 成功穿透输出，记录正常请求日志
    writeRequestLog($imgName, '正常访问');
} else {
    // 如果文件不存在或者不支持的格式，发送默认错误图片
    sendDefaultImage('svg');
    writeRequestLog($imgName, '【404】图片不存在或格式不支持');
}

// ========================================== 日志记录核心函数 ==========================================

function writeRequestLog($imgName, $statusTag = '') {
    // 获取客户端IP地址
    $keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
    $ip = 'UNKNOWN';
    foreach ($keys as $key) {
        if (array_key_exists($key, $_SERVER)) {
            if (filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                $ip = $_SERVER[$key];
                break;
            }
        }
    }

    // 获取当前请求的完整 URL 地址
    $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host      = $_SERVER['HTTP_HOST'];
    $currentUrl = $protocol . $host . $_SERVER['REQUEST_URI'];

    // 换算缩略图预览日志地址
    $search = "img.php?=";
    $replace = "/min.php?=min_";
    $minurl = str_replace($search, $replace, $currentUrl);

    // 组装日志消息
    $logFile = 'log.php'; 
    $statusPrefix = $statusTag ? "[{$statusTag}] " : "";
    
    $message = "IP: " . $ip . "       ----Name: <a href=\"" . htmlspecialchars($minurl) . "\">" . htmlspecialchars($imgName) . "</a>       ----URL: " . htmlspecialchars($currentUrl) . " " . $statusPrefix . "</br>";
    
    // 将变量内容追加到日志文件
    //@file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
$date = new DateTime('now', new DateTimeZone('Asia/Shanghai'));
@file_put_contents($logFile, $date->format('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
}
?>