<?php
// admin_whitelist.php - 白名单配置管理（含开关联动版）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title = "白名单管理"; 

session_start();

// 检查用户是否已登录
if (empty($_SESSION['logged_in_admin']) || $_SESSION['logged_in_admin'] !== true) {
    header('Location: login.php');
    exit();
}

// 配置文件路径
$config_file = "../index_src/defaul/functions.php";

$message = '';
$message_type = '';

// 初始化默认配置变量，防止读取失败时无数据
$current_home_url = 'http://192.168.1.10/';
$current_title = '希米的图册';
$current_blacklist = 'img.cd,www.img.cd';
$current_whitelist_status = '0'; // 默认关闭

// --- 1. 先尝试读取当前文件并解析数据 ---
if (file_exists($config_file)) {
    $file_content = file_get_contents($config_file);
    
    // 提取域名函数里的文本
    $current_domains_text = '';
    if (preg_match('/return\s*\[(.*?)\]\s*;/s', $file_content, $matches)) {
        $array_content = $matches[1];
        if (preg_match_all('/[\'"]([^\'"]+)[\'"]/', $array_content, $domain_matches)) {
            $current_domains_text = implode("\n", $domain_matches[1]);
        }
    }

    // 利用正则精准捕获原有数组中的配置项，防止复写时丢失原有数据
    if (preg_match('/\'home_url\'\s*=>\s*[\'"]([^\'"]*)[\'"]/i', $file_content, $m)) { $current_home_url = $m[1]; }
    if (preg_match('/\'title\'\s*=>\s*[\'"]([^\'"]*)[\'"]/i', $file_content, $m)) { $current_title = $m[1]; }
    if (preg_match('/\'admin_blacklist\'\s*=>\s*[\'"]([^\'"]*)[\'"]/i', $file_content, $m)) { $current_blacklist = $m[1]; }
    if (preg_match('/\'admin_whitelist\'\s*=>\s*[\'"]([^\'"]*)[\'"]/i', $file_content, $m)) { $current_whitelist_status = $m[1]; }
} else {
    $current_domains_text = '';
    $message = "提示：未找到原配置文件，保存时将自动创建新文件。";
    $message_type = "error";
}

// --- 2. 处理表单提交（保存修改） ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['whitelist_domains'])) {
    // A. 接收并格式化域名白名单
    $input_text = $_POST['whitelist_domains'];
    $lines = explode("\n", str_replace("\r", "", $input_text));
    $clean_domains = [];
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed !== '') {
            $clean_domains[] = $trimmed;
        }
    }
    
    $array_elements = array_map(function($domain) {
        return "'" . addslashes($domain) . "'";
    }, $clean_domains);
    
    $array_string = '[' . implode(', ', $array_elements) . ']';
    
    // B. 获取白名单启用状态开关值
    $admin_whitelist_form = isset($_POST['admin_whitelist']) ? $_POST['admin_whitelist'] : '0';
    if ($admin_whitelist_form !== '1') {
        $admin_whitelist_form = '0';
    }

    // C. 重新组装生成完整的 functions.php 文件内容结构
    $new_file_content = "<?php\n" .
                        "//  ../index_src/defaul/functions.php\n" .
                        "//白名单\n" .
                        "function geturls() {\n" .
                        "    return " . $array_string . ";\n" .
                        "}\n\n" .
                        "\$config_whitelist = array (\n" .
                        "  'home_url' => '" . addslashes($current_home_url) . "',\n" .
                        "  'title' => '" . addslashes($current_title) . "',\n" .
                        "  'admin_blacklist' => '" . addslashes($current_blacklist) . "',\n" .
                        "  'admin_whitelist' => '" . $admin_whitelist_form . "',\n" .
                        ");\n" .
                        "?>";
                        
    // D. 写入文件
    // 确保目标目录和文件可写
    $target_dir = dirname($config_file);
    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0777, true);
    }

    if (is_writable($config_file) || (!file_exists($config_file) && is_writable($target_dir))) {
        if (file_put_contents($config_file, $new_file_content) !== false) {
            $message = "白名单模板配置及启用状态更新成功！ 请手动点击模板管理-同步更新 即可对所有相册生效";
            $message_type = "success";
            
            // 实时同步当前内存数据，避免页面刷新回老状态
            $current_domains_text = implode("\n", $clean_domains);
            $current_whitelist_status = $admin_whitelist_form;
        } else {
            $message = "文件写入失败，请检查文件写入权限。";
            $message_type = "error";
        }
    } else {
        $message = "错误：配置文件不可写，请检查 Linux 或群晖共享文件夹的写入权限。";
        $message_type = "error";
    }
}

// 引入公共页眉
include("header.php");
?>

<div class="admin-main-wrapper" style="padding: 20px;">
    <div class="container" style="max-width: 1000px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h1 style="font-size: 24px; color: #333; margin-top: 0;">🛡️ 安全白名单设置</h1>
        <div class="nav-links" style="font-size: 14px; margin-bottom: 20px; color: #888;">
            <a href="admin.php" style="color: #007bff; text-decoration: none;">后台首页</a> | 
            <a href="logout.php" style="color: #007bff; text-decoration: none;">退出登录</a> | 
            <a href="../" target="_blank" style="color: #007bff; text-decoration: none;">网站首页</a>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>" style="padding: 12px 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; font-size: 14px; <?= $message_type === 'success' ? 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form action="admin_whitelist.php" method="post">
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                <label style="font-weight: bold; color: #333; font-size: 15px;">白名单防盗链状态：</label>
                <select name="admin_whitelist" style="padding: 8px 16px; font-size: 14px; font-weight: bold; border-radius: 4px; border: 1px solid #ccc; cursor: pointer; outline: none; background: #fff;">
                    <option value="1" <?= $current_whitelist_status === '1' ? 'selected' : '' ?> style="color: #28a745; font-weight: bold;">🟢 开启白名单过滤</option>
                    <option value="0" <?= $current_whitelist_status === '0' ? 'selected' : '' ?> style="color: #dc3545; font-weight: bold;">🔴 关闭白名单过滤</option>
                </select>
                <span style="font-size: 12px; color: #6c757d;">(关闭后系统将不再拦截未在白名单列表内的请求)</span>
            </div>

            <h4 style="margin: 0 0 10px 0; color: #444;">编辑允许防盗链/请求的域名白名单：</h4>
            <p style="font-size: 13px; color: #666; margin-top: -5px; margin-bottom: 12px;">提示：请不要带 http:// 或末尾斜杠，每行输入一个域名或IP（例如：<code>ximi.me</code>）。</p>
            
            <textarea name="whitelist_domains" placeholder="请输入域名，一行一个" style="width: 100%; height: 320px; font-family: monospace; padding: 15px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 14px; line-height: 1.6; outline: none; resize: vertical;"><?= htmlspecialchars($current_domains_text) ?></textarea>
            
            <div style="margin-top: 20px;">
                <button type="submit" style="background: #007bff; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 15px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,123,255,0.2); transition: background 0.2s;">
                    💾 保存白名单配置
                </button>
            </div>
        </form>
    </div>
</div>

</main> 
</body>
</html>