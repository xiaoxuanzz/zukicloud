<?php
// 开启输出缓冲，防止意外输出破坏JSON
ob_start();

// 定义常量
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__) . '/');
}
if (!defined('SYSTEM_ROOT')) {
    define('SYSTEM_ROOT', ROOT . 'includes/');
}

// 直接加载数据库配置
require_once ROOT . 'config.php';

// 确保$dbconfig存在
if (!isset($dbconfig) || !is_array($dbconfig)) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => '数据库配置加载失败']);
    exit;
}

// 连接数据库获取配置
try {
    $dsn = "mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['dbname']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    $pdo = new PDO($dsn, $dbconfig['user'], $dbconfig['pwd'], $options);
    $GLOBALS['pdo'] = $pdo; // 保存供后续函数使用
    
    $stmt = $pdo->query("SELECT * FROM pre_config");
    $conf = [];
    if ($stmt) {
        foreach ($stmt as $row) {
            $conf[$row['k']] = $row['v'];
        }
    }
    $GLOBALS['conf'] = $conf; // 保存供后续函数使用
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// 定义 DRIVE_ROOT（本地存储根目录）
if (!defined('DRIVE_ROOT')) {
    $filepath = isset($conf['filepath']) ? $conf['filepath'] : 'upload';
    $base = rtrim(ROOT, '/\\') . DIRECTORY_SEPARATOR;
    $driveRoot = $base . rtrim($filepath ?: 'upload', '/\\') . DIRECTORY_SEPARATOR;
    
    // 如果路径不存在则创建
    if (!is_dir($driveRoot)) {
        $driveRoot = $base . 'upload' . DIRECTORY_SEPARATOR;
    }
    if (!is_dir($driveRoot)) {
        @mkdir($driveRoot, 0755, true);
    }
    
    define('DRIVE_ROOT', $driveRoot);
}

// 清除可能存在的意外输出
ob_end_clean();

// 调试：记录所有请求头
$debug = date('Y-m-d H:i:s') . ' ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . "\n";
$debug .= "Headers: " . json_encode(getallheaders()) . "\n";
$debug .= "SERVER: ";
foreach ($_SERVER as $k => $v) {
    if (strpos($k, 'HTTP_') === 0 || strpos($k, 'REQUEST_') === 0) {
        $debug .= "$k: $v\n";
    }
}
$debug .= "\n";
file_put_contents(__DIR__ . '/request_debug.log', $debug, FILE_APPEND);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY, X-Admin-Key, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// 手动加载所需文件（不依赖autoloader）
require_once SYSTEM_ROOT . 'lib/Auth/AuthMiddleware.php';
require_once SYSTEM_ROOT . 'lib/Auth/ApiKeyManager.php';
require_once SYSTEM_ROOT . 'lib/Utils/FileHelper.php';
require_once __DIR__ . '/Config/ConfigManager.php';

use lib\Auth\AuthMiddleware;
use api\Config\ConfigManager;
use lib\Utils\FileHelper;

$auth = new AuthMiddleware();
$configManager = new ConfigManager();

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function error_out($message, $status = 400) {
    respond(['error' => $message], $status);
}

$action = $_GET['action'] ?? null;
if (!$action) {
    error_out('Missing action parameter', 400);
}

// 状态查询（公开）
if ($action === 'status') {
    respond([
        'api_enabled' => $configManager->isApiEnabled(),
        'version' => '2.0'
    ]);
}

// 管理员操作
if ($action === 'admin_toggle' || $action === 'regenerate_key') {
    try {
        $auth->authenticateAdmin();
    } catch (Exception $e) {
        error_out('Admin auth failed: ' . $e->getMessage(), 401);
    }
    
    if ($action === 'admin_toggle') {
        $enabled = null;
        $body = file_get_contents('php://input');
        if ($body) {
            $payload = json_decode($body, true);
            if (is_array($payload) && array_key_exists('enabled', $payload)) {
                $enabled = filter_var($payload['enabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
        }
        if ($enabled === null && isset($_POST['enabled'])) {
            $enabled = filter_var($_POST['enabled'], FILTER_VALIDATE_BOOLEAN);
        }
        if ($enabled === null) {
            error_out('Missing enabled parameter', 400);
        }
        $configManager->setApiEnabled($enabled);
        respond(['success' => true, 'api_enabled' => $enabled, 'message' => 'API ' . ($enabled ? 'enabled' : 'disabled')]);
    }
    
    if ($action === 'regenerate_key') {
        $keyManager = new \lib\Auth\ApiKeyManager();
        try {
            $newKey = $keyManager->regenerateKey();
            respond(['success' => true, 'key' => $newKey]);
        } catch (Exception $e) {
            error_out('Failed: ' . $e->getMessage(), 500);
        }
    }
}

// API密钥认证
try {
    $auth->authenticateApi();
} catch (Exception $e) {
    error_out('API auth failed: ' . $e->getMessage(), 401);
}

if (!$configManager->isApiEnabled()) {
    http_response_code(503);
    respond(['error' => 'API is disabled'], 503);
}

// 路由
switch ($action) {
    case 'upload':
        handleUpload();
        break;
    case 'list':
        handleList();
        break;
    case 'file':
        handleDownload();
        break;
    default:
        error_out('Unknown action: ' . htmlspecialchars($action), 400);
}

function handleUpload() {
    global $pdo;
    $conf = $GLOBALS['conf'] ?? [];
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        error_out('No file uploaded', 400);
    }
    
    $name = $_FILES['file']['name'];
    $tmp = $_FILES['file']['tmp_name'];
    $size = intval($_FILES['file']['size']);
    $clientip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // 清理文件名
    $name = preg_replace('/[\/\\\\:*?"<>|]/', '', $name);
    if (empty($name)) {
        error_out('Invalid filename', 400);
    }
    
    // 获取扩展名和hash
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (empty($ext)) $ext = 'bin';
    $hash = md5_file($tmp);
    
    // 检查文件类型
    if (!empty($conf['type_block'])) {
        $type_block = explode('|', $conf['type_block']);
        if (in_array($ext, $type_block)) {
            error_out('File type blocked', 400);
        }
    }
    
    // 检查是否已存在
    $stmt = $pdo->prepare("SELECT * FROM pre_file WHERE hash = ? LIMIT 1");
    if ($stmt && $stmt->execute([$hash])) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            respond([
                'success' => true, 'exists' => 1, 'hash' => $hash,
                'name' => $name, 'size' => $size, 'type' => $ext,
                'id' => $row['id'], 'message' => 'File exists'
            ]);
        }
    }
    
    // 保存文件
    $targetPath = DRIVE_ROOT . $hash . '.' . $ext;
    if (!move_uploaded_file($tmp, $targetPath)) {
        error_out('Failed to save file', 500);
    }
    
    // 写入数据库
    $stmt = $pdo->prepare("INSERT INTO pre_file (name, type, size, hash, addtime, ip, hide, pwd) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
    if (!$stmt || !$stmt->execute([$name, $ext, $size, $hash, $clientip, 0, null])) {
        @unlink($targetPath);
        error_out('DB error: ' . implode(', ', $pdo->errorInfo()), 500);
    }
    
    respond(['success' => true, 'exists' => 0, 'hash' => $hash, 'name' => $name, 'id' => $pdo->lastInsertId()]);
}

function handleList() {
    global $pdo;
    $path = $_GET['path'] ?? '';
    $recursive = filter_var($_GET['recursive'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    
    $checkedPath = \lib\Utils\FileHelper::checkPathTraversal($path, DRIVE_ROOT);
    if ($checkedPath === false) error_out('Invalid path', 400);
    
    $dir = empty($path) ? DRIVE_ROOT : $checkedPath;
    if (!is_dir($dir)) error_out('Not found', 404);
    
    $items = [];
    $rel = empty($path) ? '' : ltrim($path, '/');
    
    if ($recursive) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot()) continue;
            $fp = $fileInfo->getPathname();
            $filename = basename($fp);
            $hash = pathinfo($filename, PATHINFO_FILENAME); // 去掉扩展名，得到纯hash
            $displayName = $filename;
            $fileType = $fileInfo->isDir() ? 'directory' : 'file';
            
            // 查询数据库获取原始文件名
            if ($fileType === 'file' && isset($pdo)) {
                $stmt = $pdo->prepare("SELECT name, type FROM pre_file WHERE hash = ? LIMIT 1");
                if ($stmt && $stmt->execute([$hash])) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $displayName = $row['name'];
                        if (!empty($row['type'])) {
                            $fileType = $row['type'];
                        }
                    }
                }
            }
            
            $items[] = [
                'name' => $displayName,
                'path' => ltrim(substr($fp, strlen(DRIVE_ROOT) + 1), '/'),
                'type' => $fileType,
                'size' => $fileInfo->isDir() ? 0 : filesize($fp),
                'mtime' => filemtime($fp),
                'hash' => $hash
            ];
        }
    } else {
        foreach (scandir($dir) as $filename) {
            if ($filename === '.' || $filename === '..') continue;
            $full = $dir . '/' . $filename;
            $hash = pathinfo($filename, PATHINFO_FILENAME); // 去掉扩展名，得到纯hash
            $displayName = $filename;
            $fileType = is_dir($full) ? 'directory' : 'file';
            
            // 查询数据库获取原始文件名
            if ($fileType === 'file' && isset($GLOBALS['pdo'])) {
                $pdo = $GLOBALS['pdo'];
                $stmt = $pdo->prepare("SELECT name, type FROM pre_file WHERE hash = ? LIMIT 1");
                if ($stmt && $stmt->execute([$hash])) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $displayName = $row['name'];
                        if (!empty($row['type'])) {
                            $fileType = $row['type'];
                        }
                    }
                }
            }
            
            $items[] = [
                'name' => $displayName,
                'path' => ltrim(($rel ? $rel . '/' : '') . $filename, '/'),
                'type' => $fileType,
                'size' => is_dir($full) ? 0 : filesize($full),
                'mtime' => filemtime($full),
                'hash' => $hash
            ];
        }
    }
    respond(['path' => $rel, 'contents' => $items, 'count' => count($items)]);
}

function handleDownload() {
    $path = $_GET['path'] ?? '';
    if (empty($path)) error_out('Missing path', 400);
    
    $checkedPath = \lib\Utils\FileHelper::checkPathTraversal($path, DRIVE_ROOT);
    if ($checkedPath === false) error_out('Invalid path', 400);
    
    $abs = realpath($checkedPath);
    if ($abs === false || strpos($abs, realpath(DRIVE_ROOT)) !== 0) {
        error_out('Invalid path', 403);
    }
    if (!is_file($abs)) error_out('Not a file', 400);
    
    // 查询数据库获取原始文件名
    $hash = basename($abs);
    $displayName = $hash;
    if (isset($GLOBALS['pdo'])) {
        $pdo = $GLOBALS['pdo'];
        $stmt = $pdo->prepare("SELECT name FROM pre_file WHERE hash = ? LIMIT 1");
        if ($stmt && $stmt->execute([$hash])) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $displayName = $row['name'];
            }
        }
    }
    
    header('Content-Type: ' . mime_content_type($abs));
    header('Content-Disposition: attachment; filename="' . $displayName . '"');
    header('Content-Length: ' . filesize($abs));
    readfile($abs);
    exit;
}
