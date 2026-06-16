<?php
// index.php
include("./index_src/ini/seting.php");

$directoryPath = './user';
$linkItems = []; 
$allAlbumConfigs = []; 

$getIniConfig = function($path) {
    $config = [];
    if (file_exists($path)) {
        include $path;
    }
    return $config;
};



// --- 扫描与数据准备 ---
if (is_dir($directoryPath)) {
    // 按目录修改时间排序（可选，让最新的相册排在前面）
    $items = array_diff(scandir($directoryPath), ['.', '..']);
    
    foreach ($items as $item) {
        $current_album_dir = $directoryPath . '/' . $item;
        if (is_dir($current_album_dir)) {

               // 探测 3D 预览子目录 (1-5)
    $threeDLinks = [];
    for ($i = 1; $i <= 5; $i++) {
        if (is_dir($current_album_dir . '/' . $i)) {
            $threeDLinks[] = $i;
        }
    } 

            $iniFilePath = $current_album_dir . '/ini.php';
            $config = $getIniConfig($iniFilePath);

            // 跳过禁止读取的相册
            if ((isset($config['list_read']) && $config['list_read'] === '0')) continue;

            // 分类逻辑
            $category = 'Null';
            foreach ($class as $catName => $albums) {
                if (in_array($item, $albums)) { $category = $catName; break; }
            }

            // 轮播图配置收集
            if (isset($config['max'])) {
                $allAlbumConfigs[] = ['dir_name' => $item, 'max_path' => $config['max'], 'title' => $config['title'] ?? '精彩瞬间'];
            }

            // 获取目录的最后修改时间作为创建时间
            $folderTime = date("Y-m-d", filemtime($current_album_dir));

            // 封面图逻辑
            $albumCover = '';
            if (!empty($config['cover'])) {
                $albumCover = htmlspecialchars($config['cover']);
            } else {
                $minPath = isset($config['min']) ? str_replace('../', '', $config['min']) : '';
                if (is_dir($minPath)) {
                    $minFiles = array_diff(scandir($minPath), ['.', '..']);
                    $imageFiles = array_values(array_filter($minFiles, function($f) { return preg_match('/\.(jpg|jpeg|png|webp)$/i', $f); }));
                    if (!empty($imageFiles)) {
                        $albumCover = htmlspecialchars($current_album_dir) . '/min.php?=' . $imageFiles[array_rand($imageFiles)];
                    }
                }
            }
            


    // 构造 HTML 时，将 3D 信息存入 data 属性供 JS 读取
    $threeDData = implode(',', $threeDLinks);


            // 构造 HTML 结构 (注意：此处已移除后端 API 请求)
               $linkItem = '<div class="post-card" data-category="' . htmlspecialchars($category) . '" data-3d="' . $threeDData . '" data-album-url="' . htmlspecialchars($current_album_dir) . '">';
            $linkItem .= '  <div class="post-thumb">';
            $linkItem .= '    <a href="' . htmlspecialchars($current_album_dir) . '/" target="_blank">';
            $linkItem .= '      <img class="thumb" src="' . $albumCover . '" alt="' . htmlspecialchars($config['title']) . '" loading="lazy">';
            $linkItem .= '    </a>';
            $linkItem .= '  </div>';
            $linkItem .= '  <div class="post-content">';
            $linkItem .= '    <div class="post-source"><span class="cat-dot"></span>' . htmlspecialchars($category) . '</div>';
            $linkItem .= '    <a href="' . htmlspecialchars($current_album_dir) . '/" target="_blank" class="title-link">';
            $linkItem .= '      <h2 class="post-title">' . htmlspecialchars($config['title']) . '</h2>';
            $linkItem .= '    </a>';
            $linkItem .= '    <div class="post-meta">';
            // 时间：目录修改时间
            $linkItem .= '      <span class="meta-item"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"></path></svg> ' . $folderTime . '</span>';
            // 访问量：留给 JS 异步填充，data-album 存储目录名
            $linkItem .= '      <span class="meta-item"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path></svg> ';
            $linkItem .= '      <span class="view-count" data-album="' . htmlspecialchars($item) . '">...</span></span>';
            $linkItem .= '    </div>';
            $linkItem .= '  </div>';
            $linkItem .= '</div>';
            $linkItems[] = $linkItem;
        }
    }
}


