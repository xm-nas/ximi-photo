<?php

$folders = [];

// 获取 ../../user/ 目录下的所有子目录
$directories = glob("../../user/*", GLOB_ONLYDIR);

// 遍历每个子目录
foreach ($directories as $dir) {
    // 拼接 ini.php 文件路径
    $iniFile = $dir . "/ini.php";
    
    // 检查 ini.php 文件是否存在
    if (file_exists($iniFile)) {
        // 引用 ini.php 文件
        include($iniFile);
        
        // 检查 $config['tu_1'] 是否存在并写入 $folders 数组
        if (isset($config['tu_1'])) {
            $folders[] = $config['tu_1'];
        }
    }
}

//include("../../user/guest/ini.php");
//$le = $config['tu_1']; 

function countImagesInDirectory($directory) {
    // 支持的图片扩展名
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg','ico'];
    $imageCount = 0;

    // 检查目录是否存在
    if (is_dir($directory)) {
        // 获取目录中的所有文件
        $files = scandir($directory);
        foreach ($files as $file) {
            // 获取文件扩展名
            $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            // 检查文件是否是图片
            if (in_array($fileExtension, $imageExtensions)) {
                $imageCount++;
            }
        }
    }

    return $imageCount;
}

// 指定的文件夹
//$folders = [     $la,     $lb,     $lc,     $ld,     $le]; // 请根据实际文件夹路径替换

//'./img/update/ffx7w3pa/kb5kxvid/',
//    './img/update/img/hide_1/'



$totalImageCount = 0; // 初始化总计数量

// 遍历文件夹并计算每个文件夹中的图片数量
foreach ($folders as $folder) {
    $totalImageCount += countImagesInDirectory($folder);
}

// 返回总图片数量的 JSON 格式数据
header('Content-Type: application/json');
echo json_encode(['total_images' => $totalImageCount]); // 仅返回总数量
?>
