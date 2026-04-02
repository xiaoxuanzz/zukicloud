<?php
/**
 * 安装处理器 - AJAX接口
 */
session_start();
error_reporting(0);
date_default_timezone_set("PRC");

header('Content-Type: application/json; charset=utf-8');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$result = ['success' => false, 'message' => '未知操作'];

switch ($action) {
    case 'check_env':
        $result = checkEnvironment();
        break;
    case 'test_db':
        $result = testDatabase();
        break;
    case 'save_settings':
        $result = saveSettings();
        break;
    case 'save_admin':
        $result = saveAdmin();
        break;
    case 'install':
        $result = runInstall();
        break;
}

echo json_encode($result);
exit;

// ===== 环境检测 =====
function checkEnvironment() {
    $checks = [];
    $ok = true;

    // PHP版本
    $phpOk = version_compare(PHP_VERSION, '7.4.0', '>=');
    if (!$phpOk) $ok = false;
    $checks[] = ['name' => 'PHP 版本 >= 7.4', 'ok' => $phpOk, 'detail' => '当前 ' . PHP_VERSION];

    // 必需扩展
    $exts = ['pdo','pdo_mysql','mbstring','openssl','tokenizer','xml','ctype','json','bcmath','fileinfo','curl','gd'];
    foreach ($exts as $ext) {
        $loaded = extension_loaded($ext);
        if (!$loaded) $ok = false;
        $checks[] = ['name' => '扩展 ' . $ext, 'ok' => $loaded, 'detail' => $loaded ? '' : '未安装'];
    }

    // 目录权限
    $base = dirname(__DIR__);
    $dirs = [
        'config.php' => is_writable($base . '/config.php') || !file_exists($base . '/config.php'),
    ];
    foreach ($dirs as $path => $writable) {
        if (!$writable) $ok = false;
        $checks[] = ['name' => '可写 ' . basename($path), 'ok' => $writable, 'detail' => $writable ? '' : '不可写'];
    }

    return ['success' => true, 'passed' => $ok, 'checks' => $checks];
}

// ===== 数据库连接测试 =====
function testDatabase() {
    $host = isset($_POST['host']) ? trim($_POST['host']) : '';
    $port = isset($_POST['port']) ? intval($_POST['port']) : 3306;
    $user = isset($_POST['user']) ? trim($_POST['user']) : '';
    $pwd = isset($_POST['pwd']) ? $_POST['pwd'] : '';
    $dbname = isset($_POST['dbname']) ? trim($_POST['dbname']) : '';

    if (!$host || !$user || !$dbname) {
        return ['success' => false, 'message' => '请填写完整数据库信息'];
    }

    try {
        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pwd, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // 检查MySQL版本
        $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
        if (version_compare($ver, '5.5.3', '<')) {
            return ['success' => false, 'message' => 'MySQL版本过低，需要5.5.3+，当前 ' . $ver];
        }

        // 尝试创建数据库
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4");
        $pdo->exec("USE `{$dbname}`");

        // 检查是否有已存在的表
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $hasTables = count($tables) > 0;

        // 保存到session供后续使用
        $_SESSION['install_db'] = compact('host','port','user','pwd','dbname');

        return ['success' => true, 'message' => "连接成功！MySQL {$ver}" . ($hasTables ? "（已存在 {$count($tables)} 张表）" : "（数据库为空）"), 'has_tables' => $hasTables];
    } catch (PDOException $e) {
        $code = $e->getCode();
        if ($code == 1045) $msg = '用户名或密码错误';
        elseif ($code == 1049) $msg = '数据库不存在（已自动创建，请继续）';
        elseif ($code == 2002) $msg = '数据库连接失败，请检查地址和端口';
        else $msg = $e->getMessage();
        return ['success' => false, 'message' => $msg];
    }
}

// ===== 保存系统设置 =====
function saveSettings() {
    $siteName = isset($_POST['site_name']) ? trim($_POST['site_name']) : 'ZuKizZ';
    $siteDesc = isset($_POST['site_desc']) ? trim($_POST['site_desc']) : '';
    $storage = isset($_POST['storage']) ? $_POST['storage'] : 'local';
    $regOpen = isset($_POST['reg_open']) ? intval($_POST['reg_open']) : 1;
    $uploadSize = isset($_POST['upload_size']) ? intval($_POST['upload_size']) : 100;

    $_SESSION['install_settings'] = [
        'site_name' => $siteName,
        'site_desc' => $siteDesc,
        'storage' => $storage,
        'reg_open' => $regOpen,
        'upload_size' => $uploadSize,
    ];

    return ['success' => true, 'message' => '设置已保存'];
}

// ===== 保存管理员信息 =====
function saveAdmin() {
    $adminUser = isset($_POST['admin_user']) ? trim($_POST['admin_user']) : '';
    $adminEmail = isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '';
    $adminPwd = isset($_POST['admin_pwd']) ? $_POST['admin_pwd'] : '';
    $adminPwd2 = isset($_POST['admin_pwd2']) ? $_POST['admin_pwd2'] : '';

    if (!$adminUser || !$adminPwd) {
        return ['success' => false, 'message' => '请填写管理员用户名和密码'];
    }
    if (strlen($adminPwd) < 6) {
        return ['success' => false, 'message' => '密码至少6位'];
    }
    if ($adminPwd !== $adminPwd2) {
        return ['success' => false, 'message' => '两次密码不一致'];
    }

    $_SESSION['install_admin'] = [
        'user' => $adminUser,
        'email' => $adminEmail,
        'pwd' => md5($adminPwd),
    ];

    return ['success' => true, 'message' => '管理员信息已保存'];
}

