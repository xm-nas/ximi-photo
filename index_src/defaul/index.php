<?php
//index-1.php 瀑布流预览
include("./ini.php");
session_start();
//=================
// 管理员与用户登陆状态验证代码：
// 获取动态登录标识符
$logged_in_key = $config['login_admin'];
$foot_txt=$config['txt'];

// 检查是否需要读取列表（config['list_read']）
if (isset($config['list_read']) && $config['list_read'] == 0) {
    // 如果 config['list_read'] 为 0，则进行登录状态验证
    if (!(isset($_SESSION['logged_in_admin']) && $_SESSION['logged_in_admin'] === true) && (empty($_SESSION[$logged_in_key]) || $_SESSION[$logged_in_key] !== true)) {
        // 如果不是总管理员登录状态，并且用户未登录，则弹窗提示并重定向
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
        echo "<script>";
        echo "window.onload = function() {";
        echo "  Swal.fire({";
        echo "    title: '访问失败！',";
        echo "    text: '该相册为私密相册，请登陆后访问',";
        echo "    icon: 'warning',";
        echo "    confirmButtonText: '确定'";
        echo "  }).then((result) => {"; // Added .then() for potential actions after the alert
        echo "    if (result.isConfirmed) {";
        echo "      window.location.href = 'login.php';"; // Redirect after user clicks "确定"
        echo "    }";
        echo "  });";
        echo "};";
        echo "</script>";
        exit(); // 阻止后续页面内容的输出
    } else {
        // 管理员或用户已登录，继续输出后续页面
        // echo "管理员或用户已登录，显示后续内容"; // 在此处输出你的后续页面内容
    }
} else {
    // 如果 config['list_read'] 为 1 或未设置，则跳过此步判断，继续输出后续页面
    // echo "config['list_read'] 为 1 或未设置，跳过登录验证，显示后续内容"; // 在此处输出你的后续页面内容
}
//=================


include 'img_num.php';  // 确保路径正确

// 获取当前目录下所有图片文件
$uploadDir =$config['tu_1'];   //$_SERVER['DOCUMENT_ROOT'] . 'update/user/ximi/class/img';

$page_title=$config['title'];

//$imageFiles = glob($uploadDir . '*.{jpg,jpeg,png,gif,webp,ico}', GLOB_BRACE);
$imageFiles = array_merge(
    glob($uploadDir . '*.jpg'),
    glob($uploadDir . '*.jpeg'),
    glob($uploadDir . '*.png'),
    glob($uploadDir . '*.gif'),
    glob($uploadDir . '*.webp'),
    glob($uploadDir . '*.ico')
);

// 按文件的修改时间进行倒序排序
usort($imageFiles, function ($a, $b) {
    return filemtime($b) - filemtime($a); // 倒序排列
});


// 获取屏幕宽度（从 URL 参数传递）
$screenWidth = isset($_GET['screenWidth']) ? (int)$_GET['screenWidth'] : 0; // 默认为 0

// 根据屏幕宽度生成图片链接
if ($screenWidth > 458) {

    $imagesPerPage = 200; // 每页显示的图片数量
} else {
    // 屏幕宽度小于或等于 458 时，加载 max 版本的图片

    $imagesPerPage = 50; // 每页显示的图片数量
}

// 分页设置
//$imagesPerPage = 48; // 每页显示的图片数量
$totalImages = count($imageFiles); // 总图片数量
$totalPages = ceil($totalImages / $imagesPerPage); // 总页数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // 当前页码，默认为1
$page = max($page, 1); // 确保页码至少为1
$page = min($page, $totalPages); // 确保页码不超过总页数
$offset = ($page - 1) * $imagesPerPage; // 计算偏移量
$currentImages = array_slice($imageFiles, $offset, $imagesPerPage); // 当前页的图片


?>



<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?>-希米图册</title>
<!-- <link rel="stylesheet" href="../admin/css/themes.css"> 

<link href="https://www.ximi.me/admin/css/app.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>

<link rel="stylesheet" href="../../index_src/defaul/src/tailwind.min.css">

<style>
@font-face {
    font-family: '华文行楷';
    src: url('../../../index_src/fonts/hwxk.ttf') format('truetype');
 font-display: swap;
}

