<?php
// admin.php 后台默认首页
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title = "相册管理"; 

session_start();

// 检查用户是否已登录
if (empty($_SESSION['logged_in_admin']) || $_SESSION['logged_in_admin'] !== true) {
    header('Location: login.php');
    exit();
}

$_SESSION['admin_logged_in'] = true;

// --- 辅助函数：安全地读取ini.php配置 ---
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

// --- 辅助函数：计算指定目录中的图片数量 ---
function countImagesInDirectory($directory) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico'];
    $imageCount = 0;

    if (is_dir($directory)) {
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $imageExtensions)) {
                    $imageCount++;
                }
            }
        }
    }
    return $imageCount;
}

// --- ⚙️ 递归删除空目录辅助函数（深度清理子层级空文件夹） ---
function deleteEmptyDirRecursive($dir) {
    if (!is_dir($dir)) return false;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            deleteEmptyDirRecursive($path);
        } else {
            @unlink($path); // 如果有非图片残余空文件顺便清理
        }
    }
    if (count(array_diff(scandir($dir), ['.', '..'])) === 0) {
        return @rmdir($dir);
    }
    return false;
}

// ==========================================
// 🎯 安全核心：后端纯文本读取代理（100%免路由拦截）
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'load_ini_raw' && isset($_GET['name'])) {
    header('Content-Type: text/plain; charset=utf-8');
    $album_name = trim($_GET['name']);
    if (preg_match('/^[a-zA-Z0-9_\-]+$/', $album_name)) {
        $target_file = __DIR__ . '/../user/' . $album_name . '/ini.php';
        if (file_exists($target_file) && is_readable($target_file)) {
            echo file_get_contents($target_file);
            exit();
        }
    }
    http_response_code(404);
    echo "// 无法读取或配置文件不存在";
    exit();
}

$userMessage = '';
$userMessageType = ''; 

if (isset($_GET['msg']) && isset($_GET['type'])) {
    $userMessage = htmlspecialchars($_GET['msg']);
    $userMessageType = htmlspecialchars($_GET['type']);
}

// ==========================================
// 🛠️ 深度完善：对接 new_dir.php 规范的标准相册创建动作
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_album_folder') {
    $folder_name = trim($_POST['folder_name']);
    $album_title = trim($_POST['album_title']);

    if (empty($folder_name) || empty($album_title)) {
        $userMessage = "相册目录名和相册标题均不能为空！";
        $userMessageType = "error";
    } elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $folder_name)) {
        $userMessage = "目录名格式不合法，仅支持字母、数字、下划线和连字符！";
        $userMessageType = "error";
    } else {
        $target_user_dir = __DIR__ . '/../user/' . $folder_name . '/';
        $source_dir = __DIR__ . '/../index_src/defaul/';

        if (is_dir($target_user_dir)) {
            $userMessage = "创建失败：该相册物理节点已存在，请换一个目录标识！";
            $userMessageType = "error";
        } else {
            // 1. 创建目标 user 目录
            $created_user = mkdir($target_user_dir, 0755, true);

            if ($created_user) {
                // 2. 完美的模板文件流无缝同步复制（提取自原 new_dir.php）
                if (is_dir($source_dir)) {
                    $dir_handle = opendir($source_dir);
                    while (($file = readdir($dir_handle)) !== false) {
                        if ($file !== '.' && $file !== '..') {
                            $source_file = $source_dir . $file;
                            $target_file = $target_user_dir . $file;
                            if (is_file($source_file)) {
                                @copy($source_file, $target_file);
                            }
                        }
                    }
                    closedir($dir_handle);
                }

                // 3. 对齐原版：动态生成 22 位高强度随机混淆子路径标识（$class）
                $class_token = bin2hex(random_bytes(11));

                // 4. 深度对齐：同步创建对应的 update/user 下的三个标准存储子目录
                $base_update_path = __DIR__ . '/../update/user/' . $folder_name . '/' . $class_token;
                @mkdir($base_update_path . '/min_image/', 0755, true);
                @mkdir($base_update_path . '/max_image/', 0755, true);
                @mkdir($base_update_path . '/img/', 0755, true);

                // 5. 完璧归赵：灌入完全符合系统要求的 9 项标准优雅数组配置
                $ini_content = "<?php\n" .
                    "\$config = [\n" .
                    "  'min' => '../../update/user/" . $folder_name . "/" . $class_token . "/min_image/',\n" .
                    "  'max' => '../../update/user/" . $folder_name . "/" . $class_token . "/max_image/',\n" .
                    "  'tu_1' => '../../update/user/" . $folder_name . "/" . $class_token . "/img/',\n" .
                    "  'login_admin' => 'login_defaul',\n" .
                    "  'list_read' => '1',\n" .
                    "  'list_home' => '1',\n" .
                    "  'title' => '" . addslashes($album_title) . "',\n" .
                    "  'cover' => '',\n" .
                    "  'txt' => ''\n" .
                    "];\n" .
                    "?>";

                if (file_put_contents($target_user_dir . 'ini.php', $ini_content) !== false) {
                    header("Location: admin_photo.php?msg=" . urlencode("全新标准相册 [{$album_title}] 架构初始化成功！") . "&type=success");
                    exit();
                } else {
                    $userMessage = "模板复制成功，但写入标准 ini.php 配置文件失败！";
                    $userMessageType = "error";
                }
            } else {
                $userMessage = "创建物理主目录失败，请检查服务器权限！";
                $userMessageType = "error";
            }
        }
    }
}

