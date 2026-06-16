<?php
// index.php 后台登录验证页
session_start();
       $_SESSION['admin_logged_in'] = true;
        $_SESSION['logged_in_admin'] = true;
        $_SESSION[$logged_in_key] = true; 
// 1. 引入相册本地配置（保持与 admin.php 一致，兼容独立相册配置）
$currentAlbumConfig = [];
if (file_exists('ini.php')) {
    $config = [];
    include('ini.php');
    $currentAlbumConfig = $config;
}

// 2. 动态检测登录标识符，确保两端键名完全对齐
$logged_in_key = isset($currentAlbumConfig['login_admin']) ? $currentAlbumConfig['login_admin'] : 'logged_in_admin';

// 3. 检查用户是否已经登录，如果已登录直接放行到管理页面
if (
    (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) ||
    (isset($_SESSION['logged_in_admin']) && $_SESSION['logged_in_admin'] === true) ||
    (!empty($_SESSION[$logged_in_key]) && $_SESSION[$logged_in_key] === true)
) {
    header('Location: admin.php');
    exit();
}

// 默认账号密码
$usera = "admin";
//$passa = "admin";
$passa = bin2hex(random_bytes(11));

// 4. 处理登录提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // 判断用户名和密码是否匹配
    if ($username === $usera && $password === $passa) {
        // 登录成功，同时写入主后台状态和动态相册状态，确保安全通过 admin.php 的强校验
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['logged_in_admin'] = true;
        $_SESSION[$logged_in_key] = true; 
        
        header('Location: admin.php');
        exit();
    } else {
        // 登录失败，留在当前 index.php 页面
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
        echo "<script>";
        echo "window.onload = function() {";
        echo "  Swal.fire({";
        echo "    title: '登录失败！',";
        echo "    text: '用户名或密码错误',";
        echo "    icon: 'error',";
        echo "    confirmButtonText: '确定'";
        echo "  }).then((result) => {"; 
        echo "    if (result.isConfirmed) {";
        echo "      window.location.href = './index.php';";
        echo "    }";
        echo "  });";
        echo "};";
        echo "</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录后台</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f0f4f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            width: 100vw;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            text-align: center;
        }
        .login-header h1 {
            color: #3b82f6;
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .login-header p {
            color: #64748b;
            font-size: 15px;
            margin-bottom: 24px;
        }
        .form-group {
            text-align: left;
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            color: #334155;
            font-size: 14px;
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            font-size: 14px;
            color: #334155;
            background-color: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-control::placeholder {
            color: #94a3b8;
        }
        .form-options {
            display: flex;
            align-items: center;
            text-align: left;
            margin-bottom: 20px;
        }
        .form-options input[type="checkbox"] {
            width: 15px;
            height: 15px;
            margin-right: 8px;
            cursor: pointer;
            accent-color: #3b82f6;
        }
        .form-options label {
            color: #334155;
            font-size: 14px;
            user-select: none;
            cursor: pointer;
        }
        .btn-submit {
            width: 100%;
            padding: 10px;
            font-size: 15px;
            color: #fff;
            background-color: #3b82f6;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-submit:hover {
            background-color: #2563eb;
        }
        .btn-submit:active {
            background-color: #1d4ed8;
        }
        .login-footer {
            margin-top: 24px;
            font-size: 13px;
            color: #64748b;
        }
        .login-footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <h1>Welcome back!</h1>
            <p>Sign in to your account to continue</p>
        </div>

        <form action="index.php" method="post">
            <div class="form-group">
                <label for="username">User</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Enter your user" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
            </div>

            <div class="form-options">
                <input type="checkbox" id="remember_me">
                <label for="remember_me">Remember me</label>
            </div>

            <button type="submit" class="btn-submit">登录</button>
        </form>

        <div class="login-footer">
            Don't have an account? <a href="#">Sign up</a>
        </div>
    </div>

</body>
</html>