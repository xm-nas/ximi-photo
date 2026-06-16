<?php
// 后台上传与同步更新缩略图
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//include("./header.php");
include("./ini.php"); // 确保这个路径正确且 ini.php 存在

session_start();

//===================================================
// 获取动态登录标识符
$logged_in_key = $config['login_admin'];

// 检查用户是否已登录
if (!(isset($_SESSION['logged_in_admin']) && $_SESSION['logged_in_admin'] === true) && (empty($_SESSION[$logged_in_key]) || $_SESSION[$logged_in_key] !== true)) {
    // 如果不是总管理员登录状态，并且用户未登录，则重定向到登录页面
    header('Location: login.php');
    exit();
}

// 设置管理员登录状态
$_SESSION['admin_logged_in'] = true;

// 设置上传目录
$uploadDir = $config['tu_1'];
$thumbnailDir = $config['min']; // 小缩略图存储路径
$largeThumbnailDir = $config['max'];// 大缩略图存储路径

// 确保目录存在
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
if (!is_dir($thumbnailDir)) {
    mkdir($thumbnailDir, 0777, true);
}
if (!is_dir($largeThumbnailDir)) {
    mkdir($largeThumbnailDir, 0777, true);
}

// 允许上传的文件扩展名
$allowedUploadExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];

// 可以通过 GD 转换为 WebP 的文件扩展名
$gdConvertibleExtensions = ['jpg', 'jpeg', 'png', 'gif'];


// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 检查文件上传
    if (isset($_FILES['file'])) {
        $renameFile = isset($_POST['rename_file']) && $_POST['rename_file'] == 'yes';
        $convertToWebp = isset($_POST['convert_to_webp']) && $_POST['convert_to_webp'] == 'yes';
        // 默认质量调整为 100
        $webpQuality = isset($_POST['webp_quality']) ? (int)$_POST['webp_quality'] : 100;

        handleFileUpload($_FILES['file'], $renameFile, $convertToWebp, $webpQuality);
        // Dropzone 会处理响应，所以这里不需要重定向
    } elseif (isset($_POST['generate_thumbnails'])) {
        // 更新小缩略图
        // 重新生成时，暂时不进行 WebP 转换，保持原格式生成缩略图，除非你特别希望重新生成也强制 WebP
        regenerateAllThumbnails($thumbnailDir, 'min_', 500);
        $_SESSION['uploadMessage'] = "所有min_缩略图已重新生成。";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['generate_large_thumbnails'])) {
        // 更新大缩略图
        regenerateAllThumbnails($largeThumbnailDir, 'max_', 1920);
        $_SESSION['uploadMessage'] = "所有max_缩略图已重新生成。";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

/**
 * 处理上传的文件，包括重命名、保存和生成缩略图（可选 WebP 转换）。
 * @param array $file 上传文件的 $_FILES 条目。
 * @param bool $rename 是否将文件重命名为时间戳。
 * @param bool $convertToWebp 是否将图像转换为 WebP 格式。
 * @param int $webpQuality WebP 压缩质量 (0-100)。
 */
function handleFileUpload($file, $rename = false, $convertToWebp = false, $webpQuality = 100) {
    global $uploadDir, $thumbnailDir, $largeThumbnailDir, $allowedUploadExtensions, $gdConvertibleExtensions;

    $originalFileName = pathinfo($file['name'], PATHINFO_FILENAME);
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowedUploadExtensions)) {
        $_SESSION['uploadMessage'] = "不允许的文件类型：$file_ext";
        // 为 Dropzone 发送 JSON 响应
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $_SESSION['uploadMessage']]);
        exit();
    }

    $newFileName = '';
    $targetExtension = $file_ext; // 最终文件的扩展名

    $isSourceWebp = ($file_ext === 'webp'); // 判断源文件是否已经是 WebP

    // 确定最终的目标扩展名
    if ($convertToWebp && in_array($file_ext, $gdConvertibleExtensions)) {
        $targetExtension = 'webp'; // 转换 JPG/PNG/GIF 到 WebP
    } elseif ($isSourceWebp) {
        $targetExtension = 'webp'; // 源文件已经是 WebP，目标也是 WebP
    }

    if ($rename) {
        $newFileName = time() . '_' . uniqid() . '.' . $targetExtension; // 添加 uniqid 提高唯一性
    } else {
        $newFileName = $originalFileName . '.' . $targetExtension;
        $i = 1;
        while (file_exists($uploadDir . $newFileName)) {
            $newFileName = $originalFileName . '_' . $i . '.' . $targetExtension;
            $i++;
        }
    }

    $tempFilePath = $file['tmp_name'];
    $finalFilePath = $uploadDir . $newFileName;
    $originalFileSize = filesize($tempFilePath); // 原始临时文件大小

    $conversionSuccess = false;
    $convertedFileSize = 0;
    $originalWidth = 0;
    $originalHeight = 0;

    // 获取原始图片信息
    $originalImageInfo = getimagesize($tempFilePath);
    if ($originalImageInfo) {
        $originalWidth = $originalImageInfo[0];
        $originalHeight = $originalImageInfo[1];
    }

    if ($isSourceWebp) {
        // 如果源文件已经是 WebP，直接移动，不执行 GD 转换
        if (move_uploaded_file($tempFilePath, $finalFilePath)) {
            $conversionSuccess = true;
            $convertedFileSize = filesize($finalFilePath);
            $_SESSION['uploadMessage'] = "文件上传成功 (已是 WebP 格式)：{$file['name']} (保存为 {$newFileName})。";
        } else {
            $_SESSION['uploadMessage'] = "文件移动失败 (已是 WebP 格式)：{$file['name']}。";
        }
    } elseif ($convertToWebp && in_array($file_ext, $gdConvertibleExtensions)) {
        // 如果需要转换且格式支持 GD 转换
        if (createWebpFromAny($tempFilePath, $finalFilePath, $webpQuality)) {
            $conversionSuccess = true;
            $convertedFileSize = filesize($finalFilePath);
            $_SESSION['uploadMessage'] = "文件上传并转换为 WebP 成功：{$file['name']} (保存为 {$newFileName})。";
        } else {
            $_SESSION['uploadMessage'] = "文件上传成功但 WebP 转换失败：{$file['name']}。";
            // 转换失败时，回退：尝试移动原始文件
            if (move_uploaded_file($tempFilePath, $finalFilePath)) {
                $conversionSuccess = true; // 仍然算作成功移动
                $convertedFileSize = filesize($finalFilePath); // 更新大小为原始文件大小
                $_SESSION['uploadMessage'] .= " 已保存原格式文件。";
            } else {
                $_SESSION['uploadMessage'] = "文件上传失败，请重试。";
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $_SESSION['uploadMessage']]);
                exit();
            }
        }
    } else {
        // 不转换 WebP，或者格式不支持 GD 转换（如 SVG, ICO）
        if (move_uploaded_file($tempFilePath, $finalFilePath)) {
            $conversionSuccess = true;
            $convertedFileSize = filesize($finalFilePath);
            $_SESSION['uploadMessage'] = "文件上传成功：{$file['name']} (保存为 {$newFileName})。";
        } else {
            $_SESSION['uploadMessage'] = "文件上传失败，请重试。";
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $_SESSION['uploadMessage']]);
            exit();
        }
    }

    if ($conversionSuccess) {
        // 根据最终文件路径生成缩略图
        $thumbnail_new_name = pathinfo($newFileName, PATHINFO_FILENAME) . '.' . $targetExtension;

        $thumbnail_path = $thumbnailDir . 'min_' . $thumbnail_new_name;
        $large_thumbnail_path = $largeThumbnailDir . 'max_' . $thumbnail_new_name;

        // 传递目标扩展名和质量到 createThumbnail
        createThumbnail($finalFilePath, $thumbnail_path, 500, $targetExtension, $webpQuality); // 小缩略图
        createThumbnail($finalFilePath, $large_thumbnail_path, 1920, $targetExtension, $webpQuality); // 大缩略图

        // 准备 Dropzone 响应
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $_SESSION['uploadMessage'],
            'original_file_name' => $file['name'],
            'new_file_name' => $newFileName,
            'original_size_mb' => round($originalFileSize / (1024 * 1024), 2),
            'converted_size_mb' => round($convertedFileSize / (1024 * 1024), 2),
            'original_dimensions' => $originalWidth . 'x' . $originalHeight,
            'target_format_is_webp' => ($targetExtension === 'webp'), // 标记最终格式是否是 WebP
            'is_source_webp' => $isSourceWebp // 标记源文件是否是 WebP
        ]);
        exit();
    }
}