body {
    /* 设置字体样式为 Arial 或者 sans-serif 作为后备字体 */
    font-family: Arial, sans-serif;
background-color: #f5f7fb;
    /* 使用线性渐变背景，渐变颜色从左到右依次为 #fed6e3 和 #c0efec */
    /* background-image: linear-gradient(to right, #fed6e3, #c0efec);  
background-image: linear-gradient(to right, #fed6e3, #c0efec);*/
    /* 将页面边距设置为 0，以消除默认边距 */
    margin: 0 auto; /* 居中显示 */

    /* 确保背景颜色为渐变色，不需要再指定 */
    /* background-color:;  /* 这行可以去掉，因为已经使用了背景渐变 */ 

    /* 设置文本水平居中 */
    text-align: center;



    /* 设置页面高度自动，根据内容自适应 */
    height: auto;


}

/* 下拉菜单*/
.link-items-container {
    display: none;
    position: absolute;
    top: 49.5px;
    left: -5px;
    z-index: 1000;
    margin-left: 0;
    height: 100%;
    width: 200px;
    background: #05080361;
}
.link-item {
    display: flex
;
    align-items: center;
    width: 100%;
    height: 40px;
    padding: 0 25px;
    background-color: rgba(255, 255, 255, 0.85);
    background-color: #88519900;
    /* box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); */
    transition: background-color 0.3s;
    cursor: pointer;
    color: #fff;
    font-size: 22px;
    left: -5px;
    margin-left: 0;
    align-content: stretch;
    /* flex-direction: row; */
    /* justify-content: center; */
    /* flex-wrap: nowrap; */
}

.link-item:hover {
     background-color: #1f2937; /*鼠标悬停时的背景颜色 */
color: #31be7c; /* 链接颜色 */
}

.link-item svg {
    margin-right: 10px; /* 图标与文字之间的间距 */
    fill: #fff; /* 图标颜色 */
}

        .link-item a {
            flex-grow: 1; /* 填满剩余空间 */
            color: #fff;  /*链接颜色 */
        }

        .link-item a:hover {
            color: #31be7c; /* 鼠标悬停效果 */
        }

.link-item svg:hover  {
    margin-right: 10px; /* 图标与文字之间的间距 */
    fill: #31be7c; /* 图标颜色 */
    color: #31be7c; /* 链接颜色 */
}

a{
text-decoration:none;
 /* color: inherit; 保持链接与文本的颜色一致 */
}

a:hover{
text-decoration:none;
}


.pagination a {
    display: inline-flex;       /* 使用 flex 方便居中 */
    align-items: center;        /* 垂直居中 */
    justify-content: center;    /* 水平居中 */
    
    margin: 5px;                /* 适当调整间距 */
    text-decoration: none;
    color: #333;
    
    /* 关键修改：固定宽高 */
    width: 36px;                /* 宽度 */
    height: 36px;               /* 高度必须与宽度相等 */
    
    border: 1px solid #ff6a6a;
    border-radius: 50%;         /* 使用 50% 确保绝对圆形 */
    
    transition: all 0.3s;       /* 加上过渡动画，体验更好 */
}

.pagination a:hover {
    background-color: #f0f0f0;
}

.pagination .active {
    background-color: #007bff;
    color: white;
    border: 1px solid #007bff;
}
/*================顶部标题菜单 statr============================*/
.title_log  {
    display: flex;
    justify-content: space-between; /* 分配子元素之间的空间 */
    align-items: center; /* 垂直居中对齐 */
    width: 100%; /* 设置宽度 */
    padding: 5px; /* 可选: 为容器添加内边距 */
    color:white;
    margin:0;
    top:0;
    background-color: rgba(170,170,170, 0.05);   /*默认背景颜色 */
    color: #fff;
  font-family: Arial, sans-serif;

}
.denglus2 {
font-size:18px;
  align-items: center; /* 垂直居中对齐 */
padding-top: 8px;
font-weight: bold;
}
.denglus2 svg {
    fill: #fff; /* 设置填充颜色 */
}
.denglu {
  align-items: center; /* 垂直居中对齐 */
padding-top: 5px;

}
/* 悬停时，文字和 SVG 的颜色变化 */
.left2:hover {
    color: #31be7c; /* 悬停时改变颜色 */
}
.left2 svg {
        fill: #fff; /* 图标颜色 */
}
.left2:hover svg path {
    fill: currentColor; /* SVG 路径颜色继承文字颜色 */
}

.centers2 {
    /*flex: 1;  子元素占据剩余空间 */
    text-align: center; /*  -webkit-user-select: none;文本居中 */
   
}

