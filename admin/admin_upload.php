<?php
// 后台上传与同步更新缩略图 - 动态相册路径自动换算版
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (file_exists("./ini.php")) {
    include("./ini.php"); 
}

session_start();
$page_title="图片上传";
// ===================================================
// 1. 扫描 ../user/ 下所有子目录，读取其 ini.php
// ===================================================
$userBaseDir = '../user/';
$albums = []; 

if (is_dir($userBaseDir)) {
    $subDirs = scandir($userBaseDir);
    foreach ($subDirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        
        $currentPath = $userBaseDir . $dir;
        if (is_dir($currentPath)) {
            $albumIniPath = $currentPath . '/ini.php';
            if (file_exists($albumIniPath)) {
                $albumConfig = call_user_func(function($path) {
                    @include($path);
                    return isset($config) ? $config : null;
                }, $albumIniPath);
                
                if ($albumConfig && isset($albumConfig['title'])) {
                    $albums[$dir] = [
                        'title' => $albumConfig['title'],
                        'config' => $albumConfig
                    ];
                }
            }
        }
    }
}

// 决定当前选中的相册目录
$selectedAlbumKey = isset($_POST['target_album']) ? $_POST['target_album'] : (isset($_GET['target_album']) ? $_GET['target_album'] : '');
if (empty($selectedAlbumKey) && !empty($albums)) {
    $selectedAlbumKey = array_key_first($albums);
}

// 动态锁定当前的相册配置
$currentAlbumConfig = isset($albums[$selectedAlbumKey]) ? $albums[$selectedAlbumKey]['config'] : null;

// ===================================================
// 2. 鉴权逻辑
// ===================================================
if ($currentAlbumConfig) {
    $logged_in_key = isset($currentAlbumConfig['login_admin']) ? $currentAlbumConfig['login_admin'] : 'logged_in_admin';
    if (!(isset($_SESSION['logged_in_admin']) && $_SESSION['logged_in_admin'] === true) && (empty($_SESSION[$logged_in_key]) || $_SESSION[$logged_in_key] !== true)) {
        header('Location: login.php');
        exit();
    }
} else {
    if (!(isset($_SESSION['logged_in_admin']) && $_SESSION['logged_in_admin'] === true)) {
        header('Location: login.php');
        exit();
    }
}

$_SESSION['admin_logged_in'] = true;

// ===================================================
// 3. 动态设置上传与缩略图存储路径（核心修复：路径换算）
// ===================================================
$uploadDir          = $currentAlbumConfig ? $currentAlbumConfig['tu_1'] : '';
$thumbnailDir       = $currentAlbumConfig ? $currentAlbumConfig['min'] : ''; 
$largeThumbnailDir  = $currentAlbumConfig ? $currentAlbumConfig['max'] : '';

// 【核心修复：将配置中的 ../../ 动态转换为适应 admin/ 目录的 ../】
if ($uploadDir && substr($uploadDir, 0, 6) === '../../') {
    $uploadDir = '../' . substr($uploadDir, 6);
}
if ($thumbnailDir && substr($thumbnailDir, 0, 6) === '../../') {
    $thumbnailDir = '../' . substr($thumbnailDir, 6);
}
if ($largeThumbnailDir && substr($largeThumbnailDir, 0, 6) === '../../') {
    $largeThumbnailDir = '../' . substr($largeThumbnailDir, 6);
}

// 确保换算后的目录存在
if (!empty($uploadDir)) {
    if (!@is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
    if (!@is_dir($thumbnailDir)) { @mkdir($thumbnailDir, 0777, true); }
    if (!@is_dir($largeThumbnailDir)) { @mkdir($largeThumbnailDir, 0777, true); }
}

$allowedUploadExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];
$gdConvertibleExtensions = ['jpg', 'jpeg', 'png', 'gif'];

