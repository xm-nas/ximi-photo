<?php
/**
 * 希米3D图册系统 (Ximi Gallery) 后台管理
 * 完美优化版：消灭并发漏图丢图隐患、更新PicSizer组件下载总线、重组系统标识
 */
// 🚀 【安全加固：自毁拦截总线】检查前台 index.html 是否存在。若不存在，证明系统已自毁，直接阻断
if (!file_exists('../index.html')) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>系统已终止</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>
            body {
                background: #070a14;
                margin: 0;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
        </style>
    </head>
    <body>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    title: '☣️ 系统提示',
                    text: '该相册已执行全面自毁协议，前台数据已被物理擦除且无法恢复！',
                    icon: 'error',
                    background: '#13192e',
                    color: '#ff3860',
                    confirmButtonColor: '#ff3860',
                    confirmButtonText: '确定并关闭页面',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    // 1. 尝试直接关闭当前标签页
                    window.opener = null;
                    window.open('', '_self');
                    window.close();
                    
                    // 2. 针对微信内置浏览器环境的专用关闭 API
                    if (typeof WeixinJSBridge !== 'undefined') {
                        WeixinJSBridge.call('closeWindow');
                    }
                    
                    // 3. 【终极防御机制】如果浏览器策略死锁、拒绝关闭标签，则强行将页面打入一片纯白的死寂虚无状态
                    setTimeout(() => {
                        window.location.href = "about:blank";
                    }, 100);
                });
            });
        </script>
    </body>
    </html>
    <?php
    exit(); // 🚀 极其重要：直接切断 PHP 脚本，后续的 admin 登录验证、后台系统内容绝对不会被加载或暴露
}
// -----------------------------------------------------------
// 1. 常量与配置定义
// -----------------------------------------------------------
define('AUTH_PASSWORD', bin2hex(random_bytes(32)));

const MOBILE_DIR = '../files/mobile/';
const THUMB_DIR = '../files/thumb/';
const CONFIG_FILE_PATH = '../src/Config.json';
const XIMI_JS_PATH = '../src/ximi.js';
const INI_FILE_PATH = '../../ini.php';

const MAX_FILE_COUNT = 50; 
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
const ALLOWED_EXT = ['jpg'];
const THUMB_MAX_WIDTH = 250;

$keyTranslations = [
    'backGroundImgURL' => '背景 地址',
    'appLogoIcon' => 'Logo 地址',
    'appLogoLinkURL' => 'Logo链接',
    'LargeLogoTarget' => 'logo链接打开方式',
    'totalPageCount' => '总页数',
    'largePageWidth' => '相册宽',
    'largePageHeight' => '相册高',
    'RightToLeft' => '从右往左翻',
    'thicknessWidthType' => '书页厚度',
    'BindingType' => '书脊样式',
    'HardPageEnable' => '硬纸板封面',
    'hardCoverBorderWidth' => '边框宽度',
    'borderColor' => '边框颜色',
    'cornerRound' => '边框圆角',
    'aboutButtonVisible' => '显示关于按钮',
    'AboutAuthor' => '作者',
    'AboutEmail' => '邮件',
    'AboutAddress' => '地址',
    'AboutMobile' => '手机',
    'AboutWebsite' => '网站',
    'AboutDescription' => '描述',
];
$allowedEditableKeys = array_keys($keyTranslations);

// -----------------------------------------------------------
// 2. 会话状态与登录检查
// -----------------------------------------------------------
session_start();

$currentAlbumConfig = [];
if (file_exists('ini.php')) {
    $config = [];
    include('ini.php');
    $currentAlbumConfig = $config;
}
$logged_in_key = isset($currentAlbumConfig['login_admin']) ? $currentAlbumConfig['login_admin'] : 'logged_in_admin';

if (
    !(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) && 
    !(isset($_SESSION['logged_in_admin']) && $_SESSION['logged_in_admin'] === true) && 
    (empty($_SESSION[$logged_in_key]) || $_SESSION[$logged_in_key] !== true)
) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <title>未授权访问</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>
            body { background: #0b0f19; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        </style>
    </head>
    <body>
        <script>
            Swal.fire({
                title: 'ACCESS DENIED',
                text: '检测到未登录或凭证过期，请重新登录。',
                icon: 'error',
                confirmButtonText: '安全验证',
                confirmButtonColor: '#00f2fe',
                background: '#111827',
                color: '#f3f4f6',
                allowOutsideClick: false
            }).then(() => { window.location.href = 'index.php'; });
        </script>
 
    </body>
    </html>
    <?php
    exit();
}

if (isset($_GET['logout'])) { 
    session_destroy(); 
    header('Location: ./'); 
    exit(); 
}

// -----------------------------------------------------------
// 3. 核心业务处理函数
// -----------------------------------------------------------
function createDirIfNotExist($dir) { if (!is_dir($dir)) mkdir($dir, 0755, true); }

function generateThumbnail($sourcePath, $thumbPath, $maxWidth) {
    $imageType = @exif_imagetype($sourcePath);
    if ($imageType !== IMAGETYPE_JPEG) return false;
    $sourceImage = @imagecreatefromjpeg($sourcePath);
    if (!$sourceImage) return false;
    list($width, $height) = getimagesize($sourcePath);
    $ratio = $maxWidth / $width;
    $newHeight = $height * $ratio;
    $thumbImage = imagecreatetruecolor($maxWidth, $newHeight);
    imagecopyresampled($thumbImage, $sourceImage, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
    imagejpeg($thumbImage, $thumbPath, 85);
    imagedestroy($sourceImage); imagedestroy($thumbImage);
    return true;
}

function callApi(string $apiUrl, array $params): array {
    $url = $apiUrl . '?' . http_build_query($params);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false) { throw new Exception("API 链接中斩断: " . curl_error($ch)); }
    $data = json_decode($response, true);
    if ($data === null || $httpCode !== 200 || ($data['status'] ?? 'error') !== 'success') {
        $errorMessage = $data['message'] ?? '未知幽灵封包';
        throw new Exception("API 冲突: 状态码 $httpCode, 异常信息: $errorMessage");
    }
    return $data['data'];
}


function readConfig($path, $keysToDecrypt): array {
    if (!file_exists($path)) return [];
    $jsonContent = file_get_contents($path);
    $config = json_decode($jsonContent, true) ?: [];
    $decodedConfig = $config;
    $paramsToDecrypt = [];
    foreach ($keysToDecrypt as $key) {
        if (isset($config[$key])) $paramsToDecrypt[$key] = $config[$key];
    }
    if (!empty($paramsToDecrypt)) {
        try {
            $decryptedData = callApi('https://www.ximi.me/usr/api/decrypt.php', $paramsToDecrypt);
            foreach ($decryptedData as $key => $value) { $decodedConfig[$key] = $value; }
        } catch (Throwable $e) { error_log("核心解密失败: " . $e->getMessage()); }
    }
    return $decodedConfig;
}

function writeConfig($path, array $data, array $keysToEncrypt) {
    $encodedData = $data;
    $paramsToEncrypt = [];
    foreach ($keysToEncrypt as $key) {
        if (isset($data[$key])) $paramsToEncrypt[$key] = $data[$key];
    }
    if (!empty($paramsToEncrypt)) {
        $encryptedData = callApi('https://www.ximi.me/usr/api/encrypt.php', $paramsToEncrypt);
        foreach ($encryptedData as $key => $value) { $encodedData[$key] = $value; }
    }
    $jsonContent = json_encode($encodedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($path, $jsonContent) === false) { throw new Exception("配置矩阵定格失败 '$path'"); }
}


function syncToXimiJs() {
    if (!file_exists(CONFIG_FILE_PATH) || !file_exists(XIMI_JS_PATH)) return;
    $configJsonContent = file_get_contents(CONFIG_FILE_PATH);
    $configData = json_decode($configJsonContent, true);
    if (!$configData) return;
    $ximiJsContent = file_get_contents(XIMI_JS_PATH);
    if ($ximiJsContent === false) return;

    $patterns = []; $replacements = [];
    foreach ($configData as $key => $value) {
        $patterns[] = '/(["\']' . preg_quote($key, '/') . '["\']\s*:\s*)["\'][^"\']+["\']/';
        $replacements[] = '$1"' . addslashes($value) . '"';
    }
    $newXimiJsContent = $ximiJsContent;
    for ($i = 0; $i < count($patterns); $i++) {
        $newXimiJsContent = preg_replace($patterns[$i], $replacements[$i], $newXimiJsContent, 1);
    }
    @file_put_contents(XIMI_JS_PATH, $newXimiJsContent);
}