?>

<?php
// 轮播图代码
// 在测试阶段，可以暂时不包含 seting.php，或者确保它存在
// include("./index_src/seting.php");

$directoryPath = './user';
$allAlbumConfigs = []; // 存储所有相册的配置信息
$allImageLists = []; // 新增：存储每个相册下的所有图片文件列表

// 扫描所有包含 ini.php 的子目录
if (is_dir($directoryPath)) {
    $items = scandir($directoryPath);
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..' && is_dir($directoryPath . '/' . $item)) {
            $iniFilePath = $directoryPath . '/' . $item . '/ini.php';
            if (file_exists($iniFilePath)) {
                // 使用匿名函数来隔离变量范围，避免ini.php中的$config污染全局
                $getIniConfig = function($path) {
                    $config = [];
                    include $path; // ini.php 会定义 $config
                    return $config;
                };
                $config = $getIniConfig($iniFilePath); // 获取当前相册的配置

                // ==========================================================
                // 如果 'list_read' 或 'list_home' 存在且值为 '0'，则跳过此目录
                if ((isset($config['list_read']) && $config['list_read'] === '0') || (isset($config['list_home']) && $config['list_home'] === '0')) {
                    continue; // 跳过当前循环的剩余部分，处理下一个目录
                }
                // ==========================================================

                // 确保 'max' 路径存在
                if (isset($config['max'])) {
                    $allAlbumConfigs[] = [
                        'dir_name' => $item, // 目录名，用于构建链接和图片路径
                        'max_path' => $config['max'], // 图片存放的相对路径
                        'title' => isset($config['title']) ? $config['title'] : '随机图片' // 默认标题
                    ];
                }
            }
        }
    }
} else {
    // 实际应用中，这里可能需要更好的错误处理
    error_log('Error: Directory ' . $directoryPath . ' not found.');
}

$carouselImages = []; // 存储最终用于轮播的图片信息
$numberOfCopies = 2; // 轮播图片数量，修改为每个相册获取2张

// 获取服务器的协议和主机名
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$scriptDir = dirname($_SERVER['PHP_SELF']);

// 用于调试输出到控制台
$debugLogs = [];

// 如果没有找到任何有效的相册配置，则停止
if (empty($allAlbumConfigs)) {
    $debugLogs[] = "警告: 没有找到任何有效的相册配置用于轮播。";
} else {
    // 步骤1：遍历所有相册配置，获取并存储每个相册的图片列表
    foreach ($allAlbumConfigs as $index => $album) {
        $iniFileAbsDir = realpath($directoryPath . '/' . $album['dir_name']);
        $fullMaxDirPath = realpath($iniFileAbsDir . '/' . $album['max_path']);

        if ($fullMaxDirPath && is_dir($fullMaxDirPath)) {
            $maxFiles = scandir($fullMaxDirPath);
            $imageFiles = array_filter($maxFiles, function($file) use ($fullMaxDirPath) {
                return $file !== '.' && $file !== '..' && is_file($fullMaxDirPath . '/' . $file) && !is_dir($fullMaxDirPath . '/' . $file) && preg_match('/\.(jpg|jpeg|png|gif|webp|svg|ico)$/i', $file); // 增加文件类型过滤
            });

            if (!empty($imageFiles)) {
                shuffle($imageFiles); // 随机打乱图片顺序，以便每次获取的图片不同
                $allImageLists[$index] = $imageFiles; // 存储图片列表
            }
        }
    }

    // 步骤2：执行两次循环，每次从每个目录获取一张图片
    for ($i = 0; $i < $numberOfCopies; $i++) {
        foreach ($allAlbumConfigs as $index => $album) {
            // 检查当前相册是否有足够的图片
            if (isset($allImageLists[$index]) && count($allImageLists[$index]) > $i) {
                $randomImageName = $allImageLists[$index][$i]; // 获取第i+1张图片

                // 构建 A 链接
                $linkUrl = $protocol . "://" . $host . $scriptDir . "/user/" . $album['dir_name'] . "/";

                // 构建图片 URL
                $imageUrl = $linkUrl . "max.php?=" . $randomImageName;

                $carouselImages[] = [
                    'src' => htmlspecialchars($imageUrl),
                    'href' => htmlspecialchars($linkUrl),
                    'alt' => htmlspecialchars($album['title'])
                ];

                $debugLogs[] = "成功获取第 " . ($i + 1) . " 张图片，来自相册: " . $album['dir_name'] . "，文件名: " . $randomImageName;

            } else {
                $debugLogs[] = "警告: 相册 '" . $album['dir_name'] . "' 没有足够的图片（至少需要 " . ($i + 1) . " 张）。";
            }
        }
    }
}
?>