// ===================================================
// 4. 处理表单提交
// ===================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($currentAlbumConfig)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '未找到有效的相册配置文件，拒绝上传。']);
        exit();
    }

    if (isset($_FILES['file'])) {
        $renameFile = isset($_POST['rename_file']) && $_POST['rename_file'] == 'yes';
        $convertToWebp = isset($_POST['convert_to_webp']) && $_POST['convert_to_webp'] == 'yes';
        $webpQuality = isset($_POST['webp_quality']) ? (int)$_POST['webp_quality'] : 100;

        handleFileUpload($_FILES['file'], $renameFile, $convertToWebp, $webpQuality);
    } elseif (isset($_POST['generate_thumbnails'])) {
        regenerateAllThumbnails($thumbnailDir, 'min_', 500);
        $_SESSION['uploadMessage'] = "该相册所有 min_ 缩略图已重新生成。";
        header("Location: " . $_SERVER['PHP_SELF'] . "?target_album=" . urlencode($selectedAlbumKey));
        exit();
    } elseif (isset($_POST['generate_large_thumbnails'])) {
        regenerateAllThumbnails($largeThumbnailDir, 'max_', 1920);
        $_SESSION['uploadMessage'] = "该相册所有 max_ 缩略图已重新生成。";
        header("Location: " . $_SERVER['PHP_SELF'] . "?target_album=" . urlencode($selectedAlbumKey));
        exit();
    }
}

function handleFileUpload($file, $rename = false, $convertToWebp = false, $webpQuality = 100) {
    global $uploadDir, $thumbnailDir, $largeThumbnailDir, $allowedUploadExtensions, $gdConvertibleExtensions;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errMsgs = [
            1 => '文件大小超出了 php.ini 中 upload_max_filesize 的限制。',
            2 => '文件大小超出了 HTML 表单 MAX_FILE_SIZE 的限制。',
            3 => '文件只有部分被上传。',
            4 => '没有文件被上传。',
            6 => '找不到临时文件夹。',
            7 => '文件写入失败，磁盘可能已满。'
        ];
        $msg = isset($errMsgs[$file['error']]) ? $errMsgs[$file['error']] : '未知 PHP 上传错误。';
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }

    $originalFileName = pathinfo($file['name'], PATHINFO_FILENAME);
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowedUploadExtensions)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "不允许的文件类型：$file_ext"]);
        exit();
    }

    if (empty($uploadDir) || !@is_dir($uploadDir)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "存储路径无效或无写入权限：$uploadDir"]);
        exit();
    }

    $newFileName = '';
    $targetExtension = $file_ext;
    $isSourceWebp = ($file_ext === 'webp');

    if ($convertToWebp && in_array($file_ext, $gdConvertibleExtensions)) {
        $targetExtension = 'webp';
    } elseif ($isSourceWebp) {
        $targetExtension = 'webp';
    }

    if ($rename) {
        $newFileName = time() . '_' . uniqid() . '.' . $targetExtension;
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
    $originalFileSize = filesize($tempFilePath);

    $conversionSuccess = false;
    $convertedFileSize = 0;
    $originalWidth = 0;
    $originalHeight = 0;

    $originalImageInfo = @getimagesize($tempFilePath);
    if ($originalImageInfo) {
        $originalWidth = $originalImageInfo[0];
        $originalHeight = $originalImageInfo[1];
    }

    if ($isSourceWebp) {
        if (@move_uploaded_file($tempFilePath, $finalFilePath)) {
            $conversionSuccess = true;
            $convertedFileSize = filesize($finalFilePath);
            $msg = "上传成功。";
        } else {
            $msg = "文件移动失败，目标目录不可写或受 open_basedir 限制。路径: " . $uploadDir;
        }
    } elseif ($convertToWebp && in_array($file_ext, $gdConvertibleExtensions)) {
        if (createWebpFromAny($tempFilePath, $finalFilePath, $webpQuality)) {
            $conversionSuccess = true;
            $convertedFileSize = filesize($finalFilePath);
            $msg = "文件上传并转换为 WebP 成功！";
        } else {
            if (@move_uploaded_file($tempFilePath, $finalFilePath)) {
                $conversionSuccess = true;
                $convertedFileSize = filesize($finalFilePath);
                $msg = "WebP转换失败，已自动保存原格式图片。";
            } else {
                $msg = "保存原图失败，目标目录不可写或受 open_basedir 限制。路径: " . $uploadDir;
            }
        }
    } else {
        if (@move_uploaded_file($tempFilePath, $finalFilePath)) {
            $conversionSuccess = true;
            $convertedFileSize = filesize($finalFilePath);
            $msg = "文件上传成功！";
        } else {
            $msg = "文件移动失败，目标目录不可写或受 open_basedir 限制。路径: " . $uploadDir;
        }
    }

    if ($conversionSuccess) {
        $thumbnail_new_name = pathinfo($newFileName, PATHINFO_FILENAME) . '.' . $targetExtension;
        $thumbnail_path = $thumbnailDir . 'min_' . $thumbnail_new_name;
        $large_thumbnail_path = $largeThumbnailDir . 'max_' . $thumbnail_new_name;

        @createThumbnail($finalFilePath, $thumbnail_path, 500, $targetExtension, $webpQuality);
        @createThumbnail($finalFilePath, $large_thumbnail_path, 1920, $targetExtension, $webpQuality);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $msg,
            'original_file_name' => $file['name'],
            'new_file_name' => $newFileName,
            'original_size_mb' => round($originalFileSize / (1024 * 1024), 2),
            'converted_size_mb' => round($convertedFileSize / (1024 * 1024), 2),
            'original_dimensions' => $originalWidth . 'x' . $originalHeight,
            'target_format_is_webp' => ($targetExtension === 'webp'),
            'is_source_webp' => $isSourceWebp
        ]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }
}

