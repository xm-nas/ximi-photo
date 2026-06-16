<?php
//后台批管理2
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("./header.php");
include("./ini.php");

session_start();

//===================================================
// 获取动态登录标识符
$logged_in_key = $config['login_admin'];

// 检查用户是否已登录
if (!(isset($_SESSION['logged_in_admin']) && $_SESSION['logged_in_admin'] === true) && (empty($_SESSION[$logged_in_key]) || $_SESSION[$logged_in_key] !== true)) {
    // 如果不是总管理员登录状态，并且用户未登录，则重定向到登录页面
    header('Location: login.php');
    exit();
}



// 设置管理员登录状态
$_SESSION['admin_logged_in'] = true;


// 获取当前目录下所有图片文件
$uploadDir = $config['tu_1']; // '../img/update/img/hide_1/';
//$imageFiles = glob($uploadDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
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

// 删除图片逻辑
if (isset($_GET['delete'])) {
    $fileToDelete = $_GET['delete'];
    $filePath = realpath($uploadDir . basename($fileToDelete));
    
    // 确保文件存在且路径安全
    if (file_exists($filePath)) {
        // 获取文件名
        $fileName = basename($filePath);
        
        // 构建缩略图路径
        $thumbnailDir = $config['min']; // 缩略图1目录
        $maxDir = $config['max']; // 缩略图2目录
        $thumbnailPath = $thumbnailDir . 'min_' . $fileName;
        $maxPath = $maxDir . 'max_' . $fileName;
        
        // 删除主图片
        unlink($filePath);
        
        // 删除对应的缩略图1
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
        
        // 删除对应的缩略图2
        if (file_exists($maxPath)) {
            unlink($maxPath);
        }
        
        // 重定向到当前页面
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}



// 分页设置
$imagesPerPage = 300; // 每页显示的图片数量
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>



    <title>图片展示</title>
    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        /* 图片展示区域样式 */

.gallery {
    display: flex;           /* 使用 Flexbox 布局 */
 flex-wrap: wrap;         /* 自动换行，保持响应式布局 */
    gap: 30px;               /* 模块之间的间距 */
    justify-content: center; /* 将模块组合水平居中排列 */
    align-items: center;     /* 子元素在垂直方向居中对齐 */
    width: 100%;             /* 确保父容器占满宽度 */
height: 100%; /* 确保容器有高度 */
    margin: auto;          /* 确保整个容器水平居中 */
 background-color: rgba(255, 255, 255, 0.01); /* 半透明背景颜色，80% 不透明度 */
padding-bottom: 15px;
}

.gallery-item {
    width: 250px; /* 外边框宽度 */
    height: 225px; /* 外边框高度 */
    border: 1px solid rgba(0, 0, 0, 0.2); /* 透明外边框  padding: 10px;*/
    box-sizing: border-box;
    text-align: center;
    position: relative;
 padding-top: 18px;
    background-color: #f0f0f0; /* 背景颜色，用于小图片 */
    border-radius: 5px;
    display: flex; /* 使用 Flexbox 布局 */
    flex-direction: column; /* 设置主轴方向为列，允许上下居中 */
    justify-content: center; /* 垂直居中对齐内容 */
    align-items: center; /* 水平居中对齐内容 */
    overflow: hidden; /* 确保图片不超出边框 */
 background-color: rgba(255, 255, 255, 0.15); /* 半透明背景颜色，80% 不透明度 */

}

.gallery-item img {
    max-width: 100%; /* 图片最大宽度为容器的 100% */
    max-height: 180px; /* 图片最大高度为 170px */
    object-fit: contain; /* 保持图片长宽比例，适应容器 */
    margin: auto; /* 确保图片在容器内水平居中 */
    display: block; /*margin-top: 15px; 确保图片为块级元素，避免和其他内容冲突 */
}


.gallery img {
    max-width: 100%; /* 图片最大宽度为容器的 100% */
    max-height: 180px; /* 图片最大高度为 170px */
    width: auto; /* 自动设置宽度，保持纵横比 */
    height: auto; /* 自动设置高度，保持纵横比 */
    object-fit: contain; /* 保持图片长宽比例，适应容器 */
    border-radius: 8px; /* 设置图片圆角 */
    background-color: #f0f0f0; /* 背景颜色，用于填充空白区域 */
    display: block; /* 设置图片为块级元素 */
    margin: auto; /* 确保图片水平居中 */
    transition: transform 0.3s ease, background-color 0.3s ease; /* 平滑的变换效果 */
object-fit: contain ; /* 或使用 以保持比例 padding-top: 15px;*/

}

.gallery img:hover {
    transform: scale(1.1); /* 悬停时的放大效果 */
    background-color: #e0e0e0; /* 可选：悬停时背景颜色变化 */

}

   .gallery-item a {
            color: #333;
            font-size: 14px;
            display: block;
            margin-top: 5px;
            text-decoration: none;
        }

   .gallery-item a:hover {
             color: #ff6a6a;
            display: block;
            margin-top: 5px;
            
        }


    .delete-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        background-color: red;
        color: white;
        border: none;
        padding: 5px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 12px;
    }

    .delete-btn:hover {
        background-color: darkred;
    }
