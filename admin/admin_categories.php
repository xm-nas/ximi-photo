<?php
// admin_categories.php
session_start(); // 确保这是第一个
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug: 打印会话信息在包含 header.php 之前
error_log("admin_categories.php: SESSION_ID: " . session_id() . ", Before header.php include. SESSION: " . print_r($_SESSION, true));

// 检查用户是否已登录 (如果 header.php 没有处理，这里保留)
if (empty($_SESSION['logged_in_admin']) || $_SESSION['logged_in_admin'] !== true) {
    error_log("admin_categories.php: Login check failed! Redirecting to login.php. Current SESSION: " . print_r($_SESSION, true));
    header('Location: login.php');
    exit();
}

// 记录管理员登录状态 (如果 header.php 没有处理，这里保留)
$_SESSION['admin_logged_in'] = true;

$page_title="分类管理";

define('SETING_FILE', __DIR__ . '/../index_src/ini/seting.php'); 
define('USER_DIR', __DIR__ . '/../user/'); // 相册存放根目录

if (file_exists(SETING_FILE)) {
    include(SETING_FILE);
}

if (!isset($class) || !is_array($class)) {
    $class = [];
}
if (!isset($config_home) || !is_array($config_home)) {
    $config_home = [];
}

// ==========================================
// 🚀 核心扩展：扫描物理目录并检测“未分类”相册
// ==========================================
$all_physical_albums = []; // 存放所有扫描到的有效物理相册数据 [目录名 => 标题]
$unclassified_albums = []; // 存放未归类的相册数据

if (is_dir(USER_DIR)) {
    $dirs = scandir(USER_DIR);
    // 收集所有已绑定的物理目录名，用于去重和查漏
    $bound_dirs = [];
    foreach ($class as $catName => $albums) {
        if (is_array($albums)) {
            foreach ($albums as $bTitle => $bDir) {
                $bound_dirs[trim($bDir)] = $catName;
            }
        }
    }

    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        $ini_path = USER_DIR . $dir . '/ini.php';
        
        if (is_dir(USER_DIR . $dir) && file_exists($ini_path)) {
            // 局部作用域引入 ini.php 避免变量污染
            $config = [];
            include($ini_path);
            
            if (isset($config['title'])) {
                $album_title = $config['title'];
                $all_physical_albums[$dir] = $album_title;
                
                // 如果这个目录没有在任何分类中出现，则标记为未分类
                if (!isset($bound_dirs[$dir])) {
                    $unclassified_albums[$dir] = $album_title;
                }
            }
        }
    }
}

$userMessage = '';
$userMessageType = '';

if (isset($_GET['msg']) && isset($_GET['type'])) {
    $userMessage = htmlspecialchars($_GET['msg']);
    $userMessageType = htmlspecialchars($_GET['type']);
}

// ==========================================
// 核心逻辑：添加新分类
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $new_cat = trim($_POST['new_category_name']);
    if (!empty($new_cat)) {
        if (!isset($class[$new_cat])) {
            $class[$new_cat] = []; 
            if (writeConfig($class, $config_home)) {
                header("Location: admin_categories.php?msg=" . urlencode("分类 [{$new_cat}] 添加成功！") . "&type=success");
                exit();
            } else {
                $userMessage = "写入配置文件失败，请检查权限。"; $userMessageType = "error";
            }
        } else {
            $userMessage = "分类已存在！"; $userMessageType = "error";
        }
    } else {
        $userMessage = "分类名称不能为空！"; $userMessageType = "error";
    }
}

// ==========================================
// 核心逻辑：删除整个分类
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'delete_category' && isset($_GET['name'])) {
    $cat_to_delete = trim($_GET['name']);
    if (isset($class[$cat_to_delete])) {
        unset($class[$cat_to_delete]);
        if (writeConfig($class, $config_home)) {
            header("Location: admin_categories.php?msg=" . urlencode("分类 [{$cat_to_delete}] 已删除！") . "&type=success");
            exit();
        } else {
            $userMessage = "写入配置文件失败。"; $userMessageType = "error";
        }
    }
}

// ==========================================
// 核心逻辑：编辑分类名称
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_category_name') {
    $old_cat = trim($_POST['old_category_name']);
    $new_cat = trim($_POST['new_category_name']);
    if (!empty($new_cat) && $old_cat !== $new_cat) {
        if (isset($class[$old_cat])) {
            if (!isset($class[$new_cat])) {
                $class[$new_cat] = $class[$old_cat];
                unset($class[$old_cat]);
                if (writeConfig($class, $config_home)) {
                    header("Location: admin_categories.php?msg=" . urlencode("分类名已从 [{$old_cat}] 修改为 [{$new_cat}]") . "&type=success");
                    exit();
                } else {
                    $userMessage = "写入配置文件失败。"; $userMessageType = "error";
                }
            } else {
                $userMessage = "目标分类名已存在！"; $userMessageType = "error";
            }
        }
    }
}

