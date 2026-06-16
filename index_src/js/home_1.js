
    const albumCategories = document.querySelectorAll('.album-category');
    const albumList = document.querySelector('.album-list');
    const albumItems = document.querySelectorAll('.album-item');
    let activeCategory = 'all';

    const mobileMenuIcon = document.querySelector('.mobile-menu-icon');
    const header = document.querySelector('header');
    let currentMobileMenu = null; // 初始化移动端菜单变量
    let previousScrollY = window.scrollY; // 记录之前的滚动位置

    // 创建移动端菜单
    function createMobileMenu() {
        const mobileMenuDiv = document.createElement('div');
        mobileMenuDiv.classList.add('mobile-menu');
        mobileMenuDiv.style.display = 'none'; // 初始隐藏

        albumCategories.forEach(categoryLink => {
            const mobileLink = document.createElement('a');
            mobileLink.href = '#'; // 阻止默认跳转
            mobileLink.textContent = categoryLink.textContent;
            mobileLink.dataset.category = categoryLink.dataset.category;
            mobileLink.addEventListener('click', filterAlbums);
            mobileMenuDiv.appendChild(mobileLink);
        });

        document.body.appendChild(mobileMenuDiv); // 直接添加到 body 尾部，确保存在
        return mobileMenuDiv;
    }

    // 确保移动端菜单只创建一次
    function ensureMobileMenuExists() {
        if (!currentMobileMenu) {
            currentMobileMenu = createMobileMenu();
        }
    }

    // 切换移动端菜单的显示和图标
    if (mobileMenuIcon) {
        mobileMenuIcon.addEventListener('click', () => {
            ensureMobileMenuExists();
            currentMobileMenu.classList.toggle('open'); // 切换 'open' class
            if (currentMobileMenu.classList.contains('open')) {
                mobileMenuIcon.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M18.21 4.22a.75.75 0 0 1 1.06 1.06L13.06 12l6.21 6.21a.75.75 0 1 1-1.06 1.06L12 13.06l-6.21 6.21a.75.75 0 0 1-1.06-1.06L10.94 12 4.73 5.79a.75.75 0 0 1 1.06-1.06L12 10.94l6.21-6.72Z" clip-rule="evenodd" />
                    </svg>
                `; // X 图标
            } else {
                mobileMenuIcon.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"/>
                    </svg>
                `; // 三横杠图标
            }
        });
    }

    function filterAlbums(event) {
        event.preventDefault();
        const categoryName = this.dataset.category;
        activeCategory = categoryName;

        albumItems.forEach(item => {
            item.classList.remove('hidden');
            if (categoryName !== 'all' && item.dataset.category !== categoryName) {
                item.classList.add('hidden');
            }
        });

        albumCategories.forEach(cat => {
            cat.classList.remove('active');
            if (cat.dataset.category === categoryName) {
                cat.classList.add('active');
            }
        });

        toggleFooterVisibility(); // 在筛选后更新 footer 状态 (根据新的逻辑可能不需要在这里调用)
    }

    albumCategories.forEach(category => {
        category.addEventListener('click', filterAlbums);
    });

    // 初始加载时显示所有分类
    albumItems.forEach(item => {
        item.classList.remove('hidden');
    });

    function toggleFooterVisibility() {
        const footer = document.querySelector('footer');
        const html = document.documentElement;
        const hasScroll = html.scrollHeight > html.clientHeight;
        const isScrolledToBottom = (window.innerHeight + window.scrollY) >= document.body.offsetHeight - 1;

        if (!hasScroll || isScrolledToBottom) {
            footer.classList.remove('hidden-footer');
        } else {
            footer.classList.add('hidden-footer');
        }
    }

    function handleScroll() {
        const footer = document.querySelector('footer');
        const currentScrollY = window.scrollY;

        if (currentScrollY < previousScrollY) {
            // 向上滚动
            footer.classList.add('hidden-footer');
        } else {
            // 向下滚动
            toggleFooterVisibility(); // 调用之前的逻辑，在没有滚动条或滚动到底部时显示
        }

        previousScrollY = currentScrollY; // 更新之前的滚动位置
    }

    // 在页面加载完成后执行
    document.addEventListener('DOMContentLoaded', () => {
        ensureMobileMenuExists();
        toggleFooterVisibility(); // 初始检查页脚可见性
        window.addEventListener('scroll', handleScroll); // 添加滚动事件监听器
    });

    // 在窗口大小改变时重新检查
    window.addEventListener('resize', toggleFooterVisibility);


        /* 这里将放置下面的 JavaScript 代码 */
        document.addEventListener('DOMContentLoaded', () => {
    const modeToggleBtn = document.querySelector('.lightbulb'); // 获取主题切换按钮元素
    const body = document.body;
    const localStorageKey = 'themeMode'; // 用于在 LocalStorage 中存储用户偏好的键名
    // 获取 SVG 内部的 path 元素
    const modeIconPath = document.getElementById('mode-icon-path');

    // 定义太阳和月亮图标的 SVG path 数据
    const sunIconPath = "M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.414a.5.5 0 1 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.343 2.343a.5.5 0 0 1 .707 0L6.46 3.757a.5.5 0 0 1-.707.707L4.343 3.05a.5.5 0 0 1 0-.707z";
  const moonIconPath = "M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278M4.858 1.311A7.27 7.27 0 0 0 1.025 7.71c0 4.02 3.279 7.276 7.319 7.276a7.32 7.32 0 0 0 5.205-2.162q-.506.063-1.029.063c-4.61 0-8.343-3.714-8.343-8.29 0-1.167.242-2.278.681-3.286M10.794 3.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387a1.73 1.73 0 0 0-1.097 1.097l-.387 1.162a.217.217 0 0 1-.412 0l-.387-1.162A1.73 1.73 0 0 0 9.31 6.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387a1.73 1.73 0 0 0 1.097-1.097zM13.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732l-.774-.258a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732z";

    /**
     * 应用主题到页面。
     * @param {boolean} isDark - true 表示深色模式，false 表示浅色模式。
     * @param {string} source - 主题应用的来源（例如：'LocalStorage 偏好', '系统偏好', '手动切换'）。
     */
    const applyTheme = (isDark, source = 'manual') => {
        if (isDark) {
            body.classList.add('dark-mode-active'); // 添加深色模式类
            if (modeIconPath) {
                modeIconPath.setAttribute('d', moonIconPath); // 设置为月亮图标
            }
        } else {
            body.classList.remove('dark-mode-active'); // 移除深色模式类
            if (modeIconPath) {
                modeIconPath.setAttribute('d', sunIconPath); // 设置为太阳图标
            }
        }
        // 调试输出：显示当前模式和来源，方便开发时查看
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const modeText = isDark ? '深色模式' : '浅色模式';
        console.log(`[主题] ${hours}:${minutes} - 已切换到 ${modeText} (来源: ${source})`);
    };

    // --- 页面加载时检查并应用主题逻辑 ---

    // 1. 检查 LocalStorage 中是否有保存的用户偏好
    const savedTheme = localStorage.getItem(localStorageKey);
    // 获取当前的系统颜色偏好
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
    console.log(`[加载] 系统当前是否为深色模式: ${prefersDark.matches}`); // 调试输出系统偏好

    if (savedTheme) {
        // 如果 LocalStorage 中有保存的偏好，则优先应用该偏好
        applyTheme(savedTheme === 'dark', 'LocalStorage 偏好');
        console.log(`[加载] 检测到 LocalStorage 偏好 "${savedTheme}"，应用该偏好。`);
    } else {
        // 2. 如果 LocalStorage 中没有保存的偏好，则根据系统偏好设置初始主题
        applyTheme(prefersDark.matches, '系统偏好');
        console.log(`[加载] LocalStorage 无偏好，根据系统偏好 (${prefersDark.matches ? '深色' : '浅色'}) 应用主题。`);
    }

    // --- 监听系统模式变化 ---
    // 监听 prefers-color-scheme 的变化，以便在用户更改系统主题时做出响应
    prefersDark.addEventListener('change', event => {
        const currentSavedTheme = localStorage.getItem(localStorageKey);
        // 只有当用户没有明确手动设置偏好时，才自动根据系统变化切换主题
        if (!currentSavedTheme) {
            applyTheme(event.matches, '系统偏好变化');
            console.log(`[系统变化] 检测到系统模式切换，当前 ${event.matches ? '深色' : '浅色'}。`);
        } else {
            // 如果用户手动设置了偏好，则保持用户选择，不自动跟随系统
            console.log(`[系统变化] 检测到系统模式切换，但用户已设置偏好 "${currentSavedTheme}"，保持用户偏好。`);
        }
    });

    // --- 为灯泡图标添加点击事件监听器 ---
    if (modeToggleBtn) { // 确保按钮元素存在
        modeToggleBtn.addEventListener('click', () => {
            // 获取当前实际应用的模式状态
            const isCurrentlyDark = body.classList.contains('dark-mode-active');
            // 切换到相反的模式
            const newModeIsDark = !isCurrentlyDark;
            applyTheme(newModeIsDark, '手动切换');
            // 手动切换后，将用户的选择保存到 LocalStorage，以便下次访问时记住
            localStorage.setItem(localStorageKey, newModeIsDark ? 'dark' : 'light');
            console.log(`[手动切换] 用户手动切换至 ${newModeIsDark ? '深色' : '浅色'}，并已保存偏好。`);
        });
    } else {
        console.error('错误：未找到主题切换按钮元素 (.lightbulb)。请检查 HTML 类名或文档结构。');
    }

    // --- 其他原有的 JavaScript 代码 ---
    // 这里包含了你的 Swiper 初始化代码
    var swiper = new Swiper(".mySwiper", {
        loop: true,
        effect: 'fade',
        fadeEffect: {
            crossFade: true,
        },
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        grabCursor: true,
        keyboard: {
            enabled: true,
        },
    });
    // ... 确保这里包含所有其他你的 JS 代码，例如相册筛选和底部可见性逻辑
});
   