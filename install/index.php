<?php
// 程序安装文件 - 全新UI设计
error_reporting(0);
date_default_timezone_set("PRC");
$databaseFile = '../config.php';

@header('Content-Type: text/html; charset=UTF-8');
$step = isset($_GET['step']) ? $_GET['step'] : 1;
if (file_exists('install.lock')) {
    exit('你已经成功安装，如需重新安装，请手动删除install目录下install.lock文件！');
}

function random($length, $numeric = 0) {
    $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    $hash = '';
    $max = strlen($seed) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $seed[mt_rand(0, $max)];
    }
    return $hash;
}

if ($step == 3) {
    if ($_GET['jump'] == 1) {
        include '../config.php';
        if (!$dbconfig['user'] || !$dbconfig['pwd'] || !$dbconfig['dbname']) {
            $errorMsg = '请先填写好数据库并保存后再安装！';
        }
    } else {
        $host = isset($_POST['host']) ? $_POST['host'] : null;
        $port = isset($_POST['port']) ? $_POST['port'] : null;
        $user = isset($_POST['user']) ? $_POST['user'] : null;
        $pwd = isset($_POST['pwd']) ? $_POST['pwd'] : null;
        $database = isset($_POST['database']) ? $_POST['database'] : null;
        if (empty($host) || empty($port) || empty($user) || empty($pwd) || empty($database)) {
            $errorMsg = '请填写完整所有数据库信息！';
        }
        $dbconfig = array(
            'host' => $host, 'port' => $port,
            'user' => $user, 'pwd' => $pwd, 'dbname' => $database
        );
        $config = "<?php\n/*数据库配置*/\n\$dbconfig=array(\n    'host' => '{$host}',\n    'port' => {$port},\n    'user' => '{$user}',\n    'pwd' => '{$pwd}',\n    'dbname' => '{$database}'\n);\n    ";
    }
    if (empty($errorMsg)) {
        try {
            $DB = new PDO("mysql:host=" . $dbconfig['host'] . ";dbname=" . $dbconfig['dbname'] . ";port=" . $dbconfig['port'], $dbconfig['user'], $dbconfig['pwd']);
        } catch (Exception $e) {
            if ($e->getCode() == 2002) $errorMsg = '连接数据库失败：数据库地址填写错误！';
            elseif ($e->getCode() == 1045) $errorMsg = '连接数据库失败：数据库用户名或密码填写错误！';
            elseif ($e->getCode() == 1049) $errorMsg = '连接数据库失败：数据库名不存在！';
            else $errorMsg = '连接数据库失败：' . $e->getMessage();
        }
        if (empty($errorMsg)) {
            $DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            $DB->exec("set sql_mode = ''");
            $DB->exec("set names utf8");
            $mysqlversion = $DB->query("select version()")->fetchColumn();
            if (version_compare($mysqlversion, '5.5.3', '<')) {
                $errorMsg = 'MySQL数据库版本太低，需要MySQL 5.6或以上版本！';
            }
            if (!$_GET['jump'] && !file_put_contents($databaseFile, $config)) {
                $errorMsg = '保存失败，请确保网站根目录有写入权限';
            }
        }
    }
} elseif ($step == 4) {
    include '../config.php';
    if (!$dbconfig['user'] || !$dbconfig['pwd'] || !$dbconfig['dbname']) {
        $errorMsg = '请先填写好数据库并保存后再安装！';
    } else {
        try {
            $DB = new PDO("mysql:host=" . $dbconfig['host'] . ";dbname=" . $dbconfig['dbname'] . ";port=" . $dbconfig['port'], $dbconfig['user'], $dbconfig['pwd']);
        } catch (Exception $e) {
            $errorMsg = '连接数据库失败：' . $e->getMessage();
        }
        if (empty($errorMsg) && !$_GET['jump']) {
            $DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            $DB->exec("set sql_mode = ''");
            $DB->exec("set names utf8");
            $sqls = file_get_contents('install.sql');
            $sqls = explode(';', $sqls);
            $sqls[] = "INSERT INTO `pre_config` VALUES ('syskey', '" . random(32) . "')";
            $sqls[] = "INSERT INTO `pre_config` VALUES ('build', '" . date("Y-m-d") . "')";
            $success = 0;
            $error = 0;
            $errorMsg = null;
            foreach ($sqls as $value) {
                $value = trim($value);
                if (empty($value)) continue;
                if ($DB->exec($value) === false) {
                    $error++;
                    $dberror = $DB->errorInfo();
                    $errorMsg .= $dberror[2] . "<br>";
                } else {
                    $success++;
                }
            }
        }
        if (empty($errorMsg)) {
            $lock_status = file_put_contents("install.lock", '安装锁');
            $step = 5;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>安装向导 - ZuKizZ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            overflow: hidden;
        }

        /* ========== 渐变流体背景 ========== */
        .fluid-bg {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            z-index: -2;
        }
        .fluid-bg::before,
        .fluid-bg::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            animation: float 12s ease-in-out infinite;
        }
        .fluid-bg::before {
            width: 500px; height: 500px;
            background: rgba(102, 126, 234, 0.6);
            top: -150px; left: -100px;
            animation-delay: 0s;
        }
        .fluid-bg::after {
            width: 600px; height: 600px;
            background: rgba(240, 147, 251, 0.5);
            bottom: -200px; right: -150px;
            animation-delay: -6s;
        }
        .fluid-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.4;
            animation: float2 15s ease-in-out infinite;
        }
        .fluid-blob.b1 {
            width: 400px; height: 400px;
            background: #43e97b;
            top: 60%; left: 10%;
        }
        .fluid-blob.b2 {
            width: 350px; height: 350px;
            background: #fa709a;
            top: 20%; right: 15%;
            animation-delay: -8s;
        }
        .fluid-blob.b3 {
            width: 300px; height: 300px;
            background: #a18cd1;
            bottom: 10%; left: 50%;
            animation-delay: -4s;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(50px, -30px) scale(1.05); }
            66% { transform: translate(-30px, 50px) scale(0.95); }
        }
        @keyframes float2 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(-40px, 60px) rotate(120deg); }
            66% { transform: translate(60px, -20px) rotate(240deg); }
        }

        /* ========== 主容器 ========== */
        .main-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        /* ========== 玻璃卡片 ========== */
        .glass-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 24px;
            padding: 48px 40px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            animation: cardIn 0.6s ease-out;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ========== Logo & 标题 ========== */
        .logo {
            text-align: center;
            margin-bottom: 36px;
        }
        .logo-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, rgba(255,255,255,0.3), rgba(255,255,255,0.1));
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .logo-icon i {
            font-size: 32px;
            color: #fff;
        }
        .logo h1 {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.5px;
        }
        .logo p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 6px;
        }

        /* ========== 步骤指示器 ========== */
        .steps {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 36px;
            gap: 0;
        }
        .step-dot {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.5);
            transition: all 0.4s;
            position: relative;
        }
        .step-dot.active {
            background: #fff;
            color: #764ba2;
            border-color: #fff;
            box-shadow: 0 0 20px rgba(255,255,255,0.4);
        }
        .step-dot.done {
            background: rgba(255,255,255,0.3);
            color: #fff;
            border-color: rgba(255,255,255,0.5);
        }
        .step-line {
            width: 48px; height: 2px;
            background: rgba(255, 255, 255, 0.2);
            transition: background 0.4s;
        }
        .step-line.done {
            background: rgba(255, 255, 255, 0.5);
        }

        /* ========== 表单元素 ========== */
        .form-title {
            font-size: 20px;
            font-weight: 600;
            color: #fff;
            text-align: center;
            margin-bottom: 28px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
        }
        .form-group label i {
            margin-right: 6px;
            width: 16px;
            text-align: center;
        }
        .form-control {
            width: 100%;
            height: 48px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 0 16px;
            font-size: 15px;
            color: #fff;
            outline: none;
            transition: all 0.3s;
            font-family: inherit;
        }
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.18);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
        }

        /* ========== 按钮 ========== */
        .btn-primary {
            width: 100%;
            height: 52px;
            background: rgba(255, 255, 255, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 14px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
        }
        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* ========== 环境检测列表 ========== */
        .check-list {
            list-style: none;
            margin-bottom: 28px;
        }
        .check-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
        }
        .check-list li i {
            margin-right: 8px;
        }
        .check-ok {
            color: #4ade80;
            font-size: 13px;
        }
        .check-fail {
            color: #f87171;
            font-size: 13px;
        }

        /* ========== 结果卡片 ========== */
        .result-box {
            text-align: center;
            padding: 20px 0;
        }
        .result-icon {
            font-size: 56px;
            margin-bottom: 16px;
        }
        .result-box h3 {
            font-size: 20px;
            color: #fff;
            margin-bottom: 12px;
        }
        .result-box p {
            font-size: 14px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 8px;
            line-height: 1.6;
        }
        .result-box .info-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255,255,255,0.1);
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 8px;
            font-size: 14px;
            color: rgba(255,255,255,0.9);
        }
        .result-box .info-item a {
            color: #fbbf24;
            text-decoration: none;
        }

        /* ========== 警告/错误提示 ========== */
        .alert-box {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #fca5a5;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert-box i {
            margin-top: 2px;
        }
        .success-box {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.4);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #86efac;
            text-align: center;
        }

        /* ========== 跳转链接 ========== */
        .link-text {
            text-align: center;
            margin-top: 16px;
        }
        .link-text a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s;
        }
        .link-text a:hover {
            color: #fff;
        }

        /* ========== Footer ========== */
        .footer-text {
            text-align: center;
            margin-top: 28px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
        }
        .footer-text a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
        }

        /* ========== 列表项 ========== */
        .info-list {
            list-style: none;
            margin: 16px 0;
        }
        .info-list li {
            padding: 12px 16px;
            background: rgba(255,255,255,0.08);
            border-radius: 10px;
            margin-bottom: 8px;
            font-size: 14px;
            color: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-list li i {
            color: #4ade80;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="fluid-bg">
        <div class="fluid-blob b1"></div>
        <div class="fluid-blob b2"></div>
        <div class="fluid-blob b3"></div>
    </div>

    <div class="main-container">
        <div class="glass-card">
            <!-- Logo -->
            <div class="logo">
                <div class="logo-icon"><i class="fa fa-cloud"></i></div>
                <h1>ZuKizZ</h1>
                <p>安装向导</p>
            </div>

            <!-- 步骤指示器 -->
            <div class="steps">
                <div class="step-dot <?php echo $step == 1 ? 'active' : ($step > 1 ? 'done' : ''); ?>">
                    <?php echo $step > 1 ? '<i class="fa fa-check" style="font-size:12px"></i>' : '1'; ?>
                </div>
                <div class="step-line <?php echo $step > 1 ? 'done' : ''; ?>"></div>
                <div class="step-dot <?php echo $step == 2 ? 'active' : ($step > 2 ? 'done' : ''); ?>">
                    <?php echo $step > 2 ? '<i class="fa fa-check" style="font-size:12px"></i>' : '2'; ?>
                </div>
                <div class="step-line <?php echo $step > 2 ? 'done' : ''; ?>"></div>
                <div class="step-dot <?php echo $step == 3 ? 'active' : ($step > 3 ? 'done' : ''); ?>">
                    <?php echo $step > 3 ? '<i class="fa fa-check" style="font-size:12px"></i>' : '3'; ?>
                </div>
                <div class="step-line <?php echo $step > 3 ? 'done' : ''; ?>"></div>
                <div class="step-dot <?php echo $step == 4 ? 'active' : ($step > 4 ? 'done' : ''); ?>">
                    <?php echo $step > 4 ? '<i class="fa fa-check" style="font-size:12px"></i>' : '4'; ?>
                </div>
                <div class="step-line <?php echo $step > 4 ? 'done' : ''; ?>"></div>
                <div class="step-dot <?php echo $step == 5 ? 'active' : ''; ?>">5</div>
            </div>

            <?php if ($step == 1) { ?>
                <!-- ===== 步骤1：环境检测 ===== -->
                <div class="form-title">环境检测</div>
                <?php
                $install = true;
                $checks = [];
                
                $checks[] = ['name' => 'PHP 版本 >= 7.1', 'ok' => version_compare(PHP_VERSION, '7.1.0', '>='), 'detail' => '当前 ' . PHP_VERSION];
                
                $checks[] = ['name' => 'PDO_MYSQL 组件', 'ok' => class_exists("PDO") && extension_loaded('pdo_mysql')];
                
                $checks[] = ['name' => 'cURL 组件', 'ok' => function_exists('curl_exec')];
                
                $checks[] = ['name' => 'mbstring 组件', 'ok' => extension_loaded('mbstring')];
                
                $checks[] = ['name' => 'gd 组件', 'ok' => extension_loaded('gd')];
                
                $checks[] = ['name' => 'config.php 可写', 'ok' => is_writable($databaseFile)];
                
                foreach ($checks as $c) {
                    if (!$c['ok']) $install = false;
                }
                ?>
                <ul class="check-list">
                    <?php foreach ($checks as $c): ?>
                        <li>
                            <span><i class="fa <?php echo $c['ok'] ? 'fa-cog' : 'fa-cog'; ?>"></i><?php echo $c['name']; ?></span>
                            <?php if ($c['ok']): ?>
                                <span class="check-ok"><i class="fa fa-check-circle"></i> 正常</span>
                            <?php else: ?>
                                <span class="check-fail"><i class="fa fa-times-circle"></i> <?php echo $c['detail'] ?? '不支持'; ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($install): ?>
                    <button class="btn-primary" onclick="location.href='?step=2'">
                        <i class="fa fa-arrow-right"></i> 检测通过，下一步
                    </button>
                <?php else: ?>
                    <div class="alert-box">
                        <i class="fa fa-exclamation-triangle"></i>
                        <span>服务器环境不满足要求，请检查标红项目后重试。</span>
                    </div>
                    <button class="btn-primary" disabled>
                        <i class="fa fa-ban"></i> 无法继续安装
                    </button>
                <?php endif; ?>

            <?php } elseif ($step == 2) { ?>
                <!-- ===== 步骤2：数据库配置 ===== -->
                <div class="form-title">数据库配置</div>
                <?php if (!empty($errorMsg)): ?>
                    <div class="alert-box"><i class="fa fa-times-circle"></i><span><?php echo $errorMsg; ?></span></div>
                <?php endif; ?>
                <form action="?step=3" method="post">
                    <div class="form-group">
                        <label><i class="fa fa-server"></i>数据库地址</label>
                        <input type="text" name="host" class="form-control" value="localhost" placeholder="如：localhost 或 127.0.0.1" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fa fa-plug"></i>数据库端口</label>
                        <input type="text" name="port" class="form-control" value="3306" placeholder="默认：3306" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fa fa-user"></i>数据库用户名</label>
                        <input type="text" name="user" class="form-control" placeholder="请输入数据库用户名" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fa fa-key"></i>数据库密码</label>
                        <input type="password" name="pwd" class="form-control" placeholder="请输入数据库密码" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fa fa-database"></i>数据库名称</label>
                        <input type="text" name="database" class="form-control" value="cloud" placeholder="请输入数据库名称" required>
                    </div>
                    <button type="submit" class="btn-primary">
                        <i class="fa fa-arrow-right"></i> 下一步
                    </button>
                    <div class="link-text">
                        <a href="?step=3&jump=1"><i class="fa fa-forward"></i> 已配置好 config.php，跳过此步</a>
                    </div>
                </form>

            <?php } elseif ($step == 3) { ?>
                <!-- ===== 步骤3：保存配置 ===== -->
                <div class="form-title">保存配置</div>
                <?php if (!empty($errorMsg)): ?>
                    <div class="alert-box"><i class="fa fa-times-circle"></i><span><?php echo $errorMsg; ?></span></div>
                    <div class="link-text"><a href="javascript:history.back(-1)"><i class="fa fa-arrow-left"></i> 返回修改</a></div>
                <?php else: ?>
                    <div class="success-box"><i class="fa fa-check-circle"></i> 数据库配置文件保存成功！</div>
                    <?php
                    try {
                        $DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
                        $has_installed = $DB->query("select * from pre_config");
                    } catch (Exception $e) {
                        $has_installed = false;
                    }
                    if ($has_installed): ?>
                        <div class="alert-box" style="background:rgba(251,191,36,0.2);border-color:rgba(251,191,36,0.4);color:#fcd34d;">
                            <i class="fa fa-info-circle"></i>
                            <span>系统检测到你已安装过 ZuKizZ</span>
                        </div>
                        <button class="btn-primary" onclick="location.href='?step=4&jump=1'" style="margin-bottom:12px">
                            <i class="fa fa-forward"></i> 跳过安装数据表
                        </button>
                        <button class="btn-primary" onclick="if(confirm('全新安装将会清空所有数据，是否继续？'))location.href='?step=4'" style="background:rgba(251,191,36,0.2);border-color:rgba(251,191,36,0.4);">
                            <i class="fa fa-exclamation-triangle"></i> 强制全新安装
                        </button>
                    <?php else: ?>
                        <button class="btn-primary" onclick="location.href='?step=4'">
                            <i class="fa fa-database"></i> 立即安装数据表
                        </button>
                    <?php endif; ?>
                <?php endif; ?>

            <?php } elseif ($step == 4) { ?>
                <!-- ===== 步骤4：安装失败 ===== -->
                <div class="form-title">安装失败</div>
                <div class="alert-box"><i class="fa fa-times-circle"></i><span><?php echo $errorMsg; ?></span></div>
                <button class="btn-primary" onclick="location.href='?step=4'">
                    <i class="fa fa-refresh"></i> 重新安装
                </button>
                <div class="link-text"><a href="javascript:history.back(-1)"><i class="fa fa-arrow-left"></i> 返回上一步</a></div>

            <?php } elseif ($step == 5) { ?>
                <!-- ===== 步骤5：安装完成 ===== -->
                <div class="result-box">
                    <div class="result-icon">🎉</div>
                    <h3>安装成功！</h3>
                    <?php if ($success > 0): ?>
                        <p>成功执行 SQL 语句 <strong><?php echo $success; ?></strong> 条<?php if ($error > 0) echo '，失败 ' . $error . ' 条'; ?></p>
                    <?php endif; ?>
                    
                    <div class="info-list">
                        <li><i class="fa fa-check"></i> 数据库表创建完成</li>
                        <li><i class="fa fa-check"></i> 配置文件写入完成</li>
                        <li><i class="fa fa-check"></i> 安装锁已生成</li>
                    </div>

                    <div style="margin-top: 24px; background:rgba(251,191,36,0.15); border:1px solid rgba(251,191,36,0.3); border-radius:12px; padding:16px; text-align:left; margin-bottom:16px;">
                        <p style="color:#fcd34d; font-size:14px; margin-bottom:8px;"><i class="fa fa-exclamation-triangle"></i> <strong>重要提醒</strong></p>
                        <p style="color:rgba(255,255,255,0.8); font-size:13px; line-height:1.8;">
                            后台默认密码为 <strong style="color:#fff;">123456</strong>，请登录后立即修改！
                        </p>
                    </div>

                    <div class="info-item" style="background:rgba(255,255,255,0.1);border-radius:12px;padding:14px;margin-bottom:8px;">
                        <span style="color:rgba(255,255,255,0.7);font-size:14px;">后台地址</span>
                        <a href="../admin/" target="_blank" style="color:#fbbf24;font-weight:500;font-size:14px;text-decoration:none;">/admin/ <i class="fa fa-external-link" style="font-size:11px;"></i></a>
                    </div>

                    <?php if (!$lock_status): ?>
                        <div class="alert-box" style="background:rgba(251,191,36,0.2);border-color:rgba(251,191,36,0.4);color:#fcd34d;">
                            <i class="fa fa-exclamation-triangle"></i>
                            <span>空间不支持自动写入，请手动在 /install/ 目录创建 install.lock 空文件</span>
                        </div>
                    <?php endif; ?>

                    <button class="btn-primary" onclick="location.href='../'" style="margin-top:16px">
                        <i class="fa fa-home"></i> 进入网站首页
                    </button>
                </div>

            <?php } ?>

            <div class="footer-text">
                Powered by <a href="#" target="_blank">ZuKizZ</a>
            </div>
        </div>
    </div>
</body>
</html>