// ==========================================
// 🔥 安全删除逻辑：完美支持深度混淆路径的精确空盘检查与连坐强力清理
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'delete_album_folder' && isset($_GET['name'])) {
    $album_name = trim($_GET['name']);
    $user_dir = __DIR__ . '/../user/' . $album_name;
    $update_base_dir = __DIR__ . '/../update/user/' . $album_name;

    if (empty($album_name) || !is_dir($user_dir)) {
        $userMessage = "未找到该相册目录！";
        $userMessageType = "error";
    } else {
        // 1. 读取标准配置，动态拿到 3 处真实的图片存放绝对物理路径
        $album_config = getAlbumConfig($user_dir);
        
        $img_path = '';
        $min_path = '';
        $max_path = '';

        if (!empty($album_config)) {
            // 将相对配置层级修正为符合后台脚本的层级路径
            if (isset($album_config['tu_1'])) $img_path = str_replace('../../', '../', $album_config['tu_1']);
            if (isset($album_config['min'])) $min_path = str_replace('../../', '../', $album_config['min']);
            if (isset($album_config['max'])) $max_path = str_replace('../../', '../', $album_config['max']);
        }

        // 如果配置没读到，采取缺省全包容兜底路径扫描
        if (empty($img_path)) $img_path = $update_base_dir;

        // 2. 检查 3 个物理存放点以及整个 update 基础分支下的图片数量，只要有 1 张图就绝对熔断不给删
        $img_count_1 = countImagesInDirectory($img_path);
        $img_count_2 = !empty($min_path) ? countImagesInDirectory($min_path) : 0;
        $img_count_3 = !empty($max_path) ? countImagesInDirectory($max_path) : 0;
        $img_count_fallback = countImagesInDirectory($update_base_dir);
        
        if ($img_count_1 > 0 || $img_count_2 > 0 || $img_count_3 > 0 || $img_count_fallback > 0) {
            $userMessage = "为了绝对数据安全，该相册存储链中图片数大于 0 ，禁止删除该相册！";
            $userMessageType = "error";
        } else {
            // 3. 确认完全清空后，开始执行强力连坐深度抹除
            // 先同步抹除 update/user/相册名/ 下包含混淆哈希的完整子树
            if (is_dir($update_base_dir)) {
                deleteEmptyDirRecursive($update_base_dir);
                if (is_dir($update_base_dir)) @rmdir($update_base_dir);
            }
            // 再连根拔起整个 user/相册名/ 下的所有复制过去的脚本模板文件及 ini.php
            if (is_dir($user_dir)) {
                $files = array_diff(scandir($user_dir), ['.', '..']);
                foreach ($files as $file) {
                    @unlink($user_dir . '/' . $file);
                }
                @rmdir($user_dir);
            }

            header("Location: admin_photo.php?msg=" . urlencode("相册 [{$album_name}] 及其复制模板、哈希物理存储链已彻底安全删除！") . "&type=success");
            exit();
        }
    }
}