// 统一重排与清理函数（全面加强防御，杜绝缓存及锁死冲突引起的漏图）
function fixImagesSequence($allowedEditableKeys) {
    // 强制清除内核级文件状态缓存
    clearstatcache();
    
    $allFiles = array_diff(scandir(MOBILE_DIR), ['.', '..']);
    $imageFiles = array_filter($allFiles, fn($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ALLOWED_EXT));
    
    // 使用自然排序法进行物理对齐
    natsort($imageFiles); 
    $imageFiles = array_values($imageFiles);
    
    // 第一步：全部重命名为临时中转名，彻底隔离新旧文件队列冲突
    $newCounter = 1;
    foreach ($imageFiles as $oldName) {
        $newNameTemp = $newCounter . '.jpg.tmp';
        @rename(MOBILE_DIR . $oldName, MOBILE_DIR . $newNameTemp);
        if (file_exists(THUMB_DIR . $oldName)) {
            @rename(THUMB_DIR . $oldName, THUMB_DIR . $newNameTemp);
        }
        $newCounter++;
    }

    // 第二步：将所有 .tmp 文件固化归位为标准纯数字排布名称
    $allFilesTemp = array_diff(scandir(MOBILE_DIR), ['.', '..']);
    $tempImageFiles = array_filter($allFilesTemp, fn($f) => pathinfo($f, PATHINFO_EXTENSION) === 'tmp');
    natsort($tempImageFiles);

    $finalCounter = 1;
    foreach ($tempImageFiles as $tempName) {
        $finalName = $finalCounter . '.jpg';
        @rename(MOBILE_DIR . $tempName, MOBILE_DIR . $finalName);
        if (file_exists(THUMB_DIR . $tempName)) {
            @rename(THUMB_DIR . $tempName, THUMB_DIR . $finalName);
        }
        $finalCounter++;
    }
// 读取图片数量 

$photoList = count(glob(MOBILE_DIR . '*.jpg') ?: []);

// --- 盘点结束：$photoList 此时已完成资产注入 ---


    // 第三步：物理强制清除现有过期的缩略图文件夹，根据最新的最终排序重新精确生成全套缩略图
    if (is_dir(THUMB_DIR)) {
        $oldThumbs = array_diff(scandir(THUMB_DIR), ['.', '..']);
        foreach ($oldThumbs as $ot) { @unlink(THUMB_DIR . $ot); }
    }
    
    $finalFiles = array_diff(scandir(MOBILE_DIR), ['.', '..']);
    foreach ($finalFiles as $file) {
        if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ALLOWED_EXT)) {
            generateThumbnail(MOBILE_DIR . $file, THUMB_DIR . $file, THUMB_MAX_WIDTH);
        }
    }
    autoUpdateConfigAndJs($allowedEditableKeys);
}

function autoUpdateConfigAndJs($allowedEditableKeys) {
    clearstatcache();
    $imageCount = 0; $imageWidth = 0; $imageHeight = 0;
    if (is_dir(MOBILE_DIR)) {
        $files = scandir(MOBILE_DIR);
        foreach ($files as $file) {
            if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ALLOWED_EXT)) $imageCount++;
        }
    }
    
    $firstImagePath = MOBILE_DIR . '1.jpg';
    if (!file_exists($firstImagePath)) {
        $allFiles = array_diff(scandir(MOBILE_DIR), ['.', '..']);
        $imageFiles = array_filter($allFiles, fn($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ALLOWED_EXT));
        if (!empty($imageFiles)) {
            natsort($imageFiles);
            $firstImagePath = MOBILE_DIR . reset($imageFiles);
        }
    }

    if (file_exists($firstImagePath)) {
        $imageSize = @getimagesize($firstImagePath);
        if ($imageSize !== false) { $imageWidth = $imageSize[0]; $imageHeight = $imageSize[1]; }
    }
    
    $currentConfig = readConfig(CONFIG_FILE_PATH, $allowedEditableKeys);
    $currentConfig['totalPageCount'] = (string)$imageCount;
    if ($imageWidth > 0) $currentConfig['largePageWidth'] = (string)$imageWidth;
    if ($imageHeight > 0) $currentConfig['largePageHeight'] = (string)$imageHeight;
    
    writeConfig(CONFIG_FILE_PATH, $currentConfig, $allowedEditableKeys);
    syncToXimiJs();
}

function addDirToZip($dir, $zip, $zipSubDir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $filePath = $dir . '/' . $file;
        $localPath = $zipSubDir ? $zipSubDir . '/' . $file : $file;
        if (is_dir($filePath)) {
            $zip->addEmptyDir($localPath);
            addDirToZip($filePath, $zip, $localPath);
        } else {
            $zip->addFile($filePath, $localPath);
        }
    }
}

function removeDirRecursive($dir) {
    if (!is_dir($dir)) return false;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? removeDirRecursive($path) : @unlink($path);
    }
    return @rmdir($dir);
}

// -----------------------------------------------------------
// 4. 请求路由及 API 响应分发
// -----------------------------------------------------------
createDirIfNotExist(MOBILE_DIR); createDirIfNotExist(THUMB_DIR);
$uploadMessage = ''; $uploadMessageType = '';

// 新增的 AJAX 导入接口
if (isset($_GET['action']) && $_GET['action'] === 'ajax_import_ini') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        if (!file_exists(INI_FILE_PATH)) throw new Exception("未找到配置文件：ini.php");
        
        $iniContent = file_get_contents(INI_FILE_PATH);
        if (!preg_match('/\'max\'\s*=>\s*[\'"]([^\'"]+)[\'"]/', $iniContent, $matches)) {
            throw new Exception("无法在 ini.php 中解析出 'max' 路径。");
        }
        
        $convertedPath = str_replace('../../', '../../../../', $matches[1]);
        $convertedPath = rtrim($convertedPath, '/\\') . '/'; 
        if (!is_dir($convertedPath)) throw new Exception("路径不存在: " . $convertedPath);

        // 1. 先清空原本目录里残存的非标准垃圾文件，确保全新起点
        clearstatcache();
        foreach (glob(MOBILE_DIR . '*') as $existFile) {
            if (is_file($existFile)) @unlink($existFile);
        }
        foreach (glob(THUMB_DIR . '*') as $existThumb) {
            if (is_file($existThumb)) @unlink($existThumb);
        }

        $sourceFiles = array_diff(scandir($convertedPath), ['.', '..']);
        
        // 采用自然排序法整理源文件，确保导入进去的顺序和你在外部看到的一模一样
        natsort($sourceFiles);
        $sourceFiles = array_values($sourceFiles);

        $importCount = 0;
        $log = [];
        $localAllowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        foreach ($sourceFiles as $file) {
            $sourceFilePath = $convertedPath . $file;
            if (!is_file($sourceFilePath)) continue;

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $localAllowedExt)) {
                
                // 【核心突破】：不再保留原文件名，直接按照计数器落地为 1.jpg, 2.jpg...
                // 这样从源头上就消灭了重命名冲突、杜绝漏图丢图！
                $nextIndex = $importCount + 1;
                $targetMobilePath = MOBILE_DIR . $nextIndex . '.jpg';
                $targetThumbPath = THUMB_DIR . $nextIndex . '.jpg';

                // 2. 如果本来就是 JPG/JPEG，直接无脑高效复制
                if ($ext === 'jpg' || $ext === 'jpeg') {
                    if (@copy($sourceFilePath, $targetMobilePath)) {
                        // 同步创建缩略图
                        generateThumbnail($targetMobilePath, $targetThumbPath, THUMB_MAX_WIDTH);
                        $importCount++;
                        $log[] = "成功固化JPG: {$file} -> {$nextIndex}.jpg";
                    }
                    continue;
                }

                // 3. 其它格式（PNG/WEBP/GIF）转码并精准落地为数字.jpg
                $image = null;
                if ($ext === 'png') {
                    $image = @imagecreatefrompng($sourceFilePath);
                } elseif ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
                    $image = @imagecreatefromwebp($sourceFilePath);
                } elseif ($ext === 'gif') {
                    $image = @imagecreatefromgif($sourceFilePath);
                }

                if ($image) {
                    $width = imagesx($image);
                    $height = imagesy($image);
                    $bg = imagecreatetruecolor($width, $height);
                    
                    $white = imagecolorallocate($bg, 255, 255, 255);
                    imagefill($bg, 0, 0, $white);
                    imagecopy($bg, $image, 0, 0, 0, 0, $width, $height);
                    
                    if (@imagejpeg($bg, $targetMobilePath, 90)) {
                        // 同步创建缩略图
                        generateThumbnail($targetMobilePath, $targetThumbPath, THUMB_MAX_WIDTH);
                        $importCount++;
                        $log[] = "成功转码固化: {$file} -> {$nextIndex}.jpg";
                    }

                    imagedestroy($image);
                    imagedestroy($bg);
                }
            }
        }

        if ($importCount > 0) {
            // 4. 数据完全落地后，刷新系统母舰配置矩阵与前端前端总线
            autoUpdateConfigAndJs($allowedEditableKeys);
            
            echo json_encode([
                'status' => 'success', 
                'message' => "资产总线大捷！成功导入并转换了 {$importCount} 张图片，已完成全自动重排命名与缩略图重构！", 
                'log' => $log
            ]);
        } else {
            echo json_encode(['status' => 'info', 'message' => "检索范围未捕获可用（JPG/PNG/WEBP/GIF）格式资源。"]);
        }
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}