function createWebpFromAny($sourcePath, $destinationPath, $quality = 100) {
    if (!extension_loaded('gd') || !function_exists('imagewebp')) return false;
    $imageInfo = @getimagesize($sourcePath);
    if ($imageInfo === false) return false;

    $mime = $imageInfo['mime'];
    $sourceImage = null;

    switch ($mime) {
        case 'image/jpeg': $sourceImage = @imagecreatefromjpeg($sourcePath); break;
        case 'image/png': 
            $sourceImage = @imagecreatefrompng($sourcePath);
            if ($sourceImage) { imagepalettetotruecolor($sourceImage); imagealphablending($sourceImage, false); imagesavealpha($sourceImage, true); }
            break;
        case 'image/gif': 
            $sourceImage = @imagecreatefromgif($sourcePath);
            if ($sourceImage) { imagepalettetotruecolor($sourceImage); imagealphablending($sourceImage, false); imagesavealpha($sourceImage, true); }
            break;
        case 'image/webp': 
            $sourceImage = @imagecreatefromwebp($sourcePath);
            if ($sourceImage) { imagealphablending($sourceImage, false); imagesavealpha($sourceImage, true); }
            break;
        default: return false;
    }

    if ($sourceImage === null) return false;
    $success = @imagewebp($sourceImage, $destinationPath, $quality);
    imagedestroy($sourceImage);
    return $success;
}