.img-body{
    width: calc(100% - 160px); /* 容器宽度为 800 像素 */
    margin: 70px auto 60px; /* 容器顶部距离页面 150 像素，左右居中对齐，底部无外边距 */
    padding: 10px 20px; /* 上下内边距为 0，左右内边距为 20 像素 */
    box-sizing: border-box; /* 包括内边距和边框在内计算容器宽度 */
    padding-bottom: 15px; /* 增加下边距，避免内容被页脚遮挡 */
    background-color: 	#f9f9f9; /* 设置背景颜色为白色    #ffffff       	#696969          */
  border-radius: 10px;
  /*box-shadow:  */
   /*               -3px 0 10px rgba(0, 0, 0, 0.3),左边阴影 */
   /*                3px 0 10px rgba(0, 0, 0, 0.3);右边阴影 */
/*opacity: 0.6;  设置透明度为 50% */
 background-color: rgba(255, 255, 255, 0.3); /* 半透明背景颜色，80% 不透明度 */
}

@media screen and (max-width: 1366px) {
.img-body{
    width: calc(100% - 40px); /* 容器宽度为 800 像素 */
}
}

.content2 {
    font-size: 22px; /* 设置正文字体大小 */
    line-height: 1.9; /* 设置行距 */
    letter-spacing: 0.2em; /* 设置字体间距 */
    overflow: hidden; /* 隐藏溢出的内容 */
    text-overflow: ellipsis; /* 当内容溢出时显示省略号 */
font-weight: 700; /* 使用粗体 */
}


.list_img {
    display: flex; /* 使用 Flexbox 布局 */
    justify-content: space-between; /* 使子元素分布在行的两端 */
    align-items: center; /* 垂直居中对齐子元素 */
    width: 100%; /* 宽度设置为100%以适应容器 */
     height: 48px; /* 设置高度 */
     padding: 2px; /* 可选：设置内边距 */
    /*box-sizing: border-box;  包括内边距和边框在宽度和高度计算中 */
}

.left {
    /* 左边样式 */
    text-align: left; /* 确保文本在容器内左对齐 */
    max-width: calc(100% - 175px); /* 设置最大宽度为 100% - 75px */
    white-space: nowrap; /* 禁止文本换行 */
    overflow: hidden; /* 如果内容超出，隐藏溢出 */
    text-overflow: ellipsis; /* 使用省略号表示溢出的文本 */
    height: 44px; /* 设置高度 */
    font-size: 16px; /* 设置字体大小 */
    color: #000; /* 设置字体颜色 */
}

.right {
    /* 右边样式 */
    text-align: right; /* 确保文本在容器内右对齐 */
    max-width: 175px; /* 设置最大宽度为 75px */
    white-space: nowrap; /* 禁止文本换行 */
    overflow: hidden; /* 隐藏溢出 */
    text-overflow: ellipsis; /* 使用省略号表示溢出的文本 */
    height: 44px; /* 设置高度 */
    font-size: 16px; /* 设置字体大小 */
    color: #000; /* 设置字体颜色 */
}

