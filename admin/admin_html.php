<?php
// admin.php 后台默认首页
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title = "模板管理"; 

session_start();

// 检查用户是否已登录
if (empty($_SESSION['logged_in_admin']) || $_SESSION['logged_in_admin'] !== true) {
    header('Location: login.php');
    exit();
}

$_SESSION['admin_logged_in'] = true;

$userMessage = '';
$userMessageType = ''; 

if (isset($_GET['msg']) && isset($_GET['type'])) {
    $userMessage = htmlspecialchars($_GET['msg']);
    $userMessageType = htmlspecialchars($_GET['type']);
}

// --- 辅助函数：安全地读取ini.php配置（同步逻辑所需） ---
function getAlbumConfig($albumDir) {
    $config_data = [];
    $iniFilePath = $albumDir . '/ini.php';
    if (file_exists($iniFilePath) && is_readable($iniFilePath)) {
        ob_start();
        include $iniFilePath;
        ob_end_clean();
        if (isset($config) && is_array($config)) {
            $config_data = $config;
        }
        unset($config);
    }
    return $config_data;
}

// --- 获取两个指定目录下的所有可编辑文件 ---
function getFolderFiles($dirPath) {
    $fileList = [];
    if (is_dir($dirPath)) {
        $files = scandir($dirPath);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && !is_dir($dirPath . $file)) {
                // 仅允许编辑文本及代码相关后缀的文件
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['php', 'html', 'htm', 'js', 'css', 'json', 'txt'])) {
                    $fileList[] = $file;
                }
            }
        }
    }
    return $fileList;
}

// 扫描当前 admin 目录文件与默认模板目录文件
$admin_dir = './'; 
$default_template_dir = '../index_src/defaul/';

$admin_files = getFolderFiles($admin_dir);
$template_files = getFolderFiles($default_template_dir);

// --- 核心处理区：包含文件保存与模板同步 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 【找回逻辑】：处理同步更新操作 (防止 502 关键逻辑)
    if (isset($_POST['sync_update'])) {
        // 1. 提升环境性能上限
        set_time_limit(0); // 取消脚本运行时间限制
        ini_set('memory_limit', '512M'); // 提升内存限制

        $source_dir = '../index_src/defaul/';
        $target_root_dir = '../user/';
        $sync_success = true;

        if (!is_dir($source_dir) || !is_readable($source_dir)) {
            $userMessage = "错误：源目录不存在或不可读。";
            $userMessageType = 'error';
            $sync_success = false;
        } else {
            // 2. 预先读取源文件列表（优化 IO）
            $raw_source_items = array_diff(scandir($source_dir), ['.', '..', 'ini.php']);
            $source_files = [];
            foreach ($raw_source_items as $item) {
                if (is_file($source_dir . $item)) {
                    $source_files[] = $item;
                }
            }

            if (is_dir($target_root_dir)) {
                $directories = array_diff(scandir($target_root_dir), ['.', '..']);
                foreach ($directories as $name) {
                    $target_dir = $target_root_dir . $name . DIRECTORY_SEPARATOR;
                    if (is_dir($target_dir)) {
                        $album_config = getAlbumConfig($target_dir);
                        $name_title = $album_config['title'] ?? $name;

                        foreach ($source_files as $file) {
                            $source_file_path = $source_dir . $file;
                            
                            // 替换逻辑
                            $new_file_name = str_replace(['defaul/', '默认模板'], [$name . '/', $name_title], $file);
                            $target_file_path = $target_dir . $new_file_name;

                            if (!@copy($source_file_path, $target_file_path)) {
                                $sync_success = false;
                            }
                        }
                    }
                }
            } else {
                $sync_success = false;
                $userMessage = "错误：目标用户根目录不存在。";
            }
        }

        if ($sync_success) {
            $userMessage = '全部目录模板同步更新完成。';
            $userMessageType = 'success';
        } else {
            $userMessage = $userMessage ?: '部分文件同步失败，请检查目录写权限。';
            $userMessageType = 'error';
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode($userMessage) . "&type=" . $userMessageType);
        exit();
    }

    // 处理正常的编辑器保存逻辑
    if (isset($_POST['action']) && $_POST['action'] === 'edit_file_content') {
        $filename = $_POST['filename'] ?? '';
        $content = $_POST['content'] ?? '';

        // 安全检查：限制只能写入 admin 目录或默认模板目录下的文件，防止越权路径穿越
        $real_path = realpath($filename);
        $allowed_admin = realpath($admin_dir);
        $allowed_template = realpath($default_template_dir);

        $is_allowed = false;
        if ($real_path !== false) {
            if (($allowed_admin && strpos($real_path, $allowed_admin) === 0) || 
                ($allowed_template && strpos($real_path, $allowed_template) === 0)) {
                $is_allowed = true;
            }
        }

        if ($is_allowed && !empty($filename) && file_exists($filename) && is_writable($filename)) {
            if (file_put_contents($filename, $content) !== false) {
                $userMessage = "文件 [" . basename($filename) . "] 修改已成功永久保存！";
                $userMessageType = "success";
            } else {
                $userMessage = "保存失败，写入文件时发生未知内部错误。";
                $userMessageType = "error";
            }
        } else {
            $userMessage = "无法保存。文件不存在、无写入权限，或超出了允许编辑的目录范围！";
            $userMessageType = "error";
        }
        
        $_SESSION['last_file_edited'] = $filename;
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode($userMessage) . "&type=" . $userMessageType);
        exit();
    }
}