function createThumbnail($sourcePath, $thumbnailPath, $maxWidth, $targetExtension = null, $webpQuality = 100) {
    if (file_exists($thumbnailPath) && (!is_null($targetExtension) && strtolower(pathinfo($thumbnailPath, PATHINFO_EXTENSION)) == strtolower($targetExtension))) {
        return true;
    }

    $thumbnailDir = dirname($thumbnailPath);
    if (!@is_dir($thumbnailDir)) @mkdir($thumbnailDir, 0777, true);
    if (!file_exists($sourcePath)) return false;

    $imageInfo = @getimagesize($sourcePath);
    if ($imageInfo === false) return false;
    list($width, $height, $type) = $imageInfo;

    $sourceFileExt = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    $isSvgOrIco = ($sourceFileExt === 'svg' || $sourceFileExt === 'ico');

    if (($width <= $maxWidth || $isSvgOrIco) && ($targetExtension == $sourceFileExt || ($targetExtension === 'webp' && $isSvgOrIco))) {
        return @copy($sourcePath, $thumbnailPath);
    }

    $ratio = $maxWidth / $width;
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);

    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
    $sourceImage = null;

    switch ($type) {
        case IMAGETYPE_JPEG: $sourceImage = @imagecreatefromjpeg($sourcePath); break;
        case IMAGETYPE_PNG:
            $sourceImage = @imagecreatefrompng($sourcePath);
            imagealphablending($thumbnail, false); imagesavealpha($thumbnail, true);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = @imagecreatefromgif($sourcePath);
            if ($sourceImage) {
                $transparentindex = imagecolortransparent($sourceImage);
                if ($transparentindex >= 0) {
                    $transparentcolor = imagecolorsforindex($sourceImage, $transparentindex);
                    $newtransparentcolor = imagecolorallocate($thumbnail, $transparentcolor['red'], $transparentcolor['green'], $transparentcolor['blue']);
                    imagefill($thumbnail, 0, 0, $newtransparentcolor);
                    imagecolortransparent($thumbnail, $newtransparentcolor);
                }
            }
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = @imagecreatefromwebp($sourcePath);
            if ($sourceImage) { imagealphablending($thumbnail, false); imagesavealpha($thumbnail, true); }
            break;
        default: return false;
    }

    if (!$sourceImage) return false;

    imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    $outputType = $targetExtension ? $targetExtension : strtolower(pathinfo($thumbnailPath, PATHINFO_EXTENSION));

    $success = false;
    switch ($outputType) {
        case 'jpeg': case 'jpg': $success = @imagejpeg($thumbnail, $thumbnailPath, 90); break;
        case 'png': $success = @imagepng($thumbnail, $thumbnailPath); break;
        case 'gif': $success = @imagegif($thumbnail, $thumbnailPath); break;
        case 'webp': $success = @imagewebp($thumbnail, $thumbnailPath, $webpQuality); break;
        default: $success = false; break;
    }

    if (PHP_VERSION_ID < 80500) {
        if (isset($thumbnail)) @imagedestroy($thumbnail);
    }
    return $success;
}