.image_title{
    position: absolute;/* position: fixed; 将页脚固定在页面底部 */
    top: 175px;
    left: 0; /* 页脚左侧与页面左边缘对齐 */
    bottom: 0; /* 页脚底部与页面底边缘对齐 */
    width: 100%; /* 页脚宽度占据整个页面 */
    text-align: center; /* 文本内容居中对齐 */
  background-color:#d3e3fd; /*   设置背景颜色为白色    #ffffff       	#696969          */
  /*   border-top: 1px solid #f9f9f9; 顶部添加一个 1 像素宽的灰色边框 */
    margin-top: 10px;
    padding: 6px 5px; /* 上下内边距为 8 像素，左右内边距为 0 */
     /* line-height: 8px;设置文本行距为 10 像素 */
    font-size: 16px; /* 设置文本字体大小为 14 像素 */
    /*background-image: linear-gradient(to right, #fbc2eb, #a6c1ee);*/
    /*background-image: linear-gradient(to right, #fed6e3, #c0efec);*/
  height: 25px;
font-weight: 700; /* 使用粗体 */

 background-color: rgba(255, 255, 255, 0.09);
}


     .pagination {
            text-align: center; /* 将分页链接居中 */
            margin-top: 20px; /* 分页链接与图片展示区的距离 */
        }

        .pagination a {
            margin: 0 5px; /* 分页链接之间的间距 */
            text-decoration: none; /* 去掉链接下划线 */
            color: #333; /* 链接颜色 */
            padding: 5px 10px; /* 链接内边距 */
            border: 1px solid #ddd; /* 边框 */
            border-radius: 4px; /* 圆角 */
            line-height: 2.3;/*设置行距 */

        }

        .pagination a:hover {
            background-color: #f0f0f0; /* 悬停时背景颜色 */
        }

        .pagination .active {
            background-color: #007bff; /* 当前页的背景颜色 */
            color: white; /* 当前页的字体颜色 */
            border: 1px solid #007bff; /* 当前页边框颜色 */
        }
     </style>
    <script>
         确认删除的JavaScript函数
        function confirmDelete() {
        return confirm("确定要删除这张图片吗？");
       }
    </script>
</head>
<body>
<div class="img-body">
    <!-- 图片展示区 防盗图床 | -->
    <div class="list_img"> 
        <div class="left">
            <div class="content2" style="font-family: 'Noto Serif SC', serif;">
                <div style="color: #ff6a6a;">图片预览：</div>
            </div>
        </div>
        <div class="right">
            <div class="content2" style="font-family: 'Noto Serif SC', serif;">
                <div style="color: #ff6a6a;"><a href="./list-1.php" target="_self">列表显示</a></div>
            </div>  
        </div>
    </div>
    <hr>
    <br>
    
    <div class="gallery">
        <?php foreach ($currentImages as $image): ?>
            <div class="gallery-item">
                <!--<a href="https://img.cd/img/max.php?=<?= urlencode(basename($image)) ?>" target="_blank">  </a> -->
                    <img src="<?= htmlspecialchars("./min.php?=min_" . basename($image)) ?>" alt="<?= basename($image) ?>">
               

                <div class="image_title">
                    <a href="./img.php?=<?= urlencode(basename($image)) ?>" target="_blank"><?= basename($image) ?></a>
                </div>
                
                <!-- 删除按钮，增加onclick确认提示 -->
                <form method="GET" style="display: inline;" onsubmit="return confirmDelete();">
                    <button type="submit" name="delete" value="<?= htmlspecialchars(basename($image)) ?>" class="delete-btn">
                        删除
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>



    <!-- 分页链接 -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>





<script>
    document.addEventListener('DOMContentLoaded', function () {
        const images = [];
        const docContent = document.querySelector('.gallery');

        // 收集所有图片的信息
        docContent.querySelectorAll('img').forEach((img) => {
            const imgName = img.src.split('=')[1].replace('min_', ''); // 去除 'min_' 字符
            const modifiedSrc = `max.php?=max_${imgName}`; // 预览图片链接
            const originalSrc = `img.php?=${imgName}`; // 原图链接
            images.push({ modifiedSrc, originalSrc, thumb: img.src });
        });

        // 为每张图片添加点击事件
        docContent.querySelectorAll('img').forEach((img, index) => {
            img.addEventListener('click', function (event) {
                event.preventDefault();

                // 输出当前点击的索引
                console.log("Clicked index:", index); // 调试输出

                // 仅在点击时生成 `fancyboxItems`，并传递当前索引
                const fancyboxItems = images.map((item) => ({
                    src: item.modifiedSrc,
                    type: 'image',
                    thumb: item.thumb,
                    opts: {
                        caption: `<a href="${item.originalSrc}" target="_blank" style="color: white;">查看原图</a>`,
                    }
                }));

                // 显示 Fancybox，并确保使用点击的当前索引ximi
                Fancybox.show(fancyboxItems, {
                    Thumbs: {
                        autoStart: true, // 取消自动显示缩略图面板
                    },
                    startIndex: index, // 使用当前索引
                });
            });
        });
    });
</script>




</body>
<?php include("./footer.php"); ?>