/* 右侧导航样式 */
.right2 {
    padding-right: 1px;
    font-size: 18px;
    color: #fff;
    display: inline-flex;
    align-items: center;
    gap: 15px; /* 控制每个链接之间的间距  -webkit-user-select: none; */

padding-top: 10px;
padding-bottom: 10px;
}

.right2 a {
    color: #fff; /* 设置默认文字和SVG颜色为白色 */
    display: inline-flex;
    /*align-items: center;*/
}

/* 悬停时，文字和 SVG 的颜色变化 */
.right2 svg {
    fill: #fff; /* 悬停时改变颜色 */
}
.right2 svg:hover {
    fill: #31be7c;
}

.right2 a:hover svg path {
    fill: currentColor; /* SVG 路径颜色继承文字颜色 */
}


/* 屏幕宽度小于5120px时隐藏 */
@media screen and (max-width: 5120px) {
    .left2 {
       width:auto;
       padding-left: 15px;
    }
    .right2 {
       width:auto;
padding-right: 1px;
    }
    .centers2 {
    /* flex: 1; 子元素占据剩余空间 */
    text-align: center; /* 文本居中   -webkit-user-select: none; */
 
           padding-left: 0px;
}


}

/* 屏幕宽度小于550px时隐藏 */
@media screen and (max-width: 550px) {
    .denglu {
        display: none; 
    }
    .left2 {
       width:auto;
       padding-left: 15px;
    }
    .right2 {
       width:auto;

    }
    .centers2 {
    /* flex: 1; 子元素占据剩余空间 */
    text-align: center; /* 文本居中  -webkit-user-select: none; */
  
           padding-left: 0px;
}


}


@media screen and (max-width: 360px) {
.left {
    padding-left: 5px;
}
.right2 {
 padding-right: 1px;
}

}

/*================顶部标题菜单 stop============================*/
/* =====================添加媒体查询，以便在不同屏幕宽度下调整样式=================== */


/* ============================添加到你的CSS文件中或在<style>标签内============================ */
.item {
    transition: transform 0.23s ease-in-out;  /* 平滑过渡效果 */
    background-color: #e0e0e0;
    padding: 0.1px;
}

.item:hover {
    background-color: #e0e0e0;
    transform: scale(1.035);  /* 鼠标悬停时图片缩小 */
}

/*
//======================···菜单================================
*/
.zd1b-container {
    display: none;
    position: absolute;
    top: 45.15px;
    right: 20px;
    z-index: 1000;
    margin-left: 0;
    height: auto;
    /* width: 89px; */
    background-color: rgb(75 70 67);
    /* backdrop-filter: blur(10px); */
    box-shadow: 0 4px 8px rgb(0 0 0 / 29%);
    padding: 10px 0px;
    border-radius: 10px;
    color: white;
    /* line-height: 1.6; */
    font-size: 18px;
}

.zd1b-container svg {
    width: 18px;
    height: 18px;
    margin-right: 5px;
    vertical-align: middle;
    fill: #fff;
}


.zd1b:hover {
    background-color: rgb(0 0 0 / 31%); /* 鼠标悬停时背景颜色 */
  
}

.zd1b {
    margin: 0;
    display: flex;
    align-items: center;
    padding: 5px 16px;
    font-size:18px;
}

.zd1b a {
    color: #fff;
    padding-left: 2px;
    font-size: 14px;
 }
 
.zd1b a:hover {
color: #fff; /* 鼠标悬停效果 */

 }



/* 禁止所有文本输入框闪烁光标 */
/* 禁止页面所有元素的文本光标显示和文本选择 */
* {
    caret-color: transparent;  /* 禁用光标显示 */
      /*user-select: none;        禁用文本选择 */
}

/*========================*/
.link-items-containerxc {
    display: none;
    position: absolute;
    top: 0px;
    right: 108px;
    z-index: 1000;
    margin-left: 0;
    height: auto;
    width: 130px;
    background-color: #11111195;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    padding: 5px 0;
    border-radius: 5px;
    color: white;
    line-height: 1.6;
    font-size: 18px;
}
/*
.xiangce a {
color: #fff; 
padding-left:2px;
    font-size:18px;
 }
*/ 
</style>



</head>
<body>

<?php include("Sidebar.php"); ?>

