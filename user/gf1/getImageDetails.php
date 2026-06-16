<?php
// 引入文件，确保包含 formatBytes 函数
include 'ini.php'; 
//include 'img_num.php';
// 设置响应头为 JSON 格式
header('Content-Type: application/json');

// 定义 formatBytes 函数
if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];  // 定义单位
        $bytes = max($bytes, 0);  // 防止负数
        $power = floor(($bytes ? log($bytes) : 0) / log(1024));  // 计算单位
        return number_format($bytes / pow(1024, $power), $precision) . ' ' . $units[$power];
    }
}

// 获取传递的文件名
if (isset($_GET['filename'])) {
    $filename = basename($_GET['filename']);
    
    // 获取文件路径
    //$filePath = 'path/to/your/images/' . $filename;
    $filePath = realpath($config['tu_1'] . $filename);  // 获取图片的完整路径
    // 检查文件是否存在
    if (file_exists($filePath)) {
        // 获取文件大小
        $fileSize = formatBytes(filesize($filePath));
        
        // 获取分辨率
        list($width, $height) = getimagesize($filePath);
        $resolution = $width . 'x' . $height;
        
        // 获取原图 URL
        $originalUrl = './img.php?=' . $filename; // 替换为实际的原图 URL
        
        // 获取文件出现次数
        $viewCount = countImageOccurrences($filename);

        // 返回数据为 JSON 格式
        echo json_encode([
            'size' => $fileSize,
            'resolution' => $resolution,
            'originalUrl' => $originalUrl,
            'viewCount' => $viewCount // 输出下载次数
        ]);
    } else {
        echo json_encode([
            'size' => '文件不存在',
            'resolution' => '无法获取分辨率',
            'viewCount' => 0
        ]);
    }
} else {
    echo json_encode([
        'size' => '参数错误',
        'resolution' => '参数错误',
        'viewCount' => 0
    ]);
}

// 统计目标字符串在文件中的出现次数
function countImageOccurrences($imageName) {
    // Current directory's fixed file path
    $filePath = 'log.php'; 

    // The line that added the prefix has been removed:
     $imageName = "=" . $imageName;

    // Check if the file exists
    if (!file_exists($filePath)) {
        return 0; // Return 0 if the file doesn't exist
    }

    // Get the file content
    $fileContent = file_get_contents($filePath);
    
    // Use substr_count to calculate the occurrences of the specified string
    $count = substr_count($fileContent, $imageName);
    
    // Divide the count by 2
    //$count = $count / 2;
    
    return $count;
}
?>
