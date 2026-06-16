<?php
// album_generator.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("./ini.php");

// 定义普通相册的链接
const NORMAL_ALBUM_LINK = './index-1.php';
$page_title = $config['title'];

/**
 * 将阿拉伯数字转换为中文大写数字
 */
function convertToChineseNum($num) {
    $chineseNums = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
    $result = '';
    $numStr = (string)$num;
    for ($i = 0; $i < strlen($numStr); $i++) {
        $digit = (int)$numStr[$i];
        $result .= $chineseNums[$digit];
    }
    return $result;
}

// 扫描 3D 目录
$threeDAlbumDirs = [];
$entries = scandir('.');
if ($entries) {
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        if (is_dir('./' . $entry) && is_numeric($entry)) {
            $threeDAlbumDirs[] = $entry;
        }
    }
    sort($threeDAlbumDirs, SORT_NUMERIC);
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?>-希米图册</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="./index_src/icons/favicon.ico" type="image/x-icon">
    
    <style>
        body { margin: 0; padding: 0; background-color: #ffffffff; font-family: -apple-system, sans-serif; }

        /* 顶部导航 */
        .fbTopBar {
            position: fixed; z-index: 100; top: 0; left: 0; width: 100%; height: 40px;
            background-color: rgba(255, 255, 255, 0.99); backdrop-filter: blur(10px);
            display: flex; align-items: center; justify-content: center; padding: 10px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .fbTopBar a { text-decoration: none; color: #555; font-size: 24px; font-weight: bold; }

        /* 背景图片展示 */
        .post-thumb { margin-top: 80px; text-align: center; padding: 20px; }
        .thumb { max-width: 100%; height: auto; border-radius: 8px; cursor: pointer; transition: transform 0.3s; }
        .thumb:hover { transform: scale(1.01); }

        /* 遮罩层 - 默认开启以实现自动弹窗 */
        .main-container {
            display: block; 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.4); z-index: 9998;
        }

        /* 玻璃效果菜单 - 初始居中 */
        .custom-context-menu {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 210px;
            background-color: rgba(180, 171, 171, 0.55);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 8px 0;
            border-radius: 12px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: opacity 0.2s;
        }

        /* 菜单行 */
        .info-text {
            margin: 0;
            display: flex;
            align-items: center;
            min-height: 44px;
            transition: background 0.2s ease;
        }

        .info-text a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.9); /* 默认白色文字 */
            width: 100%;
            height: 100%;
            padding: 0 16px;
            gap: 12px;
            font-size: 15px;
            transition: color 0.2s ease;
        }

        /* 核心修复：Hover 时背景变白，文字变深 */
        .info-text:not(.readonly):hover {
            background-color: rgba(255, 255, 255, 0.92);
        }
        .info-text:not(.readonly):hover a {
            color: #521919; /* 变回深色文字 */
        }

        /* 图标 */
        .custom-context-menu svg {
            width: 18px; height: 18px;
            fill: currentColor; /* 让图标颜色随文字变化 */
            flex-shrink: 0;
        }

        .menu-divider {
            height: 1px; background-color: rgba(255, 255, 255, 0.1);
            margin: 5px 0; border: none;
        }

        /* 移动端强制居中 */
        @media (max-width: 768px) {
            .custom-context-menu {
                width: 80%;
                max-width: 240px;
            }
        }
    </style>
</head>

<body>
    <div class="fbTopBar">
        <a href="../../"><span><?php echo htmlspecialchars($page_title); ?></span></a>
    </div>

<!--
    <div class="post-thumb">
        <img class="thumb" src="https://img.ximi.me/index_src/pic.php" alt="Gallery Image">
    </div>
 -->
    <div class="main-container">
        <div class="custom-context-menu" id="context-menu">
            
            <div class="info-text">
                <a href="<?php echo htmlspecialchars(NORMAL_ALBUM_LINK); ?>" target="_blank">
                    <svg viewBox="0 0 24 24"><path d="M7 3C6.44772 3 6 3.44772 6 4V7H3C2.44772 7 2 7.44772 2 8V20C2 20.5523 2.44772 21 3 21H17C17.5523 21 18 20.5523 18 20V17H21C21.5523 17 22 16.5523 22 16V4C22 3.44772 21.5523 3 21 3H7ZM17 7H8V5H20V15H18V8C18 7.44772 17.5523 7 17 7ZM16 9V15.7394L11.4911 11.6404L4 18.6321V9H16Z"></path></svg>
                    平铺预览
                </a>
            </div>

            <?php if (!empty($threeDAlbumDirs)): ?>
                <?php foreach ($threeDAlbumDirs as $dirName): ?>
                    <div class="info-text">
                        <a href="./<?php echo htmlspecialchars($dirName); ?>/" target="_blank">
                            <svg viewBox="0 0 24 24"><path d="M13 21V23H11V21H3C2.44772 21 2 20.5523 2 20V4C2 3.44772 2.44772 3 3 3H9C10.1947 3 11.2671 3.52375 12 4.35418C12.7329 3.52375 13.8053 3 15 3H21C21.5523 3 22 3.44772 22 4V20C22 20.5523 21.5523 21 21 21H13ZM20 19V5H15C13.8954 5 13 5.89543 13 7V19H20ZM11 19V7C11 5.89543 10.1046 5 9 5H4V19H11Z"></path></svg>
                            3D预览<?php echo convertToChineseNum($dirName); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <hr class="menu-divider">

            <div id="info-view-down" class="info-text">
                <a href="javascript:void(0);">
                    <svg viewBox="0 0 24 24"><path d="M12 10.5858L16.95 5.63574L18.3642 7.04996L13.4142 12L18.3642 16.95L16.95 18.3642L12 13.4142L7.04996 18.3642L5.63574 16.95L10.5858 12L5.63574 7.04996L7.04996 5.63574L12 10.5858Z"></path></svg>
                    我不看了
                </a>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const thumbImg = document.querySelector('.thumb');
    const mainContainer = document.querySelector('.main-container');
    const menu = document.getElementById('context-menu');
    const closeBtn = document.getElementById('info-view-down');

    // 唤起菜单函数
    function showMenu(e) {
        if (e) e.preventDefault();
        mainContainer.style.display = 'block';

        // 如果是通过右键/点击触发且在 PC 端，移动到鼠标位置
        if (e && e.clientX && window.innerWidth > 768) {
            let x = e.clientX;
            let y = e.clientY;
            const menuWidth = 210;
            const menuHeight = menu.offsetHeight || 200;

            if (x + menuWidth > window.innerWidth) x -= menuWidth;
            if (y + menuHeight > window.innerHeight) y -= menuHeight;

            menu.style.left = x + 'px';
            menu.style.top = y + 'px';
            menu.style.transform = 'none'; // 取消居中
        } else {
            // 默认或移动端回到居中位置
            resetToCenter();
        }
    }

    function resetToCenter() {
        menu.style.left = '50%';
        menu.style.top = '50%';
        menu.style.transform = 'translate(-50%, -50%)';
    }

    // 绑定交互
    if(thumbImg) {
        thumbImg.addEventListener('click', showMenu);
        thumbImg.addEventListener('contextmenu', showMenu);
    }

    // 关闭逻辑
    closeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        mainContainer.style.display = 'none';
    });

    // 点击遮罩空白处关闭并重置位置
    mainContainer.addEventListener('click', (e) => {
        if (e.target === mainContainer) {
            mainContainer.style.display = 'none';
            resetToCenter();
        }
    });

    // 阻止主容器的默认右键
    mainContainer.addEventListener('contextmenu', e => e.preventDefault());
});
</script>

</body>
</html>