<!--body-->
<?php
// 在文件顶部定义 formatBytes 函数，以避免重复声明
if (!function_exists('formatBytes')) {
    // 文件大小格式化函数
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];  // 定义单位
        $bytes = max($bytes, 0);  // 防止负数
        $power = floor(($bytes ? log($bytes) : 0) / log(1024));  // 计算单位
        return number_format($bytes / pow(1024, $power), $precision) . ' ' . $units[$power];
    }
}
?>

<div class="p-4 pt-12" style="background-color: #bbb7ff2b;">
    <div class="a_img" style="max-width: 1920px;margin: 0 auto;margin-top: -25px;">
        <div id="grid" class="ajax-container grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
            <?php foreach ($currentImages as $image): ?>
                <div class="w-6/12 sm:w-4/12 lg:w-3/12 xl:w-1/5 2xl:w-2/12 ajax-post in-load in-loaded p-2">
                    <?php
                    // 获取图片文件的完整路径
                    $filePathsa = realpath($config['tu_1'] . basename($image));

                    // 检查文件是否存在
                    if ($filePathsa && file_exists($filePathsa)) {
                        // 获取文件大小并转换为可读格式
                        $fileSize = formatBytes(filesize($filePathsa));

                        // 获取图片的分辨率
                        list($width, $height) = getimagesize($filePathsa);
                        $resolution = $width . 'x' . $height;
                    }
                    ?>
                    
     <img class="shadow-md rounded-sm item scrollLoading ojbk" style="border-radius: 0.5px;" 
     src="<?= htmlspecialchars("./min.php?=min_" . basename($image)) ?>" 
     alt="<?= basename($image) ?>"
     data-filename="<?= basename($image) ?>" 
     data-size="<?= $fileSize ?>" 
     data-resolution="<?= $resolution ?>" 
     aria-hidden="true">
 
     <button class="right-click-button" data-original-filename="<?= basename($image) ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="26" height="22" fill="" class="bi bi-three-dots" viewBox="0 0 16 16">
        <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3"></path>
      </svg>
     </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    

<style>
/* 弹窗遮罩层 */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background-color: rgba(0, 0, 0, 0.3); /* 轻轻调暗背景 */
    z-index: 10000;
    display: none; /* 初始隐藏 */
    align-items: center;
    justify-content: center;
}

/* 弹窗主体：完全复刻右键菜单样式 */
#image-info-modal {
    background-color: rgba(30, 30, 30, 0.6);
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(20px);
    width: 148px;
    padding: 8px 6px;
    border-radius: 9px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
    color: #ffffff;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    animation: menuFadeIn 0.2s ease;
}

@keyframes menuFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

/* 菜单行基础样式 */
.menu-item {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    margin: 2px 0;
    transition: all 0.2s ease;
    border-radius: 8px; /* 圆角胶囊效果 */
    cursor: pointer;
    font-size: 15px;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    border: none;
    background: transparent;
    width: 100%;
    box-sizing: border-box;
}

/* 鼠标悬停高亮 */
.menu-item:hover {
    background-color: rgba(255, 255, 255, 0.15);
    color: #ffffff;
}

/* 图标统一样式 */
.menu-item svg {
    width: 18px;
    height: 18px;
    margin-right: 12px;
    fill: currentColor;
    flex-shrink: 0;
}

/* 分隔线 */
.menu-divider {
    height: 1px;
    background-color: rgba(255, 255, 255, 0.1);
    margin: 6px 4px;
    border: none;
}

/* 关闭项特殊颜色（可选） */
.menu-close {
    color: rgba(245, 101, 101, 0.9);
}
.menu-close:hover {
    background-color: rgba(245, 101, 101, 0.2);
}
</style>