// 极速单文件异步上传入口（加入更高随机因子的独占文件名，安全避开并发写入冲突）
if (isset($_GET['action']) && $_GET['action'] === 'ajax_upload') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        if (!isset($_FILES['file'])) throw new Exception("未捕捉到上行文件流。");
        $file = $_FILES['file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception("上传内部错误。代码:" . $file['error']);
        if ($file['size'] > MAX_FILE_SIZE) throw new Exception("超出5MB单文件限制安全阈值。");
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXT)) throw new Exception("格式拒绝，只接纳 .jpg 文件。");
        
        $isOverwrite = isset($_POST['overwrite']) && $_POST['overwrite'] === 'true';
        $autoRename = isset($_POST['autorename']) && $_POST['autorename'] === 'true';
        
        if ($isOverwrite) {
            $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
            if (!is_numeric($baseName) || substr($baseName, 0, 1) === '0') {
                throw new Exception("覆盖模式需要源文件名必须是标准纯数字。");
            }
            $targetName = $baseName . '.jpg';
        } else {
            if ($autoRename) {
                // 🚀 防冲突升级：通过微秒级uniqid结合mt_rand高能随机值，100%隔开并发线程名
                $threadPrefix = 'xm' . uniqid() . mt_rand(1000, 9999) . '__';
                $targetName = $threadPrefix . strtolower($file['name']);
            } else {
                $targetName = strtolower($file['name']);
                if (file_exists(MOBILE_DIR . $targetName)) {
                    echo json_encode(['status' => 'success', 'message' => "节点 [{$targetName}] 已存在，系统自动跳过。"]);
                    exit();
                }
            }
        }
        
        $destMobile = MOBILE_DIR . $targetName;
        $destThumb = THUMB_DIR . $targetName;
        
        if (move_uploaded_file($file['tmp_name'], $destMobile)) {
            // 先行在内存及物理区创建暂存缩略图，防止缓存击穿
            generateThumbnail($destMobile, $destThumb, THUMB_MAX_WIDTH);
            echo json_encode(['status' => 'success', 'message' => "已无冲突暂存: [{$targetName}]"]);
        } else {
            throw new Exception("文件写入磁盘失败。");
        }
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
 }

 // 专门给前端上传完成后，调用的排序同步接口
 if (isset($_GET['action']) && $_GET['action'] === 'ajax_sort_sync') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        fixImagesSequence($allowedEditableKeys);
        echo json_encode(['status' => 'success', 'message' => '全序列重排索引成功，系统已闭环！']);
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
 }

 // 传统同步表单响应区
 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 行为 A: 批量删除
    if (isset($_POST['delete_action'])) {
        try {
            $filesToDelete = [];
            if (!empty($_POST['delete_files']) && is_array($_POST['delete_files'])) {
                $filesToDelete = $_POST['delete_files'];
            } elseif (!empty($_POST['single_delete_file'])) {
                $filesToDelete[] = $_POST['single_delete_file'];
            }
            if (empty($filesToDelete)) throw new Exception("没有勾选需要移除的数据链。");
            foreach ($filesToDelete as $filename) {
                $filename = basename($filename);
                @unlink(MOBILE_DIR . $filename);
                @unlink(THUMB_DIR . $filename);
            }
            autoUpdateConfigAndJs($allowedEditableKeys);
            $uploadMessage =  "🗑️ 图像单元 {$filename} 已被安全剔除。"; $uploadMessageType = "success";
            // $sysMsg = "🗑️ 图像单元 {$targetFile} 已被安全剔除。";
        } catch (Throwable $e) { $uploadMessage = $e->getMessage(); $uploadMessageType = "error"; }
    }



