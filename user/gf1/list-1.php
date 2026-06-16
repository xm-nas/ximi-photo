<?php
// list-1.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

include("./header.php"); 

// 定义ini文件路径
define('INI_FILE_BATCH', __DIR__ . '/ini.php');

/**
 * 读取配置文件
 */
function readIniConfigBatch() {
    $config_data = [];
    if (file_exists(INI_FILE_BATCH)) {
        ob_start();
        include INI_FILE_BATCH;
        ob_end_clean();
        if (isset($config) && is_array($config)) {
            $config_data = $config;
        }
    }
    return $config_data;
}

$config = readIniConfigBatch();

// 获取动态登录标识符
$logged_in_key = $config['login_admin'] ?? 'logged_in_admin';

// 检查用户是否已登录
if (!(isset($_SESSION[$logged_in_key]) && $_SESSION[$logged_in_key] === true)) {
    header('Location: login.php');
    exit();
}
$_SESSION['admin_logged_in'] = true;

// 设置目录
$imageDir = $config['tu_1'];
$thumbnailDir = $config['min'];
$maxDir = $config['max'];

$userMessages = [];
$uploadedFiles = [];

// 扫描目录
if (!is_dir($imageDir) || !is_readable($imageDir)) {
    $userMessages[] = "当前相册无图片或目录访问受限。";
} else {
    $scannedFiles = scandir($imageDir);
    if ($scannedFiles === false) {
        $userMessages[] = "警告：无法扫描目录。";
    } else {
        $uploadedFiles = array_diff($scannedFiles, ['.', '..']);
        if (!empty($uploadedFiles)) {
            usort($uploadedFiles, function($a, $b) use ($imageDir) {
                $fileA = $imageDir . $a;
                $fileB = $imageDir . $b;
                return (file_exists($fileB) ? filemtime($fileB) : 0) - (file_exists($fileA) ? filemtime($fileA) : 0);
            });
        }
    }
}

// 获取分页设置
$imagesPerPage = isset($_GET['itemsPerPage']) ? (int)$_GET['itemsPerPage'] : 50;
$imagesPerPage = max(1, $imagesPerPage);

// --- 处理删除逻辑 ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deletedFilesCount = 0;
    if (!empty($_POST['delete_file']) && is_array($_POST['delete_file'])) {
        foreach ($_POST['delete_file'] as $file_name) {
            $file_name = basename($file_name); 
            $file_path = $imageDir . $file_name;
            $thumbnail_path = $thumbnailDir . 'min_' . $file_name;
            $max_path = $maxDir . 'max_' . $file_name;

            if (file_exists($file_path) && unlink($file_path)) {
                $deletedFilesCount++;
            }
            if (file_exists($thumbnail_path)) @unlink($thumbnail_path);
            if (file_exists($max_path)) @unlink($max_path);
        }
    }
    $_SESSION['deleteMessage'] = $deletedFilesCount > 0 ? $deletedFilesCount . " 个文件已删除。" : "没有文件被删除。";
    
    // 重定向回当前页
    $page = $_GET['page'] ?? 1;
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=$page&itemsPerPage=$imagesPerPage");
    exit();
}

$totalImages = count($uploadedFiles);
$totalPages = ($totalImages > 0) ? ceil($totalImages / $imagesPerPage) : 1;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $imagesPerPage;
$currentImages = array_slice($uploadedFiles, $offset, $imagesPerPage);

