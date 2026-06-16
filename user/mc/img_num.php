<?php
// 统计目标字符串在文件中的出现次数  【统计图片下载次数】
function countImageOccurrences($imageName) {
    // 当前目录下的固定文件路径
    $filePath = 'log.php'; 

    // 在图片名称前加上 "min_" 前缀
    $imageName = "min_" . $imageName;

    // 检查文件是否存在
    if (!file_exists($filePath)) {
        return "文件不存在";
    }

    // 获取文件内容
    $fileContent = file_get_contents($filePath);
    
    // 使用 substr_count 函数计算指定字符串出现的次数
    $count = substr_count($fileContent, $imageName);
    
    return $count;
}
?>
