<?php
// delete_album.php

// ----------------------------------------------------
// DEBUGGING SETTINGS (ONLY FOR DEVELOPMENT/TESTING)
// In production, these should be off or errors logged to file.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ----------------------------------------------------

session_start();

// Get the absolute path of the current script's directory.
// Example: if delete_album.php is in /var/www/html/admin/, then $current_script_dir is /var/www/html/admin
$current_script_dir = __DIR__;

// --- Helper Function: Centralized Redirection ---
function redirectToAdmin($msg, $type) {
    if (headers_sent()) {
        error_log("delete_album.php FATAL ERROR: Headers already sent before redirect. Message: " . $msg);
        echo "<h1>操作失败或完成</h1>";
        echo "<div style='padding: 20px; border: 1px solid #ccc; background-color: #f8d7da; color: #721c24;'>";
        echo "<strong>错误：</strong>无法自动跳转！请手动返回。原因：输出已开始。<br>";
        echo htmlspecialchars($msg);
        echo "</div>";
        echo "<br><button onclick=\"window.location.href='admin.php'\">返回管理页面</button>";
        exit();
    }
    header('Location: admin.php?msg=' . urlencode($msg) . '&type=' . urlencode($type));
    exit();
}

// --- Security Check: User Login ---
if (empty($_SESSION['logged_in_admin']) || $_SESSION['logged_in_admin'] !== true) {
    error_log("delete_album.php SECURITY: Unauthorized access attempt. Redirecting to login.");
    redirectToAdmin('请先登录以执行此操作。', 'error');
}

// --- Input Validation: Check for album_name in POST ---
if (!isset($_POST['album_name']) || empty(trim($_POST['album_name']))) {
    error_log("delete_album.php ERROR: No album_name provided in POST request.");
    redirectToAdmin('错误：未指定要删除的相册名称。', 'error');
}

$album_name = trim($_POST['album_name']);

// -------------------------------------------------------------------------------------------------
// Define Root Paths based on the project structure and your clarification "相对于根目录的"
// Assuming 'admin' and 'user' and 'update' are direct children of the project root.
// project_root/
// ├── admin/
// ├── user/
// └── update/
// -------------------------------------------------------------------------------------------------

// Determine the project root. This moves up one level from 'admin/' directory.
// Example: If $current_script_dir is /var/www/html/admin, then $project_root will be /var/www/html
$project_root = realpath($current_script_dir . DIRECTORY_SEPARATOR . '..');
if ($project_root === false) {
    error_log("delete_album.php FATAL ERROR: Could not determine project root from " . $current_script_dir . DIRECTORY_SEPARATOR . "..");
    redirectToAdmin('系统错误：无法确定项目根目录。', 'error');
}

// Absolute path to user albums root (e.g., /var/www/html/user)
$user_albums_root = $project_root . DIRECTORY_SEPARATOR . 'user';
// Absolute path to update user directory (e.g., /var/www/html/update/user)
$update_user_root = $project_root . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . 'user';

// --- Check if these core directories exist and are accessible ---
if (!is_dir($user_albums_root) || !is_readable($user_albums_root)) {
    error_log("delete_album.php FATAL ERROR: User albums root directory not found or not readable: " . $user_albums_root);
    redirectToAdmin('系统错误：用户相册根目录不可访问。', 'error');
}
if (!is_dir($update_user_root) || !is_readable($update_user_root)) {
    error_log("delete_album.php FATAL ERROR: Update user root directory not found or not readable: " . $update_user_root);
    redirectToAdmin('系统错误：更新文件根目录不可访问。', 'error');
}


// --- Construct Album Specific Paths using the defined roots ---
// Full absolute path to the album directory in 'user/'
$album_user_path = $user_albums_root . DIRECTORY_SEPARATOR . $album_name;
// Full absolute path to the album directory in 'update/user/'
$album_update_path = $update_user_root . DIRECTORY_SEPARATOR . $album_name;


error_log("delete_album.php DEBUG: Determined Project Root: " . $project_root);
error_log("delete_album.php DEBUG: User Albums Root: " . $user_albums_root);
error_log("delete_album.php DEBUG: Update User Root: " . $update_user_root);
error_log("delete_album.php DEBUG: Album Name to delete: " . $album_name);
error_log("delete_album.php DEBUG: Calculated album_user_path: " . $album_user_path);
error_log("delete_album.php DEBUG: Calculated album_update_path: " . $album_update_path);


// --- Security Check: Validate Album Path in 'user/' ---
// Ensure the provided album_name actually points to a directory within the expected user_albums_root.
// This prevents deleting outside the intended scope.
$real_album_user_path = realpath($album_user_path); // Get the canonicalized absolute path

// Check if realpath failed, or if the resolved path is outside the user_albums_root,
// or if it's attempting to delete the user_albums_root itself.
if ($real_album_user_path === false || strpos($real_album_user_path, $user_albums_root) !== 0 || $real_album_user_path === $user_albums_root) {
    error_log("delete_album.php SECURITY ALERT: Invalid or protected user directory delete attempt. Album: " . $album_name . " Resolved path: " . ($real_album_user_path ?: "N/A") . " vs User Root: " . $user_albums_root);
    redirectToAdmin('错误：尝试删除非法或受保护的目录。', 'error');
}

