<?php
// 支持的图片扩展名
if (!function_exists('countImagesInDirectory')) {
    function countImagesInDirectory($directory) {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico'];
        $imageCount = 0;

        if (is_dir($directory)) {
            $files = scandir($directory);
            foreach ($files as $file) {
                $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($fileExtension, $imageExtensions)) {
                    $imageCount++;
                }
            }
        }
        return $imageCount;
    }
}

// 新增：专门供侧边栏渲染时快捷获取单个相册总图片数的函数
if (!function_exists('getAlbumImageCount')) {
    function getAlbumImageCount($albumPath) {
        // $albumPath 传入的形如 "bnt" 或 "gufeng"
        $userDir = __DIR__ . '/../../user/'; // 锚定当前 count_images.php 所在位置向上找
        $fullPath = realpath($userDir . $albumPath);
        
        if (!$fullPath) return 0;
        
        $iniFile = $fullPath . "/ini.php";
        if (file_exists($iniFile)) {
            // 在独立的局部作用域中 include，避免配置变量污染全局
            $config = [];
            include($iniFile);
            if (isset($config['tu_1']) && is_dir($config['tu_1'])) {
                // 如果 ini.php 里定义了图片存放文件夹，去算那个文件夹
                return countImagesInDirectory($config['tu_1']);
            }
        }
        // 如果没有配置 tu_1，默认计算相册根目录下的图片
        return countImagesInDirectory($fullPath);
    }
}

// --- 保持你原有独立访问此文件返回 JSON 的功能不变 ---
// 只有在浏览器直接请求这个文件，而不是被其他页面 include 时，才输出 JSON 
if (basename($_SERVER['SCRIPT_FILENAME']) === 'count_images.php') {
    $folders = [];
    $directories = glob("../../user/*", GLOB_ONLYDIR);

    foreach ($directories as $dir) {
        $iniFile = $dir . "/ini.php";
        if (file_exists($iniFile)) {
            $config = [];
            include($iniFile);
            if (isset($config['tu_1'])) {
                $folders[] = $config['tu_1'];
            }
        }
    }

    $totalImageCount = 0;
    foreach ($folders as $folder) {
        $totalImageCount += countImagesInDirectory($folder);
    }

    header('Content-Type: application/json');
    echo json_encode(['total_images' => $totalImageCount]);
    exit;
}
?>