<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title><?php echo $config_home['title'] ;?></title>
    <link rel="stylesheet" href="./index_src/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="./index_src/css/home_1.css1">
    <link rel="stylesheet" href="./index_src/css/index.css">
<style>
/* 遮罩层 */
.main-container {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.3);
    z-index: 10001;
}

/* 菜单主体 */
.custom-context-menu {
    position: absolute; /* 相对于 main-container 定位 */
    background: rgba(17, 17, 17, 0.8);
    /*backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.1);*/
        background-color: rgb(180 171 171 / 55%);
    backdrop-filter: blur(4px);
    border-radius: 12px;
    width: 180px;
    padding: 8px 0;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    color: #fff;
    /* 居中计算通过 JS 完成 */
}

/* 基础状态 */
.info-text {
    padding: 0; /* 建议 padding 给 a 标签，增加点击热区 */
    transition: background 0.2s;
}

.info-text a { 
    color: #eee; 
    text-decoration: none; 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    font-size: 15px;
    padding: 10px 16px; /* 将 padding 移到这里 */
    width: 100%;       /* 确保 a 标签撑满整个容器 */
    box-sizing: border-box;
    transition: color 0.2s;
}

/* 核心修复：当 .info-text 被 hover 时，改变内部 a 的颜色 */
.info-text:not(.readonly):hover {
    background-color: rgba(255, 255, 255, 0.91);
    backdrop-filter: blur(2px);
}

/* 只要鼠标进入行内，文字颜色就变深 */
.info-text:not(.readonly):hover a {
    color: #ae4b2d;
}

/* 图标颜色同步（如果是 currentColor） */
.info-text:not(.readonly):hover svg {
    fill: #ae4b2d; /* 或者 stroke: #ae4b2d; 取决于你的 SVG 结构 */
}

.info-text svg { width: 18px; height: 18px; fill: currentColor; }
.menu-divider { border: none; height: 1px; background: rgba(255,255,255,0.1); margin: 5px 0; }

</style>

</head>

<body oncontextmenu="return false;">

<div class="main-container" id="global-menu-container">
    <div class="custom-context-menu" id="context-menu">
        <div id="menu-dynamic-links"></div>
        
        <hr class="menu-divider">
        <div id="info-view-down" class="info-text">
            <a href="javascript:void(0);">
                <svg viewBox="0 0 24 24"><path d="M12 10.5858L16.95 5.63574L18.3642 7.04996L13.4142 12L18.3642 16.95L16.95 18.3642L12 13.4142L7.04996 18.3642L5.63574 16.95L10.5858 12L5.63574 7.04996L7.04996 5.63574L12 10.5858Z"></path></svg>
                我不想看了
            </a>
        </div>

    </div>
