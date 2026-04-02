<?php
define('IN_ADMIN', true);
include("../includes/common.php");

// Handle logout
if(isset($_GET['logout'])){
    setcookie("admin_token", "", time() - 1, '/');
    header('Content-Type: text/html; charset=UTF-8');
    exit("<script>alert('已退出登录');window.location.href='./login.php';</script>");
}

// Already logged in
if($islogin==1){
    header('Location: ./index.php');
    exit;
}

// Handle login form submission
$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if($username === $conf['admin_user'] && $password === $conf['admin_pwd']){
        // Create admin token - same logic as common.php
        $session = md5($conf['admin_user'] . $conf['admin_pwd'] . $password_hash);
        $expiretime = time() + 2592000;
        $token = authcode("{$username}\t{$session}\t{$expiretime}", 'ENCODE', SYS_KEY);
        setcookie("admin_token", $token, time() + 2592000, '/');
        header('Location: ./index.php');
        exit;
    } else {
        $error = '用户名或密码错误';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台登录 - <?php echo $conf['title']?></title>
    <link href="https://s4.zstatic.net/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://s4.zstatic.net/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"/>
    <link href="../assets/css/style.css?v=<?php echo VERSION?>" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { background: #fff; border-radius: 10px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .login-box h2 { text-align: center; margin-bottom: 30px; color: #333; }
        .login-box .form-control { height: 45px; border-radius: 5px; }
        .login-box .btn { height: 45px; font-size: 16px; border-radius: 5px; }
        .login-icon { text-align: center; font-size: 60px; color: #667eea; margin-bottom: 20px; }
        .alert { border-radius: 5px; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-icon"><i class="fa fa-lock"></i></div>
        <h2>管理后台登录</h2>
        <?php if($error){?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error)?></div>
        <?php }?>
        <form method="post" action="">
            <div class="form-group">
                <label><i class="fa fa-user"></i> 用户名</label>
                <input type="text" name="username" class="form-control" placeholder="请输入用户名" required autofocus value="<?php echo htmlspecialchars($_POST['username'] ?? '')?>">
            </div>
            <div class="form-group">
                <label><i class="fa fa-key"></i> 密码</label>
                <input type="password" name="password" class="form-control" placeholder="请输入密码" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">登 录</button>
        </form>
        <p style="text-align:center;margin-top:20px;color:#999;font-size:12px;">
            <a href="../" style="color:#999;">← 返回前台</a>
        </p>
    </div>
</body>
</html>
