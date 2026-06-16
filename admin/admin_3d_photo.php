<?php

// 核心逻辑：先处理 API 请求，防止 header 冲突

define('SRC_DIR', '../photo/1');

define('USER_ROOT', '../user');

// API 处理部分
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    function removeDir($dir) {
        if (!is_dir($dir)) return;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        rmdir($dir);
    }

    $dir = basename($_GET['dir']);
    $dst = USER_ROOT . '/' . $dir . '/1';

    if ($_GET['action'] === 'deploy') {
        // 如果已存在且强制覆盖，则先删除
        if (is_dir($dst) && isset($_GET['force'])) {
            removeDir($dst);
        }
        
        if (!is_dir($dst)) {
            function recurseCopy($s, $d) { @mkdir($d, 0755, true); $dir = opendir($s); while(($f=readdir($dir))!==false){ if($f!='.'&&$f!='..'){ is_dir($s.'/'.$f) ? recurseCopy($s.'/'.$f, $d.'/'.$f) : copy($s.'/'.$f, $d.'/'.$f); } } closedir($dir); }
            recurseCopy(SRC_DIR, $dst);
            echo json_encode(['status' => 'success', 'message' => "部署完成"]);
        } else {
            echo json_encode(['status' => 'skip', 'message' => "已存在，已跳过"]);
        }
    } elseif ($_GET['action'] === 'delete') {
        removeDir($dst);
        echo json_encode(['status' => 'success', 'message' => "已删除"]);
    }
    exit;
}
?>
<?php include("./header.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="main-content" style="padding: 20px;">
    <div class="card" style="background:#fff; padding:20px; border-radius:8px;">
        <h3>相册管理矩阵</h3>
            <?php
    $dirs = array_diff(scandir(USER_ROOT), ['.', '..']);
    $readyCount = 0;
    $list = [];
    foreach ($dirs as $dir) {
        $ini = USER_ROOT . '/' . $dir . '/ini.php';
        $config = file_exists($ini) ? include $ini : [];
        $isReady = is_dir(USER_ROOT . '/' . $dir . '/1');
        if($isReady) $readyCount++;
        
        $path = USER_ROOT . '/' . $dir . '/1/files/mobile/';
        $count = is_dir($path) ? count(glob($path . "*.{jpg,png,gif,webp}", GLOB_BRACE)) : 0;
        
        $list[] = ['dir' => $dir, 'title' => $config['title'] ?? $dir, 'ready' => $isReady, 'count' => $count];
    }
    ?> 
<style>
.btn-batch-delete {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 8px 16px;
    font-size: 14px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 5px;
}
.btn-batch-delete:hover {
    background: #bd2130;
}
.btn-batch-ref {
    background-color: #3b82f6;
    color: white;
    border: none;
    padding: 8px 16px;
    font-size: 14px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 5px;
   
}
.btn-batch-ref:hover {
    background: #4f46e5 ;
}


/*

.btn-table-action {
    border: none;
    width: 80px;
    height: 28px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    margin-left: 6px;
    text-decoration: none;
}
.btn-table-action.edit {
    background: rgb(170 186 188 / 54%);
    color: #4f46e5;
}
.btn-table-action.edit:hover {
    background:#868692;
    color: #fff;
}
.btn-table-action.del {
    background:#dc3545;
    color: #fff;
}
.btn-table-action.del:hover {
    background: #bd2130;
    color: #fff;
}
.btn-table-action.ref {
    background: #3b82f6;
    color: #fff;
}
.btn-table-action.ref:hover {
    background: #4f46e5 ;
    color: #fff;
}
.album-count-badge {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 20px;
    font-weight: 500;
}
*/
//===
/* 现代表格样式 */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 15px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden; /* 实现圆角表格 */
}

/* 单元格通用样式 */
th, td {
    padding: 16px 20px;
    text-align: center;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

/* 表头样式 */
thead tr {
    background-color: #f9fafb;
    color: #374151;
    font-weight: 600;
}

/* 表格行悬停效果 */
tbody tr:hover {
    background-color: #f8fafc;
}

/* 计数徽章美化 */
.album-count-badge {
    padding: 4px 12px;
    border-radius: 20px;
    background: #f0fdf4;
    color: #166534;
    font-size: 13px;
    font-weight: 300;
}

/* 按钮组容器：确保内部元素居中并排 */
.action-group {
    display: flex;
    justify-content: center;
    gap: 10px;
}

/* 按钮统一美化 */
.btn-table-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
    text-decoration: none;
    color: #374151;
}

.btn-table-action:hover {
    border-color: #d1d5db;
    background: #f3f4f6;
}

.btn-table-action.del:hover {
    color: #dc2626;
    background: #fef2f2;
    border-color: #fecaca;
}