// ==========================================
// 核心逻辑：在一个分类下绑定已有相册
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bind_album') {
    $current_cat = trim($_POST['category']);
    $album_title = trim($_POST['album_title']);
    $album_dir = trim($_POST['album_dir']);

    if (empty($album_title) || empty($album_dir)) {
        $userMessage = "相册标题和目录名称不能为空！"; $userMessageType = "error";
    } else {
        if (isset($class[$current_cat])) {
            if (!isset($class[$current_cat][$album_title])) {
                $class[$current_cat][$album_title] = $album_dir;
                if (writeConfig($class, $config_home)) {
                    header("Location: admin_categories.php?msg=" . urlencode("相册 [{$album_title}] 成功绑定到分类 [{$current_cat}]！") . "&type=success");
                    exit();
                } else {
                    $userMessage = "写入配置文件失败。"; $userMessageType = "error";
                }
            } else {
                $userMessage = "该分类下已存在同名相册标题！"; $userMessageType = "error";
            }
        }
    }
}

// ==========================================
// 核心逻辑：编辑相册关联关系
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_album') {
    $current_cat = trim($_POST['category']);
    $old_title = trim($_POST['old_album_title']);
    $new_title = trim($_POST['new_album_title']);
    $new_dir = trim($_POST['new_album_dir']);

    if (empty($new_title) || empty($new_dir)) {
        $userMessage = "相册标题和目录名称不能为空！"; $userMessageType = "error";
    } else {
        if (isset($class[$current_cat]) && isset($class[$current_cat][$old_title])) {
            unset($class[$current_cat][$old_title]);
            $class[$current_cat][$new_title] = $new_dir;
            if (writeConfig($class, $config_home)) {
                header("Location: admin_categories.php?msg=" . urlencode("相册信息更新成功！") . "&type=success");
                exit();
            } else {
                $userMessage = "写入配置文件失败。"; $userMessageType = "error";
            }
        }
    }
}

// ==========================================
// 核心逻辑：移除相册归类
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'delete_album' && isset($_GET['category']) && isset($_GET['title'])) {
    $cat = trim($_GET['category']);
    $title = trim($_GET['title']);
    if (isset($class[$cat]) && isset($class[$cat][$title])) {
        unset($class[$cat][$title]);
        if (writeConfig($class, $config_home)) {
            header("Location: admin_categories.php?msg=" . urlencode("已从 [{$cat}] 中移除相册 [{$title}]") . "&type=success");
            exit();
        } else {
            $userMessage = "写入配置文件失败。"; $userMessageType = "error";
        }
    }
}

/**
 * 🎯 占位符切片数据回写机制（彻底修复 `]` 变 `);` 错乱问题）
 */
function writeConfig($class_data, $config_home_data) {
    if (!file_exists(SETING_FILE)) return false;
    $content = @file_get_contents(SETING_FILE);
    if ($content === false) return false;

    $up_block = '';
    $up_pattern = '/\$config_up\s*=\s*\[.*?\s*\]\s*;|\$config_up\s*=\s*array\s*\(.*?\)\s*;/s';
    if (preg_match($up_pattern, $content, $matches)) {
        $up_block = $matches[0];
    } else {
        global $config_up;
        $up_block = '$config_up = ' . var_export($config_up ?? ['user_xm'=>'ximi','pass_xm'=>'admin'], true) . ';';
    }

    $class_str = '$class = ' . var_export($class_data, true) . ';';
    $home_str = '$config_home = ' . var_export($config_home_data, true) . ';';

    $clean_arrays = "<?php\n\n" . $class_str . "\n\n" . $home_str . "\n\n?>";
    $clean_arrays = preg_replace('/array\s*\(\s*\)/s', '[]', $clean_arrays);
    $clean_arrays = str_replace("array (\n", "[\n", $clean_arrays);
    $clean_arrays = preg_replace('/^(\s*)\)(,?)$/m', '$1]$2', $clean_arrays);
    $clean_arrays = preg_replace('/^(\s*)\);$/m', '$1];', $clean_arrays);

    $clean_arrays = str_replace(["<?php", "?>"], "", $clean_arrays);
    $clean_arrays = trim($clean_arrays);

    $final_file_content = "<?php\n" .
                          "// This file is automatically generated by the config editor.\n" .
                          "// DO NOT EDIT MANUALLY unless you know what you are doing.\n\n" .
                          $clean_arrays . "\n\n" .
                          $up_block . "\n?>";

    return @file_put_contents(SETING_FILE, $final_file_content, LOCK_EX) !== false;
}