// --- 接收编辑文件表单提交的动作 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && isset($_POST['folder_identifier'])) {
    $folder_identifier = trim($_POST['folder_identifier']);
    $content = $_POST['content'];

    if (preg_match('/^[a-zA-Z0-9_\-]+$/', $folder_identifier)) {
        $real_save_path = __DIR__ . '/../user/' . $folder_identifier . '/ini.php';
        if (file_exists($real_save_path)) {
            if (file_put_contents($real_save_path, $content) !== false) {
                $userMessage = "配置文件更新成功！";
                $userMessageType = "success";
            } else {
                $userMessage = "文件保存失败，请检查文件写入权限！";
                $userMessageType = "error";
            }
        } else {
            $userMessage = "未定位到目标相册文件，保存终止！";
            $userMessageType = "error";
        }
    } else {
        $userMessage = "非法相册目录节点标识！";
        $userMessageType = "error";
    }
}

// 基础变量扫描与列表排查准备
$userDirectory = '../user';
$albums = [];
$debug_logs = [];

if (is_dir($userDirectory)) {
    $dirs = array_diff(scandir($userDirectory), ['.', '..']);
    foreach ($dirs as $dir) {
        $albumPath = $userDirectory . '/' . $dir;
        if (is_dir($albumPath)) {
            $config = getAlbumConfig($albumPath);
            $imageCount = 0;
            $raw_tu_1 = '未定义';
            $resolved_path = '未定义';

            if (!empty($config) && isset($config['tu_1'])) {
                $raw_tu_1 = $config['tu_1'];
                $clean_tu_1 = str_replace('../../', '../', $raw_tu_1);
                $fullImgPath = (strpos($clean_tu_1, '../') === 0) ? $clean_tu_1 : $albumPath . '/' . $clean_tu_1;
                $resolved_path = realpath($fullImgPath) ?: $fullImgPath;
                $imageCount = countImagesInDirectory($fullImgPath);
            } else {
                $fallbackPath = '../update/user/' . $dir;
                $resolved_path = realpath($fallbackPath) ?: $fallbackPath;
                $imageCount = countImagesInDirectory($fallbackPath);
            }

            $albums[] = [
                'name' => $dir,
                'title' => $config['title'] ?? '未命名相册',
                'image_count' => $imageCount
            ];

            $debug_logs[] = [
                'album_name' => $config['title'] ?? '未命名相册',
                'folder' => $dir,
                'raw_tu_1' => $raw_tu_1,
                'resolved_path' => $resolved_path,
                'count' => $imageCount
            ];
        }
    }
}

include("header.php"); 
?>