</div>

    <div class="index-banner">
        <div class="swiper mySwiper">
            <div class="swiper-wrapper">
                <?php foreach ($carouselImages as $img): ?>
                <div class="swiper-slide">
                    <a href="#home_anchor"><img src="<?= $img['src'] ?>" alt="<?= $img['alt'] ?>"></a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

<div class="fullscreen-overlay">
            <h1 style="font-size:3.8em;">捕捉瞬间，定格美好</h1>
            <p style="font-size:1.4em;">探索我们精选的高质量摄影作品，每一张都是一个故事。</p>
            <p style="font-size:1.4em;"> 截至目前本站已托管 <span id="total-count" class="image-count" style="color:#ff5722;">0</span> 张图片</p>
        </div>

      <!--   <div class="fullscreen-overlay">
            <h1>捕捉瞬间，定格美好</h1>
            <p style="font-size:1.2rem;">截至目前本站已托管 <span id="total-count" style="font-weight:bold; color:#ff5722;">0</span> 张图片</p>
        </div>
        -->
    </div>

    <div id="home_anchor"></div>

<!-- Modal modalA About Me-->
<div class="modal-wrapper" id="modalA" style="z-index: 9999;">
	<div class="modal-body card">
		<div class="modal-header">
			<h2 class="heading">About</h2>
			<a href="#!" role="button" class="close" aria-label="close this modal">
				<svg viewBox="0 0 24 24">
					<path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
				</svg>
			</a>
		</div>

        <span>本站同款程序已开源：</span>
<p>
<a href="https://www.ximi.me/post-6027.html" target="_blank">详情访问：https://www.ximi.me/post-6027.html</a><br>
</p>
		<span>当前版本：V5.01</span></br>
<span>支持PHP7.0-8.5，未使用数据库，支持虚拟机搭建.</span>
<p>欢迎有开发能力的小伙伴一同开发完善.</p>

<span style="">
    <a href="#modalB" role="button" class="">登陆</a>
</span>
	</div>
	<a href="#!" class="outside-trigger"></a>
</div>

<!-- Modal modalB 后台登陆-->
<div class="modal-wrapper" id="modalB" style="z-index: 9999;">

<div class="modal-body card">
		<div class="modal-header"style="margin-bottom:8px;">
			<h2 class="heading">后台登陆</h2>
			<a href="#!" role="button" class="close" aria-label="close this modal">
				<svg viewBox="0 0 24 24">
					<path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
				</svg>
			</a>
		</div>
		<!--<p>登陆接口已被管理员隐藏</p>-->
 
        <div class="login_dvi">
        <form action="./admin/login.php" method="post">
            <input type="text" name="username" placeholder="用户名" required="">
            <input type="password" name="password" placeholder="密码" required="">
            <input type="submit" value="登录">
       </form>
    </div>
    
	</div>

	<a href="#!" class="outside-trigger"></a>
</div>



    <div class="Nv_title" id="navTitle">
        <div class="ll"><a href="<?php echo $config_home['home_url'] ;?>"><?php echo $config_home['title'] ;?></a></div>
        
        <div class="rr desktop-menu">
            <a href="#home_anchor" class="album-category" data-category="all">首页</a>
            <?php foreach ($class as $cat => $val): ?>
                <a href="#home_anchor" class="album-category" data-category="<?= htmlspecialchars($cat) ?>"><?= $cat ?></a>
            <?php endforeach; ?>
            
                    <?php
echo '    <a href="#home_anchor" style=" " class="album-category" data-category="Null">更多</a>';
        ?>
            <a href="#modalA">关于</a>
        </div>

        <div class="mobile-menu-toggle" id="menuToggle">
            <svg class="icon-menu" viewBox="0 0 24 24"><path fill="currentColor" d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
            <svg class="icon-close" viewBox="0 0 24 24" style="display:none;"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </div>
    </div>

    <div class="mobile-drawer" id="mobileDrawer">
        <div class="drawer-content">
            <a href="#home_anchor" class="mobile-link album-category" data-category="all">首页展示</a>
            <?php foreach ($class as $cat => $val): ?>
                <a href="#home_anchor" class="mobile-link album-category" data-category="<?= htmlspecialchars($cat) ?>"><?= $cat ?></a>
            <?php endforeach; ?>
              <?php
               echo '    <a href="#home_anchor" style=" " class="mobile-link album-category" data-category="Null">更多美图</a>';
