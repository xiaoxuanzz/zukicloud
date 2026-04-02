<?php
include("./includes/common.php");

// Handle logout
if(isset($_GET['logout'])){
    setcookie("user_token", '', time() - 3600, '/');
    header('Location: ./');
    exit;
}

// Handle login
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'] ?? 'login';
    
    if(empty($username) || empty($password)){
        $error = "用户名和密码不能为空";
    } elseif(strlen($username) < 3 || strlen($username) > 20){
        $error = "用户名需要3-20个字符";
    } elseif(strlen($password) < 6){
        $error = "密码至少6位字符";
    } else {
        if($action == 'register'){
            // Check if username exists
            $exists = $DB->getColumn("SELECT COUNT(*) FROM pre_user WHERE nickname=:name", array(':name' => $username));
            if($exists){
                $error = "该用户名已被注册";
            } else {
                // Register
                $ip = real_ip($conf['ip_type'] ? $conf['ip_type'] : 0);
                $DB->exec("INSERT INTO pre_user (nickname, password, type, regip, addtime, enable) VALUES ('$username', '" . md5($password) . "', 'local', '$ip', NOW(), 1)");
                $uid = $DB->lastInsertId();
                // Auto login
                $token = authcode("$uid\t" . md5('local'.$password_hash) . "\t" . (time() + 2592000), 'ENCODE', SYS_KEY);
                setcookie("user_token", $token, time() + 2592000, '/');
                header('Location: ./');
                exit;
            }
        } else {
            // Login
            $row = $DB->getRow("SELECT * FROM pre_user WHERE nickname=:name AND type='local'", array(':name' => $username));
            if($row && $row['password'] == md5($password)){
                if($row['enable'] != 1){
                    $error = "账号已被禁用，请联系管理员";
                } else {
                    $token = authcode("{$row['uid']}\t" . md5('local'.$password_hash) . "\t" . (time() + 2592000), 'ENCODE', SYS_KEY);
                    setcookie("user_token", $token, time() + 2592000, '/');
                    header('Location: ./');
                    exit;
                }
            } else {
                $error = "用户名或密码错误";
            }
        }
    }
}

$error = $error ?? '';
$show_register = isset($_GET['act']) && $_GET['act'] == 'register';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $show_register ? '注册' : '登录'?> - <?php echo $conf['title']?></title>
    <link href="https://s4.zstatic.net/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://s4.zstatic.net/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="./assets/css/style.css?v=<?php echo VERSION?>" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-top: 0 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .login-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 15px;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 40px 35px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .login-logo i {
            font-size: 48px;
            color: #667eea;
        }
        .login-logo h2 {
            margin: 10px 0 0;
            font-size: 22px;
            color: #333;
            font-weight: 700;
        }
        .login-logo p {
            color: #999;
            font-size: 14px;
            margin-top: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-size: 13px;
            color: #666;
            font-weight: 500;
        }
        .form-control {
            height: 46px;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .input-group-addon {
            background: transparent;
            border: 2px solid #e8e8e8;
            border-right: none;
            border-radius: 10px 0 0 10px;
            color: #999;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .btn-login {
            width: 100%;
            height: 46px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            margin-top: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
        .alert {
            border-radius: 10px;
            border: none;
            font-size: 14px;
        }
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
            color: #ccc;
            font-size: 13px;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #eee;
        }
        .divider span {
            padding: 0 15px;
        }
        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
            text-decoration: none;
            transition: transform 0.2s;
        }
        .social-btn:hover {
            transform: scale(1.1);
            text-decoration: none;
            color: #fff;
        }
        .social-btn.qq { background: #12B7F5; }
        .social-btn.wx { background: #07C160; }
        .back-home {
            position: fixed;
            top: 20px;
            left: 20px;
            color: #fff;
            font-size: 14px;
            text-decoration: none;
            opacity: 0.8;
        }
        .back-home:hover { opacity: 1; color: #fff; text-decoration: none; }
        @media (max-width: 767px) {
            .login-card {
                padding: 28px 20px;
                border-radius: 14px;
                margin: 0 8px;
            }
            .login-logo i { font-size: 36px; }
            .login-logo h2 { font-size: 18px; }
            .login-logo p { font-size: 13px; }
            .btn-login { height: 42px; font-size: 15px; }
            .form-control { height: 42px; font-size: 14px; }
            .back-home { top: 12px; left: 12px; font-size: 13px; }
        }
    </style>
</head>
<body>
    <a href="./" class="back-home"><i class="fa fa-arrow-left"></i> 返回首页</a>
    
    <div class="login-content">
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <i class="fa fa-cloud"></i>
                <h2><?php echo htmlspecialchars($conf['title'])?></h2>
                <p><?php echo $show_register ? '创建新账号' : '欢迎回来，请登录'?></p>
            </div>
            
            <?php if($error){?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error)?>
                </div>
            <?php }?>
            
            <form method="post" action="">
                <input type="hidden" name="action" value="<?php echo $show_register ? 'register' : 'login'?>">
                
                <div class="form-group">
                    <label><i class="fa fa-user"></i> 用户名</label>
                    <input type="text" name="username" class="form-control" placeholder="请输入用户名（3-20个字符）" 
                           required autofocus maxlength="20" value="<?php echo htmlspecialchars($_POST['username'] ?? '')?>">
                </div>
                
                <div class="form-group">
                    <label><i class="fa fa-lock"></i> 密码</label>
                    <input type="password" name="password" class="form-control" placeholder="<?php echo $show_register ? '请设置密码（至少6位）' : '请输入密码'?>" 
                           required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fa <?php echo $show_register ? 'fa-user-plus' : 'fa-sign-in'?>"></i>
                    <?php echo $show_register ? ' 注 册' : ' 登 录'?>
                </button>
            </form>
            

            
            <div class="login-footer">
                <?php if($show_register){?>
                    已有账号？<a href="./login.php">立即登录</a>
                <?php }else{?>
                    还没有账号？<a href="./login.php?act=register">立即注册</a>
                <?php }?>
            </div>
        </div>
        </div>
    </div>
    <div style="text-align:center;color:rgba(255,255,255,0.6);font-size:12px;padding:15px 0;margin-top:auto;">
        © <?php echo date('Y')?> <?php echo htmlspecialchars($conf['title'])?>
    </div>


</body>
</html>