include("header.php"); 
?>

<div class="category-manager">
    <?php if (!empty($unclassified_albums)): ?>
        <div class="config-card unclassified-alert-card">
            <h2 class="card-title" style="color: #e67e22;">⚠️ 检测到未分类相册 (<?= count($unclassified_albums) ?>)</h2>
            <p class="empty-text" style="color: #d35400; margin-bottom: 12px;">以下物理目录已存在于 <code>../user/</code> 下，但尚未绑定到任何分类中：</p>
            <div class="unclassified-tags">
                <?php foreach ($unclassified_albums as $uDir => $uTitle): ?>
                    <span class="uc-tag">
                        <strong><?= htmlspecialchars($uTitle) ?></strong> 
                        <code class="dir-code">(<?= htmlspecialchars($uDir) ?>)</code>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="config-card">
        <h2 class="card-title">➕ 添加新分类</h2>
        <form method="post" class="add-cat-form">
            <input type="hidden" name="action" value="add_category">
            <div class="form-group inline-group">
                <input type="text" name="new_category_name" placeholder="输入新分类名称，例如: 街头纪实" required>
                <button type="submit" class="btn-submit">新建分类</button>
            </div>
        </form>
    </div>

    <h2 class="section-main-title">📁 当前分类结构映射矩阵</h2>

    <?php if (empty($class)): ?>
        <div class="config-card" style="text-align: center; color: #7f8c8d; padding: 40px 20px;">
            目前尚未创建任何分类，请在上方添加您的第一个相册分类。
        </div>
    <?php else: ?>
        <?php foreach ($class as $categoryName => $albums): ?>
            <div class="config-card category-block">
                <div class="category-header">
                    <div class="cat-left">
                        <span class="folder-icon">📂</span>
                        <h3 class="cat-name"><?= htmlspecialchars($categoryName) ?></h3>
                        <span class="album-count-badge"><?= count($albums) ?> 个相册</span>
                    </div>
                    <div class="cat-actions">
                        <button class="btn-action btn-bind" onclick="openBindAlbumModal('<?= addslashes($categoryName) ?>')">➕ 绑定相册</button>
                        <button class="btn-action btn-edit" onclick="openEditCategoryModal('<?= addslashes($categoryName) ?>')">📝 重命名</button>
                        <a href="admin_categories.php?action=delete_category&name=<?= urlencode($categoryName) ?>" class="btn-action btn-delete" onclick="return confirm('确定要删除 [<?= htmlspecialchars($categoryName) ?>] 分类吗？其中的相册关联将被解除。')">❌ 删除分类</a>
                    </div>
                </div>

                <div class="albums-list">
                    <?php if (empty($albums)): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <p class="empty-text">该分类下暂无关联相册。可在右侧直接手动绑定，或者前往相册控制台编辑。</p>
                            <button class="btn-submit" style="padding: 6px 14px; font-size: 13px;" onclick="openBindAlbumModal('<?= addslashes($categoryName) ?>')">立即绑定相册</button>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>展示标题 (键)</th>
                                    <th>文件夹目录名 (值)</th>
                                    <th style="text-align: right;">操作行为</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($albums as $albumTitle => $albumDir): ?>
                                    <tr>
                                        <td class="font-weight-600"><?= htmlspecialchars($albumTitle) ?></td>
                                        <td><code class="dir-code">/user/<?= htmlspecialchars($albumDir) ?>/</code></td>
                                        <td style="text-align: right;">
                                            <button class="btn-table-action edit" onclick="openEditAlbumModal('<?= addslashes($categoryName) ?>', '<?= addslashes($albumTitle) ?>', '<?= addslashes($albumDir) ?>')">改</button>
                                            <a href="admin_categories.php?action=delete_album&category=<?= urlencode($categoryName) ?>&title=<?= urlencode($albumTitle) ?>" class="btn-table-action del" onclick="return confirm('确定从该分类中移除此相册映射吗？')">移</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="bindAlbumModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>➕ 绑定相册到分类</h3>
            <span class="close-btn" onclick="closeBindAlbumModal()">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="bind_album">
            <input type="hidden" name="category" id="bindCategory">
            
            <div class="form-group">
                <label>选择未分类的物理目录 (Folder Dir)</label>
                <select name="album_dir" id="bindAlbumDir" class="form-select" onchange="syncBindTitle(this)" required>
                    <option value="">-- 请选择物理目录 --</option>
                    <?php foreach ($all_physical_albums as $dirName => $iniTitle): ?>
                        <option value="<?= htmlspecialchars($dirName) ?>" data-title="<?= htmlspecialchars($iniTitle) ?>">
                            <?= htmlspecialchars($dirName) ?> (<?= htmlspecialchars($iniTitle) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label>前台显式标题 (将随目录选择自动变动，可手动微调)</label>
                <input type="text" name="album_title" id="bindAlbumTitle" placeholder="例如: 2026日本樱花季" required>
            </div>

            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn-action" style="background:#e0e0e0; color:#333; margin-right:8px;" onclick="closeBindAlbumModal()">取消</button>
                <button type="submit" class="btn-submit">确认绑定</button>
            </div>
        </form>
    </div>
</div>

<div id="editAlbumModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>⚙️ 调整相册映射节点</h3>
            <span class="close-btn" onclick="closeEditAlbumModal()">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="edit_album">
            <input type="hidden" name="category" id="editCategory">
            <input type="hidden" name="old_album_title" id="oldAlbumTitle">
            
            <div class="form-group">
                <label>映射物理目录名 (Folder Dir Value)</label>
                <select name="new_album_dir" id="newAlbumDir" class="form-select" onchange="syncEditTitle(this)" required>
                    <?php foreach ($all_physical_albums as $dirName => $iniTitle): ?>
                        <option value="<?= htmlspecialchars($dirName) ?>" data-title="<?= htmlspecialchars($iniTitle) ?>">
                            <?= htmlspecialchars($dirName) ?> (<?= htmlspecialchars($iniTitle) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label>前台显式标题 (Title Key)</label>
                <input type="text" name="new_album_title" id="newAlbumTitle" required>
            </div>

            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn-action" style="background:#e0e0e0; color:#333; margin-right:8px;" onclick="closeEditAlbumModal()">取消</button>
                <button type="submit" class="btn-submit">保存变更</button>
            </div>
        </form>
    </div>
</div>

<div id="editCategoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📝 重命名分类</h3>
            <span class="close-btn" onclick="closeEditCategoryModal()">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="edit_category_name">
            <input type="hidden" name="old_category_name" id="editOldCategoryName">
            
            <div class="form-group">
                <label>新分类名称</label>
                <input type="text" name="new_category_name" id="editNewCategoryName" required>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn-action" style="background:#e0e0e0; color:#333; margin-right:8px;" onclick="closeEditCategoryModal()">取消</button>
                <button type="submit" class="btn-submit">确认修改</button>
            </div>
        </form>
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
    /* 未分类提示面板毛玻璃视效 */
    .unclassified-alert-card {
        background: rgba(254, 249, 195, 0.45);
        border: 1px solid rgba(234, 179, 8, 0.3);
    }
    .unclassified-tags {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 10px;
    }
    .uc-tag {
        background: rgba(255, 255, 255, 0.7);
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 13px;
        border: 1px solid rgba(234, 179, 8, 0.15);
        color: #444;
    }
    .card-title {
        font-size: 16px;
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 15px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .section-main-title {
        font-size: 16px;
        color: #5c6b73;
        margin: 35px 0 15px 5px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .inline-group {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .inline-group input[type="text"] {
        flex: 1;
    }
    .form-group label {
        display: block;
        font-size: 13px;
        color: #5c6b73;
        margin-bottom: 8px;
        font-weight: 500;
    }
    .form-group input[type="text"], .form-select {
        width: 100%;
        padding: 11px 15px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 9px;
        background: rgba(255, 255, 255, 0.85);
        font-size: 14px;
        color: #333;
        outline: none;
        box-sizing: border-box;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .form-select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23555' viewBox='0 0 16 16'><path d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/></svg>");
        background-repeat: no-repeat;
        background-position: calc(100% - 15px) center;
        padding-right: 40px;
    }
    .form-group input:focus, .form-select:focus {
        border-color: #5c7cfa;
        box-shadow: 0 0 0 3px rgba(92, 124, 250, 0.15);
        background: #ffffff;
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
        white-space: nowrap;
    }
    .btn-submit:hover {
        opacity: 0.95;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.25);
    }
    .category-block {
        padding: 0;
        overflow: hidden;
    }
    .category-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 25px;
        background: rgba(255, 255, 255, 0.4);
        border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    }
    .cat-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .folder-icon {
        font-size: 18px;
    }
    .cat-name {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #2c3e50;
    }
    .album-count-badge {
        font-size: 11px;
        background: rgba(79, 70, 229, 0.1);
        color: #4f46e5;
        padding: 2px 8px;
        border-radius: 20px;
        font-weight: 500;
    }
    .cat-actions {
        display: flex;
        gap: 10px;
    }
    .btn-action {
        border: none;
        padding: 6px 14px;
        font-size: 13px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.25s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    .btn-bind {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }
    .btn-bind:hover {
        background: #10b981;
        color: #fff;
    }
    .btn-edit {
        background: rgba(92, 124, 250, 0.1);
        color: #5c7cfa;
    }
    .btn-edit:hover {
        background: #5c7cfa;
        color: #fff;
    }
    .btn-delete {
        background: rgba(250, 82, 82, 0.1);
        color: #fa5252;
    }
    .btn-delete:hover {
        background: #fa5252;
        color: #fff;
    }
    .albums-list {
        padding: 20px 25px;
    }
    .empty-text {
        font-size: 13px;
        color: #8a9a9a;
        margin: 5px 0;
        font-style: italic;
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
    .admin-table tr:last-child td {
        border-bottom: none;
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
    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.25);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }
    .modal-content {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        margin: 12% auto;
        padding: 25px;
        width: 100%;
        max-width: 460px;
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        box-sizing: border-box;
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding-bottom: 10px;
    }
    .modal-header h3 {
        margin: 0;
        font-size: 16px;
        color: #2c3e50;
        font-weight: 600;
    }
    .close-btn {
        color: #aaa;
        font-size: 22px;
        font-weight: 300;
        cursor: pointer;
        line-height: 1;
        transition: color 0.2s;
    }
    .close-btn:hover {
        color: #333;
    }

    @media (max-width: 576px) {
        .category-manager {
            padding: 5px 10px 15px 10px;
        }
        .config-card {
            padding: 15px;
        }
        .category-header {
            padding: 15px;
        }
        .albums-list {
            padding: 15px 10px;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

<script>
    const editAlbumModal = document.getElementById('editAlbumModal');
    const editCategoryInput = document.getElementById('editCategory');
    const oldAlbumTitleInput = document.getElementById('oldAlbumTitle');
    const newAlbumTitleInput = document.getElementById('newAlbumTitle');
    const newAlbumDirSelect = document.getElementById('newAlbumDir');

    const editCategoryModal = document.getElementById('editCategoryModal');
    const editOldCategoryNameInput = document.getElementById('editOldCategoryName');
    const editNewCategoryNameInput = document.getElementById('editNewCategoryName');

    const bindAlbumModal = document.getElementById('bindAlbumModal');
    const bindCategoryInput = document.getElementById('bindCategory');
    const bindAlbumTitleInput = document.getElementById('bindAlbumTitle');
    const bindAlbumDirSelect = document.getElementById('bindAlbumDir');

    // 选择物理目录时，自动提取其内置的 ini.php title 到输入框中
    function syncBindTitle(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const defaultTitle = selectedOption.getAttribute('data-title');
        if (defaultTitle) {
            bindAlbumTitleInput.value = defaultTitle;
        } else {
            bindAlbumTitleInput.value = '';
        }
    }

    function syncEditTitle(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const defaultTitle = selectedOption.getAttribute('data-title');
        if (defaultTitle) {
            newAlbumTitleInput.value = defaultTitle;
        }
    }

    function openBindAlbumModal(category) {
        bindCategoryInput.value = category;
        bindAlbumDirSelect.value = '';
        bindAlbumTitleInput.value = '';
        bindAlbumModal.style.display = 'block';
    }

    function closeBindAlbumModal() {
        bindAlbumModal.style.display = 'none';
    }

    function openEditAlbumModal(category, oldTitle, oldDir) {
        editCategoryInput.value = category;
        oldAlbumTitleInput.value = oldTitle;
        newAlbumTitleInput.value = oldTitle;
        newAlbumDirSelect.value = oldDir; // 下拉菜单自动定位到当前绑定的目录
        editAlbumModal.style.display = 'block';
    }

    function closeEditAlbumModal() {
        editAlbumModal.style.display = 'none';
    }

    function openEditCategoryModal(oldCategoryName) {
        editOldCategoryNameInput.value = oldCategoryName;
        editNewCategoryNameInput.value = oldCategoryName; 
        editCategoryModal.style.display = 'block';
    }

    function closeEditCategoryModal() {
        editCategoryModal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == editAlbumModal) {
            closeEditAlbumModal();
        }
        if (event.target == editCategoryModal) {
            closeEditCategoryModal();
        }
        if (event.target == bindAlbumModal) {
            closeBindAlbumModal();
        }
    }
</script>
</body>
</html>
<?php include("footer.php"); ?>