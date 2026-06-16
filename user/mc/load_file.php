<?php
//加载编辑模板文件
$filename = $_GET['filename'];
echo file_get_contents($filename);
?>