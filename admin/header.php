<?php
// header.php 后台页眉

// 获取当前请求的主域名
$host = $_SERVER['HTTP_HOST'];

// 获取当前协议（http 或 https）
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

// 构建完整的域名
$full_domain = $protocol . '://' . $host;

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($page_title) ? $page_title : '欢迎访问希米的图册'; ?></title>
  <link rel="stylesheet" href="/admin/css/themes.css">
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
    /* =============== PC 端默认样式 ( > 768px) =============== */
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
    
    #left-sidebar::-webkit-scrollbar {
        display: none;
    }

    body.sidebar-hidden #left-sidebar {
        transform: translateX(-100%);
    }

    /* =================================================== */
    /* =============== 手机端媒体查询 ( <= 768px) ============= */
    /* =================================================== */
    @media (max-width: 768px) {
        body {
            padding-left: 0 !important; 
        }

        .top-navbar {
            left: 0 !important; 
        }

        #left-sidebar {
            transform: translateX(-100%); 
        }

        /* 当手机端点击展开菜单时激活的类 */
        body.sidebar-open #left-sidebar {
            transform: translateX(0) !important;
        }
        
        body.sidebar-open {
            overflow: hidden;
        }
    }

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
        padding: 20px 0;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        flex-shrink: 0;
    }
    .sidebar-logo-area img {
        width: 150px;
        display: block;
        margin: 0 auto;
    }

    /* 主分类基础项 */
    .album-category {
        display: flex !important;                  
        width: 100% !important;                    
        box-sizing: border-box !important;         
        margin: 10px 0 0 0 !important;                 
        padding: 13px 20px !important;             
        font-size: 16px !important;
        color: #fff !important;
        text-decoration: none !important;
        cursor: pointer;
        justify-content: space-between;
        align-items: center;
        border-left: 4px solid transparent;
        transition: all 0.2s;
    }

    .album-category:hover {
        color: #fff !important;
        background-color: rgba(255, 255, 255, 0.05) !important;
    }

    /* 侧边箭头 */
    .album-category::after {
        content: '❯';
        font-size: 0.7rem;
        color: rgba(255,255,255,0.3);
        transition: transform 0.2s;
    }
    .album-category.current-active::after {
        transform: rotate(90deg);
        color: #fff;
    }

    /* --- 子列表抽屉外壳 --- */
    .album-list-container {
        display: none; 
        flex-direction: column;
        width: 100%;
        padding: 0;
        background-color: var(--sub-bg);
    }
    
    .album-list-container.expanded {
        display: flex;
    }

    /* 子链接显示样式 */
    .album-link {
        display: flex !important;              
        align-items: center !important;
        justify-content: space-between !important; 
        width: 100% !important;
        box-sizing: border-box !important;
        padding: 10px 19px 11px 40px !important;
        color: rgba(255, 255, 255, 0.65) !important;
        text-decoration: none !important;
        font-size: 14px !important;
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
        background-color: rgba(255, 255, 255, 0.02);
    }
    
    .album-link.current-item {
        background: linear-gradient(90deg, rgba(59, 125, 221, .1), rgba(59, 125, 221, .088) 50%, transparent);
        color: #e9ecef;
        font-weight: bold;
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
        flex-shrink: 0 !important; 
    }
    
    .feather {
        stroke-width: 2;
        height: 30px;
        width: 30px;
    }
</style>
</head>

<body>

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

<div id="left-sidebar">
    <div class="sidebar-logo-area">
        <img src="https://www.ximi.me/admin/img/icons/logo.webp" alt="Logo">
    </div>
         
    <a href="#" class="album-category" data-category="config">控制台</a>
    <div class="album-list-container" data-for-category="config">
        <a href="admin.php" class="album-link"><span>配置</span></a>
        <a href="admin_categories.php" class="album-link"><span>分类</span></a>
        <a href="admin_html.php" class="album-link"><span>模板</span></a>
        <a href="admin_whitelist.php" class="album-link"><span>白名单</span></a>
    </div>      
    
    <a href="#" class="album-category" data-category="upload">相册管理</a>
    <div class="album-list-container" data-for-category="upload">
         <a href="admin_photo.php" class="album-link"><span>相册配置</span></a>
         <a href="admin_upload.php" class="album-link"><span>图片上传</span></a>
         <a href="admin_list.php" class="album-link"><span>图片编辑</span></a>
         <a href="admin_3d_photo.php" class="album-link"><span>三维相册</span></a>         
    </div>   
        
    <a href="#" class="album-category" data-category="about">关于</a>
    <div class="album-list-container" data-for-category="about">
        <a href="about.php" class="album-link"><span>使用说明</span></a>

    </div>     
    
</div>

<main>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById('left-sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const albumCategories = document.querySelectorAll('.album-category');
    const allSubContainers = document.querySelectorAll('.album-list-container');

    // =================================================== 
    // 1. 修复后的侧边栏按钮切换核心逻辑 (不再报错挂掉)
    // =================================================== 
    toggleBtn.addEventListener('click', function(e) {
        e.stopPropagation(); 
        if (window.innerWidth <= 768) {
            document.body.classList.toggle('sidebar-open');
        } else {
            document.body.classList.toggle('sidebar-hidden');
        }
        
        if (window.myMasonry) {
            setTimeout(function() { window.myMasonry.layout(); }, 300);
        }
    });

    // 手机端点击外部关闭侧边栏
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && e.target !== toggleBtn && !toggleBtn.contains(e.target)) {
                document.body.classList.remove('sidebar-open');
            }
        }
    });

    // =================================================== 
    // 2. 干净的后台手风琴展开与菜单互斥逻辑
    // =================================================== 
    albumCategories.forEach(category => {
        category.addEventListener('click', function(event) {
            event.preventDefault();
            const categoryName = this.dataset.category;
            const targetContainer = sidebar.querySelector(`.album-list-container[data-for-category="${categoryName}"]`);

            if (!targetContainer) return;

            if (targetContainer.classList.contains('expanded')) {
                targetContainer.classList.remove('expanded');
                this.classList.remove('current-active');
            } else {
                // 互斥关闭其它展开项
                allSubContainers.forEach(c => c.classList.remove('expanded'));
                albumCategories.forEach(c => c.classList.remove('current-active'));

                targetContainer.classList.add('expanded');
                this.classList.add('current-active');
            }
        });
    });

    // =================================================== 
    // 3. 页面高亮智能匹配
    // =================================================== 
    const currentUrl = window.location.href.split('?')[0];
    document.querySelectorAll('.album-link').forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href === '#') return;

        const absoluteLinkUrl = new URL(href, window.location.href).href.split('?')[0];
        if (currentUrl.includes(absoluteLinkUrl)) {
            link.classList.add('current-item');
            // 自动把它的父级抽屉展开
            const parentContainer = link.closest('.album-list-container');
            if (parentContainer) {
                parentContainer.classList.add('expanded');
                const pCategory = sidebar.querySelector(`.album-category[data-category="${parentContainer.dataset.forCategory}"]`);
                if (pCategory) pCategory.classList.add('current-active');
            }
        }
    });
});
</script>