</style>

    <p>当前已部署相册 <?php echo $readyCount; ?> 个，待部署相册 <?php echo count($list) - $readyCount; ?> 个</p>
     <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
        <button onclick="batchAction('deploy')"  class="btn-batch-ref">🔄 批量部署选中相册</button> 
        <button onclick="batchAction('delete')" class="btn-batch-delete">🗑️ 批量删除选中相册</button>
     </div>

        <table border="1" style="width:100%; border-collapse:collapse; margin-top:15px;">
            <thead><tr><th><input type="checkbox" id="all"></th><th>相册名称</th><th>图片数</th><th>操作</th></tr></thead>
            <tbody>
                <?php
                $dirs = array_diff(scandir(USER_ROOT), ['.', '..']);
                foreach ($dirs as $dir) {
                    $ini = USER_ROOT . '/' . $dir . '/ini.php';
                    $config = []; if (file_exists($ini)) include $ini;
                    $ready = is_dir(USER_ROOT . '/' . $dir . '/1');
                    $tuPath = USER_ROOT . '/' . $dir . '/1/files/mobile/';
                    $count = (is_dir($tuPath)) ? count(glob($tuPath . "*.{jpg,png,gif,webp}", GLOB_BRACE)) : 0;
                ?>
                <tr data-ready="<?php echo $ready ? '1' : '0'; ?>" data-dir="<?php echo $dir; ?>">
                    <td><input type="checkbox" class="cb" value="<?php echo $dir; ?>"></td>
                    <td><?php echo $config['title'] ?? $dir; ?></td>
                    <td><span class="album-count-badge"style="background: rgba(46, 204, 113, 0.15); color: #2ecc71;"><?php echo $count; ?>张</span></td>
                    <td>
                        <?php if ($ready): ?>
                            <a href="../user/<?php echo $dir; ?>/1/admin/" target="_blank" class="btn-table-action edit">⚙️ 进入后台</a>
                           
                            <button class="btn-table-action del" onclick="run('delete', '<?php echo $dir; ?>', false, this)">🗑️ 删除</button>
                        <?php else: ?>
                            <button onclick="run('deploy', '<?php echo $dir; ?>', false, this)" class="btn-table-action ref">🔄 部署</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <div id="log" style="margin-top:20px; background:#000; color:#0f0; padding:10px; height:100px; overflow-y:auto; font-family:monospace;">[系统就绪]</div>
    </div>
</div>

<script>
    // 统一处理全选逻辑
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('all');
        if (selectAll) {
            selectAll.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('.cb');
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
            });
        }
    });

    const log = document.getElementById('log');
    function print(m) { 
        if(log) {
            log.innerHTML += `<br>> ${m}`; 
            log.scrollTop = log.scrollHeight; 
        }
    }

async function run(act, dir, force = false, btnElement = null) {
    const url = `?action=${act}&dir=${dir}${force ? '&force=1' : ''}`;
    try {
        const res = await fetch(url);
        const data = await res.json();
        print(data.message);
        
        // 操作成功后的 UI 动态更新
        if (data.status === 'success' && btnElement) {
            if (act === 'deploy') {
                updateToReadyState(btnElement, dir);
            } else if (act === 'delete') {
                updateToPendingState(btnElement, dir);
            }
        }
        
        return data.status;
    } catch(e) {
        print("请求异常: " + e.message);
        return 'error';
    }
}

// 部署成功 -> 变更为“后台”和“删除”
function updateToReadyState(btn, dir) {
    const td = btn.closest('td');
    const tr = btn.closest('tr');
    tr.dataset.ready = '1';
    td.innerHTML = `
        <div class="action-group">
            <a href="../user/${dir}/1/admin/" target="_blank" class="btn-table-action edit">⚙️ 进入后台</a>
            <button class="btn-table-action del" onclick="run('delete', '${dir}', false, this)">🗑️ 删除</button>
        </div>
    `;
}

// 删除成功 -> 变更为“部署”按钮
function updateToPendingState(btn, dir) {
    const td = btn.closest('td');
    const tr = btn.closest('tr');
    tr.dataset.ready = '0';
    td.innerHTML = `
        <button onclick="run('deploy', '${dir}', false, this)" class="btn-table-action ref">🔄 部署</button>
    `;
}
    async function batchAction(act) {
        const cbs = document.querySelectorAll('.cb:checked');
        if(cbs.length === 0) return alert('请先选择操作对象');
        
        let shouldReload = false;
        for (let cb of cbs) {
            const tr = cb.closest('tr');
            const dir = cb.value;
            const isReady = tr && tr.dataset.ready === '1';

            if (act === 'deploy' && isReady) {
                const result = await Swal.fire({
                    title: '⚠️ 覆盖确认',
                    text: `相册 [${dir}] 已存在，是否强制覆盖部署？`,
                    icon: 'warning',
                    background: '#13192e',
                    color: '#fff',
                    showCancelButton: true,
                    confirmButtonText: '覆盖',
                    cancelButtonText: '跳过'
                });
                if (result.isConfirmed) {
                    if(await run('deploy', dir, true) === 'success') shouldReload = true;
                }
            } else {
                if(await run(act, dir) === 'success') shouldReload = true;
            }
        }
        if (shouldReload) setTimeout(() => location.reload(), 1000);
    }
</script>