function formatBytes($bytes, $precision = 2) {
    if (!is_numeric($bytes) || $bytes < 0) return 'N/A';
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $pow = floor(($bytes > 0 ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>图片管理列表</title>
    <style>
        body { font-family: -apple-system, system-ui, sans-serif; background-color: #f8f9fa; padding: 20px; margin: 0; }
        .container { max-width: 1200px; margin: 30px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { color: #007bff; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: #f1f3f5; padding: 15px; border-radius: 6px; }
        .img-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .img-table th, .img-table td { padding: 12px; border: 1px solid #dee2e6; text-align: center; vertical-align: middle; }
        .img-table img { max-height: 100px; max-width: 150px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; transition: 0.3s; }
        .btn-delete { background-color: #dc3545; color: white; }
        .btn-delete:hover { background-color: #c82333; }
        .btn-primary { background-color: #007bff; color: white; padding: 12px 40px; font-size: 16px; }
        .pagination { margin-top: 20px; text-align: center; }
        .pagination a, .pagination span { padding: 8px 14px; border: 1px solid #dee2e6; margin: 0 3px; text-decoration: none; border-radius: 4px; display: inline-block; }
        .current-page { background: #007bff; color: white; border-color: #007bff; }
        .alert-message { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>

<div class="container">
    <h2>图片批管理</h2>

    <?php if (isset($_SESSION['deleteMessage'])): ?>
        <script>alert("<?= htmlspecialchars($_SESSION['deleteMessage']) ?>");</script>
        <?php unset($_SESSION['deleteMessage']); ?>
    <?php endif; ?>

    <div class="controls">
        <div>
            <label>每页显示：</label>
            <select onchange="location.href='?page=1&itemsPerPage='+this.value">
                <?php foreach([50, 100, 200, 500] as $v): ?>
                    <option value="<?= $v ?>" <?= $imagesPerPage == $v ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <input type="checkbox" id="selectAll" style="transform: scale(1.2); vertical-align: middle;"> 
            <label for="selectAll" style="font-weight: bold; cursor: pointer;"> 全选当前页</label>
        </div>
    </div>

    <?php if (empty($currentImages)): ?>
        <div class="alert-message">当前目录没有图片。</div>
    <?php else: ?>
        <form id="managementForm" method="POST">
            <input type="hidden" name="action" value="delete">
            
            <table class="img-table">
                <thead>
                    <tr>
                        <th width="50">选</th>
                        <th>图片名</th>
                        <th>缩略图</th>
                        <th>大小</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($currentImages as $image): 
                        $original_path = $imageDir . $image;
                        $thumb_src = $thumbnailDir . 'min_' . $image;
                    ?>
                    <tr>
                        <td><input type="checkbox" name="delete_file[]" value="<?= htmlspecialchars($image); ?>" class="file-checkbox"></td>
                        <td><small><?= htmlspecialchars($image); ?></small></td>
                        <td>
                            <?php if (file_exists($thumb_src)): ?>
                                <img src="<?= htmlspecialchars($thumb_src); ?>">
                            <?php else: ?>
                                <span style="color:#999">无缩略图</span>
                            <?php endif; ?>
                        </td>
                        <td><?= file_exists($original_path) ? formatBytes(filesize($original_path)) : 'N/A' ?></td>
                        <td>
                            <button type="button" class="btn btn-delete single-del-btn" data-name="<?= htmlspecialchars($image); ?>">删除</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&itemsPerPage=<?= $imagesPerPage ?>" class="<?= $i==$page?'current-page':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">确认批量删除所选图片</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.file-checkbox');
    const mainForm = document.getElementById('managementForm');

    // 1. 全选功能
    if(selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }

    // 2. 单条删除按钮逻辑
    document.querySelectorAll('.single-del-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('确定要删除这张图片吗？')) {
                const fileName = this.getAttribute('data-name');
                // 先取消所有勾选
                checkboxes.forEach(cb => cb.checked = false);
                // 仅勾选当前行
                const target = Array.from(checkboxes).find(cb => cb.value === fileName);
                if (target) {
                    target.checked = true;
                    mainForm.submit();
                }
            }
        });
    });

    // 3. 批量删除提交验证
    if(mainForm) {
        mainForm.addEventListener('submit', function(e) {
            const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
            if (checkedCount === 0) {
                alert('请先勾选需要删除的图片！');
                e.preventDefault();
                return;
            }
            if (!confirm('确定要永久删除这 ' + checkedCount + ' 张图片吗？此操作不可恢复！')) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php include("./footer.php"); ?>
</body>
</html>