<?php
// 引入文件，确保包含 countImageOccurrences() 函数
include 'img_num.php'; 

if (isset($_GET['filename'])) {
    // 获取传递的文件名
    $filename = basename($_GET['filename']); 

    // 调用函数返回查看次数
    echo countImageOccurrences($filename);  
} else {
    // 如果没有传递 filename 参数，则返回 0
    echo '0'; 
}