function regenerateAllThumbnails($thumbnailDir, $prefix, $maxWidth) {
    global $uploadDir, $allowedUploadExtensions;
    if (empty($uploadDir) || !@is_dir($uploadDir)) return;
    $files = scandir($uploadDir);
    if ($files === false) return;

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $filePath = $uploadDir . $file;
        $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowedUploadExtensions) && is_file($filePath)) {
            $targetExtension = $file_ext;
            $thumbnailFileName = $prefix . pathinfo($file, PATHINFO_FILENAME) . '.' . $targetExtension;
            $thumbnailPath = $thumbnailDir . $thumbnailFileName;

            if (file_exists($thumbnailPath)) @unlink($thumbnailPath);
            @createThumbnail($filePath, $thumbnailPath, $maxWidth, $targetExtension, 80);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>图片上传与管理</title>
    <link rel="stylesheet" href="/admin/css/themes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <style>
        body { font-family: system-ui, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; display: flex; flex-direction: column; align-items: center; }
        .container { width: 100%; max-width: 1200px; text-align: center; margin: 30px auto; padding: 0 15px; box-sizing: border-box; }
        h2 { color: #333; }
        .select-album-box { margin: 20px auto; padding: 15px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: inline-flex; align-items: center; gap: 12px; }
        .select-album-box select { padding: 8px 16px; font-size: 15px; border: 1px solid #ccc; border-radius: 4px; outline: none; cursor: pointer; }
        .dropzone { width: 100%; border: 2px dashed #ccc; border-radius: 6px; background-color: #fff; padding: 40px 20px; text-align: center; cursor: pointer; box-sizing: border-box; }
        button[type="button"], button[type="submit"], .btn-action { background-color: #007bff; color: white; border: none; border-radius: 5px; padding: 10px 20px; font-size: 16px; cursor: pointer; margin: 10px 5px; }
        #upload-message { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background-color: #4CAF50; color: white; padding: 15px 20px; border-radius: 5px; z-index: 1000; display: none; }
        .options-container { display: flex; align-items: center; justify-content: center; margin: 15px 0; gap: 20px; }
        .slider-container { display: flex; align-items: center; gap: 10px; margin: 10px auto; color: #555; width: 100%; max-width: 400px; }
        .slider-container input[type="range"] { flex-grow: 1; }
        .comparison-box { background-color: #e9ecef; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: left; display: none; max-width: 500px; margin-left: auto; margin-right: auto; }
        #manualUploadBtn { margin-top: 20px; display: none; }
    </style>
</head>
<body>

    <?php include("header.php"); ?>

    <div class="container" style="padding-top: 40px;">
        <div id="upload-message"></div>

        <h2>图片上传与管理</h2>

        <div class="select-album-box">
            <label for="albumSelector">选择上传目标相册：</label>
            <select id="albumSelector" onchange="location.href='?target_album=' + encodeURIComponent(this.value)">
                <?php if(empty($albums)): ?>
                    <option value="">-- 未检测到任何有效相册 --</option>
                <?php else: ?>
                    <?php foreach ($albums as $key => $album): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $key === $selectedAlbumKey ? 'selected' : '' ?>>
                            <?= htmlspecialchars($album['title']) ?> (<?= htmlspecialchars($key) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="dropzone" id="myDropzone" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="target_album" value="<?= htmlspecialchars($selectedAlbumKey) ?>">
            <div class="dz-default dz-message">
                <p class="text-lg mb-2" style="font-size: 18px; font-weight: bold; color: #555;">点击或拖放图片到这里上传</p>
                <p class="text-sm" style="color: #888;">支持 JPG, JPEG, PNG, GIF, WEBP, SVG, ICO 格式</p>
            </div>
        </form>

        <div class="options-container">
            <div>
                <input type="checkbox" id="rename_file" name="rename_file" value="yes" checked>
                <label for="rename_file">自动随机命名</label>
            </div>
            <div>
                <input type="checkbox" id="convert_to_webp" name="convert_to_webp" value="yes">
                <label for="convert_to_webp">转换为 WebP 格式</label>
            </div>
        </div>

        <div class="slider-container" id="webpQualitySliderContainer" style="display: none;">
            <label for="webp_quality">WebP 质量:</label>
            <input type="range" id="webp_quality" name="webp_quality" min="60" max="100" value="100">
            <span id="webpQualityValue">100%</span>
        </div>

        <button type="button" id="manualUploadBtn">确认上传</button>

        <div class="comparison-box" id="comparisonBox">
            <p><strong>原始文件:</strong> <span id="originalFileNameDisplay"></span></p>
            <p>尺寸: <span id="originalDimensionsDisplay"></span> | 大小: <span id="originalSizeDisplay"></span> MB</p>
            <p><strong>转换后 (WebP):</strong> 大小: <span id="convertedSizeDisplay"></span> MB</p>
        </div>
        <br>

        <div>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="target_album" value="<?= htmlspecialchars($selectedAlbumKey) ?>">
                <button type="submit" class="btn-action" name="generate_thumbnails">更新当前相册 min_ 缩略图</button>
            </form>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="target_album" value="<?= htmlspecialchars($selectedAlbumKey) ?>">
                <button type="submit" class="btn-action" name="generate_large_thumbnails">更新当前相册 max_ 缩略图</button>
            </form>
        </div>
    </div>

    <script>
        const convertToWebpCheckbox = document.getElementById('convert_to_webp');
        const webpQualitySliderContainer = document.getElementById('webpQualitySliderContainer');
        const webpQualitySlider = document.getElementById('webp_quality');
        const webpQualityValueSpan = document.getElementById('webpQualityValue');
        const renameCheckbox = document.getElementById('rename_file');
        const manualUploadBtn = document.getElementById('manualUploadBtn');
        const comparisonBox = document.getElementById('comparisonBox');
        const albumSelector = document.getElementById('albumSelector');

        Dropzone.autoDiscover = false;

        const myDropzone = new Dropzone("#myDropzone", {
            url: "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>",
            maxFilesize: 50,
            acceptedFiles: ".jpg,.jpeg,.png,.gif,.webp,.svg,.ico",
            autoProcessQueue: false,
            addRemoveLinks: true,
            paramName: "file",
            parallelUploads: 1,

            init: function () {
                const dz = this;

                webpQualitySlider.oninput = function() {
                    webpQualityValueSpan.textContent = this.value + "%";
                };

                convertToWebpCheckbox.addEventListener('change', function() {
                    webpQualitySliderContainer.style.display = this.checked ? 'flex' : 'none';
                    if (!this.checked) comparisonBox.style.display = 'none';
                });

                manualUploadBtn.addEventListener("click", function() {
                    if (dz.getQueuedFiles().length > 0) {
                        albumSelector.disabled = true;
                        dz.processQueue();
                        manualUploadBtn.disabled = true;
                        manualUploadBtn.textContent = '上传中...';
                    } else {
                        alert("请先选择或拖放文件。");
                    }
                });

                this.on("addedfile", function() {
                    manualUploadBtn.style.display = 'inline-block';
                    manualUploadBtn.disabled = false;
                    manualUploadBtn.textContent = '确认上传';
                    comparisonBox.style.display = 'none';
                });

                this.on("removedfile", function() {
                    if (dz.getQueuedFiles().length === 0 && dz.getUploadingFiles().length === 0) {
                        manualUploadBtn.style.display = 'none';
                        albumSelector.disabled = false;
                    }
                });

                this.on("sending", function(file, xhr, formData) {
                    formData.append("target_album", albumSelector.value);
                    if (renameCheckbox && renameCheckbox.checked) formData.append("rename_file", "yes");
                    if (convertToWebpCheckbox && convertToWebpCheckbox.checked) {
                        formData.append("convert_to_webp", "yes");
                        formData.append("webp_quality", webpQualitySlider.value);
                    }
                });

                this.on("success", function (file, response) {
                    if (response && response.success) {
                        if (response.target_format_is_webp) {
                            document.getElementById('originalFileNameDisplay').textContent = response.original_file_name;
                            document.getElementById('originalDimensionsDisplay').textContent = response.original_dimensions;
                            document.getElementById('originalSizeDisplay').textContent = response.original_size_mb;
                            document.getElementById('convertedSizeDisplay').textContent = response.converted_size_mb;
                            comparisonBox.style.display = 'block';
                        }
                        displayUploadMessage(response.message, 'success');
                        dz.removeFile(file);
                    } else {
                        let errMsg = (response && response.message) ? response.message : "上传失败：服务器返回了错误的响应内容。";
                        displayUploadMessage(errMsg, 'error');
                    }
                });

                this.on("error", function (file, message) {
                    let displayMsg = "上传失败: ";
                    if (typeof message === 'object') {
                        displayMsg += (message.message || "未知服务端网络错误。");
                    } else {
                        displayMsg += message;
                    }
                    displayUploadMessage(displayMsg, 'error');
                });

                this.on("queuecomplete", function() {
                    manualUploadBtn.disabled = false;
                    manualUploadBtn.textContent = '确认上传';
                    albumSelector.disabled = false;
                    if (dz.getQueuedFiles().length === 0) manualUploadBtn.style.display = 'none';
                });

                this.on("complete", function() {
                    if (dz.getQueuedFiles().length > 0 && dz.getUploadingFiles().length === 0) {
                        dz.processQueue();
                    }
                });
            }
        });

        function displayUploadMessage(message, type = 'success') {
            const msgBox = document.getElementById('upload-message');
            if (msgBox) {
                msgBox.textContent = message;
                msgBox.style.backgroundColor = type === 'success' ? '#4CAF50' : '#dc3545';
                msgBox.style.display = 'block';
                setTimeout(() => { msgBox.style.display = 'none'; }, type === 'success' ? 4000 : 8000);
            }
        }
    </script>
</body>
</html>