// --- Check if main album directory exists (in 'user/') ---
if (!is_dir($real_album_user_path)) {
    error_log("delete_album.php ERROR: Main album directory '" . htmlspecialchars($album_name) . "' does not exist at: " . $real_album_user_path . ". Already deleted or invalid.");
    // If main directory doesn't exist, we can potentially consider it "deleted" for the user path,
    // but still proceed to check/delete the update path if it might exist.
    // For now, we'll error out as this is the primary target.
    redirectToAdmin('错误：相册目录 "' . htmlspecialchars($album_name) . '" 不存在。', 'error');
}

// -------------------------------------------------------------------------
// Helper Functions (for self-containment and clarity)
// -------------------------------------------------------------------------

/**
 * Safely reads the ini.php configuration for an album.
 * @param string $albumDirPath The absolute path to the album directory.
 * @return array The configuration array, or empty array if not found/readable.
 */
function getAlbumConfigForDelete($albumDirPath) {
    $config_data = [];
    $iniFilePath = $albumDirPath . DIRECTORY_SEPARATOR . 'ini.php';
    if (file_exists($iniFilePath) && is_readable($iniFilePath)) {
        ob_start(); // Start output buffering
        include $iniFilePath; // Include the ini.php file
        ob_end_clean(); // Discard any output from the included file
        if (isset($config) && is_array($config)) {
            $config_data = $config;
        }
        unset($config); // Unset the config variable to prevent global scope pollution
    } else {
        error_log("delete_album.php WARNING: ini.php not found or not readable for album directory: " . $iniFilePath);
    }
    return $config_data;
}

/**
 * Counts image files within a specified directory.
 * @param string $directoryPath The absolute path to the directory to scan.
 * @return int The number of image files found.
 */
function countImagesInDirectoryForDelete($directoryPath) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico'];
    $imageCount = 0;

    if (is_dir($directoryPath) && is_readable($directoryPath)) {
        $files = scandir($directoryPath);
        if ($files === false) {
             error_log("delete_album.php WARNING: scandir failed for image directory: " . $directoryPath . ". Check permissions.");
             return 0;
        }
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $filePath = $directoryPath . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath) && is_readable($filePath)) {
                $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($fileExtension, $imageExtensions)) {
                    $imageCount++;
                }
            }
        }
    } else {
        error_log("delete_album.php INFO: Image directory '" . ($directoryPath ?: "N/A") . "' not found or not readable during image count check. Count will be 0.");
    }
    return $imageCount;
}

/**
 * Recursively deletes a directory and its contents.
 * @param string $dir The absolute path to the directory to delete.
 * @param string $rootCheckPath Optional: A root path to ensure deletion stays within this boundary.
 * @return bool True on success, false on failure.
 */
function deleteDirectoryRecursive($dir, $rootCheckPath = null) {
    if (!is_dir($dir)) {
        error_log("delete_album.php ERROR: deleteDirectoryRecursive received non-directory path or non-existent dir: " . $dir);
        return false;
    }

    // Canonicalize the directory path for safety checks
    $real_dir = realpath($dir);
    if ($real_dir === false) {
        error_log("delete_album.php ERROR: realpath failed for directory in deleteDirectoryRecursive: " . $dir);
        return false;
    }

    // --- Safety Checks (using global variables from parent scope) ---
    global $user_albums_root, $update_user_root, $project_root;

    // Prevent deletion of critical root directories
    if ($real_dir === $user_albums_root || $real_dir === $update_user_root || $real_dir === $project_root) {
        error_log("delete_album.php SECURITY ALERT: Attempted to delete a protected root directory inside deleteDirectoryRecursive: " . $real_dir);
        return false;
    }

    // If a rootCheckPath is provided, ensure the current directory is within it
    if ($rootCheckPath !== null) {
        $real_root_check_path = realpath($rootCheckPath);
        if ($real_root_check_path === false || strpos($real_dir, $real_root_check_path) !== 0) {
            error_log("delete_album.php SECURITY ALERT: Attempted to delete directory outside specified root check path. Dir: " . $real_dir . " Check path: " . $real_root_check_path);
            return false;
        }
    }

    // --- Core recursive deletion logic ---
    $files = array_diff(scandir($real_dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $real_dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            if (!deleteDirectoryRecursive($path, $rootCheckPath)) { // Pass rootCheckPath recursively
                error_log("delete_album.php ERROR: Failed to recursively delete subdirectory: " . $path);
                return false; // Propagate failure up
            }
        } else {
            if (!unlink($path)) {
                error_log("delete_album.php ERROR: Failed to delete file: " . $path . " (Permissions: " . (file_exists($path) ? decoct(fileperms($path)) : "N/A") . ")");
                return false; // Propagate failure up
            }
        }
    }
    // Finally, remove the now empty directory
    if (!rmdir($real_dir)) {
        error_log("delete_album.php ERROR: Failed to remove directory: " . $real_dir . " (Permissions: " . (file_exists($real_dir) ? decoct(fileperms($real_dir)) : "N/A") . ")");
        return false; // Propagate failure up
    }
    error_log("delete_album.php DEBUG: Successfully deleted directory: " . $real_dir);
    return true;
}


