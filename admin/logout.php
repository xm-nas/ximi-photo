<?php
session_start();

// 清除会话变量
session_unset();
session_destroy();

// 重定向到登录页面
header('Location: login.php');
exit();
?>
