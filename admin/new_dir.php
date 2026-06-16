<?php
// new_dir.php

// 检查用户是否已登录（如果需要）
session_start();
// 检查用户是否已登录
if (empty($_SESSION['logged_in_admin']) || $_SESSION['logged_in_admin'] !== true) {
    // 如果未登录，则重定向到登录页面
    header('Location: login.php');
    exit();
}


// 检查 URL 参数 "name" 是否存在且不为空
if (!isset($_GET['name']) || empty(trim($_GET['name']))) {
    die('参数 "name" 未提供或为空。<br><br><button onclick="window.location.href=\'admin.php\'">返回管理页面</button>');
}

// 检查 URL 参数 "name_title" 是否存在
if (isset($_GET['name_title'])) {
    $name_title = trim($_GET['name_title']);
} else {
    // 如果 name_title 不存在，提供一个默认值或输出错误信息
    $name_title = '默认标题'; // 或者 die('参数 "name_title" 未提供。');
}

$name = trim($_GET['name']);

// 自定义源目录
$source_dir = '../index_src/defaul/';

// 创建新的目标目录路径
$target_dir = '../user/' . $name . '/';

// 创建目标目录
if (!is_dir($target_dir)) {
    if(!mkdir($target_dir)){
        die("目录创建失败<br><br><button onclick=\"window.location.href='admin.php'\">返回管理页面</button>");
    }
}

// 遍历源目录并复制文件，替换相关字符串
foreach (scandir($source_dir) as $file) {
    if ($file != '.' && $file != '..') {
        $new_file = str_replace('defaul/', $name . '/', $file); // 替换 'defaul/'
        $new_file = str_replace('默认模板', $name_title, $new_file); // 替换 '默认模板'
        $source_file = $source_dir . '/' . $file;
        $target_file = $target_dir . $new_file;

        if (is_file($source_file)) {
            if(!copy($source_file, $target_file)){
                echo "文件复制失败：".$source_file."<br>";
            }
        }
    }
}

// 创建 ini.php 文件
$ini_file = $target_dir . 'ini.php';

$class=bin2hex(random_bytes(11));
// 定义配置数组
$config = [
    'min' => '../../update/user/' . $name . '/' . $class . '/min_image/',
    'max' => '../../update/user/' . $name . '/' . $class . '/max_image/',
    'tu_1' => '../../update/user/' . $name . '/' . $class . '/img/',
    'login_admin' => 'login_defaul',
    'list_read' => '1',
    'list_home' => '1',
    'title' => $name_title,
    'cover' => '',
    'txt' => ''
];

// 将数组写入 ini.php 文件
if(file_put_contents($ini_file, '<?php' . PHP_EOL . trim(var_export($config, true)) . ';' . PHP_EOL . '?>')){
    echo "目录创建成功！<br>";
}else{
    echo "ini.php文件创建失败<br>";
}

// 文件路径
$filePath = '../user/' . $name . '/ini.php';

// 要替换的字符串
$search = ['array (', ')'];
$replace = ['$config = [', ']'];

// 读取文件内容
// 检查文件是否存在且可读，避免 file_get_contents 失败
if (file_exists($filePath) && is_readable($filePath)) {
    $content = file_get_contents($filePath);

    // 执行替换操作
    $newContent = str_replace($search, $replace, $content);

    // 将修改后的内容写回文件
    if(file_put_contents($filePath, $newContent)){
        echo "ini.php文件替换完成！";
    }else{
        echo "ini.php文件替换失败";
    }
} else {
    echo "错误：无法读取或访问 ini.php 文件进行后续处理：{$filePath}<br>";
}


// 增加返回按钮
echo '<br><br><button onclick="window.location.href=\'admin_photo.php\'">返回管理页面</button>';

?>