// --- Get image count for the album (from its configured image directory) ---
// We use $real_album_user_path here because ini.php is inside ../user/ALBUM_NAME/
$album_config = getAlbumConfigForDelete($real_album_user_path);
$image_dir_relative_to_album = $album_config['tu_1'] ?? null;
$image_count = 0; // Images in the album's specifically configured folder

if ($image_dir_relative_to_album) {
    // Construct the full path to the image directory based on the ini.php's *relative* path.
    // ini.php content: 'tu_1' => '../../update/user/' . $name . '/class/img/'
    // This path is relative to the ini.php's location, which is $real_album_user_path.
    $image_full_path_from_ini = realpath($real_album_user_path . DIRECTORY_SEPARATOR . $image_dir_relative_to_album);

    error_log("delete_album.php DEBUG: Album '" . $album_name . "' ini[tu_1] value: " . $image_dir_relative_to_album);
    error_log("delete_album.php DEBUG: Base path for image count (from ini.php perspective): " . $real_album_user_path);
    error_log("delete_album.php DEBUG: Resolved image count path (realpath from ini config): " . ($image_full_path_from_ini ?: "false"));

    if ($image_full_path_from_ini && is_dir($image_full_path_from_ini) && is_readable($image_full_path_from_ini)) {
        $image_count = countImagesInDirectoryForDelete($image_full_path_from_ini);
        error_log("delete_album.php DEBUG: Actual image count for '" . $album_name . "': " . $image_count);
    } else {
        error_log("delete_album.php WARNING: Image directory for album '" . $album_name . "' could not be resolved or accessed (from ini.php config): " . ($image_full_path_from_ini ?: "N/A") . ". Assuming 0 images for delete check.");
    }
} else {
    error_log("delete_album.php INFO: Album '" . $album_name . "' has no 'tu_1' defined in ini.php. Assuming 0 images for delete check.");
}

// --- Core Logic: Disallow deletion if images > 0 ---
if ($image_count > 0) {
    error_log("delete_album.php INFO: Attempted to delete album '" . $album_name . "' with " . $image_count . " images. Deletion prevented.");
    redirectToAdmin('错误：相册 "' . htmlspecialchars($album_name) . '" 中包含 ' . $image_count . ' 张图片，请先清空图片再删除相册。', 'error');
}

// -------------------------------------------------------------------------
// Main Deletion Logic
// -------------------------------------------------------------------------

$deletion_overall_success = true; // Overall flag for deletion status

// 1. Delete associated image directories in 'update/user/'
// This targets the specific album folder under update/user/
error_log("delete_album.php INFO: Attempting to delete associated update directory: " . $album_update_path);

// Check if the update path exists and is a directory before attempting to delete
if (is_dir($album_update_path)) {
    // Pass $update_user_root as the boundary for deletion to ensure safety
    if (!deleteDirectoryRecursive($album_update_path, $update_user_root)) {
        $deletion_overall_success = false;
        error_log("delete_album.php ERROR: Failed to delete update directory for album: " . $album_name . " at: " . $album_update_path . ". Check permissions.");
    } else {
        error_log("delete_album.php INFO: Successfully deleted update directory for album: " . $album_name . " at: " . $album_update_path);
    }
} else {
    error_log("delete_album.php INFO: Associated update directory for album '" . $album_name . "' not found or already deleted at: " . $album_update_path);
}


// 2. Delete the main album directory in 'user/'
error_log("delete_album.php INFO: Attempting to delete main album directory: " . $album_user_path);

// Check if the user path exists and is a directory before attempting to delete
if (is_dir($album_user_path)) {
    // Pass $user_albums_root as the boundary for deletion to ensure safety
    if (!deleteDirectoryRecursive($album_user_path, $user_albums_root)) {
        $deletion_overall_success = false;
        error_log("delete_album.php ERROR: Failed to delete main album directory: " . $album_name . " at: " . $album_user_path . ". Check permissions.");
    } else {
        error_log("delete_album.php INFO: Successfully deleted main album directory: " . $album_name . " at: " . $album_user_path);
    }
} else {
    error_log("delete_album.php INFO: Main album directory for album '" . $album_name . "' not found or already deleted at: " . $album_user_path);
}


// 3. Provide feedback based on overall deletion status
if ($deletion_overall_success) {
    redirectToAdmin('相册 "' . htmlspecialchars($album_name) . '" 及其所有关联数据已成功删除。', 'success');
} else {
    redirectToAdmin('错误：删除相册 "' . htmlspecialchars($album_name) . '" 失败，或部分关联数据未能删除。请检查文件权限和服务器日志以获取更多详情。', 'error');
}

?>