/**
 * 从任何 GD 支持的源图像创建 WebP 图像。
 * @param string $sourcePath 源图像路径。
 * @param string $destinationPath 保存 WebP 图像的路径。
 * @param int $quality WebP 质量 (0-100)。
 * @return bool 成功返回 true，失败返回 false。
 */
function createWebpFromAny($sourcePath, $destinationPath, $quality = 100) {
    if (!extension_loaded('gd')) {
        error_log("GD 扩展未加载。无法转换为 WebP。");
        return false;
    }
    if (!function_exists('imagewebp')) {
        error_log("GD 扩展不支持 WebP。无法转换为 WebP。");
        return false;
    }

    $imageInfo = getimagesize($sourcePath);
    if ($imageInfo === false) {
        error_log("无法获取图像信息：" . $sourcePath);
        return false;
    }

    $mime = $imageInfo['mime'];
    $sourceImage = null;

    switch ($mime) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            if ($sourceImage) {
                // 为 PNG 保留透明度
                imagepalettetotruecolor($sourceImage);
                imagealphablending($sourceImage, false);
                imagesavealpha($sourceImage, true);
            }
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            if ($sourceImage) {
                // 为 GIF 保留透明度
                imagepalettetotruecolor($sourceImage);
                imagealphablending($sourceImage, false);
                imagesavealpha($sourceImage, true);
            }
            break;
        case 'image/webp':
            // 已经是 WebP，直接从现有文件创建
            $sourceImage = imagecreatefromwebp($sourcePath);
            if ($sourceImage) {
                // WebP 也支持透明度，与 PNG 类似处理
                imagealphablending($sourceImage, false);
                imagesavealpha($sourceImage, true);
            }
            break;
        default:
            error_log("不支持的 WebP 转换图像格式：" . $mime);
            return false;
    }

    if ($sourceImage === null) {
        error_log("无法从 " . $sourcePath . " 创建图像资源进行 WebP 转换。");
        return false;
    }

    $success = imagewebp($sourceImage, $destinationPath, $quality);
    imagedestroy($sourceImage);
    return $success;
}


