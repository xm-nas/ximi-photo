<html data-immersive-translate-page-theme="light">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>My Bookcase</title>
    
    <meta name="Keywords" content="">
    <meta name="Description" content="">
    <meta name="Generator" content="flipbuilder.com">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <link rel="stylesheet" type="text/css" href="./css/bookcase.css">

    <style>
    body.red {
    background: #4d2f2f;
}
    /* 1. 当 body 只有 red 类名时 */
    body.red .bookcase_containerPanel {
        width: calc(100vw - 300px) !important; 
    }

    /* 2. 当 body 同时具有 red 和 sidebar-hidden 类名时 */
    body.red.sidebar-hidden .bookcase_containerPanel  {
        width: calc(100vw - 40px) !important;
           
    }
    body.red.sidebar-hidden .bookcase_bo  {
        width: calc(100vw - 40px) !important;
          
    }
    .control-panel {
        height: 42px;
        position: absolute;
        text-align: center;
        bottom: 0;
        top: calc(100vh - 42px) !important;
        width: 100%;
        z-index: 10;
    }
    .book-wrapper {
        height: 100%;
        display: inline-block;
        padding: 0 18px;
    }
.bookcase_bo {
    position: absolute;
    width: calc(100vw - 300px) !important;
    background: url(./img/red-3.png);
    /* margin-top: calc(100vh - 315px); */
    top: 675px;
    height: 50px;
    z-index: 99;
    margin-left: 20px;
    display: flex;
    justify-content: flex-start;
    align-items: center;
}

    </style>

    <?php
// ==========================================
// 1. 动态遍历 ../user 目录并提取数据
// ==========================================
$books = [];
$user_base_dir = '../user/';
$id_counter = 0;

