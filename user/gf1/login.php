<?php
// login.php 子目录登陆
session_start();
include("./ini.php");

$logged_in_key = $config['login_admin']; // 从配置中获取动态的登录标识符

// 检查用户是否已经登录，如果已登录则跳转到管理页面
if (!empty($_SESSION[$logged_in_key]) && $_SESSION[$logged_in_key]) {
    header('Location: admin.php');
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Login";

$usera = "ximi";
//$passa = "1751096133685f9b45e497f";
$passa = bin2hex(random_bytes(11));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 判断用户名和密码是否匹配
    if ($username === $usera && $password === $passa) {
        // 登录成功，设置会话变量并重定向
        $_SESSION['logged_in'] = true;
        $_SESSION[$logged_in_key] = true; // 设置动态的管理员登录状态
        header('Location: admin.php');
        exit();
    } else {
        // 登录失败，可以显示错误信息
       // echo "用户名或密码错误";
        // 登录失败，可以显示错误信息
        // 登录失败，显示错误信息 using SweetAlert
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
        echo "<script>";
        echo "window.onload = function() {";
        echo "  Swal.fire({";
        echo "    title: '登录失败！',";
        echo "    text: '用户名或密码错误',";
        echo "    icon: 'error',";
        echo "    confirmButtonText: '确定'";
        echo "  }).then((result) => {"; // Added .then() for potential actions after the alert
        echo "    if (result.isConfirmed) {";
        echo "      window.location.href = './login.php';"; // Redirect after user clicks "确定"
        echo "    }";
        echo "  });";
        echo "};";
        echo "</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/admin/css/themes.css">

<title>登陆</title>

<style>

@font-face {
    font-family: '华文行楷';
    src: url('../../../index_src/fonts/hwxk.ttf') format('truetype');
 font-display: swap;
}
body{
      /*display: flex;         使用 Flexbox 布局 */
     /*justify-content: center; 垂直居中对齐内容 */
    /* align-items: center;    Background: #06070d;  子元素在垂直方向居中对齐 */
color: #fff;
 
}

</style>
 <style>


.title_log  {
    display: flex;
    justify-content: space-between; /* 分配子元素之间的空间 */
    align-items: center; /* 垂直居中对齐 */
    width: 100%; /* 设置宽度 */
    padding: 5px; /* 可选: 为容器添加内边距 */
    color:white;
    margin:0;
    top:0;
}

.left {
    flex: 0 0 auto; /* 子元素左侧不伸展，保持原有宽度 */
    padding-left: 15px;
}

/* 悬停时，文字和 SVG 的颜色变化 */
.left:hover {
    color: #31be7c; /* 悬停时改变颜色 */
}

.left:hover svg path {
    fill: currentColor; /* SVG 路径颜色继承文字颜色 */
}



.center {
    flex: 1; /* 子元素占据剩余空间 */
    text-align: center; /* 文本居中 */
    -webkit-user-select: none;
}


a {
    text-decoration: none;
    color: inherit; /* 保持链接与文本的颜色一致 */
}

a:hover {
    color: #ff6a6a; /* 悬停时改变文字颜色 */
    text-decoration: none;
}

/* 右侧导航样式 */
.right2 {
    padding-right: 15px;
    font-size: 18px;
    color: #fff;
    display: inline-flex;
    align-items: center;
    gap: 15px; /* 控制每个链接之间的间距 */
  -webkit-user-select: none;

}

.right2 a {
    color: #fff; /* 设置默认文字和SVG颜色为白色 */
    display: inline-flex;
    align-items: center;
}

/* 悬停时，文字和 SVG 的颜色变化 */
.right2 a:hover {
    color: #31be7c; /* 悬停时改变颜色 */
}

.right2 a:hover svg path {
    fill: currentColor; /* SVG 路径颜色继承文字颜色 */
}

.denglu {
    display: block; /* 默认显示 */
}

/* 屏幕宽度小于450px时隐藏 */
@media screen and (max-width: 450px) {
    .denglu {
        display: none; /* 隐藏 */
    }
}


@media screen and (max-width: 360px) {
.left {
    padding-left: 5px;
}
.right2 {
 padding-right: 5px;
}
}




.a1{
     /*display: flex;*/
    /*justify-content: space-between;  分配子元素之间的空间 */
    /* align-items: center; 垂直居中对齐 */
   width: 100%; /* 768px max-设置宽度 */
    /* padding: 10px; 可选: 为容器添加内边距 */
    color:white; 
   /*background:#ff6a6a; */
   margin-left: auto; 
  position: absolute;
            top: 50%;
            transform: translateY(-50%);
}

.puts{
  display: flex; 
  align-items: center; 
  justify-content: center; 
  text-decoration: none; 
  width: 160px!important;
  height:50px;
  border-radius: 5px; 
  background:#fe771d;
  color:#fff;
}
.puts:hover {
  background:#31be7c;
}
</style>         


</head>
<body>


<div class="title_log" style="display: flex; width:100%; height:50px;white-space: nowrap; /* 禁止文本换行 */">

<div class="left" style=""></svg></div>

<div class="center">
<a href="../../"  title="重载页面" style="display: inline-flex; align-items: center;">
<span style="font-size: 26px; font-family: 华文行楷; color: #fff; padding-top: 0; display: inline-flex; align-items: center; height: 35px;">
后台管理
</span>
</a>        
</div>


<div class="right2">


</div>


</div>
    
 
  
<div  class="a1" style="display: block;margin: 20px 0 0; text-align: center;">

<div class="parent-container">
    <div class="login_dvi">
        <form action="login.php" method="post">
            <input type="text" name="username" placeholder="用户名" required>
            <input type="password" name="password" placeholder="密码"required>
            <input type="submit" value="登录">
       </form>
    </div>
</div>

</div>



   <style>
        /* 全局重置，确保没有默认的外边距 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box; /* 确保所有元素的宽高计算 */
        }

        /* 取消全局滚动条 */
        body {
            overflow: hidden; /* 隐藏所有滚动条 */
            height: 100vh; /* 设置高度为视口高度 */
            width: 100vw; /* 设置宽度为视口宽度 */
            position: relative; /* 确保父元素定位 */
        }

        .background-slider {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1; /* 确保在其他内容后面 */
            overflow: hidden; /* 确保没有内部滚动条 */
        }

        .background-slider img {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%; /* 设置为100%以确保填充 */
            height: 100%; /* 设置为100%以确保填充 */
            opacity: 0; /* 初始透明度为0 */
            transform: translate(-50%, -50%) scale(1); /* 初始状态居中且不放大 */
            object-fit: cover; /* 保持原始比例裁剪 */
            transition: opacity 1s ease-in-out, transform 6s ease-in-out; /* 添加透明度和缩放的过渡 */
        }

        /* 初始状态: 第一张图片可见 */
        .background-slider img:first-child {
            opacity: 1; /* 第一个图片完全可见 */
            transform: translate(-50%, -50%) scale(1.05); /* 初始放大效果 */
        }

        /* 图片在显示期间的样式 */
        .background-slider img.active {
            opacity: 1; /* 设置为可见 */
            transform: translate(-50%, -50%) scale(1.05); /* 轻微放大 */
        }

        .background-slider img.fade-out {
            opacity: 0; /* 过渡到透明 */
            transform: translate(-50%, -50%) scale(1); /* 恢复到原始大小 */
        }
    </style>
 
<div class="background-slider">
  <img src="https://www.ximi.me/img_src/bak3.jpeg" alt="背景图片1">
  <img src="https://www.ximi.me/img_src/bak2.jpeg" alt="背景图片2">
  <img src="https://www.ximi.me/img_src/bak1.jpeg" alt="背景图片3">
  <img src="https://www.ximi.me/img_src/bak4.jpeg" alt="背景图片4">
  <img src="https://www.ximi.me/img_src/bak5.jpeg" alt="背景图片5">
  </div>


    <script>
        const images = document.querySelectorAll('.background-slider img');
        let currentIndex = 0;

        function showImage(index) {
            images.forEach((image, i) => {
                if (i === index) {
                    image.classList.add('active'); // 添加活动类
                    image.style.opacity = 1; // 设置为可见
                    image.style.transform = 'translate(-50%, -50%) scale(1.05)'; // 设置放大效果
                } else {
                    image.classList.remove('active'); // 移除活动类
                    image.style.opacity = 0; // 隐藏其他图片
                    image.style.transform = 'translate(-50%, -50%) scale(1)'; // 恢复到原始大小
                }
            });
        }

        // 初始显示第一张图片
        showImage(currentIndex);

        // 定时切换图片
        setInterval(() => {
            // 将当前图片放大并透明度变化
            images[currentIndex].style.transform = 'translate(-50%, -50%) scale(1.05)'; // 放大当前图片
            images[currentIndex].style.opacity = 1; // 设置为可见

            // 延迟切换到下一个图片
            setTimeout(() => {
                currentIndex = (currentIndex + 1) % images.length; // 切换到下一个图片
                showImage(currentIndex); // 显示下一个图片
            }, 6000); // 6秒后切换到下张图片

        }, 6000); // 每张图片展示6秒

        // 禁用右键菜单
        document.addEventListener('contextmenu', (event) => {
            event.preventDefault(); // 阻止右键菜单的默认行为
        });
    </script>




</body>
</html>

</html>