<div id="modal-overlay" class="modal-overlay">
    <div id="image-info-modal">
        <button id="copy-link-button" class="menu-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M6.9998 6V3C6.9998 2.44772 7.44752 2 7.9998 2H19.9998C20.5521 2 20.9998 2.44772 20.9998 3V17C20.9998 17.5523 20.5521 18 19.9998 18H16.9998V20.9991C16.9998 21.5519 16.5499 22 15.993 22H4.00666C3.45059 22 3 21.5554 3 20.9991L3.0026 7.00087C3.0027 6.44811 3.45264 6 4.00942 6H6.9998ZM8.9998 6H16.9998V16H18.9998V4H8.9998V6Z"></path></svg>
            复制链接
        </button>

        <a id="view-original-link" href="#" target="_blank" class="menu-item">
           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 5H4V19L13.2923 9.70649C13.6828 9.31595 14.3159 9.31591 14.7065 9.70641L20 15.0104V5ZM2 3.9934C2 3.44476 2.45531 3 2.9918 3H21.0082C21.556 3 22 3.44495 22 3.9934V20.0066C22 20.5552 21.5447 21 21.0082 21H2.9918C2.44405 21 2 20.5551 2 20.0066V3.9934ZM8 11C6.89543 11 6 10.1046 6 9C6 7.89543 6.89543 7 8 7C9.10457 7 10 7.89543 10 9C10 10.1046 9.10457 11 8 11Z"></path></svg>
            查看原图
        </a>

        <a id="download-original-link" href="#" download class="menu-item">
            <svg viewBox="0 0 24 24"><path d="M3 19H21V21H3V19ZM13 13.1716L19.0711 7.1005L20.4853 8.51472L12 17L3.51472 8.51472L4.92893 7.1005L11 13.1716V2H13V13.1716Z"></path></svg>
            下载图片
        </a>

        <div class="menu-divider"></div>

        <button id="close-modal" class="menu-item menu-close">
            <svg viewBox="0 0 24 24"><path d="M12 10.5858L16.95 5.63574L18.3642 7.04996L13.4142 12L18.3642 16.95L16.95 18.3642L12 13.4142L7.04996 18.3642L5.63574 16.95L10.5858 12L5.63574 7.04996L7.04996 5.63574L12 10.5858Z"></path></svg>
            取消
        </button>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('modal-overlay');
    const viewOriginalLink = document.getElementById('view-original-link');
    const downloadLink = document.getElementById('download-original-link');
    const copyLinkButton = document.getElementById('copy-link-button');
    const closeModalButton = document.getElementById('close-modal');

    let currentImageUrl = ''; // 存储当前点击的 URL

    // 1. 监听所有“三点”按钮
    document.addEventListener('click', function(event) {
        const btn = event.target.closest('.right-click-button');
        if (btn) {
            event.preventDefault();
            event.stopPropagation();

            const filename = btn.getAttribute('data-original-filename');
            const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
            currentImageUrl = new URL(`./img.php?=${filename}`, baseUrl).href;

            // 更新链接
            viewOriginalLink.href = currentImageUrl;
            downloadLink.href = currentImageUrl;
            downloadLink.download = filename;

            // 显示菜单
            overlay.style.display = 'flex';
        }
    });

    // 2. 复制功能 (改为静默复制)
    copyLinkButton.onclick = function() {
        const tempInput = document.createElement('input');
        tempInput.value = currentImageUrl;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);

        const originalText = this.innerHTML;
        this.innerHTML = `<svg viewBox="0 0 24 24"><path d="M10 15.172L19.192 5.979L20.606 7.393L10 18L3.636 11.636L5.05 10.222L10 15.172Z"></path></svg> 已复制`;
        
        setTimeout(() => {
            this.innerHTML = originalText;
            overlay.style.display = 'none'; // 复制完自动关闭
        }, 1000);
    };

    // 3. 关闭功能
    const hideModal = () => { overlay.style.display = 'none'; };
    closeModalButton.onclick = hideModal;
    overlay.onclick = (e) => { if (e.target === overlay) hideModal(); };
});
</script>

<!--分割  -->


<style>
#original-image-link {
    padding: 12px;
    border: 2px solid #e3276d9c;
    border-radius: 5px;
    font-size: 14px;
    box-sizing: border-box;
    width: 100%;
    outline: none; /* 移除默认的 focus 轮廓，以便我们自定义边框 */
}

#original-image-link:focus {
    border-color: #e3276d9c; /* 激活状态下保持相同的边框颜色 */
}

.modal-button {
    display: inline-block;
    width: 100%; /* 使按钮宽度一致 */
    padding: 12px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    text-align: center;
    text-decoration: none;
    box-sizing: border-box; /* 包含 padding 和 border 在元素总宽度内 */
}

.modal-primary {
    background-color: #007bff;
    color: white;
}

.modal-primary:hover {
    background-color: #0056b3;
}

.modal-success {
    background-color: #28a745;
    color: white;
}

.modal-success:hover {
    background-color: #1e7e34;
}

.modal-secondary {
    background-color: #6c757d;
    color: white;
}

.modal-secondary:hover {
    background-color: #545b62;
}

.modal-danger {
    background-color: #dc3545;
    color: white;
}

.modal-danger:hover {
    background-color: #c82333;
}