// ===== 执行安装 =====
function runInstall() {
    // 获取数据库配置
    if (!isset($_SESSION['install_db'])) {
        return ['success' => false, 'message' => '缺少数据库配置，请返回重新填写'];
    }
    $db = $_SESSION['install_db'];

    // 获取系统设置
    $settings = $_SESSION['install_settings'] ?? [
        'site_name' => 'ZuKizZ',
        'site_desc' => '',
        'storage' => 'local',
        'reg_open' => 1,
        'upload_size' => 100,
    ];

    // 获取管理员
    if (!isset($_SESSION['install_admin'])) {
        return ['success' => false, 'message' => '缺少管理员信息，请返回重新填写'];
    }
    $admin = $_SESSION['install_admin'];

    try {
        $pdo = new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset=utf8mb4", $db['user'], $db['pwd'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // 读取SQL文件并逐条执行
        $sqlFile = __DIR__ . '/install.sql';
        if (!file_exists($sqlFile)) {
            return ['success' => false, 'message' => 'install.sql 文件缺失'];
        }
        $sql = file_get_contents($sqlFile);
        
        // 用正则拆分SQL语句（忽略括号内的分号）
        $statements = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $strChar = '';
        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            if (!$inString && ($ch === "'" || $ch === '"' || $ch === '`')) {
                $inString = true;
                $strChar = $ch;
                $current .= $ch;
            } elseif ($inString && $ch === $strChar && $sql[$i-1] !== '\\') {
                $inString = false;
                $current .= $ch;
            } elseif (!$inString && $ch === '(') {
                $depth++;
                $current .= $ch;
            } elseif (!$inString && $ch === ')') {
                $depth--;
                $current .= $ch;
            } elseif (!$inString && $ch === ';' && $depth === 0) {
                $stmt = trim($current);
                if (!empty($stmt)) {
                    $statements[] = $stmt;
                }
                $current = '';
            } else {
                $current .= $ch;
            }
        }
        $last = trim($current);
        if (!empty($last)) {
            $statements[] = $last;
        }
        
        foreach ($statements as $stmt) {
            $pdo->exec($stmt);
        }

        // 插入管理员账号
        $adminPwd = $admin['pwd']; // 已md5
        $adminUser = $admin['user'];
        $adminEmail = $admin['email'];

        // 更新默认管理员
        $pdo->exec("UPDATE pre_config SET v='{$adminUser}' WHERE k='admin_user'");
        $pdo->exec("UPDATE pre_config SET v='{$adminPwd}' WHERE k='admin_pwd'");

        // 插入用户
        $pdo->exec("INSERT INTO pre_user (nickname, password, type, regip, addtime, enable) VALUES ('{$adminUser}', '{$adminPwd}', 'local', '127.0.0.1', NOW(), 1)");

        // 插入本地存储记录
        $uploadSizeBytes = intval($settings['upload_size']) * 1024 * 1024;
        $pdo->exec("INSERT INTO pre_storage (name, type, maxsize, usedsize, state, orders) VALUES ('本地存储', 'local', {$uploadSizeBytes}, 0, 1, 0)");

        // 更新系统配置
        $pdo->exec("UPDATE pre_config SET v='1' WHERE k='isupload'");
        $pdo->exec("UPDATE pre_config SET v='{$settings['reg_open']}' WHERE k='userlogin'");
        $pdo->exec("UPDATE pre_config SET v='{$settings['site_name']}' WHERE k='title'");
        $pdo->exec("UPDATE pre_config SET v='{$settings['site_desc']}' WHERE k='description'");
        $pdo->exec("UPDATE pre_config SET v='build_" . date("Ymd") . "' WHERE k='build'");

        // 写入syskey和build（如果不存在则插入，存在则更新）
        $syskey = bin2hex(random_bytes(16));
        $pdo->exec("INSERT INTO pre_config (k, v) VALUES ('syskey', '{$syskey}') ON DUPLICATE KEY UPDATE v='{$syskey}'");
        $build = 'build_' . date("Ymd");
        $pdo->exec("INSERT INTO pre_config (k, v) VALUES ('build', '{$build}') ON DUPLICATE KEY UPDATE v='{$build}'");

        // 生成config.php
        $configContent = "<?php\n";
        $configContent .= "\$dbconfig=array(\n";
        $configContent .= "    'host' => '{$db['host']}',\n";
        $configContent .= "    'port' => {$db['port']},\n";
        $configContent .= "    'user' => '{$db['user']}',\n";
        $configContent .= "    'pwd' => '{$db['pwd']}'\n";
        $configContent .= ");\n";

        $configFile = dirname(__DIR__) . '/config.php';
        if (file_put_contents($configFile, $configContent) === false) {
            return ['success' => false, 'message' => '配置文件写入失败，请检查根目录权限'];
        }

        // 创建安装锁定
        file_put_contents(__DIR__ . '/install.lock', 'ZuKizZ - ' . date('Y-m-d H:i:s'));

        return ['success' => true, 'message' => '安装成功！'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => '安装失败：' . $e->getMessage()];
    } catch (Exception $e) {
        return ['success' => false, 'message' => '安装失败：' . $e->getMessage()];
    }
}