// 检查用户目录是否存在
if (is_dir($user_base_dir)) {
    // 扫描目录，排除系统自带的隐藏目录 . 和 ..
    $sub_dirs = array_diff(scandir($user_base_dir), ['.', '..']);

    foreach ($sub_dirs as $dir_name) {
        $current_user_dir = $user_base_dir . $dir_name;

        // 确保是个真正的文件夹
        if (is_dir($current_user_dir)) {
            $ini_path = $current_user_dir . '/ini.php';
            $txt_content = '暂无简介'; // 默认缺省值
            $title_content = '未知书名';

            // 1. 尝试提取 ini.php 中的 txt 和 title 项
            if (file_exists($ini_path)) {
                // 使用 include 引入配置文件以直接获取 $config 数组
                @include($ini_path);
                if (isset($config) && is_array($config)) {
                    if (isset($config['txt'])) {
                        $txt_content = $config['txt'];
                    }
                    if (isset($config['title'])) {
                        $title_content = $config['title'];
                    }
                    // 释放变量，避免污染下一个循环
                    unset($config); 
                }
            }


// 2. 组装图片的相对路径与点击跳转的链接路径
$thumb_dir = $user_base_dir . $dir_name . '/1/files/thumb/';
$link_path = $user_base_dir . $dir_name . '/1/index.html';
$src_path = '';

// 检查缩略图目录是否存在
if (is_dir($thumb_dir)) {
    // 匹配目录下所有的 jpg, jpeg, png, gif, webp 图片
    $images = glob($thumb_dir . '*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);
    
    if ($images && count($images) > 0) {
        // 随机选择一个索引
        $random_index = array_rand($images);
        $src_path = $images[$random_index];
    }
}

// 如果成功获取到图片路径，则加入队列
if (!empty($src_path)) {
    $books[] = [
        'id'    => $id_counter++,
        'title' => $title_content,
        'txt'   => $txt_content,
        'src'   => $src_path,
        'link'  => $link_path
    ];
}
            //===
        }
    }
}
?>

</head>

<?php include("./Sidebar.php"); ?>

<body class="red">
            
    <div class="bookcase_containerPanel" style="top: 56px; padding: 20px;    max-height: 640px;">

        <!-- 书架总面板外壳 -->
        <div class="bookcase-panel" id="gridbook">
            
            <!-- 顶部导航栏区域 -->
            <div class="nav-container">
                <div class="nav-left"></div>
                <div class="nav-middle"></div>
                <div class="nav-right"></div>
                <div class="nav-branding-container disabled">
                    <span class="font-style">My Gallery</span>
                </div>
                <div class="nav-right-button-container"></div>
                <div class="right_nav_Container">
                    <div class="mShoppingCart mShoppingCart-button" style="touch-action: pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></div>
                   
                    <div class="mSearch-button mSearch-icon" style="touch-action: pan-x pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></div>
                </div>
               
            </div>

            <!-- 中间主书架物理箱体 (自适应高度) -->
            <div class="case-container" style="min-height: 709px; height: auto;">
                <div class="case-panel" style="touch-action: pan-x pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); transform: translateY(0px); height: 100%;">
                    
                    <div class="case-panel-top"></div>
                    
                    <!-- 原生书架背景物理隔板 (8行物理木质刻线背景) -->
                    <div class="case-background">
                        <?php for ($row = 1; $row <= 8; $row++): ?>
                            <div class="case-row">
                                <div class="case-left"></div>
                                <div class="case-middle"></div>
                                <div class="case-right"></div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- 书籍实际摆放渲染页面 -->
                    <div class="case-page-container user-select-none" style="touch-action: pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);">
                        <div id="page-1" class="single-page">
                            <div class="case-row">
                                
                                <!-- =================================================== -->
                                <!-- 动态合并：高级自适应 Flex 布局多图容器 -->
                                <!-- =================================================== -->
                                <div class="book-wrapper" style="width: 100% !important;display: flex !important;flex-wrap: wrap !important;justify-content: flex-start !important;gap: 48px 15px !important;box-sizing: border-box;padding: 20px 30px;">

                                    <?php foreach ($books as $book): ?>
                                        <div class="book-container" style="flex: 1 1 85px !important; max-width: 110px !important; display: flex !important; justify-content: center !important; align-items: flex-end !important; position: relative !important; margin-top: -20px !important;">
                                            
                                            <!-- 3. 新增：新选项卡打开链接的 a 标签 -->
                                            <a href="<?php echo $book['link']; ?>" target="_blank" style="display: block; text-decoration: none; max-width: 120px;">
                                                
                                                <!-- 单本书籍卡片包裹层 (此处 title 改为展示提取到的 txt 说明) -->
                                                <div id="<?php echo $book['id']; ?>" class="book-img-wrapper" date-title="【<?php echo htmlspecialchars($book['title']); ?>】 <?php echo htmlspecialchars($book['txt']); ?>" style="height: auto !important; max-width: 100% !important; display: block !important; position: relative !important;">
                                                    
                                                    <!-- 书籍封面图 -->
                                                    <img class="book-img" src="<?php echo $book['src']; ?>" style="max-width: 120px !important; height: auto !important; max-height: 109px !important; width: auto !important; object-fit: contain !important; display: block;">
                                                    
                                                    <!-- 右侧 3D 仿真书页厚度边框立体特效 -->
                                                    <div class="book-border-container" style="right: -4px; width: 4px; position: absolute; top: 0; bottom: 0;">
                                                        <div style="position: absolute; width: 1px; background: rgb(204, 204, 204); height: calc(100% - 2px); top: 1px; left: 1px;"></div>
                                                        <div style="position: absolute; width: 1px; background: rgb(255, 255, 255); height: calc(100% - 4px); top: 2px; left: 2px;"></div>
                                                        <div style="position: absolute; width: 1px; background: rgb(204, 204, 204); height: calc(100% - 6px); top: 3px; left: 3px;"></div>
                                                        <div style="position: absolute; width: 1px; background: rgb(255, 255, 255); height: calc(100% - 8px); top: 4px; left: 4px;"></div>
                                                    </div>
                                                    
                                                    <!-- 角标/标签 (预留) -->
                                                    <img class="book-label" src="" style="position: absolute; top: 0; left: 0; display: none;">
                                                </div>

                                            </a> <!-- a标签结束 -->
                                            
                                        </div>
                                    <?php endforeach; ?>

                                </div>
                                <!-- =================================================== -->

                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

            <!-- 底部控制与皮肤切换面板 -->
  <div class="bookcase_bo">
  <span class="font-style" id="alt_title"style="
    /* margin-top: 10px; */
    margin-left: 16px;"></span>
                <div class="mSort-name-date mSort-button" style="touch-action: pan-x pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></div>
            </div>
        </div>

    </div>

</body>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const altTitleBox = document.getElementById('alt_title');
    if (!altTitleBox) return;

    // 强行开启多行换行支持
    //altTitleBox.style.whiteSpace = 'pre-line';

    document.addEventListener('mouseover', (event) => {
        // 【核心修改】：精准锁定带有包含 title 的书本卡片包裹层
        const wrapper = event.target.closest('.book-img-wrapper');
        
        if (wrapper) {
            const tipText = wrapper.getAttribute('date-title');
            if (tipText && tipText.trim() !== "") {
                altTitleBox.textContent = tipText;
            }
        }
    });

    document.addEventListener('mouseout', (event) => {
        // 当鼠标彻底离开这本书的包裹层时，才清空文本
        const wrapper = event.target.closest('.book-img-wrapper');
        if (wrapper) {
            altTitleBox.textContent = '';
        }
    });
});
</script>

</html>