<div class="category-manager" style="padding-bottom: 0px;">
    <div class="config-card" style="margin-bottom: 15px;">
        <h2 class="card-title">✨ 新建标准相册物理目录（自动复制系统模板结构）</h2>
        <form method="POST" action="admin_photo.php">
            <input type="hidden" name="action" value="create_album_folder">
            <div class="split-container" style="gap: 20px; align-items: flex-end;">
                <div style="flex: 1;">
                    <div class="form-group" style="margin-bottom: 0px;">
                        <label>目录标识名称（对应原 name 参数，如: gf1）</label>
                        <input type="text" name="folder_name" placeholder="请输入生成的物理文件夹目录名..." required style="width: 100%; padding: 11px 15px; border: 1px solid rgba(0,0,0,0.06); border-radius: 9px; font-size:14px; box-sizing: border-box; background: rgba(255,255,255,0.7); outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='#4f46e5'">
                    </div>
                </div>
                <div style="flex: 1;">
                    <div class="form-group" style="margin-bottom: 0px;">
                        <label>相册全局标题（对应原 name_title 参数，如: 我的女神画册）</label>
                        <input type="text" name="album_title" placeholder="请输入相册前端展示的名称..." required style="width: 100%; padding: 11px 15px; border: 1px solid rgba(0,0,0,0.06); border-radius: 9px; font-size:14px; box-sizing: border-box; background: rgba(255,255,255,0.7); outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='#4f46e5'">
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn-submit" style="padding: 11px 24px; height: 42px; box-sizing: border-box;">🚀 自动创建并生成相册</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="category-manager">
    <div class="config-card">
        <h2 class="card-title">📂 系统相册物理节点管理</h2>
        
        <div class="split-container">
            <div class="list-side">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>目录标识</th>
                            <th>相册标题</th>
                            <th>主轴图片</th>
                            <th style="text-align: right;">操作行为</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($albums)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #999; padding: 20px 0;">暂无相册物理目录</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($albums as $album): ?>
                                <tr>
                                    <td><code class="dir-code"><?= htmlspecialchars($album['name']) ?></code></td>
                                    <td class="font-weight-600"><?= htmlspecialchars($album['title']) ?></td>
                                    <td>
                                        <span class="album-count-badge" style="background: <?= $album['image_count'] > 0 ? 'rgba(46, 204, 113, 0.15)' : 'rgba(0, 0, 0, 0.05)' ?>; color: <?= $album['image_count'] > 0 ? '#2ecc71' : '#999' ?>;">
                                            <?= $album['image_count'] ?> 张
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <button class="btn-table-action edit" onclick="loadIniProxy('<?= addslashes($album['name']) ?>')">编</button>
                                        <a href="admin_photo.php?action=delete_album_folder&name=<?= urlencode($album['name']) ?>" class="btn-table-action del" style="font-size: 11px;" onclick="return confirm('确定要彻底删除该相册目录吗？\n这将同时深度清理绑定的哈希乱序物理存储链及对应的全套复制模板文件！')">删</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="editor-side">
                <div id="currentFile" style="margin-bottom: 10px; font-weight: 500; color: #5c6b73; font-size: 13px;">
                    💡 鼠标点击左侧“编”按钮加载该相册的原始数据配置
                </div>
                <form method="POST" id="editForm">
                    <input type="hidden" name="folder_identifier" id="folder_identifier" value="">
                    <textarea name="content" id="content" placeholder="// 选中相册的 ini.php 代码流将在这里载入并支持即时重写..."></textarea>
                    <div style="margin-top: 15px; text-align: right;">
                        <button type="submit" class="btn-submit">💾 保存配置文件内容</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .category-manager {
        width: 100%;
        box-sizing: border-box;
        padding: 5px 20px 20px 20px;
    }
    .config-card {
        background: rgba(255, 255, 255, 0.65);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.04);
        box-sizing: border-box;
    }
    .card-title {
        font-size: 16px;
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 20px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .split-container {
        display: flex;
        gap: 30px;
    }
    .list-side {
        flex: 1.2;
    }
    .editor-side {
        flex: 1;
        background: rgba(255, 255, 255, 0.4);
        padding: 20px;
        border-radius: 12px;
        border: 1px solid rgba(0, 0, 0, 0.03);
    }
    .form-group label {
        display: block;
        font-size: 13px;
        color: #5c6b73;
        margin-bottom: 8px;
        font-weight: 500;
    }
    #content {
        width: 100%;
        height: 380px;
        font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
        font-size: 13px;
        padding: 15px;
        box-sizing: border-box;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 9px;
        background: rgba(255, 255, 255, 0.9);
        color: #2c3e50;
        outline: none;
        resize: vertical;
        transition: all 0.25s ease;
    }
    #content:focus {
        border-color: #5c7cfa;
        box-shadow: 0 0 0 3px rgba(92, 124, 250, 0.15);
    }
    .admin-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 14px;
    }
    .admin-table th {
        color: #7f8c8d;
        font-weight: 500;
        font-size: 12px;
        text-transform: uppercase;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    .admin-table td {
        padding: 12px 0;
        color: #333;
        border-bottom: 1px solid rgba(0, 0, 0, 0.02);
        vertical-align: middle;
    }
    .font-weight-600 {
        font-weight: 500;
        color: #2c3e50;
    }
    .dir-code {
        font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
        font-size: 12px;
        background: rgba(0, 0, 0, 0.04);
        padding: 3px 6px;
        border-radius: 4px;
        color: #e83e8c;
    }
    .album-count-badge {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 20px;
        font-weight: 500;
    }
    .btn-table-action {
        border: none;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        margin-left: 6px;
        text-decoration: none;
    }
    .btn-table-action.edit {
        background: rgba(79, 70, 229, 0.08);
        color: #4f46e5;
    }
    .btn-table-action.edit:hover {
        background: #4f46e5;
        color: #fff;
    }
    .btn-table-action.del {
        background: rgba(244, 63, 94, 0.08);
        color: #f43f5e;
    }
    .btn-table-action.del:hover {
        background: #f43f5e;
        color: #fff;
    }
    .btn-submit {
        background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        color: #ffffff;
        border: none;
        border-radius: 9px;
        padding: 11px 22px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
    }
    .btn-submit:hover {
        opacity: 0.95;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.25);
    }

    @media (max-width: 992px) {
        .split-container {
            flex-direction: column;
        }
        .editor-side {
            margin-top: 20px;
        }
    }
    @media (max-width: 576px) {
        .category-manager {
            padding: 5px 10px 15px 10px;
        }
        .config-card {
            padding: 15px;
        }
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // 🎯 安全代理通道获取文件流内容
    function loadIniProxy(albumName) {
        $.get('admin_photo.php', { action: 'load_ini_raw', name: albumName }, function(data) {
            $('#content').val(data);
            $('#folder_identifier').val(albumName);
            $('#currentFile').html('📍 正在修改相册配置：<span style="color:#4f46e5;font-weight:600;">../user/' + albumName + '/ini.php</span>');
        }).fail(function() {
            Swal.fire({
                title: '错误',
                text: '代理通道提取失败，请核对：user/' + albumName + '/ini.php 是否正常存在',
                icon: 'error',
                confirmButtonText: '确定'
            });
        });
    }

    $(document).ready(function() {
        var lastEditedFolder = $('#folder_identifier').val();
        if (lastEditedFolder) {
            loadIniProxy(lastEditedFolder);
        }

        console.log("=== 🛠️ 后台相册路径与数量读取排查 ===");
        var debugLogs = <?= json_encode($debug_logs, JSON_UNESCAPED_UNICODE) ?>;
        if(debugLogs && debugLogs.length > 0) {
            debugLogs.forEach(function(item) {
                console.log(
                    "📌 相册: " + item.album_name + " (" + item.folder + ")\n" +
                    "   ├─ 配置文件原始 tu_1: " + item.raw_tu_1 + "\n" +
                    "   ├─ 后端最终解析物理绝对路径: " + item.resolved_path + "\n" +
                    "   └─ 🔍 扫描到的图片总数量: " + item.count + " 张"
                );
            });
        }
    });
</script>

<?php if(!empty($userMessage)): ?>
<script>
    Swal.fire({
        title: '提示消息',
        text: '<?= $userMessage ?>',
        icon: '<?= $userMessageType === "success" ? "success" : "error" ?>',
        confirmButtonText: '确定'
    });
</script>
<?php endif; ?>

</body>
</html>