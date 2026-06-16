<?php
// PHP 逻辑保留不变
$books = [];
$user_base_dir = '../user/';
if (is_dir($user_base_dir)) {
    $sub_dirs = array_diff(scandir($user_base_dir), ['.', '..']);
    foreach ($sub_dirs as $dir_name) {
        $current_user_dir = $user_base_dir . $dir_name;
        if (is_dir($current_user_dir)) {
            $ini_path = $current_user_dir . '/ini.php';
            $txt_content = '暂无简介'; $title_content = '未知书名';
            if (file_exists($ini_path)) {
                @include($ini_path);
                if (isset($config) && is_array($config)) {
                    $txt_content = $config['txt'] ?? $txt_content;
                    $title_content = $config['title'] ?? $title_content;
                    unset($config);
                }
            }
            $src_path = $user_base_dir . $dir_name . '/1/files/thumb/1.jpg';
            $link_path = $user_base_dir . $dir_name . '/1/index.html';
            if (file_exists($src_path)) {
                $books[] = ['title' => $title_content, 'txt' => $txt_content, 'src' => $src_path, 'link' => $link_path];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        /* 1. 全屏背景容器 */
        .page-wrapper {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: #4d2f2f url('./img/red-3.png') no-repeat center center;
            background-size: cover;
            display: flex; /* 确保侧边栏与内容区并排 */
            overflow: hidden;
        }

        #sidebar { width: 200px; flex-shrink: 0; }

        /* 2. 核心修改：让 main-container 充满所有剩余空间 */
        #main-container {
            flex-grow: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding: 20px;
        }

        /* 3. 核心修改：不再使用 absolute，改用 flex 自动堆叠 */
        #pages-wrapper {
            flex-grow: 1;
            width: 100%;
            position: relative;
        }

        .page-layer { 
            width: 100%;
            height: 100%;
            display: none; /* 默认隐藏 */
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            align-content: start;
        }
        .page-layer.active { display: grid; } /* 仅显示当前页 */

        .book { text-align: center; color: #f3bb9b; font-size: 12px; cursor: pointer; }
        .book img { width: 100%; max-width: 260px; border: 2px solid #f3bb9b; display: block; height: 360px; object-fit: cover; }
        
        .nav-btn { position: absolute; top: 50%; padding: 20px; background: rgba(0,0,0,0.3); color: #fff; cursor: pointer; z-index: 100; }
    </style>
</head>
<body>

<div class="page-wrapper">
    <?php include("./Sidebar.php"); ?>

    <div id="main-container">
        <div id="pages-wrapper"></div>
        <div id="prev" class="nav-btn" style="left:0" onclick="switchPage(-1)">◀</div>
        <div id="next" class="nav-btn" style="right:0" onclick="switchPage(1)">▶</div>
    </div>
</div>

<script>
    const allBooks = <?php echo json_encode($books); ?>;
    let currentPage = 0;

    function renderAll() {
        const wrapper = document.getElementById('pages-wrapper');
        wrapper.innerHTML = '';
        
        // 计算每页空间
        const container = document.getElementById('main-container');
        const cols = Math.max(1, Math.floor(container.clientWidth / 140));
        const itemsPerPage = cols * 2; // 保持3行显示，确保不溢出
        
        const totalPages = Math.ceil(allBooks.length / itemsPerPage);
        
        for (let p = 0; p < totalPages; p++) {
            const pageDiv = document.createElement('div');
            pageDiv.className = 'page-layer' + (p === currentPage ? ' active' : '');
            
            const slice = allBooks.slice(p * itemsPerPage, (p + 1) * itemsPerPage);
            slice.forEach(b => {
                pageDiv.innerHTML += `
                    <div class="book" onclick="window.location='${b.link}'">
                        <img src="${b.src}" title="${b.title}">
                        <div style="
    padding: 10px;
    font-size: 20px;
">${b.title}</div>
                    </div>`;
            });
            wrapper.appendChild(pageDiv);
        }
    }

    function switchPage(dir) {
        const pages = document.querySelectorAll('.page-layer');
        if (pages.length === 0) return;
        pages[currentPage].classList.remove('active');
        currentPage = Math.max(0, Math.min(pages.length - 1, currentPage + dir));
        pages[currentPage].classList.add('active');
    }

    window.addEventListener('resize', renderAll);
    renderAll();
</script>
</body>
</html>