.right-click-button {
position: absolute;
    top: 13px;
    right: 13px;
    background-color: rgb(220 27 255 / 48%);
    color: white;
    border: none;
    padding: 1px 6px;
    cursor: pointer;
    z-index: 10;
    display: none;
    border-radius: 3px;
    font-size: 2px;
}
.right-click-button svg {
    fill: #fff; /* 设置填充颜色 */
}
.right-click-button a svg :hover {
    fill: #6aff70;
}

/* 小于 800px 屏幕时显示按钮 */
@media screen and (max-width: 5120px) {
    .right-click-button {
        display: block;
    }
}
</style>


    
    
    
    
    
    
    
    <!-- 分页链接 -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
<h3 style="padding:20px"><?php echo $foot_txt; ?></h3> </div>
</div>
<script>
// 长按事件处理函数
function longPress(event) {
    event.preventDefault();
    // 显示上下文菜单
    var menu = document.getElementById('contextMenu');
    menu.style.display = 'block';
    menu.style.left = event.clientX + 'px';
    menu.style.top = event.clientY + 'px';
}
 
// 为目标元素添加长按事件监听
var content = document.getElementById('content');
content.addEventListener('contextmenu', longPress);
// 阻止默认的右键菜单
content.addEventListener('click', function(event) {
    event.preventDefault();
});
</script>
<!-- 自定义右键菜单    <div id="info-view-link" class="menu-item"></div> -->
<div class="custom-context-menu" id="context-menu">
    <div id="info-size"></div>
    <div id="info-resolution"></div>
    <hr class="menu-divider">
    <div id="info-view-img" class="menu-item"></div>
    <div id="info-view-down" class="menu-item"></div>
    <hr class="menu-divider">
    <div id="info-view-count"></div>
</div>