// 函数：生成缩略图
function createThumbnail($sourcePath, $thumbnailPath, $maxWidth, $targetExtension = null, $webpQuality = 100) {
    // 如果缩略图已存在，并且目标扩展名与已存在文件相同，则跳过生成
    // 注意：如果需要强制重新生成（例如改变了质量或尺寸），请在调用前手动删除 $thumbnailPath
    if (file_exists($thumbnailPath) && (!is_null($targetExtension) && strtolower(pathinfo($thumbnailPath, PATHINFO_EXTENSION)) == strtolower($targetExtension))) {
        return true; // 缩略图已存在，返回成功
    }

    // 确保缩略图目录存在
    $thumbnailDir = dirname($thumbnailPath);
    if (!is_dir($thumbnailDir)) {
        mkdir($thumbnailDir, 0777, true);
    }

    // 检查文件是否存在
    if (!file_exists($sourcePath)) {
        error_log("Source file does not exist for thumbnail creation: " . $sourcePath);
        return false;
    }

    $imageInfo = getimagesize($sourcePath);
    if ($imageInfo === false) {
        error_log("无法获取图像信息进行缩略图创建：" . $sourcePath);
        return false;
    }
    list($width, $height, $type) = $imageInfo;

    $sourceFileExt = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    $isSvgOrIco = ($sourceFileExt === 'svg' || $sourceFileExt === 'ico');

    // 对于 SVG/ICO 或者原始宽度小于等于目标宽度的图片：
    // 如果目标扩展名与源文件扩展名相同，并且不需要转换为 WebP，直接复制。
    // 如果是 SVG/ICO 并且目标是 WebP，则 GD 无法转换，也直接复制原文件。
    if (($width <= $maxWidth || $isSvgOrIco) && ($targetExtension == $sourceFileExt || ($targetExtension === 'webp' && $isSvgOrIco))) {
        if (copy($sourcePath, $thumbnailPath)) {
            error_log("因尺寸小或类型特殊直接复制图像作为缩略图: " . $sourcePath . " 到 " . $thumbnailPath);
            return true;
        } else {
             error_log("无法复制图像作为缩略图: " . $sourcePath . " 到 " . $thumbnailPath);
             return false;
        }
    }


    $ratio = $maxWidth / $width;
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);

    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

    // 根据源图像类型创建资源
    $sourceImage = null;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            // 为 PNG 保留透明度
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcePath);
            // 为 GIF 保留透明度
            if ($sourceImage) {
                $transparentindex = imagecolortransparent($sourceImage);
                if ($transparentindex >= 0) { // 检查是否有透明色
                    $transparentcolor = imagecolorsforindex($sourceImage, $transparentindex);
                    $newtransparentcolor = imagecolorallocate($thumbnail, $transparentcolor['red'], $transparentcolor['green'], $transparentcolor['blue']);
                    imagefill($thumbnail, 0, 0, $newtransparentcolor);
                    imagecolortransparent($thumbnail, $newtransparentcolor);
                }
            }
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($sourcePath);
            // WebP 也支持透明度，与 PNG 类似处理
            if ($sourceImage) {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
            }
            break;
        default:
            error_log("不支持的图像类型进行缩略图生成：" . $sourcePath);
            return false;
    }

    if (!$sourceImage) {
        error_log("无法从 " . $sourcePath . " 创建源图像资源用于缩略图。");
        return false;
    }

    imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // 根据 targetExtension 决定输出格式
    $outputType = $targetExtension ? $targetExtension : strtolower(pathinfo($thumbnailPath, PATHINFO_EXTENSION));

    $success = false;
    switch ($outputType) {
        case 'jpeg':
        case 'jpg':
            $success = imagejpeg($thumbnail, $thumbnailPath, 90); // JPEG 缩略图默认质量 90
            break;
        case 'png':
            $success = imagepng($thumbnail, $thumbnailPath);
            break;
        case 'gif':
            $success = imagegif($thumbnail, $thumbnailPath);
            break;
        case 'webp':
            $success = imagewebp($thumbnail, $thumbnailPath, $webpQuality);
            break;
        default:
            error_log("不支持的缩略图输出格式：" . $outputType);
            $success = false;
            break;
    }

// 修改前：imagedestroy($thumbnail); -> 会报错
// 修改后：
if (PHP_VERSION_ID < 80500) {
    if (isset($thumbnail)) @imagedestroy($thumbnail);
    if (isset($image_resource)) @imagedestroy($image_resource);
}

// 至于你代码中的 #imagedestroy($sourceImage); 
// 保持注释状态即可，没有任何影响。
    return $success;
}

/**
 * 从原始图像重新生成指定目录中的所有缩略图。
 * @param string $thumbnailDir 缩略图存储目录。
 * @param string $prefix 缩略图文件名的前缀（例如，'min_'，'max_'）。
 * @param int $maxWidth 生成缩略图的最大宽度。
 */