?>
            <a href="#modalA" class="mobile-link">关于本站</a>
        </div>
    </div>
    <div class="drawer-overlay" id="drawerOverlay"></div>

    <main>
        <div class="album-grid" id="mainGrid">
            <?php if (!empty($linkItems)) echo implode('', $linkItems); ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2025-2026 [ ximi ] All Rights Reserved.</p>
    </footer>

    <script src="./index_src/js/swiper-bundle.min.js"></script>

  <script>
    
   // 初始化轮播
new Swiper(".mySwiper", { 
    loop: true, 
    autoplay: { delay: 5000, disableOnInteraction: false } 
});

const nav = document.getElementById('navTitle');
const menuToggle = document.getElementById('menuToggle');
const mobileDrawer = document.getElementById('mobileDrawer');
const drawerOverlay = document.getElementById('drawerOverlay');
const iconMenu = document.querySelector('.icon-menu');
const iconClose = document.querySelector('.icon-close');

// 1. PC端滚动控制
// 1. PC端滚动控制
function handleScroll() {
    // 只有在宽度大于 768px 时才执行隐藏逻辑
    if (window.innerWidth > 768) {
        // 判断滚动高度是否大于 50
        if (window.scrollY > 50) {
            nav.classList.add('show');
        } else {
            nav.classList.remove('show');
        }
    }
}

// 绑定滚动事件
window.addEventListener('scroll', handleScroll);

// 页面加载时先跑一次，防止刷新后停留在页面中间但不显示菜单
window.addEventListener('load', handleScroll);

// 窗口大小改变时也跑一次，防止从手机端拉大到PC端时状态错误
window.addEventListener('resize', handleScroll);

// 2. 抽屉菜单核心控制 (修复点击无效)
function toggleMenu() {
    const isActive = mobileDrawer.classList.toggle('active');
    drawerOverlay.classList.toggle('active');
    
    // 切换图标
    iconMenu.style.display = isActive ? 'none' : 'block';
    iconClose.style.display = isActive ? 'block' : 'none';
    
    // 锁定背景滚动
    document.body.style.overflow = isActive ? 'hidden' : '';
}

menuToggle.addEventListener('click', (e) => {
    e.preventDefault();
    toggleMenu();
});

drawerOverlay.addEventListener('click', toggleMenu);

// 3. 分类过滤逻辑
document.querySelectorAll('.album-category').forEach(btn => {
    btn.addEventListener('click', function() {
        const cat = this.getAttribute('data-category');
        document.querySelectorAll('.post-card').forEach(card => {
            card.style.display = (cat === 'all' || card.getAttribute('data-category') === cat) ? 'flex' : 'none';
        });
        
        // 手机端点击后自动收起
        if (mobileDrawer.classList.contains('active')) toggleMenu();
    });
});

// 4. 异步数据统计
document.addEventListener('DOMContentLoaded', () => {
    // 总计
    fetch('./index_src/ini/count_images.php').then(r => r.json()).then(d => {
        if(d.total_images) document.getElementById('total-count').innerText = d.total_images;
    });

    // 单个卡片访问量
    document.querySelectorAll('.view-count').forEach(span => {
        const album = span.getAttribute('data-album');
        if(!album) return;
        fetch(`https://www.ximi.me/usr/tj/cx.php?a=img.ximi.me&b=https://img.ximi.me/user/${album}/`)
            .then(r => r.json()).then(data => {
                span.innerText = data.url_count || '0';
            }).catch(() => span.innerText = '0');
    });
});