// 行为 F: 从 ini.php 导入外部历史图片并统一转换为 JPG
    elseif (isset($_POST['import_from_ini'])) {
        try {
            if (!file_exists(INI_FILE_PATH)) {
                throw new Exception("未找到配置文件：" . INI_FILE_PATH);
            }
            $iniContent = file_get_contents(INI_FILE_PATH);
            
            if (!preg_match('/\'max\'\s*=>\s*[\'"]([^\'"]+)[\'"]/', $iniContent, $matches)) {
                throw new Exception("无法在 ini.php 中解析出 'max' 图片目录路径。");
            }
            
            $originalPath = $matches[1];
            $convertedPath = str_replace('../../', '../../../../', $originalPath);
            $convertedPath = rtrim($convertedPath, '/\\') . '/'; // 确保末尾有斜杠

            if (!is_dir($convertedPath)) {
                throw new Exception("转换后的导入目标目录不存在或无权限访问： " . $convertedPath);
            }

            $sourceFiles = array_diff(scandir($convertedPath), ['.', '..']);
            $importCount = 0;

            // 支持的源图片后缀（统一转小写比对）
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            foreach ($sourceFiles as $file) {
                $sourceFilePath = $convertedPath . $file;
                
                if (!is_file($sourceFilePath)) {
                    continue;
                }

                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowedExtensions)) {
                    
                    // 1. 如果本来就是 JPG/JPEG，直接按原逻辑照搬 copy，不做任何处理
                    if ($ext === 'jpg' || $ext === 'jpeg') {
                        $targetMobilePath = MOBILE_DIR . $file;
                        if (@copy($sourceFilePath, $targetMobilePath)) {
                            $importCount++;
                        }
                        continue;
                    }

                    // 2. 其他格式（PNG/WEBP/GIF）直接读取并转存为 .jpg 后缀文件
                    $image = null;
                    if ($ext === 'png') {
                        $image = @imagecreatefrompng($sourceFilePath);
                    } elseif ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
                        $image = @imagecreatefromwebp($sourceFilePath);
                    } elseif ($ext === 'gif') {
                        $image = @imagecreatefromgif($sourceFilePath);
                    }

                    // 3. 转码核心：直接无脑输出为 原文件名.jpg
                    if ($image) {
                        // 不去理会复杂的重命名，直接在原文件名后面追加 .jpg 扔进目标目录
                        $targetMobilePath = MOBILE_DIR . $file . '.jpg';

                        $width = imagesx($image);
                        $height = imagesy($image);
                        $bg = imagecreatetruecolor($width, $height);
                        
                        // 填充白底防止透明背景变黑
                        $white = imagecolorallocate($bg, 255, 255, 255);
                        imagefill($bg, 0, 0, $white);
                        imagecopy($bg, $image, 0, 0, 0, 0, $width, $height);
                        
                        // 导出为 JPG
                        if (@imagejpeg($bg, $targetMobilePath, 90)) {
                            $importCount++;
                        }

                        imagedestroy($image);
                        imagedestroy($bg);
                    }
                }
            }

            // 导入完成后，交由原有的全谱排序重映射函数一网打尽，统一格式化命名
            if ($importCount > 0) {
                fixImagesSequence($allowedEditableKeys);
                $uploadMessage = "同步导入并转换了 {$importCount} 枚节点图片，已执行全谱排序重映射。";
                $uploadMessageType = "success";
            } else {
                $uploadMessage = "检索范围未捕获可用（JPG/PNG/WEBP/GIF）格式资源。";
                $uploadMessageType = "info";
            }

        } catch (Throwable $e) { 
            $uploadMessage = "导入失败：" . $e->getMessage(); 
            $uploadMessageType = "error"; 
        }
    }



    // 行为 C: 手动触发一键修复排序与同步
    elseif (isset($_POST['update_config'])) {
        try {
            fixImagesSequence($allowedEditableKeys);
            $uploadMessage = "全序列索引校准完毕，核心数据网已闭环同步。"; $uploadMessageType = "success";
        } catch (Throwable $e) { $uploadMessage = $e->getMessage(); $uploadMessageType = "error"; }
    }

    // 行为 D: 缩略图重构
    elseif (isset($_POST['regenerate_thumbs'])) {
        try {
            foreach (glob(THUMB_DIR . '*.jpg') as $file) @unlink($file);
            foreach (glob(MOBILE_DIR . '*.jpg') as $m) generateThumbnail($m, THUMB_DIR . basename($m), THUMB_MAX_WIDTH);
            $uploadMessage = "物理视窗矩阵（缩略图）全部强制重组。"; $uploadMessageType = "success";
        } catch (Throwable $e) { $uploadMessage = $e->getMessage(); $uploadMessageType = "error"; }
    }

    // 行为 E: 手动提交更改参数
    elseif (isset($_POST['save_manual_config'])) {
        try {
            $currentConfig = readConfig(CONFIG_FILE_PATH, $allowedEditableKeys); 
            foreach ($_POST as $key => $value) {
                if (in_array($key, $allowedEditableKeys) && array_key_exists($key, $currentConfig)) {
                    $currentConfig[$key] = htmlspecialchars(trim($value));
                }
            }
            writeConfig(CONFIG_FILE_PATH, $currentConfig, $allowedEditableKeys);
            syncToXimiJs(); 
            $uploadMessage = "修改的节点已被安全加密固化至母舰配置。"; $uploadMessageType = "success";
        } catch (Throwable $e) { $uploadMessage = "配置固化失败: " . $e->getMessage(); $uploadMessageType = "error"; }
    }

    // 行为 F: 一键打包下载
    elseif (isset($_POST['pack_album'])) {
        try {
            if (!class_exists('ZipArchive')) throw new Exception("宿主环境组件缺失 ZipArchive 支撑。");
            //$zipName = 'XIMI_PHOTO_' . time() . '.zip';
            $zipName = 'ximi_photo_' . date('YmdHis') . '.zip';
            $zipPath = sys_get_temp_dir() . '/' . $zipName;
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new Exception("无法开辟空密闭压缩包。");
            
            $targets = ['../files' => 'files', '../src' => 'src', '../style' => 'style'];
            foreach ($targets as $sDir => $lDir) {
                if (is_dir($sDir)) { $zip->addEmptyDir($lDir); addDirToZip($sDir, $zip, $lDir); }
            }
            if (file_exists('../index.html')) $zip->addFile('../index.html', 'index.html');
            $zip->close();

            if (file_exists($zipPath)) {
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipName . '"');
                header('Content-Length: ' . filesize($zipPath));
                readfile($zipPath); @unlink($zipPath); exit();
            } else { throw new Exception("归档生成失败。"); }
        } catch (Throwable $e) { $uploadMessage = "归档异常: " . $e->getMessage(); $uploadMessageType = "error"; }
    }

    // 行为 G: 自毁控制


}

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>希米3D图册系统</title>
    <style>
        :root {
            /* 🪐 全面平移前台暗色背景参数，绝对不发白，烘托极致极客感 */
            --bg-dark: #030712;          /* 极深暗夜黑 */
            --bg-light: #0a1324;         /* 深沉钴蓝核心层 */
            
            /* 🎛️ 中间容器：打造高透全息毛玻璃 */
            --panel-bg: rgba(28, 49, 88, 0.65); 
            --border-neon: rgba(0, 242, 254, 0.55); /* 强化激光边缘线的亮度和存在感 */
            
            /* 🎨 核心霓虹点缀色 */
            --neon-blue: #00f2fe;        /* 冰川锐利蓝 */
            --neon-green: #05f3a0;       /* 碧绿全息光 */
            --text-light: #ffffff;       
            --text-muted: #a3c3f5;       
            --accent-red: #ff4a6b;
        }
        
        body {
           background-color: var(--bg-dark);
    background-image: linear-gradient(rgba(0, 242, 254, 0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 242, 254, 0.02) 1px, transparent 1px);
    background-size: 40px 40px;
    color: var(--text-light);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    margin: 0;
    padding: 40px 20px;
    min-height: 100vh;
    box-sizing: border-box;        }
        
        .container {
width: 100%;
    max-width: 1100px;
    margin: 0 auto;
    background: var(--panel-bg);
    clip-path: polygon(0 15px, 15px 0, 100% 0, 100% calc(100% - 15px), calc(100% - 15px) 100%, 0 100%);
    border: 1px solid var(--border-neon);
    padding: 40px;
    box-shadow: 0 40px 100px rgba(0, 0, 0, 0.8), inset 0 0 35px rgba(0, 242, 254, 0.25), 0 0 40px rgba(0, 242, 254, 0.1);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    position: relative;
        }
        
        h1 {
            font-size: 24px; font-weight: 700; letter-spacing: 2px; margin-top: 0; padding-bottom: 16px;
            color: #fff; text-shadow: 0 0 10px rgba(0,242,254,0.4); border-bottom: 1px solid rgba(0, 242, 254, 0.3);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }

        h2 {
            font-size: 16px; color: var(--neon-blue); font-weight: 600; margin-top: 0; margin-bottom: 20px;
            text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 8px;
        }
        /*h2::before { content: '//'; color: var(--neon-green); font-weight: bold; }*/
        
        .form-section {
                background: rgba(13, 25, 48, 0.7);
    border: 1px solid rgba(0, 242, 254, 0.25);
    padding: 25px;
    clip-path: polygon(0 12px, 12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%);
    box-shadow: inset 0 0 20px rgba(0, 242, 254, 0.1);margin-bottom: 25px;
        }

        .file-upload-wrapper { position: relative; width: 100%; margin: 15px 0; }
.file-upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px 20px;
    background: var(--bg-input);
    border: 2px dashed rgb(0 242 254 / 81%);
    border-radius: 0px;
    /* clip-path: polygon(0 12px, 12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%); */
    box-shadow: inset 0 0 20px rgba(0, 242, 254, 0.1);
    cursor: pointer;
    transition: all 0.25s ease;
    color: var(--neon-blue);
    font-weight: 500;
}
        .file-upload-label:hover {
            background: rgba(0, 242, 254, 0.05); border-color: var(--neon-green); color: var(--neon-green);
            box-shadow: 0 0 15px rgba(5, 243, 160, 0.1);
        }
        .file-upload-wrapper input[type="file"] { position: absolute; left: 0; top: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer; }

        .progress-container {
            margin: 20px 0; display: none; background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .progress-bar-bg { width: 100%; height: 6px; background: #0d1326; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
        .progress-bar-fill {
            width: 0%; height: 100%; background: linear-gradient(90deg, var(--neon-blue), var(--neon-green));
            transition: width 0.1s linear; box-shadow: 0 0 8px var(--neon-blue);
        }
        .upload-status-text { display: flex; justify-content: space-between; font-size: 12px; color: var(--color-muted); font-family: monospace; }
        .upload-log-box {
            max-height: 150px; overflow-y: auto; background: #070a14; border-radius: 6px;
            padding: 10px; font-family: monospace; font-size: 11px; color: #8ba2d4; margin-top: 10px; border: 1px solid rgba(0,0,0,0.5);
        }
        .log-line { margin: 2px 0; border-bottom: 1px solid rgba(255,255,255,0.02); }
        .log-success { color: var(--neon-green); }
        .log-error { color: var(--neon-red); }

        button, input[type="submit"], .btn-link {
background: linear-gradient(135deg, #00c6ff, #0072ff);
    color: #fff;
    padding: 11px 24px;
    border: none;
    /* border-radius: 6px; */
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: all 0.25s ease;
    box-shadow: 0 2px 8px rgba(0, 114, 255, 0.3);
    display: inline-block;
    text-decoration: none;
    text-align: center;
    margin-top: 8px;
    margin-right: 6px;
    clip-path: polygon(8px 0%, 100% 0%, calc(100% - 8px) 100%, 0% 100%);
    border: 1px solid rgba(5, 243, 160, 0.5) ;
    border-radius: 0px !important;
    background: linear-gradient(135deg, rgba(5, 243, 160, 0.25) 0%, rgba(0, 120, 80, 0.4) 100%);
        }
        button:hover, input[type="submit"]:hover, .btn-link:hover { filter: brightness(1.1); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,242,254,0.4); 
         /*   clip-path: polygon(0% 0%, calc(100% - 8px) 0%, 100% 100%, 8px 100%);*/
    background: linear-gradient(135deg, rgba(5, 243, 160, 0.45) 0%, rgba(5, 243, 160, 0.15) 100%);
    box-shadow: 0 0 25px rgba(5, 243, 160, 0.5);
    text-shadow: 0 0 12px #fff;
    transform: scale(1.02);}
        
        .btn-action-green {  box-shadow: 0 2px 8px rgba(5,243,160,0.2); }
        .btn-action-grey { background: rgba(255,255,255,0.08); color: var(--color-text); border: 1px solid rgba(255,255,255,0.1); box-shadow: none; }
        .btn-action-grey:hover { background: rgba(255,255,255,0.15); color: #fff; box-shadow: none; }
.btn-action-red {
    background: linear-gradient(135deg, #ff3860a1, #b51733c2);
    box-shadow: 0 2px 8px rgba(255, 56, 96, 0.2);
} 
        .btn-preview-nav {
            text-decoration: none !important; border-bottom: none !important; background: transparent; color: var(--neon-blue);
            border: 1px solid var(--neon-blue); padding: 6px 14px; border-radius: 4px; font-size: 13px; font-weight: 500;
            box-shadow: inset 0 0 4px rgba(0,242,254,0.2);
        }
        .btn-preview-nav:hover { background: var(--neon-blue); color: var(--bg-base); text-decoration: none !important; box-shadow: 0 0 12px var(--neon-blue); }

        .tips-panel { background: rgba(245, 158, 11, 0.06); border-left: 3px solid #f59e0b; padding: 14px; border-radius: 0 8px 8px 0; font-size: 13px; color: #e0a94b; margin: 12px 0; }

        .message.error { background: rgba(255,56,96,0.1); border: 1px solid var(--neon-red); color: #fb0000ba;
         clip-path: polygon(0 12px, 12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%);
    box-shadow: inset 0 0 20px rgb(0 242 254 / 81%); }
.message.success {
    background: rgba(5, 243, 160, 0.1);
    /* border: 1px solid var(--neon-green); */
    color: #05ba1d;
    clip-path: polygon(0 12px, 12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%);
    box-shadow: inset 0 0 20px rgb(0 242 254 / 81%);
}
.message {
    padding: 12px;
    margin-bottom: 20px;
    /* border-radius: 6px; */
    font-size: 14px;
    font-weight: 600;
    text-align: center;
}   
        
.recommend-box {
    background: rgba(5, 243, 160, 0.04);
    border: .5px dashed rgba(5, 243, 160, 0.25);
    /* border-radius: 8px; */
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 13px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    clip-path: polygon(0 12px, 12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%);
    box-shadow: inset 0 0 20px rgba(0, 242, 254, 0.1);
}

        .gallery-toolbar { padding: 12px; background: rgba(0,0,0,0.2); border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; }
        .image-gallery { display: flex; flex-wrap: wrap; gap: 14px; }
        .gallery-item { display: flex; flex-direction: column; width: 105px; }
        .image-container { position: relative; width: 105px; height: 105px; background: #090d1a; border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; overflow: hidden;    transition: all 0.25s ease; }
        .image-container img { width: 100%; height: 100%; object-fit: cover; cursor: pointer; }
/* 1. 强制定义删除按钮的默认状态（严格锁定尺寸与外观） */
.image-container button.delete-button {
    position: absolute !important;
    top: 4px !important;
    right: 4px !important;
    background: rgba(239, 68, 68, 0.9) !important;
    color: white !important;
    border: none !important;
    font-size: 10px !important;
    font-weight: bold !important;
    cursor: pointer !important;
    padding: 2px 6px !important;
    border-radius: 4px !important;
    z-index: 10 !important;
    transition: background 0.2s !important;
    
    /* 强力防御重置：防止被其他地方的赛博朋克特殊裁切、阴影破坏 */
    width: auto !important;
    height: auto !important;
    box-shadow: none !important;
    clip-path: none !important;
    transform: none !important;
    text-shadow: none !important;
}

/* 2. 强力隔离悬停状态：仅允许略微变亮，彻底阻断流光、变形、放大的污染 */
.image-container button.delete-button:hover {
    filter: brightness(1.2) !important;
    background: rgba(239, 68, 68, 1) !important;
    
    /* 强力防御重置：彻底消除 scale(1.02) 和 clip-path 变形 */
    transform: none !important;
    box-shadow: none !important;
    clip-path: none !important;
    text-shadow: none !important;
}
.image-container:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}
             
        .image-container .select-box { position: absolute; bottom: 4px; left: 4px; width: 15px; height: 15px; accent-color: var(--neon-blue); z-index: 5; cursor: pointer; }
        .image-filename { font-size: 11px; text-align: center; margin-top: 5px; color: var(--color-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        input[type="text"], textarea {
               width: 100%;

    margin-top: 6px;

    transition: all 0.2s;
    flex: 1;
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(0, 242, 254, 0.3);
    padding: 10px 14px;
    color: #fff;
    font-family: monospace;
    font-size: 13px;    }
        input[type="text"]:focus, textarea:focus { border-color: var(--neon-blue); box-shadow: 0 0 8px rgba(0,242,254,0.2); outline: none; }

        .preview-modal {
            display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%;
            background: rgba(5,7,14,0.9); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); align-items: center; justify-content: center; opacity: 0; transition: opacity 0.25s ease;
        }
        .preview-modal.show { display: flex; opacity: 1; }
        .preview-content { max-width: 90%; max-height: 90%; border-radius: 4px; box-shadow: 0 0 30px rgba(0,0,0,0.8); }
        .preview-close { position: absolute; top: 20px; right: 30px; color: #fff; font-size: 35px; cursor: pointer; }
    
.btn-action-red:hover {

    transform: scale(1.02);
 background: linear-gradient(135deg, #ff3860a1, #b51733c2);
    box-shadow: 0 2px 8px rgba(255, 56, 96, 0.2);
}

    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <h1>
            <span>希米3D图册系统 <span style="font-size:12px; color:var(--neon-green); vertical-align:middle; margin-left:8px;">● CORE_CONNECTED</span></span>
            <span style="font-size:14px; font-weight:normal;">
                <a href="../" target="_blank" class="cyber-btn" style="
    padding: 3px 10px;
    background: #00c6ffa1;
">👁️ 预览相册</a>

                <a href="?logout" class="cyber-btn" style="
    padding: 3px 10px;
    background: linear-gradient(135deg, #ff3860, #b51733);
    box-shadow: 0 2px 8px rgba(255, 56, 96, 0.2);    min-width: 76px;">⚙️ 退出系统 </a>
            </span>
        </h1>
        
        <?php if (!empty($uploadMessage)) echo "<div class='message $uploadMessageType'>$uploadMessage</div>"; ?>

        <div class="recommend-box">
            <div>
                <strong style="color:var(--neon-green)">⚡ 工具推荐：</strong> 
                图册的长宽比例如果不一致，前台展示可能产生变形。建议使用轻量批量处理软件 <span style="color:#fff; font-weight:600">PicSizer</span> 预先一键裁剪/统一尺寸。
            </div>
            <a href="https://www.ximi.me/down.php?url=PicSizer%20v4.9.3.7z" class="cyber-btn" style="margin:0; padding:6px 14px; font-size:12px;" target="_blank">📥 获取工具组件 (PicSizer v4.9.3)</a>
        </div>


<div class="form-section">
    <h2>🔄 核心配置文件资产同步总线</h2>
    <div class="tips-panel" style="border-left: 3px solid #35d6a0; color: #3ed8af;">
        <strong>系统将自动解析 <strong style="color:#fff">ini.php</strong> 的 <code>max</code> 相对路径。导入时仅扫描并引入 .jpg 格式图片。</strong>
    </div>

    <!-- 修改后的操作区 -->
    <button type="button" class="btn-action-green" id="start_import_btn" style="width:100%; padding:12px; font-size:14px; margin-top:10px;">
        🔄 一键导入并生成3D相册
    </button>

    <!-- 日志监控区域 -->
    <div class="progress-container" id="import_monitor" style="display:none;">
        <div class="upload-status-text">
            <span id="import_monitor_title">正在接通链路...</span>
        </div>
        <div class="upload-log-box" id="import_monitor_log"></div>
    </div>
</div>




        <div class="form-section">
            <h2>📤 量子图像资产流式注入</h2>
            
            <div class="tips-panel">
                <strong>⚠️ 上传协议：</strong>所有上传图片单张请勿超过 <strong>5MB</strong>；单次并发吞吐请勿超过 <strong>50张</strong>。多图请分批次压入。上传前请<b>先统一长宽比例尺寸</b>，避免前台书页显示变形、产生空白边缘。
            </div>

            <div class="file-upload-wrapper">
                <div class="file-upload-label" id="drop_zone">
                    <span style="font-size: 24px; margin-bottom: 5px;">🚀</span>
                    <span id="upload_select_text">点击此块级区域或拖拽多枚 JPG 图片至此开始挂载</span>
                    <span style="font-size: 11px; opacity:0.6; margin-top:5px;">(仅限标准扩展名为 .jpg 的图像)</span>
                </div>
                <input type="file" id="ajax_file_input" multiple accept=".jpg,image/jpeg">
            </div>

            <div style="margin: 15px 0;">
                <label style="display:inline-flex; align-items:center; cursor:pointer; margin-right:25px;">
                    <input type="checkbox" id="ajax_autorename" checked style="accent-color:var(--neon-blue); margin-right:6px;"> 上传时自动递增数字命名 (推荐)
                </label>
                <label style="display:inline-flex; align-items:center; cursor:pointer;">
                    <input type="checkbox" id="ajax_overwrite" style="accent-color:var(--neon-blue); margin-right:6px;"> 覆盖同名纯数字文件 (需源文件为数字.jpg)
                </label>
            </div>

            <button type="button" class="btn-action-green" id="start_upload_btn" style="width:100%; padding:12px; font-size:14px;">⚡ 开始异步极速并发上传</button>

            <div class="progress-container" id="upload_monitor">
                <div class="upload-status-text">
                    <span id="monitor_title">正在接通链路...</span>
                    <span id="monitor_percentage">0%</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="monitor_progress_fill"></div>
                </div>
                <div class="upload-log-box" id="monitor_log"></div>
            </div>
        </div>

        <div class="form-section">
            <h2>⚙️ 控制台综合指令块 </h2>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
     <form method="POST" style="margin: 0;" id="console_cmd_form">
            
            <input type="hidden" name="action_cmd" id="action_cmd" value="">

            <button type="button" id="btn_reindex" class="btn-action-green">
                ⚙️ 修复重排物理序号并同步
            </button>
            
            <button type="button" id="btn_rebuild" class="btn-link btn-action-grey">
                🖼️ 重构全图层缩略图
            </button>
            
            <button type="submit" name="pack_album" class="btn-link" style="background: linear-gradient(135deg, #8b5cf6, #6366f1); box-shadow:0 2px 8px rgba(139,92,246,0.3)">
                📦 一键打包并下载本相册
            </button>
        </form>
     

            </div>
        </div>

        <div class="form-section">
           <h2>🖼️ 全息图元矩阵资产台面 (当前并网数: <?php echo is_array($photoList) || $photoList instanceof Countable ? count($photoList) : (is_numeric($photoList) ? $photoList : count(glob(MOBILE_DIR . '*.jpg') ?: [])); ?> 枚)</h2>
            <!--accent 
            <form method="POST" id="gallery_form">
                <input type="hidden" name="delete_action" value="1">
                <input type="hidden" name="single_delete_file" id="single_delete_file" value="">

                <div class="gallery-toolbar">
                    <input type="checkbox" id="select_all" onclick="toggleSelectAll(this)" style="width:16px; height:16px; cursor:pointer;">
                    <label for="select_all" style="margin:0 0 0 8px; cursor:pointer; font-size:13px; font-weight:500;">全选节点</label>
                    <button type="submit" class="btn-action-red" style="padding: 5px 12px; margin:0 0 0 auto;" onclick="return confirm('确定要深度抹除这些被勾选的图片映像吗？');">🗑️ EXECUTE BATCH NUKE · 批量执行清除</button>
                </div>

                <div class="image-gallery">
                    <?php
                    $files = array_diff(scandir(THUMB_DIR), ['.', '..']);
                    sort($files, SORT_NATURAL);
                    if (empty($files)) echo "<p style='color:var(--color-muted); padding:10px; font-size:12px;'>目前本地冷存储区无有效图片节点。</p>";
                    foreach ($files as $file) {
                        $thumbPath = THUMB_DIR . $file; $mobilePath = MOBILE_DIR . $file;
                        if (is_file($thumbPath) && is_file($mobilePath)) {
                            echo "<div class='gallery-item'>";
                            echo "<div class='image-container'>";
                            echo "<img src='{$thumbPath}' alt='{$file}' onclick='openPreview(\"{$mobilePath}\")'>";
                            echo "<button type='button' class='delete-button' onclick='deleteSingle(\"{$file}\")'>X</button>";
                            echo "<input type='checkbox' name='delete_files[]' value='{$file}' class='select-box'>";
                            echo "</div>";
                            echo "<div class='image-filename' title='{$file}'>{$file}</div>";
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
            </form>
-->            
   <form method="POST" id="gallery_form">
                <input type="hidden" name="delete_action" value="1">
                <input type="hidden" name="single_delete_file" id="single_delete_file" value="">

                <?php
                // 维持你原本最高效、稳定的冷存储区物理文件盘点逻辑
                $files = array_diff(scandir(THUMB_DIR), ['.', '..']);
                sort($files, SORT_NATURAL);
                
                if (!empty($files)): 
                ?>
                    <div class="batch-action-bar">
<label style="display: inline-flex;align-items: center;cursor: pointer;user-select: none;font-size: 14px;padding: 18px 9px 8px 5px;">
                            <input type="checkbox" id="select_all" onclick="toggleSelectAll(this)" style="margin-right: 8px; transform: scale(1.1); cursor:pointer;"> 
                            <span>锁定并连选全部量子图元</span>
                        </label>
<button type="button" class="cyber-btn cyber-btn-danger" id="btn_batch_nuke">
    🗑️ EXECUTE BATCH NUKE · 批量执行清除
</button>
                    </div>

                    <div class="photo-gallery-deck">
                        <?php 
                        $num = 0; // 物理序号计数器
                        foreach ($files as $file) {
                            $thumbPath = THUMB_DIR . $file; 
                            $mobilePath = MOBILE_DIR . $file;
                            
                            if (is_file($thumbPath) && is_file($mobilePath)) {
                                // 动态获取物理图元体积，防止数据丢失
                                $fileSizeBytes = is_file($mobilePath) ? filesize($mobilePath) : 0;
                                $fileSizeKb = round($fileSizeBytes / 1024, 1);
                                ?>
                                <div class="photo-card-node">
                                    <input type="checkbox" name="delete_files[]" value="<?php echo htmlspecialchars($file, ENT_QUOTES, 'UTF-8'); ?>" class="select-box-mask select-box">
                                    
                                    <div class="photo-wrapper" onclick="openPreview('<?php echo htmlspecialchars($mobilePath, ENT_QUOTES, 'UTF-8'); ?>')">
                                        <img src="<?php echo htmlspecialchars($thumbPath, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($file, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    
                                    <div class="photo-meta-info">
                                        <span title="<?php echo htmlspecialchars($file, ENT_QUOTES, 'UTF-8'); ?>">#<?php echo $num; ?> (<?php echo $fileSizeKb; ?>k)</span>
                                        <button type="button" class="trash-node-btn" onclick="deleteSingle('<?php echo htmlspecialchars($file, ENT_QUOTES, 'UTF-8'); ?>')">
                                            抹除
                                        </button>
                                    </div>
                                </div>
                                <?php
                                $num++;
                            }
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 40px 0; font-size: 14px; letter-spacing: 1px;">
                        📡 报告主控室：当前全息网络中未寻获任何活跃的图像单元，请在上方选择注入或触发资产同步总线。
                    </p>
                <?php endif; ?>
            </form>         
        </div>

<div class="form-section">
            <details style="width: 100%;" id="config_details_panel">
                <summary style="display: flex; justify-content: space-between; align-items: center; cursor: pointer; list-style: none; outline: none; user-select: none;">
                    <h2 style="margin: 0;">⚙️  全息三维常数参数变量控制</h2>
<span class="fold-indicator" style="font-size: 13px;font-family: monospace;color: var(--neon-blue);border: 2px solid rgba(0, 242, 254, 0.3);padding: 10px 10px;background: rgba(0, 242, 254, 0.05);">Expand ︾
</span>
                </summary>

                <div style="padding-top: 15px;">
                    <div class="tips-panel" style="background: rgba(59, 130, 246, 0.06); border-left-color: var(--neon-blue); color: #93c5fd; margin-bottom: 15px;">
                        <strong>💡 配置指南：</strong>如需隐藏关于按钮，可将 show 改为 hide；logo图片为空或是改为 appLogoIcon2.png 则自动隐藏，其它默认即可。
                    </div>
                    
                    <style>     
                    .trash-node-btn:hover {
                        background: var(--accent-red);
                        color: #fff;
                        box-shadow: 0 0 8px var(--accent-red);
                    }
                    .trash-node-btn {
                        background: rgba(255, 74, 107, 0.2);
                        border: 1px solid var(--accent-red);
                        color: var(--accent-red);
                        padding: 3px 6px;
                        font-size: 10px;
                        cursor: pointer;
                        font-weight: bold;
                        transition: all 0.2s;
                        clip-path: polygon(3px 0, 100% 0, calc(100% - 3px) 100%, 0 100%);
                    }
                    /* 资产展台网格流 */
                    .photo-gallery-deck {
                        display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 20px; margin-top: 20px;
                    }
                    .photo-card-node {
                        background: rgba(5, 12, 26, 0.6); border: 1px solid rgba(0, 242, 254, 0.25);
                        position: relative; overflow: hidden; transition: all 0.3s;
                        clip-path: polygon(0 8px, 8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%);
                    }
                    .photo-card-node:hover {
                        border-color: var(--neon-blue); box-shadow: 0 0 15px rgba(0, 242, 254, 0.3); transform: translateY(-2px);
                    }
                    .photo-wrapper { width: 100%; height: 115px; position: relative; cursor: pointer; background: #000; }
                    .photo-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
                    .photo-card-node:hover .photo-wrapper img { transform: scale(1.05); }
                    .photo-meta-info {
                        padding: 8px; font-size: 11px; font-family: monospace; color: var(--text-muted);
                        display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.3);
                    }
                    .select-box-mask { position: absolute; top: 8px; left: 8px; z-index: 5; transform: scale(1.2); cursor: pointer; }
                    .batch-action-bar {
                        display: flex;
                        justify-content: space-between;
                        background: rgba(0, 0, 0, 0.3);
                        padding: 4px 20px 13px 17px;
                        margin-bottom: 15px;
                        border-left: 3px solid var(--neon-blue);
                        font-size: 13px;
                        color: var(--text-muted);
                        align-items: center;
                    }
                    .form-group {
                        display: flex;
                        align-items: center;
                        margin-bottom: 16px;
                    }
                    .form-group label {
                        width: 200px;
                        font-size: 13px;
                        color: var(--text-muted);
                        font-weight: 700;
                        padding-right: 15px;
                    }
                    .form-control {
                        flex: 1;
                        background: rgba(0, 0, 0, 0.4);
                        border: 1px solid rgba(0, 242, 254, 0.3);
                        padding: 10px 14px;
                        color: #fff;
                        font-family: monospace;
                        font-size: 13px;
                        transition: all 0.3s;
                    }
                    .form-control:focus {
                        outline: none; border-color: var(--neon-blue); box-shadow: 0 0 10px rgba(0, 242, 254, 0.3);
                    }
                    .delete-button {
                        position: absolute;
                        top: 4px;
                        right: 4px;
                        background: rgba(239, 68, 68, 0.9);
                        color: white;
                        border: none;
                        font-size: 10px;
                        font-weight: bold;
                        cursor: pointer;
                        padding: 2px 6px;
                        border-radius: 4px;
                        z-index: 10;
                        transition: background 0.2s;
                    }
                    /* 隐藏浏览器原生自带的反人类三角小箭头 */
                    summary::-webkit-details-marker { display: none; }
                    summary::marker { display: none; }
                    </style>
                        
                    <form method="POST" action="">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px 20px;">
                            <?php
                            try {
                                $configForForm = readConfig(CONFIG_FILE_PATH, $allowedEditableKeys);
                                foreach ($allowedEditableKeys as $key) {
                                    $value = $configForForm[$key] ?? ''; 
                                    $label = $keyTranslations[$key] ?? $key;
                                    
                                    // 1. 渲染外层表单组容器
                                    echo '<div class="form-group">';
                                    
                                    // 2. 渲染带悬停提示的 Label
                                    echo '<label title="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '">';
                                  //  echo htmlspecialchars($label . " [" . $key . "]", ENT_QUOTES, 'UTF-8');
                                    echo htmlspecialchars($label . " ：", ENT_QUOTES, 'UTF-8');
                                    echo '</label>';
                                    
                                    // 3. 严格保留原有长文本 textarea / 普通文本 input 逻辑
                                    if (strlen($value) > 60 || $key === 'AboutDescription') {
                                        echo "<textarea name='{$key}' rows='3' class='form-control'>" . htmlspecialchars($value) . "</textarea>";
                                    } else {
                                        echo "<input type='text' name='{$key}' value='" . htmlspecialchars($value) . "' class='form-control'>";
                                    }
                                    
                                    echo '</div>';
                                }
                            } catch (Throwable $e) { 
                                echo "<div class='message error' style='grid-column: span 2;'>主控加密表单离线：" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>"; 
                            }
                            ?>
                        </div>
                        
                        <div style="margin-top: 15px; text-align: right; border-top: 1px solid rgba(0, 242, 254, 0.15); padding-top: 20px;">
                            <button type="submit" name="save_manual_config" class="cyber-btn" style="min-width: 240px; width: auto; cursor: pointer;">
                                💾 UPDATE CONSTANTS · 固化写入全局常数
                            </button>
                        </div>
                    </form>
                </div>
            </details>
        </div>

 
 <style>
 .section-title {
    font-size: 16px;
    font-weight: 800;
    color: var(--neon-blue);
    margin-top: 0;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    letter-spacing: 1px;
    border-bottom: 1px solid rgba(0, 242, 254, 0.15);
    padding-bottom: 10px;
}
 .panel-section {
    background: rgba(13, 25, 48, 0.7);
    border: 1px solid rgba(0, 242, 254, 0.25);
    padding: 25px;
    clip-path: polygon(0 12px, 12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%);
    box-shadow: inset 0 0 20px rgba(0, 242, 254, 0.1);
}
   /* 🚀 绿光全息切角按钮样式统合（前台最满意配色） */
        .cyber-btn {
            padding: 12px 24px; font-size: 13px; font-weight: 800; letter-spacing: 2px; color: #fff;
            clip-path: polygon(8px 0%, 100% 0%, calc(100% - 8px) 100%, 0% 100%);
            border: 1px solid rgba(5, 243, 160, 0.5) !important;
            border-radius: 0px !important;
            background: linear-gradient(135deg, rgba(5, 243, 160, 0.25) 0%, rgba(0, 120, 80, 0.4) 100%);
            cursor: pointer; outline: none; display: inline-flex; align-items: center; justify-content: center;
            box-shadow: 0 0 15px rgba(5, 243, 160, 0.15); text-shadow: 0 0 8px rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(5px); transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);    text-decoration: none;
        }

        .cyber-btn:hover {
            clip-path: polygon(0% 0%, calc(100% - 8px) 0%, 100% 100%, 8px 100%);
            background: linear-gradient(135deg, rgba(5, 243, 160, 0.45) 0%, rgba(5, 243, 160, 0.15) 100%); 
            box-shadow: 0 0 25px rgba(5, 243, 160, 0.5); text-shadow: 0 0 12px #fff;
            transform: scale(1.02);
        }

        .cyber-btn:active { transform: scale(0.98); background: rgba(5, 243, 160, 0.55); }

        /* 红色危险警告按钮：同步切角，改用红光配色 */
        .cyber-btn-danger {
            background: linear-gradient(135deg, rgba(255, 74, 107, 0.25) 0%, rgba(150, 20, 50, 0.4) 100%);
            border: 1px solid rgba(255, 74, 107, 0.5) !important;
            box-shadow: 0 0 15px rgba(255, 74, 107, 0.15);
        }

        .cyber-btn-danger:hover {
            background: linear-gradient(135deg, rgba(255, 74, 107, 0.45) 0%, rgba(255, 74, 107, 0.15) 100%);
            box-shadow: 0 0 25px rgba(255, 74, 107, 0.5);
        }

        .cyber-btn-danger:active { background: rgba(255, 74, 107, 0.55); }

        .btn-block { width: 100%; box-sizing: border-box; }

 
 </style>   
        
<?php
// ==========================================
// 1. 后端 PHP 核心自毁阻断总线（必须放在页面最顶部，或者确保在 HTML 输出之前执行）
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuke_system_verified'])) {
    // 强制清空之前可能存在的任何 echo 或 HTML 缓冲区，保证输出纯净
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    try {
        // 本地逆向解析物理路径，提取当前相册的纯数字目录名（如 5250）
        $currentAlbumDir = dirname(__DIR__); 
        $albumClusterId = basename($currentAlbumDir); 

        if (!empty($albumClusterId) && preg_match('/^\d+$/', $albumClusterId)) {
            $targetDir = $currentAlbumDir; 

            if (is_dir($targetDir)) {
                // 调度你原本写好的递归删除函数（请确认你的函数名叫 removeDirRecursive 还是 deleteDirectoryRecursive）
                if (removeDirRecursive($targetDir)) {
                    // 清除登录会话
                    session_destroy();
                    
                    echo json_encode([
                        'status' => 'success',
                        'message' => "整个物理网格节点（usr/{$albumClusterId}）已安全归零释放！"
                    ]);
                    exit();
                } else {
                    throw new Exception("文件受系统内核保护或锁死，部分扇区未能擦除。");
                }
            } else {
                throw new Exception("目标物理阵列未挂载或已被提前降维。");
            }
        } else {
            throw new Exception("越权防御总线拦截：无法锚定合法的数字集群标识。");
        }
    } catch (Throwable $e) { 
        echo json_encode([
            'status' => 'error',
            'message' => "自毁中断: " . $e->getMessage()
        ]);
        exit();
    }
}
?>

<div class="panel-section full-width" style="border-color: rgba(255, 74, 107, 0.4); background: rgba(30, 10, 20, 0.5); padding: 20px; border-radius: 4px;">
    <h2 class="section-title" style="color: var(--accent-red); border-color: rgba(255, 74, 107, 0.3); margin-top: 0;">
        <span>☣️ SYSTEM AUTO-DESTRUCT · 终极自毁安全断路器</span>
    </h2>
    <p style="font-size: 12px; color: #ff9ebb; line-height: 1.6; margin-bottom: 20px;">
        <strong>【绝对不可逆协议】</strong> 触发此核心断路器，系统将立刻释放物理擦除脉冲：本地服务器将通过后端静默截取当前节点，无痕抹除当前相册内的全部图像资产、核心 JS 配置文件以及当前控制台自身。一旦执行，无法恢复。
    </p>
    
    <form method="POST" action="" id="secure_nuke_form" onsubmit="return false;">
        <button type="button" class="cyber-btn cyber-btn-danger btn-block" style="padding: 18px; width: 100%; cursor: pointer; font-weight: bold;" onclick="triggerSecureNukeSystem()">
            ⚠️ ACTIVATE PROTOCOL · 立即下达并激活最高级别全盘物理自毁协议
        </button>
    </form>
</div>

<script>
function triggerSecureNukeSystem() {
    // 确认 SweetAlert2 是否成功加载
    if (typeof Swal === 'undefined') {
        alert('错误：SweetAlert2 组件尚未在当前主控台并网，请检查 CDN 挂载状态。');
        return;
    }

    // 第一层：高能自毁协议核准
    Swal.fire({
        title: '☢ 激活极度自毁协议吗？',
        text: "警告！系统将通知后端直接定位当前并网的相册节点，执行全盘物理抹除！操作在物理层面绝对不可逆！",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff3860',
        cancelButtonColor: '#6b7cac',
        confirmButtonText: '极其确定，全面摧毁',
        cancelButtonText: '终止协议',
        background: '#0d1117',
        color: '#fff'
    }).then((result) => {
        if (result.isConfirmed) {
            
            // 第二层：最后终极防御认证
            Swal.fire({
                title: '最后终极防御认证',
                text: "后端自毁总线已就绪，即将向本地磁盘发射擦除脉冲。确认下达物理抹除指令？",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#ff3860',
                confirmButtonText: '执行灭除',
                cancelButtonText: '紧急中止',
                background: '#0d1117',
                color: '#fff'
            }).then((finalVerify) => {
                if (finalVerify.isConfirmed) {
                    
                    // 弹出高能静默等待等待动画
                    Swal.fire({
                        title: '⚡ 正在释放物理擦除脉冲...',
                        text: '正在地毯式物理抹除当前扇区资产，请勿切断电源或关闭总线。',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); },
                        background: '#0d1117',
                        color: '#fff'
                    });

                    // 构建暗箱数据，发射给当前页面自身 (window.location.pathname)
                    const formData = new FormData();
                    formData.append('nuke_system_verified', 'TRUE_ALPHA_CORE');

                    fetch(window.location.pathname, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        // 如果后端直接报错或者断开（因为文件正在被unlink），直接视为毁灭成功
                        if (!response.ok) throw new Error('ERR_CONNECTION_RESET');
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                title: 'DESTRUCTION COMPLETE',
                                text: data.message,
                                icon: 'success',
                                background: '#0d1117',
                                color: '#00ff66',
                                confirmButtonColor: '#00ff66'
                            }).then(() => {
                                window.location.href = 'https://pic.ximi.me/'; 
                            });
                        } else {
                            Swal.fire({ title: '执行中断', text: data.message, icon: 'error', background: '#0d1117', color: '#fff' });
                        }
                    })
                    .catch(error => {
                        // 💡 核心保护：当 admin.php 自身被删除时，连接会瞬间掐断触发 catch，这证明已经物理湮灭成功
                        Swal.fire({
                            title: 'DESTRUCTION COMPLETE',
                            text: '当前节点控制台已随数据链一同全物理湮灭，并网安全断开。',
                            icon: 'success',
                            background: '#0d1117',
                            color: '#00ff66',
                            confirmButtonColor: '#00ff66'
                        }).then(() => {
                            window.location.href = 'https://pic.ximi.me/';
                        });
                    });
                }
            });
        }
    });
}
</script>
 

  
    </div>

    <div id="imagePreviewModal" class="preview-modal" onclick="closePreview()">
        <span class="preview-close">&times;</span>
        <img class="preview-content" id="modalTargetImage" alt="映像图层" onclick="event.stopPropagation();">
    </div>

<script>
    const fileInput = document.getElementById('ajax_file_input');
    const startBtn = document.getElementById('start_upload_btn');
    const monitor = document.getElementById('upload_monitor');
    const progressFill = document.getElementById('monitor_progress_fill');
    const percentText = document.getElementById('monitor_percentage');
    const titleText = document.getElementById('monitor_title');
    const logBox = document.getElementById('monitor_log');
    const selectText = document.getElementById('upload_select_text');

    fileInput.addEventListener('change', () => {
        if(fileInput.files.length > 0) {
            selectText.innerText = `已成功挂载 ${fileInput.files.length} 个文件就绪，请启动控制塔总线。`;
            selectText.style.color = "var(--neon-green)";
        }
    });

    startBtn.addEventListener('click', async () => {
        const files = fileInput.files;
        if (files.length === 0) {
            Swal.fire({ icon: 'warning', title: '缺少数据源', text: '请先选定或拖拽需要解构的图片。', background: '#13192e', color: '#fff', confirmButtonColor: '#00f2fe' });
            return;
        }
        if (files.length > 50) {
            Swal.fire({ icon: 'error', title: '超载拒绝', text: '检测到单次超出了50张最高负载红线，请拆分或分批次压入！', background: '#13192e', color: '#fff', confirmButtonColor: '#ff3860' });
            return;
        }

        monitor.style.display = 'block';
        logBox.innerHTML = '';
        startBtn.disabled = true;
        startBtn.innerText = '传输中...总线锁定';

        let successCount = 0;
        let failCount = 0;
        const total = files.length;

        addLog(`⚡ 正在唤醒多线程管道... 开启极速并发吞吐模式 (${total} 文件)。`, 'info');

        const uploadPromises = Array.from(files).map(async (currentFile, index) => {
            if(currentFile.size > 5 * 1024 * 1024) {
                addLog(`❌ 节点 [${currentFile.name}] 体积过大(＞5MB)，熔断拦截。`, 'error');
                failCount++;
                updateOverallProgress(successCount + failCount, total);
                return;
            }

            const formData = new FormData();
            formData.append('file', currentFile);
            formData.append('autorename', document.getElementById('ajax_autorename').checked);
            formData.append('overwrite', document.getElementById('ajax_overwrite').checked);

            try {
                const response = await fetch('admin.php?action=ajax_upload', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const resData = await response.json();
                
                if (resData.status === 'success') {
                    addLog(`✔ ${resData.message}`, 'success');
                    successCount++;
                } else {
                    addLog(`❌ 传输破裂 [${currentFile.name}]: ${resData.message}`, 'error');
                    failCount++;
                }
            } catch (err) {
                addLog(`❌ 网络中断 [${currentFile.name}]: ${err.message}`, 'error');
                failCount++;
            }
            updateOverallProgress(successCount + failCount, total);
        });

        await Promise.all(uploadPromises);

        titleText.innerText = `正在等待磁盘流完全固化归档...`;
        addLog(`⏳ 数据流已传输完。正在执行 1.5s 物理层落盘缓冲防御，防止缓存漏图...`, 'info');
        
        await new Promise(resolve => setTimeout(resolve, 1500));

        titleText.innerText = `正在重算全谱矩阵序列...`;
        addLog(`🔄 磁盘缓冲通过！正在向服务器请求重构自然数序列排序...`, 'info');

        try {
            const syncResponse = await fetch('admin.php?action=ajax_sort_sync');
            if (!syncResponse.ok) throw new Error("服务端重构序列命令离线。");
            const syncData = await syncResponse.json();
            
            if (syncData.status === 'success') {
                addLog(`🎉 系统闭环完成：${syncData.message}`, 'success');
                titleText.innerText = `传输重组圆满成功！`;
            } else {
                addLog(`⚠️ 排序调用有小冲突: ${syncData.message}`, 'error');
            }
        } catch (syncErr) {
            addLog(`❌ 最终序列校准阶段发生网络故障: ${syncErr.message}`, 'error');
        }

        addLog(`⚙ 完成总量：成功 ${successCount}，失败 ${failCount}。数据已闭环更新。`, 'info');
        
        setTimeout(() => {
            window.location.reload();
        }, 1800);
    });

    function updateOverallProgress(current, total) {
        const pct = Math.round((current / total) * 100);
        progressFill.style.width = pct + '%';
        percentText.innerText = pct + '%';
        titleText.innerText = `数据同步进度: ${current} / ${total}`;
    }

    function addLog(text, type = '') {
        const div = document.createElement('div');
        div.className = 'log-line';
        if (type === 'success') div.classList.add('log-success');
        if (type === 'error') div.classList.add('log-error');
        div.innerText = `[${new Date().toLocaleTimeString()}] ${text}`;
        logBox.appendChild(div);
        logBox.scrollTop = logBox.scrollHeight;
    }

    function toggleSelectAll(master) {
        document.querySelectorAll('.select-box').forEach(cb => cb.checked = master.checked);
    }

    function deleteSingle(filename) {
        Swal.fire({
            title: '⚠️ 物理图元擦除确权',
            text: "检测到准备切断节点 [ " + filename + " ]，该操作将直接同步深度抹除本地冷存储映像，是否下达物理摧毁指令？",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3860',
            cancelButtonColor: '#30363d',
            confirmButtonText: '确定 · 物理抹除',
            cancelButtonText: '取消挂载',
            background: '#0d1117',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('single_delete_file').value = filename;
                document.getElementById('gallery_form').submit();
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        const form = document.getElementById('console_cmd_form');
        const cmdInput = document.getElementById('action_cmd');

        // 批量删除按钮处理
        document.getElementById('btn_batch_nuke')?.addEventListener('click', function () {
            Swal.fire({
                title: '🚨 批量物理擦除确权',
                text: '警告：此举将降维打击摧毁全部勾选的图像资产，该操作无法撤销。确认执行物理抹除吗？',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff3860',
                cancelButtonColor: '#30363d',
                confirmButtonText: '确定 · 执行清除',
                cancelButtonText: '取消挂载',
                background: '#0d1117',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    const galleryForm = document.getElementById('gallery_form');
                    if (galleryForm) galleryForm.submit();
                }
            });
        });

        document.getElementById('btn_reindex').addEventListener('click', function () {
            Swal.fire({
                title: '⚙️ 序列重构确权',
                text: '确定要对物理存储区的全部图像文件，重新进行自然数重排列及冷重命名吗？',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#00f2fe',
                cancelButtonColor: '#30363d',
                confirmButtonText: '下达指令',
                cancelButtonText: '放弃调整',
                background: '#0d1117',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    cmdInput.name = "update_config";
                    cmdInput.value = "1";
                    form.submit();
                }
            });
        });

        document.getElementById('btn_rebuild').addEventListener('click', function () {
            Swal.fire({
                title: '🖼️ 缓冲映像重构',
                text: '该操作将强制物理销毁现有的全部缩略图，并一键重新构建全新的图层。确认执行？',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff3860',
                cancelButtonColor: '#30363d',
                confirmButtonText: '开始重构',
                cancelButtonText: '放弃执行',
                background: '#0d1117',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    cmdInput.name = "regenerate_thumbs";
                    cmdInput.value = "1";
                    form.submit();
                }
            });
        });

        document.getElementById('start_import_btn').addEventListener('click', function () {
            Swal.fire({
                title: '🔄 确定执行同步？',
                text: '系统将从 ini.php 指定的路径扫描并导入所有 .jpg 图像，这会执行重排及缩略图修复。',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#00f2fe',
                cancelButtonColor: '#30363d',
                confirmButtonText: '执行注入',
                background: '#0d1117',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    const monitor = document.getElementById('import_monitor');
                    const logBox = document.getElementById('import_monitor_log');
                    monitor.style.display = 'block';
                    logBox.innerHTML = '';
                    
                    const time = new Date().toLocaleTimeString();
                    const line = document.createElement('div');
                    line.className = 'log-line';
                    line.innerText = `[${time}] ⚡ 正在从 ini.php 资产总线读取数据流...`;
                    logBox.appendChild(line);

                    fetch('?action=ajax_import_ini', { method: 'POST' })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                data.log.forEach(msg => {
                                    const l = document.createElement('div');
                                    l.className = 'log-line log-success';
                                    l.innerText = `[${new Date().toLocaleTimeString()}] ✔ ${msg}`;
                                    logBox.appendChild(l);
                                });
                                const endLine = document.createElement('div');
                                endLine.className = 'log-line';
                                endLine.innerText = `[${new Date().toLocaleTimeString()}] ⏳ 数据同步完成。正在执行物理层重构与排序索引...`;
                                logBox.appendChild(endLine);
                                Swal.fire({ icon: 'success', title: '全链路同步成功', text: data.message, background: '#0d1117', color: '#fff' });
                            } else {
                                const errLine = document.createElement('div');
                                errLine.className = 'log-line log-error';
                                errLine.innerText = `[${new Date().toLocaleTimeString()}] ❌ 任务中断: ${data.message}`;
                                logBox.appendChild(errLine);
                            }
                        })
                        .catch(err => {
                            const errLine = document.createElement('div');
                            errLine.className = 'log-line log-error';
                            errLine.innerText = `[${new Date().toLocaleTimeString()}] ⚠️ 系统异常: ${err.message}`;
                            logBox.appendChild(errLine);
                        });
                }
            });
        });
    });

    function openPreview(src) {
        const modal = document.getElementById('imagePreviewModal');
        const modalImg = document.getElementById('modalTargetImage');
        modalImg.src = src; modal.style.display = "flex";
        setTimeout(() => { modal.classList.add('show'); }, 10);
    }

    function closePreview() {
        const modal = document.getElementById('imagePreviewModal');
        modal.classList.remove('show');
        setTimeout(() => { modal.style.display = "none"; }, 250);
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closePreview(); });
</script>

</body>
</html>