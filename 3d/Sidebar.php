<style>
    /* --- 侧边栏主题与全局变量 --- */
    :root {
        --sidebar-bg: #222e3c;       
        --sidebar-width: 260px;
        --navbar-height: 56px;       
        --text-color: #e9ecef;
        --hover-color: #97989a;      
        --sub-bg: #1d2733;           
        --active-bg: #2b3947;
    }

    /* =================================================== */
    /* =============== 【核心修改】PC 端默认样式 ( > 768px) =============== */
    /* =================================================== */
    body {
        margin: 0;
        padding-left: var(--sidebar-width); 
        padding-top: var(--navbar-height); 
        font-family: sans-serif;
        transition: padding-left 0.3s ease; 
        background-color: #f5f7fb;
    }

    /* PC 端隐藏侧边栏的类 */
    body.sidebar-hidden {
        padding-left: 0;
    }

    .top-navbar {
        position: fixed;
        top: 0;
        left: var(--sidebar-width);
        right: 0;
        height: var(--navbar-height);
        background-color: #ffffff;
        box-shadow: 0 0 2rem 0 rgba(41,48,66,.1);
        display: flex;
        align-items: center;
        padding: 0 24px;
        z-index: 999;
        transition: left 0.3s ease;
    }

    body.sidebar-hidden .top-navbar {
        left: 0;
    }

    #left-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: var(--sidebar-width);
        background-color: var(--sidebar-bg);
        box-sizing: border-box;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        padding: 0;
        transition: transform 0.3s ease;

        overflow-y: scroll; 
        scrollbar-width: none; 
        -ms-overflow-style: none;  
    }
    
    
    .left-sidebar img{
            padding: 60px;
    text-align: center;
    }
    #left-sidebar::-webkit-scrollbar {
        display: none;
    }

    body.sidebar-hidden #left-sidebar {
        transform: translateX(-100%);
    }

    /* =================================================== */
    /* =============== 【核心修改】手机端媒体查询 ( <= 768px) ============= */
    /* =================================================== */

    /* 顶栏左侧的三横杠按钮 */
    .toggle-sidebar-btn {
        background: none;
        border: none;
        color: #495057;
        font-size: 1.3rem;
        cursor: pointer;
        padding: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: background 0.2s;
        margin-right: 15px; 
    }
    .toggle-sidebar-btn:hover {
        color: #3b7ddd;
    }

    /* 顶栏内当前相册名称样式 */
    .navbar-page-title {
        font-size: 1.05rem;
        color: #495057;
        font-weight: 600;
    }

    /* Logo 区域 */
    .sidebar-logo-area {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    flex-shrink: 0;
    }
    .sidebar-logo-area .brand-name {
        color: #fff;
        font-size: 1.3rem;
        font-weight: bold;
    }

    /* 菜单列表滚动包裹层 */
    .menu-scroll-container {
        flex-grow: 1;
    }

    /* 主分类基础项 */
    .album-category, .home-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 13px 20px;
        color: var(--text-color);
        text-decoration: none;
        font-size: 0.92rem;
        transition: all 0.2s ease;
        cursor: pointer;
        border-left: 4px solid transparent;
    }

    .album-category:hover, .home-link:hover {
        color: #fff !important;
    /*background-color: rgba(255, 255, 255, 0.03);*/
    }

    .album-category.current-active {
        color: #fff !important;
    }
    .home-link.current-active {
        color: #fff !important;
        background-color: var(--active-bg);
        border-left-color: var(--hover-color);
    }

    /* 侧边箭头 
    .album-category::after {
        content: '❯';
        font-size: 0.7rem;
        color: rgba(255,255,255,0.3);
        transition: transform 0.2s;
    }
*/

    .album-category[data-category="all"]::after, .home-link::after {
        display: none; 
    }
    .album-category.current-active::after {
        transform: rotate(90deg);
        color: #fff;
    }

    /* --- 子列表抽屉外壳 --- */
    .album-list-container {
        display: none !important; 
        flex-direction: column;
        width: 100%;
        padding: 0;
        background-color: var(--sub-bg);
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    
    .album-list-container.expanded {
        display: flex !important;
        max-height: none !important; 
        overflow: visible !important;
    }

    /* 子相册链接显示样式 */
    .album-link {
        display: flex !important;              
        align-items: center !important;
        justify-content: space-between !important; 
        width: 100% !important;
        box-sizing: border-box !important;
        padding: 10px 19px 11px 40px !important;
        color: rgba(255, 255, 255, 0.65) !important;
        text-decoration: none !important;
        font-size: 15px !important;
        transition: all 0.2s;
        gap: 12px;
    }

    .album-link > span:first-child {
        text-align: left !important;
        flex: 1 !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .album-link:hover {
        color: #fff !important;
    }
    
    .album-link.current-item {
        background: linear-gradient(90deg, rgba(59, 125, 221, .1), rgba(59, 125, 221, .088) 50%, transparent);
        color: #e9ecef;
        font-weight: bold;
        font-size: 15px;
        border-left: 4px solid #3b7ddd;
    }

    /* 数量气泡（Badge）样式 */
    .album-badge {
        font-size: 0.75rem;
        font-weight: normal;
        background-color: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.45);
        padding: 2px 7px;
        border-radius: 10px;
        line-height: 1;
        transition: all 0.2s;
        flex-shrink: 0 !important; 
    }
    .album-link:hover .album-badge {
        color: rgba(255, 255, 255, 0.8);
        background-color: rgba(255, 255, 255, 0.15);
    }
    .album-link.current-item .album-badge {
        background-color: rgba(59, 125, 221, 0.15);
        color: var(--hover-color);
    }
    
    a.album-category {
        
        color: #fff;
        padding: 0;
        
    }
    .feather {
        stroke-width: 2;
        height: 30px;
        width: 30px;
    }
    a.album-category {
    padding: 15px 18px!important; /* 这里加了外边距！ */
    font-size: 18px;
    color: #fff;
    font-weight: 400;
}
    
</style>

<div class="top-navbar">
    <button class="toggle-sidebar-btn" id="sidebarToggle">
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-menu align-middle me-2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    <div class="navbar-page-title" style="position: absolute; left: 50%; transform: translateX(-50%); text-align: center; z-index: 1;"><?php echo $page_title; ?></div>
</div>

<?php
// 1. 获取 user 目录下所有的子目录
$userBaseDir = '../user/';
$allSubDirs = glob($userBaseDir . '*', GLOB_ONLYDIR);
$total3dCount = 0;

// 2. 遍历所有子目录，检查是否存在 /1/ 文件夹，并统计该文件夹内的图片
// 2. 遍历所有子目录
foreach ($allSubDirs as $subDir) {
    // 3. 检查是否存在 /1/ 文件夹
    $targetPath = $subDir . '/1/';
    
    if (is_dir($targetPath)) {
        // 只要文件夹存在，计数器就 +1，不再统计内部图片数量
        $total3dCount++;
    }
}

?>
<?php
/* --- PHP 核心过滤与数据层保持完全一致 --- */
include("../index_src/ini/seting.php");
include("../index_src/ini/num_img.php"); 

$home_url = $config_home['home_url'];
$home_cover = $config_home['cover'];

$userDir = '../user/'; 
$userSubDirs = scandir($userDir);
$classifiedDirs = []; 

foreach ($class as $category => $albums) {
    foreach ($albums as $title => $path) {
        $fullPath = $userDir . $path;
        $iniFilePath = $fullPath . '/ini.php';
        $config = []; 
        if (file_exists($iniFilePath)) {
            include $iniFilePath;
            if (isset($config['list_read']) && $config['list_read'] === 0) {
                continue; 
            }
        }
        $classifiedDirs[] = $path; 
    }
}

// 开始渲染左侧菜单
echo "<div id='left-sidebar'>";
//echo "<img src='$home_cover' style='padding: 0px 30px;' alt='Logo'>";
//<a href="../../"><img src="https://img.ximi.me/logo.png" style="margin: 0px 0 -60px 0;max-width: 260px;"></a>

echo "<a href='$home_url'><img src='$home_cover' style='margin: 0px 0 -60px 0;max-width: 260px;' alt='返回首页'></a>";
echo "<div class=\"sidebar-logo-area\"></div>";
echo '      <a href="#" class="album-category" data-category="all">所有相册</a>';

// 主分类迭代
foreach ($class as $categoryName => $albums) {
    $hasVisibleAlbumInCategory = false;
    foreach ($albums as $albumTitle => $albumPath) {
        $fullAlbumDirPath = $userDir . $albumPath;
        $iniFilePath = $fullAlbumDirPath . '/ini.php';
        $config = [];
        if (file_exists($iniFilePath)) {
            include $iniFilePath;
            if (isset($config['list_read']) && $config['list_read'] === 0) {
                continue;
            }
        }
        $hasVisibleAlbumInCategory = true; 
        break; 
    }
    if ($hasVisibleAlbumInCategory) {
        echo '      <a href="#" class="album-category" data-category="' . htmlspecialchars($categoryName) . '">' . htmlspecialchars($categoryName) . '</a>';
    }
}

/* =================================================== */
/* ================ 【修复点 1】规范静态菜单结构 ============= */
/* =================================================== */
echo '      <a href="#" class="album-category" data-category="no">更多美图</a>';

// 三维图册：href 统一改为 "#"，防止空链接刷新页面，并规范其抽屉外壳的渲染
echo '      <a href="#" class="album-category" data-category="3d">3D相册</a>';
echo "<div class='album-list-container' data-for-category='3d'><a href='./index.php' class='album-link'><span>\u{1F5BC}\u{FE0F} 预览</span><span class='album-badge'>$total3dCount</span></a></div>";

// 关于：移除 "album-category" 类名，防止被 JS 错误拦截，改用专用的 "sidebar-link-direct" 类名保持样式
echo '      <a href="https://img.ximi.me/#modalA" target="_blank" class="sidebar-link-direct">关于</a>';

// 原始数据桥接区
echo '      <div class="album-list-container" id="raw-data-holder" style="display:none;">';

function getAlbumImageCount($albumPath) {
    $userDir = '../user/';
    $iniFilePath = $userDir . $albumPath . '/ini.php';
    
    // 1. 如果 ini.php 不存在，直接返回 0
    if (!file_exists($iniFilePath)) {
        return 0;
    }
    
    // 2. 引入该相册的局部配置
    $config = [];
    include $iniFilePath;
    
    // 3. 检查是否有 tu_1 项
    if (!isset($config['tu_1']) || empty($config['tu_1'])) {
        return 0;
    }
    
    // 4. 将路径中的 "../../" 转换为 "../"
    $targetDir = str_replace('../../', '../', $config['tu_1']);
    
    // 5. 确保目录存在，并开始统计图片数量
    if (is_dir($targetDir)) {
        // 使用 glob 匹配常见的图片格式，防止统计到非图片文件或系统隐藏文件
        $images = glob($targetDir . '*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);
        return $images ? count($images) : 0;
    }
    
    return 0;
}
foreach ($class as $categoryName => $albums) {
    foreach ($albums as $albumTitle => $albumPath) {
        $fullAlbumDirPath = $userDir . $albumPath; 
        $iniFilePath = $fullAlbumDirPath . '/ini.php';
        $config = []; 
        if (file_exists($iniFilePath)) {
            include $iniFilePath;
            if (isset($config['list_read']) && $config['list_read'] === 0) {
                continue; 
            }
        }
        $imgCount = getAlbumImageCount($albumPath);
        echo '        <a href="../user/' . htmlspecialchars($albumPath) . '/index.php" class="album-link" data-category="' . htmlspecialchars($categoryName) . '"><span>🖼️ ' . htmlspecialchars($albumTitle) . '</span><span class="album-badge">' . $imgCount . '</span></a>';
    }
}


foreach ($userSubDirs as $subDir) {
    if ($subDir !== '.' && $subDir !== '..' && !in_array($subDir, $classifiedDirs) && is_dir($userDir . $subDir)) {
        $iniFilePath = $userDir . $subDir . '/ini.php';
        $albumTitle = $subDir; 
        $config = []; 
        if (file_exists($iniFilePath)) {
            include $iniFilePath;
            if (isset($config['list_read']) && $config['list_read'] === 0) {
                continue; 
            }
            if (isset($config['title']) && !empty($config['title'])) {
                $albumTitle = htmlspecialchars($config['title']);
            }
        }
        $imgCount = getAlbumImageCount($subDir);
        echo '        <a href="../user/' . htmlspecialchars($subDir) . '/index.php" class="album-link" data-category="no"><span>🖼️ ' . $albumTitle . '</span><span class="album-badge">' . $imgCount . '</span></a>';
    }
}
echo '      </div>'; 
echo '  </div>'; 
echo '</div>'; 
?>









<!-- 在原有 <style> 结尾前追加这一行，确保“关于”链接样式不受影响 -->
<style>
.sidebar-link-direct { display: flex; align-items: center; justify-content: space-between; color: #fff; text-decoration: none; font-weight: 400; padding: 15px 18px!important; font-size: 18px; transition: all 0.2s ease; border-left: 4px solid transparent; }
.sidebar-link-direct:hover { color: #fff !important; background-color: rgba(255, 255, 255, 0.03); }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById('left-sidebar');
        const albumCategories = document.querySelectorAll('.album-category');
        const rawDataHolder = document.getElementById('raw-data-holder');
        const albumLinks = rawDataHolder.querySelectorAll('.album-link');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        // 智能切换按钮行为 
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation(); 
            if (window.innerWidth <= 260) {
                document.body.classList.toggle('sidebar-open');
            } else {
                document.body.classList.toggle('sidebar-hidden');
            }
            if (window.myMasonry) {
                setTimeout(function() { window.myMasonry.layout(); }, 300);
            }
        });

        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 260) {
                if (!sidebar.contains(e.target) && e.target !== toggleBtn && !toggleBtn.contains(e.target)) {
                    document.body.classList.remove('sidebar-open');
                }
            }
        });

        // 1. 为“全部”建立独立抽屉
        const allWrapper = document.createElement('div');
        allWrapper.className = 'album-list-container';
        allWrapper.dataset.forCategory = 'all';
        
        albumLinks.forEach(link => {
            allWrapper.appendChild(link.cloneNode(true)); 
        });
        const allBtn = sidebar.querySelector('.album-category[data-category="all"]');
        allBtn.after(allWrapper);

        // 2. 为其他各主分类动态创建独立抽屉
        albumCategories.forEach(category => {
            const categoryName = category.dataset.category;
            if (categoryName === 'all') return;
            
            /* =================================================== */
            /* ================ 【修复点 2】避免重复覆盖手动写的抽屉 ====== */
            /* =================================================== */
            // 如果页面上已经手动写好了对应的抽屉（如 3d），则跳过动态创建
            if (sidebar.querySelector(`.album-list-container[data-for-category="${categoryName}"]`)) {
                return;
            }

            const subWrapper = document.createElement('div');
            subWrapper.className = 'album-list-container';
            subWrapper.dataset.forCategory = categoryName;

            albumLinks.forEach(link => {
                if (link.dataset.category === categoryName) {
                    subWrapper.appendChild(link.cloneNode(true));
                }
            });
            category.after(subWrapper);
        });

        rawDataHolder.remove();

        const allSubContainers = document.querySelectorAll('.album-list-container');

        // ==================== 智能检测与高亮 ====================
        const currentUrl = window.location.href;
        let matchedActiveLink = null;
        
        document.querySelectorAll('.album-link').forEach(link => {
            const href = link.getAttribute('href');
            if (!href || href === '#') return;

            try {
                const absoluteLinkUrl = new URL(href, window.location.href).href;
                if (currentUrl.split('?')[0].includes(absoluteLinkUrl.split('?')[0])) {
                    link.classList.add('current-item');
                    
                    const parentContainer = link.closest('.album-list-container');
                    if (parentContainer && parentContainer.dataset.forCategory !== 'all') {
                        matchedActiveLink = link;
                    } else if (!matchedActiveLink) {
                        matchedActiveLink = link;
                    }
                }
            } catch (e) {
                console.error("URL 转换失败:", e);
            }
        });

        albumCategories.forEach(c => c.classList.remove('current-active'));
        allSubContainers.forEach(c => c.classList.remove('expanded'));

        if (matchedActiveLink) {
            const parentContainer = matchedActiveLink.closest('.album-list-container');
            if (parentContainer) {
                parentContainer.classList.add('expanded');
                const pCategory = sidebar.querySelector(`.album-category[data-category="${parentContainer.dataset.forCategory}"]`);
                if (pCategory) pCategory.classList.add('current-active');
            }
        } else {
            if (allBtn) {
                allBtn.classList.add('current-active');
            }
        }

        // 4. 手风琴互斥单开/单关核心逻辑
        albumCategories.forEach(category => {
            category.addEventListener('click', function(event) {
                const categoryName = this.dataset.category;
                const targetContainer = sidebar.querySelector(`.album-list-container[data-for-category="${categoryName}"]`);

                // 如果找不到对应的抽屉容器，则放行默认点击行为（保险机制）
                if (!targetContainer) return;

                event.preventDefault(); // 仅对存在子抽屉的菜单进行拦截

                if (targetContainer.classList.contains('expanded')) {
                    targetContainer.classList.remove('expanded');
                    this.classList.remove('current-active');
                } else {
                    allSubContainers.forEach(c => c.classList.remove('expanded'));
                    albumCategories.forEach(c => c.classList.remove('current-active'));

                    targetContainer.classList.add('expanded');
                    this.classList.add('current-active');
                }
            });
        });
    });
</script>