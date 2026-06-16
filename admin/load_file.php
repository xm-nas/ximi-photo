<?php
$filename = $_GET['filename'];
echo file_get_contents($filename);
?>