$last_file_edited = $_SESSION['last_file_edited'] ?? '';

// 引入系统侧边栏
if (file_exists("./header.php")) {
    include("./header.php"); 
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* 增加页面主包裹内边距，让整体内容不再靠边 */
        .page-content-wrapper {
            padding: 24px;
            max-width: 1380px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
        }

        h3 {
            font-size: 18px;
            color: #1e293b;
            margin-top: 10px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
        }

        h4 {
            font-size: 14px;
            color: #475569;
            margin-top: 15px;
            margin-bottom: 10px;
            font-weight: 500;
        }

        /* 胶囊颗粒式文件列表导航组 */
        .file-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .file-list a {
            display: inline-block;
            padding: 6px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            text-decoration: none;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.15s ease;
        }

        .file-list a:hover {
            background: #edf2f7;
            border-color: #cbd5e1;
            color: #007bff;
        }

        /* 同步模板按钮样式 */
        .btn-sync-template {
            display: inline-block;
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            margin-bottom: 20px;
            transition: background 0.15s;
        }
        .btn-sync-template:hover {
            background: #218838;
        }

        /* 当前正在编辑的文件状态标签 */
        #currentFile {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12.5px;
            font-family: monospace;
            font-weight: 600;
            margin-bottom: 10px;
        }

        /* 源码文本编辑器核心框 */
        #content {
            width: 100%;
            height: 520px;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 14px;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            box-sizing: border-box;
            background-color: #f8fafc;
            color: #0f172a;
            line-height: 1.5;
            resize: vertical;
        }

        #content:focus {
            outline: none;
            border-color: #007bff;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .btn-save {
            background: #007bff; 
            color: white; 
            padding: 10px 30px; 
            border: none; 
            border-radius: 4px; 
            font-size: 14px; 
            font-weight: 500; 
            cursor: pointer;
            transition: background 0.15s;
        }
        
        .btn-save:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<div class="page-content-wrapper">

    <h3>内容区</h3>
    <div>
        <h4>分系统文件编辑[admin]目录：</h4>
        <div class="file-list">
            <?php foreach ($admin_files as $f): ?>
                <a href="javascript:void(0)" onclick="loadFile('<?= $admin_dir . $f ?>')"><?= $f ?></a>
            <?php endforeach; ?>
        </div>

        <h4>默认模板[../index_src/defaul/]目录：</h4>
        <div class="file-list">
            <?php foreach ($template_files as $f): ?>
                <a href="javascript:void(0)" onclick="loadFile('<?= $default_template_dir . $f ?>')"><?= $f ?></a>
            <?php endforeach; ?>
        </div>

        <form method="post" style="margin-top: 25px;">
            <button type="submit" name="sync_update" class="btn-sync-template" onclick="return confirm('确定要将默认模板文件同步覆盖到所有相册目录吗？此操作不可逆！');">
                🔄 同步更新相册模板
            </button>
        </form>

        <h4>编辑器：</h4>
        <span id="currentFile">请选择文件</span>
        <form method="post">
            <input type="hidden" name="action" value="edit_file_content">
            <input type="hidden" name="filename" id="filename" value="<?= htmlspecialchars($last_file_edited) ?>">
            <textarea name="content" id="content" placeholder="// 从上方选择文件以载入其源码流..."></textarea><br><br>
            <button type="submit" class="btn-save">保存当前文件</button>
        </form>
    </div>

</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
    function loadFile(filename) {
        $.get('load_file.php', { filename: filename }, function(data) {
            $('#content').val(data);
            $('#filename').val(filename);
            
            // 提取纯文件名以及所在目录名用于友好展示
            var baseName = filename.replace(/^.*[\\\/]/, '');
            var dirName = filename.includes('defaul') ? '[默认模板]' : '[系统文件]';
            $('#currentFile').text('正在编辑：' + dirName + ' ' + baseName);
        });
    }

    // 页面初次加载时，保持上一次被编辑文件的回显激活状态
    $(document).ready(function() {
        var lastEdited = $('#filename').val();
        if (lastEdited) {
            loadFile(lastEdited);
        }
    });
</script>

<?php if(!empty($userMessage)): ?>
<script>
    Swal.fire({
        title: '<?= $userMessageType === "success" ? "操作成功" : "操作提示" ?>',
        text: '<?= addslashes($userMessage) ?>',
        icon: '<?= $userMessageType ?>',
        confirmButtonText: '确定',
        confirmButtonColor: '#007bff'
    });
</script>
<?php endif; ?>

</body>
</html>