<style>  
/* 自定义右键菜单玻璃效果主体 */
.custom-context-menu {
    position: absolute;
    background-color: rgb(17 17 17 / 67%);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(12px);
    border: 0 8px 32px rgba(0, 0, 0, 0.5);
    box-shadow: 0 10px 30px rgb(0 0 0 / 69%);
    width: 215px;
    padding: 6px 0;
    display: none;
    z-index: 9999;
    color: #eeeeee;
    font-size: 16px;
    border-radius: 6px;
    overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

/* 每一行的通用基础样式 */
.info-text {
    margin: 0;
    display: flex;
    align-items: center;
    min-height: 40px;
    padding: 0 15px; 
    transition: background 0.2s ease, color 0.2s ease;
    cursor: default;
    color: rgba(255, 255, 255, 0.9);
}

/* 鼠标悬停效果：仅针对【非只读】项目 */
/* 必须配合 JS 中给容量、下载次数等 div 加上 'readonly' 类名使用 */
.info-text:not(.readonly):hover {
    background-color: rgba(255, 255, 255, 0.1); 
    color: #ffffff;
}

/* 只读信息项（容量、分辨率、下载次数）的特殊处理 */
.info-text.readonly {
    color: rgb(255 255 255);
    cursor: default;
}
.info-text.readonly:hover {
    background-color: transparent !important; /* 强制取消悬停背景 */
}

/* 动作链接样式：确保点击整行有效 */
.info-text a {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: inherit;
    width: 100%;
    height: 100%;
    /* 这里的 padding 抵消父级的，确保 a 标签热区铺满 */
    margin: 0 -16px;
    padding: 0 16px;
}

/* 图标统一样式 */
.custom-context-menu svg {
    width: 18px;
    height: 18px;
    margin-right: 12px;
    fill: currentColor;
    flex-shrink: 0;
    opacity: 0.9;
}

/* 菜单分隔线 */
.menu-divider {
    height: 1px;
    background-color: rgba(255, 255, 255, 0.08);
    margin: 4px 0;
    border: none;
}
</style>


<script>

// 获取右键菜单和图片元素
const contextMenu = document.getElementById('context-menu');
const rightClickImages = document.querySelectorAll('.shadow-md.rounded-sm.item.scrollLoading.ojbk');

// 桌面端右键菜单处理
rightClickImages.forEach(image => {
    image.addEventListener('contextmenu', (event) => {
        event.preventDefault(); // 防止浏览器默认右键菜单

        // 获取鼠标点击的位置
        const mouseX = event.clientX + window.scrollX;
        const mouseY = event.clientY + window.scrollY;

        // 获取图片的属性（假设 HTML 中有 data-filename）
        const filename = image.getAttribute('data-filename');

        // 动态加载图片的详细信息
        fetch(`getImageDetails.php?filename=${encodeURIComponent(filename)}`)
            .then(response => response.json())
            .then(imageData => {
                // 拼接出完整的绝对 URL 用于下载和查看
                const baseUrl = window.location.origin + window.location.pathname;
                const absoluteUrl = new URL(imageData.originalUrl, baseUrl).href;

                // --- 1. 容量 (信息项：readonly) ---
                const infoSize = document.getElementById('info-size');
                infoSize.className = 'info-text readonly';
                infoSize.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 9.5V12.5C21 14.9853 16.9706 17 12 17C7.02944 17 3 14.9853 3 12.5V9.5C3 11.9853 7.02944 14 12 14C16.9706 14 21 11.9853 21 9.5ZM3 14.5C3 16.9853 7.02944 19 12 19C16.9706 19 21 16.9853 21 14.5V17.5C21 19.9853 16.9706 22 12 22C7.02944 22 3 19.9853 3 17.5V14.5ZM12 12C7.02944 12 3 9.98528 3 7.5C3 5.01472 7.02944 3 12 3C16.9706 3 21 5.01472 21 7.5C21 9.98528 16.9706 12 12 12Z"></path></svg>
                    <span>容量: ${imageData.size}</span>`;

                // --- 2. 分辨率 (信息项：readonly) ---
                const infoRes = document.getElementById('info-resolution');
                infoRes.className = 'info-text readonly';
                infoRes.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20.0833 10.4999L21.2854 11.2212C21.5221 11.3633 21.5989 11.6704 21.4569 11.9072C21.4146 11.9776 21.3557 12.0365 21.2854 12.0787L11.9999 17.6499L2.71451 12.0787C2.47772 11.9366 2.40093 11.6295 2.54301 11.3927C2.58523 11.3223 2.64413 11.2634 2.71451 11.2212L3.9166 10.4999L11.9999 15.3499L20.0833 10.4999ZM20.0833 15.1999L21.2854 15.9212C21.5221 16.0633 21.5989 16.3704 21.4569 16.6072C21.4146 16.6776 21.3557 16.7365 21.2854 16.7787L12.5144 22.0412C12.1977 22.2313 11.8021 22.2313 11.4854 22.0412L2.71451 16.7787C2.47772 16.6366 2.40093 16.3295 2.54301 16.0927C2.58523 16.0223 2.64413 15.9634 2.71451 15.9212L3.9166 15.1999L11.9999 20.0499L20.0833 15.1999ZM12.5144 1.30864L21.2854 6.5712C21.5221 6.71327 21.5989 7.0204 21.4569 7.25719C21.4146 7.32757 21.3557 7.38647 21.2854 7.42869L11.9999 12.9999L2.71451 7.42869C2.47772 7.28662 2.40093 6.97949 2.54301 6.7427C2.58523 6.67232 2.64413 6.61343 2.71451 6.5712L11.4854 1.30864C11.8021 1.11864 12.1977 1.11864 12.5144 1.30864Z"></path></svg>
                    <span>分辨率: ${imageData.resolution}</span>`;

 /*                // --- 3. 复制代码 (动作项) ---
                const infoCopy = document.getElementById('info-view-link');
                infoCopy.className = 'info-text';
                infoCopy.innerHTML = `
                    <a href="../../url.php?url=${encodeURIComponent(absoluteUrl)}" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M6.9998 6V3C6.9998 2.44772 7.44752 2 7.9998 2H19.9998C20.5521 2 20.9998 2.44772 20.9998 3V17C20.9998 17.5523 20.5521 18 19.9998 18H16.9998V20.9991C16.9998 21.5519 16.5499 22 15.993 22H4.00666C3.45059 22 3 21.5554 3 20.9991L3.0026 7.00087C3.0027 6.44811 3.45264 6 4.00942 6H6.9998ZM8.9998 6H16.9998V16H18.9998V4H8.9998V6Z"></path></svg>
                        复制代码
                    </a>`;
                   */

                // --- 4. 查看原图 (动作项) ---
                const infoImg = document.getElementById('info-view-img');
                infoImg.className = 'info-text';
                infoImg.innerHTML = `
                    <a href="${absoluteUrl}" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 5H4V19L13.2923 9.70649C13.6828 9.31595 14.3159 9.31591 14.7065 9.70641L20 15.0104V5ZM2 3.9934C2 3.44476 2.45531 3 2.9918 3H21.0082C21.556 3 22 3.44495 22 3.9934V20.0066C22 20.5552 21.5447 21 21.0082 21H2.9918C2.44405 21 2 20.5551 2 20.0066V3.9934ZM8 11C6.89543 11 6 10.1046 6 9C6 7.89543 6.89543 7 8 7C9.10457 7 10 7.89543 10 9C10 10.1046 9.10457 11 8 11Z"></path></svg>
                        查看原图
                    </a>`;

                // --- 5. 下载图片 (动作项) ---
                const infoDown = document.getElementById('info-view-down');
                infoDown.className = 'info-text';
                infoDown.innerHTML = `
                    <a href="${absoluteUrl}" download="${filename}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 20.9811C3.64378 20.7257 1 17.9216 1 14.5C1 12.1716 2.22429 10.1291 4.06426 8.9812C4.56469 5.044 7.92686 2 12 2C16.0731 2 19.4353 5.044 19.9357 8.9812C21.7757 10.1291 23 12.1716 23 14.5C23 17.9216 20.3562 20.7257 17 20.9811V21H7V20.9811ZM13 12V8H11V12H8L12 17L16 12H13Z"></path></svg>
                        下载图片
                    </a>`;

                // --- 6. 下载次数 (信息项：readonly) ---
                const infoCount = document.getElementById('info-view-count');
                infoCount.className = 'info-text readonly';
                infoCount.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 12H7V21H3V12ZM17 8H21V21H17V8ZM10 2H14V21H10V2Z"></path></svg>
                    <span>下载次数: ${imageData.viewCount}</span>`;

                // 设置菜单位置并显示
                contextMenu.style.left = `${mouseX}px`;
                contextMenu.style.top = `${mouseY}px`;
                contextMenu.style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching image details:', error);
            });
    });
});

// 点击页面其他地方时关闭菜单
document.addEventListener('click', (event) => {
    if (!contextMenu.contains(event.target)) {
        contextMenu.style.display = 'none';
    }
});

</script>


<!-- ================================================= -->
 
 <!-- 引入 Masonry 和 imagesLoaded 库 -->
<script src="../../index_src/defaul/src/masonry.pkgd.min.js"></script>
<script src="../../index_src/defaul/src/imagesloaded.pkgd.min.js"></script>

<script>
  // 将 msnry 声明在全局，方便切换侧边栏时调用
  window.myMasonry = null;

  imagesLoaded('#grid', function() {
    window.myMasonry = new Masonry('#grid', {
      itemSelector: '.in-loaded',
      // 建议加上百分比宽度自适应支持
      percentPosition: true 
    });
  });
</script>
<!-- ================================================= -->





<!-- ================================================= -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    const docContent = document.querySelector('.a_img');
    if (!docContent) return;

    // 1. 初始化数组
    const images = [];
    const imagesNodeList = docContent.querySelectorAll('img');

    // 2. 预处理数据：将所有图片信息存入 images 数组
    imagesNodeList.forEach((img) => {
        const fullPath = img.src;
        let targetSrc = fullPath;

        // 核心转换：实现你要求的 min 到 max 的逻辑
        if (fullPath.includes('min.php?=min_')) {
            targetSrc = fullPath.replace('min.php?=min_', 'max.php?=max_');
        }

        images.push({
            thumb: fullPath,
            modifiedSrc: targetSrc,
            originalSrc: targetSrc
        });
    });

    // 3. 绑定点击事件：使用索引 index 关联数据
    imagesNodeList.forEach((img, index) => {
        img.addEventListener('click', function (event) {
            event.preventDefault();

            // 实时映射数据给 Fancybox
            const fancyboxItems = images.map((item) => ({
                src: item.modifiedSrc,
                type: 'image',
                thumb: item.thumb,
                opts: {
                    caption: `<a href="${item.originalSrc}" target="_blank" style="color: white;">查看原图</a>`,
                }
            }));

            // 调用 Fancybox
            Fancybox.show(fancyboxItems, {
                startIndex: index, // 确保点击哪张图就从哪张开始
                Thumbs: {
                    autoStart: false,
                    hideOnClose: true
                }
            });
        });
    });
});
</script>
<!-- ======================================================================== -->


</body>
<style>

.footer {
width: 100%;
    text-align: center;
    padding: 20px;
    height: auto;
  /*background-image: linear-gradient(to right, #fed6e3, #c0efec); */
}
</style>


<?php include("./footer.php"); ?>