function regenerateAllThumbnails($thumbnailDir, $prefix, $maxWidth) {
    global $uploadDir, $allowedUploadExtensions, $gdConvertibleExtensions; // 包含所有允许上传的扩展名

    $files = scandir($uploadDir);
    if ($files === false) {
        error_log("无法读取目录：" . $uploadDir);
        return;
    }

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $filePath = $uploadDir . $file;
        $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // 仅处理允许上传且存在的文件
        if (in_array($file_ext, $allowedUploadExtensions) && is_file($filePath)) {
            $targetExtension = $file_ext; // 重新生成时，缩略图通常保持与源文件相同的格式

            // 如果你想让重新生成时也强制转换为 WebP，可以取消注释并修改下面这部分：
            /*
            if (in_array($file_ext, $gdConvertibleExtensions)) {
                $targetExtension = 'webp';
            } elseif ($file_ext === 'webp') {
                $targetExtension = 'webp'; // 已是 WebP
            } else {
                // 对于 SVG, ICO 等无法 GD 转换的，保持原样
                $targetExtension = $file_ext;
            }
            */

            $thumbnailFileName = $prefix . pathinfo($file, PATHINFO_FILENAME) . '.' . $targetExtension;
            $thumbnailPath = $thumbnailDir . $thumbnailFileName;

            // 强制重新创建缩略图，所以先删除已存在的
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }

            // 调用 createThumbnail，传递确定的目标扩展名和质量 (重新生成时 WebP 质量默认 80)
            createThumbnail($filePath, $thumbnailPath, $maxWidth, $targetExtension, 80);
        }
    }
}

?>