document.addEventListener('DOMContentLoaded', function() {
    const menuContainer = document.getElementById('global-menu-container');
    const menuBody = document.getElementById('context-menu');
    const dynamicBox = document.getElementById('menu-dynamic-links');

    // 核心显示函数
    function handleImageClick(e, cardElement, thumbElement) {
        e.preventDefault();
        
        const albumUrl = cardElement.getAttribute('data-album-url');
        const threeDData = cardElement.getAttribute('data-3d'); // 获取如 "1,2"
        const threeDArray = threeDData ? threeDData.split(',') : [];

        // 1. 清空并构造菜单内容
        let html = `
            <div class="info-text">
                <a href="${albumUrl}/index-1.php" target="_blank">
                   <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M7 3C6.44772 3 6 3.44772 6 4V7H3C2.44772 7 2 7.44772 2 8V20C2 20.5523 2.44772 21 3 21H17C17.5523 21 18 20.5523 18 20V17H21C21.5523 17 22 16.5523 22 16V4C22 3.44772 21.5523 3 21 3H7ZM17 7H8V5H20V15H18V8C18 7.44772 17.5523 7 17 7ZM16 9V15.7394L11.4911 11.6404L4 18.6321V9H16ZM11.5089 14.3596L16 18.4424V19H6.53702L11.5089 14.3596ZM7 13.5C7.82843 13.5 8.5 12.8284 8.5 12C8.5 11.1716 7.82843 10.5 7 10.5C6.17157 10.5 5.5 11.1716 5.5 12C5.5 12.8284 6.17157 13.5 7 13.5Z"></path></svg>
                    平铺预览
                </a>
            </div>`;

        threeDArray.forEach(num => {
            html += `
                <div class="info-text">
                    <a href="${albumUrl}/${num}/" target="_blank">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13 21V23H11V21H3C2.44772 21 2 20.5523 2 20V4C2 3.44772 2.44772 3 3 3H9C10.1947 3 11.2671 3.52375 12 4.35418C12.7329 3.52375 13.8053 3 15 3H21C21.5523 3 22 3.44772 22 4V20C22 20.5523 21.5523 21 21 21H13ZM20 19V5H15C13.8954 5 13 5.89543 13 7V19H20ZM11 19V7C11 5.89543 10.1046 5 9 5H4V19H11Z"></path></svg>
                        3D预览 ${num}
                    </a>
                </div>`;
        });
        dynamicBox.innerHTML = html;

        // 2. 显示容器
        menuContainer.style.display = 'block';

        // 3. 计算位置：让菜单在图片的中心
        const rect = thumbElement.getBoundingClientRect();
        const menuWidth = menuBody.offsetWidth;
        const menuHeight = menuBody.offsetHeight;

        // 计算中心点
        let centerX = rect.left + (rect.width / 2) - (menuWidth / 2);
        let centerY = rect.top + (rect.height / 2) - (menuHeight / 2);

        // 简单边界检查
        if (centerX < 10) centerX = 10;
        if (centerX + menuWidth > window.innerWidth) centerX = window.innerWidth - menuWidth - 10;

        menuBody.style.left = centerX + 'px';
        menuBody.style.top = centerY + 'px';
    }

    // 事件委托：监听所有图片的左键和右键
/*
    document.getElementById('mainGrid').addEventListener('mousedown', function(e) {
        const thumb = e.target.closest('.post-thumb');
        if (!thumb) return;

        // 0 = 左键, 2 = 右键
        if (e.button === 0 || e.button === 2) {
            const card = thumb.closest('.post-card');
            handleImageClick(e, card, thumb);
        }
    });
    */

    // 禁用默认右键菜单
    document.getElementById('mainGrid').addEventListener('contextmenu', e => e.preventDefault());

    // 关闭逻辑
    const closeMenu = () => menuContainer.style.display = 'none';
    document.getElementById('info-view-down').onclick = closeMenu;
    menuContainer.onclick = (e) => { if(e.target === menuContainer) closeMenu(); };
});
    


    
    </script>

</body>
</html>