<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>欢迎访问希米的图册</title>
    <link rel="stylesheet" href="https://fonts-api.wp.com/css?family=Noto+Serif+SC:900%7CNoto+Serif+SC:r,i,b,bi&amp;subset=latin,latin-ext,latin,latin-ext">
    <link rel="stylesheet" href="/admin/css/themes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>

    <style>
        .man {
            width: 100%;
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
            max-width: 1400px; /* Adjust max-width for better layout */
            text-align: center;
            margin: 36px auto;
            padding: 0 15px; /* Add some padding on smaller screens */
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .dropzone {
            
            width: 100%;
            border: 2px dashed #ccc;
            border-radius: 6px;
            background-color: #f9f9f900;
            color: #777;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s ease;
            margin-bottom: 20px;
            margin: 36px auto;
        }
        .dropzone:hover {
            border-color: #26292bb5;
        }
        button[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin: 10px 5px;
        }
        button[type="submit"]:hover {
            background-color: #0056b3;
        }
        #upload-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #4CAF50;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 1000;
            opacity: 0.9;
            white-space: nowrap; /* Prevent message from wrapping too much */
        }
        .checkbox-container, .options-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            flex-wrap: wrap; /* Allow wrapping on small screens */
            gap: 10px; /* Space between items */
        }
        .checkbox-container input[type="checkbox"],
        .options-container input[type="checkbox"] {
            margin-right: 5px;
        }
        .checkbox-container label,
        .options-container label {
            color: #555;
        }
        .slider-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            color: #555;
            width: 100%;
            max-width: 400px; /* Control slider width */
            margin-left: auto;
            margin-right: auto;
        }
        .slider-container input[type="range"] {
            flex-grow: 1;
            -webkit-appearance: none;
            height: 8px;
            border-radius: 5px;
            background: #d3d3d3;
            outline: none;
            opacity: 0.7;
            transition: opacity .2s;
        }
        .slider-container input[type="range"]:hover {
            opacity: 1;
        }
        .slider-container input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #007bff;
            cursor: pointer;
        }
        .slider-container input[type="range"]::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #007bff;
            cursor: pointer;
        }

        .dropzone .dz-preview .dz-image img {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Ensure images fit in preview without cropping too much */
        }
        .comparison-box {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: left;
            display: none; /* Hidden by default */
        }
        .comparison-box p {
            margin: 5px 0;
            color: #343a40;
            font-size: 0.9em;
        }
        .comparison-box strong {
            color: #007bff;
        }

        /* Dropzone specific styles for queue display */
        .dropzone .dz-preview {
            display: inline-block; /* Arrange previews horizontally */
            vertical-align: top;
            margin: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            width: 180px; /* Fixed width for previews */
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
        }
        .dropzone .dz-preview .dz-image {
            width: 160px; /* Image container width */
            height: 120px; /* Image container height */
            overflow: hidden;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        .dropzone .dz-preview .dz-details {
            text-align: center;
            font-size: 0.8em;
            padding: 5px 0;
        }
        .dropzone .dz-preview .dz-error-message {
            color: #dc3545;
            font-size: 0.8em;
            margin-top: 5px;
        }
        .dropzone .dz-preview .dz-filename,
        .dropzone .dz-preview .dz-size {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dropzone .dz-remove {
            color: #dc3545;
            text-decoration: none;
            font-size: 0.8em;
            margin-top: 5px;
            display: block;
        }

        /* Style for the upload button (when Dropzone is not auto-processing) */
        #manualUploadBtn {
            margin-top: 20px;
            display: none; /* Hidden by default, shown when files are added */
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <a href="./">主 页</a>
            <a href="./upload.php">上 传</a>
            <a href="./list-1.php">列 表</a>
            <a href="./list-2.php">平 铺</a> |
            <a href="./admin.php">管 理</a>
        </div>
    </div>
    <div class="container">
        <?php if (isset($_SESSION['uploadMessage'])): ?>
            <div id="upload-message">
                <?= htmlspecialchars($_SESSION['uploadMessage']) ?>
            </div>
            <script>
                setTimeout(function(){
                    const msg = document.getElementById('upload-message');
                    if(msg) msg.style.display = 'none';
                }, 5000); // 显示 5 秒后隐藏
            </script>
            <?php unset($_SESSION['uploadMessage']); ?>
        <?php endif; ?>

        <h2>图片上传与管理</h2>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="dropzone" id="myDropzone" method="POST" enctype="multipart/form-data">
            <div class="dz-default dz-message">
                <button class="dz-button" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-upload mx-auto h-12 w-12 text-muted-foreground mb-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" x2="12" y1="3" y2="15"></line></svg>
                    <p class="text-lg mb-2">点击或拖放图片到这里上传</p>
                    <p class="text-sm text-muted-foreground">支持 JPG, JPEG, PNG, GIF, WEBP, SVG, ICO 格式</p>
                </button>
            </div>
        </form>

        <div class="options-container">
            <div class="checkbox-container">
                <input type="checkbox" id="rename_file" name="rename_file" value="yes" checked>
                <label for="rename_file">上传时自动随机命名文件</label>
            </div>
            <div class="checkbox-container">
                <input type="checkbox" id="convert_to_webp" name="convert_to_webp" value="yes">
                <label for="convert_to_webp">转换为 WebP 格式</label>
            </div>
        </div>

        <div class="slider-container" id="webpQualitySliderContainer" style="display: none;">
            <label for="webp_quality">WebP 质量:</label>
            <input type="range" id="webp_quality" name="webp_quality" min="60" max="100" value="100">
            <span id="webpQualityValue">100%</span>
        </div>

        <button type="submit" id="manualUploadBtn" class="btn btn-primary">上传文件</button>

        <div class="comparison-box" id="comparisonBox">
            <p><strong>原始文件:</strong> <span id="originalFileNameDisplay"></span></p>
            <p>尺寸: <span id="originalDimensionsDisplay"></span></p>
            <p>大小: <span id="originalSizeDisplay"></span> MB</p>
            <p><strong>转换后文件:</strong> <span id="newFileNameDisplay"></span></p>
            <p>大小: <span id="convertedSizeDisplay"></span> MB</p>
            <p>格式: <span id="convertedFormatDisplay"></span></p>
        </div>
        <br>

        <div>
            <form method="POST" style="display: inline;">
                <button type="submit" name="generate_thumbnails">更新 min_ 缩略图</button>
            </form>
            <form method="POST" style="display: inline;">
                <button type="submit" name="generate_large_thumbnails">更新 max_ 缩略图</button>
            </form>
        </div>
    </div>

    <script>
        // Dropzone 配置
        // 获取相关元素
        const convertToWebpCheckbox = document.getElementById('convert_to_webp');
        const webpQualitySliderContainer = document.getElementById('webpQualitySliderContainer');
        const webpQualitySlider = document.getElementById('webp_quality');
        const webpQualityValueSpan = document.getElementById('webpQualityValue');
        const renameCheckbox = document.getElementById('rename_file');
        const manualUploadBtn = document.getElementById('manualUploadBtn');
        const comparisonBox = document.getElementById('comparisonBox');

        // 初始化 Dropzone
        Dropzone.autoDiscover = false; // 阻止 Dropzone 自动附加到元素

        const myDropzone = new Dropzone("#myDropzone", {
            url: "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>", // 表单 action URL
            maxFilesize: 50, // MB
            acceptedFiles: ".jpg,.jpeg,.png,.gif,.webp,.svg,.ico",
            autoProcessQueue: false, // 保持 false，我们手动控制
            addRemoveLinks: true, // 显示每个文件的移除链接
            paramName: "file", // 服务器接收文件的参数名
            dictDefaultMessage: "点击或拖放图片到这里上传", // 自定义消息
            dictFallbackMessage: "您的浏览器不支持拖放文件上传。",
            dictFileTooBig: "文件太大 ({{filesize}}MB)。最大文件大小: {{maxFilesize}}MB。",
            dictInvalidFileType: "您不能上传此类型的文件。",
            dictResponseError: "服务器响应错误。",
            dictCancelUpload: "取消上传",
            dictRemoveFile: "移除文件",
            dictMaxFilesExceeded: "您一次只能上传最多 {{maxFiles}} 个文件。",
            uploadMultiple: false, // 每次只上传一个文件
            parallelUploads: 1, // **关键修复**：确保每次只有一个文件在上传，避免队列阻塞

            init: function () {
                const dz = this; // Dropzone 实例的引用

                // 更新质量值显示
                webpQualitySlider.oninput = function() {
                    webpQualityValueSpan.textContent = this.value + "%";
                };

                // 切换 WebP 质量滑块的可见性
                convertToWebpCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        webpQualitySliderContainer.style.display = 'flex';
                    } else {
                        webpQualitySliderContainer.style.display = 'none';
                        comparisonBox.style.display = 'none'; // 如果取消勾选 WebP，则隐藏对比框
                    }
                });

                // 上传按钮的事件监听器
                manualUploadBtn.addEventListener("click", function() {
                    if (dz.getQueuedFiles().length > 0) {
                        dz.processQueue(); // 启动上传队列
                        manualUploadBtn.disabled = true; // 上传开始后禁用按钮
                        manualUploadBtn.textContent = '上传中...';
                    } else {
                        alert("请先选择或拖放文件。");
                    }
                });

                // 当文件添加到队列时触发
                this.on("addedfile", function(file) {
                    manualUploadBtn.style.display = 'block'; // 显示上传按钮
                    manualUploadBtn.disabled = false; // 确保按钮可用
                    manualUploadBtn.textContent = '上传文件';
                    comparisonBox.style.display = 'none'; // 添加新文件时隐藏对比框
                });

                // 当文件从队列中移除时触发
                this.on("removedfile", function(file) {
                    // 检查是否还有待处理或正在上传的文件
                    if (dz.getQueuedFiles().length === 0 && dz.getUploadingFiles().length === 0) {
                        manualUploadBtn.style.display = 'none'; // 如果没有文件留下，则隐藏按钮
                        comparisonBox.style.display = 'none'; // 隐藏对比框
                    }
                });

                // 在发送每个文件之前触发
                this.on("sending", function(file, xhr, formData) {
                    if (renameCheckbox && renameCheckbox.checked) {
                        formData.append("rename_file", "yes");
                    }
                    if (convertToWebpCheckbox && convertToWebpCheckbox.checked) {
                        formData.append("convert_to_webp", "yes");
                        formData.append("webp_quality", webpQualitySlider.value);
                    }
                });

                // 成功上传一个文件后触发
                this.on("success", function (file, response) {
                    console.log("文件上传成功：", file.name, response);
                    // 处理服务器响应 (PHP 返回 JSON)
                    if (response && response.success) {
                        // 仅在上传成功，并且转换成功或源文件是WebP时，才更新对比框
                        // 为了避免多个文件上传时对比框内容频繁闪烁，
                        // 这里仍保持在每个文件成功时更新，您可能需要调整UI逻辑。
                        if (response.target_format_is_webp) {
                            document.getElementById('originalFileNameDisplay').textContent = response.original_file_name;
                            document.getElementById('originalDimensionsDisplay').textContent = response.original_dimensions;
                            document.getElementById('originalSizeDisplay').textContent = response.original_size_mb;
                            document.getElementById('newFileNameDisplay').textContent = response.new_file_name;
                            document.getElementById('convertedSizeDisplay').textContent = response.converted_size_mb;
                            document.getElementById('convertedFormatDisplay').textContent = 'WebP';
                            comparisonBox.style.display = 'block';
                        } else {
                            comparisonBox.style.display = 'none';
                        }
                        displayUploadMessage(response.message, 'success');
                    } else {
                        displayUploadMessage(response.message || "文件上传失败。", 'error');
                    }
                    // 文件成功处理后，从 Dropzone 预览中移除该文件
                    dz.removeFile(file);
                });

                // 上传错误时触发
                this.on("error", function (file, message, xhr) {
                    console.error("文件上传错误：", file.name, message);
                    let displayMsg = "文件上传失败: ";
                    if (typeof message === 'object' && message.message) {
                        displayMsg += message.message; // 如果 PHP 返回 JSON 错误
                    } else if (typeof message === 'string') {
                        displayMsg += message; // Dropzone 自己的错误消息
                    } else {
                        displayMsg += "未知错误。";
                    }
                    displayUploadMessage(displayMsg, 'error');
                    // 文件错误处理后，从 Dropzone 预览中移除该文件
                    dz.removeFile(file);
                });

                // 当队列中的所有文件都处理完毕时（无论成功或失败）
                this.on("queuecomplete", function() {
                    console.log("所有文件处理完毕。");
                    // 恢复上传按钮状态
                    manualUploadBtn.disabled = false;
                    manualUploadBtn.textContent = '上传文件';
                    // 如果队列为空，隐藏按钮 (因为 success/error 中已经移除了文件，所以这里 getQueuedFiles() 应该为 0)
                    if (dz.getQueuedFiles().length === 0 && dz.getUploadingFiles().length === 0) {
                        manualUploadBtn.style.display = 'none';
                    }
                });

                // 新增监听：当一个文件的上传完成时（无论成功或失败），检查并处理队列中的下一个文件
                // 这是为了确保即使在 autoProcessQueue:false 的情况下，也能连续上传
                this.on("complete", function(file) {
                    if (dz.getQueuedFiles().length > 0 && dz.getUploadingFiles().length === 0) {
                        // 确保当前没有文件正在上传，并且队列中还有待处理的文件
                        dz.processQueue();
                    }
                });
            }
        });

        // 显示上传消息的辅助函数
        function displayUploadMessage(message, type = 'success') {
            const msgBox = document.getElementById('upload-message');
            if (msgBox) {
                msgBox.textContent = message;
                msgBox.style.backgroundColor = type === 'success' ? '#4CAF50' : '#dc3545';
                msgBox.style.display = 'block';
                setTimeout(() => {
                    msgBox.style.display = 'none';
                }, 5000); // 5 秒后隐藏
            }
        }

        // 滑块默认值和可见性初始化
        webpQualitySlider.value = 100; // 设置默认值为 100
        webpQualityValueSpan.textContent = "100%"; // 更新显示

        if (convertToWebpCheckbox.checked) {
            webpQualitySliderContainer.style.display = 'flex';
        } else {
            webpQualitySliderContainer.style.display = 'none';
        }
    </script>

    <?php include("./footer.php"); ?>
</body>
</html>