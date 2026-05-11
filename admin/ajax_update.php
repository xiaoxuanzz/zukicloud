<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define("IN_ADMIN", true);
@include_once("../includes/common.php");

$action = isset($_GET['act']) ? $_GET['act'] : '';

header('Content-Type: application/json; charset=utf-8');

$logFile = __DIR__ . '/update_debug.log';
$progressFile = sys_get_temp_dir() . '/zuki_update_progress.json';

function update_log($msg) {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[$time] $msg\n", FILE_APPEND);
}

function delTree($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        is_dir($path) ? delTree($path) : @unlink($path);
    }
    @rmdir($dir);
}

function save_progress($step, $msg, $percent, $downloaded = 0, $total = 0) {
    global $progressFile;
    $data = ['step' => $step, 'msg' => $msg, 'percent' => $percent, 'downloaded' => $downloaded, 'total' => $total, 'time' => time()];
    @file_put_contents($progressFile, json_encode($data));
}

function get_progress() {
    global $progressFile;
    if (file_exists($progressFile)) {
        $data = @json_decode(@file_get_contents($progressFile), true);
        if ($data && (time() - $data['time']) < 300) {
            return $data;
        }
    }
    return null;
}

update_log("=== 开始更新流程, 操作: $action ===");

if ($action === 'update') {
    save_progress('start', '开始更新...', 5);
    update_log("检查PHP环境...");
    
    if (!class_exists('ZipArchive')) {
        update_log("错误: ZipArchive扩展未启用");
        echo json_encode(['code' => 1, 'msg' => '服务器不支持ZIP操作，请联系管理员启用php_zip扩展']);
        exit;
    }
    
    if (!function_exists('curl_init')) {
        update_log("错误: cURL扩展未启用");
        echo json_encode(['code' => 1, 'msg' => '服务器不支持cURL，请联系管理员启用php_curl扩展']);
        exit;
    }
    
    $githubRepo = 'xiaoxuanzz/zukicloud';
    $branch = 'main';

    $rootDir = dirname(__DIR__);
    $tempDir = $rootDir . '/upload/zuki_update_temp';
    $zipFile = $rootDir . '/upload/zuki_update.zip';
    
    update_log("根目录: $rootDir");
    update_log("临时目录: $tempDir");
    update_log("ZIP文件: $zipFile");

    if (!is_dir($rootDir . '/upload')) {
        @mkdir($rootDir . '/upload', 0777, true);
    }
    
    save_progress('prepare', '准备更新环境...', 10);
    
    if (is_dir($tempDir)) {
        update_log("清理旧临时目录...");
        foreach (scandir($tempDir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $tempDir . '/' . $item;
            is_dir($path) ? delTree($path) : @unlink($path);
        }
        @rmdir($tempDir);
    }
    if (file_exists($zipFile)) {
        @unlink($zipFile);
        update_log("删除旧ZIP文件");
    }
    if (!is_dir($tempDir)) {
        $created = @mkdir($tempDir, 0777, true);
        update_log("创建临时目录: " . ($created ? '成功' : '失败'));
        if (!$created) {
            echo json_encode(['code' => 1, 'msg' => '无法创建临时目录，请检查upload目录权限']);
            exit;
        }
    }

$downloaded = false;
            $githubBranches = ['main', 'master'];
            $lastError = '';
            $lastPercent = 15;

            foreach ($githubBranches as $br) {
        update_log("尝试下载分支: $br");
        $zipUrl = 'https://github.com/' . $githubRepo . '/archive/refs/heads/' . $br . '.zip';
        update_log("下载URL: $zipUrl");

        save_progress('download', '正在下载更新包...', 15, 0, 0);

        $fp = @fopen($zipFile, 'w');
        if (!$fp) {
            $lastError = '无法创建ZIP临时文件';
            update_log("无法创建ZIP临时文件: $zipFile");
            continue;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zipUrl);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/zip',
            'Accept-Encoding: identity'
        ]);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($resource, $downloadSize, $downloaded) use ($zipFile, &$lastPercent) {
            if ($downloadSize > 0 && $downloaded > 0) {
                $percent = round(15 + ($downloaded / $downloadSize) * 35);
                if ($percent > $lastPercent + 1) {
                    $lastPercent = $percent;
                    save_progress('download', '正在下载更新包...', $percent, $downloaded, $downloadSize);
                }
            }
            return 0;
        });

        update_log("开始cURL请求...");
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        fclose($fp);

        update_log("HTTP状态码: $httpCode");
        update_log("cURL错误: $curlError ($curlErrno)");

        if ($curlErrno !== 0) {
            $lastError = 'cURL错误: ' . $curlError;
            update_log("cURL失败: $lastError");
            @unlink($zipFile);
            continue;
        }

        if ($httpCode !== 200) {
            $lastError = 'HTTP错误: ' . $httpCode;
            update_log("HTTP失败: $lastError");
            @unlink($zipFile);
            continue;
        }

        if (!file_exists($zipFile)) {
            $lastError = '下载文件不存在';
            update_log("下载文件不存在");
            continue;
        }

        $fileSize = filesize($zipFile);
        update_log("下载文件大小: $fileSize bytes");

        if ($fileSize < 100) {
            $lastError = '下载内容过小，可能不是有效文件';
            update_log("下载内容过小: $fileSize bytes");
            @unlink($zipFile);
            continue;
        }

        $header = file_get_contents($zipFile, false, null, 0, 2);
        update_log("文件头: " . bin2hex($header));

        if ($header !== 'PK') {
            $lastError = '下载的不是有效的ZIP文件，头部: ' . bin2hex($header);
            update_log("文件头错误: $lastError");
            @unlink($zipFile);
            continue;
        }

        update_log("ZIP文件已保存，大小: $fileSize bytes");
        $downloaded = true;
        break;
    }

    if (!$downloaded) {
        update_log("下载失败: $lastError");
        echo json_encode(['code' => 1, 'msg' => '下载失败: ' . $lastError . '，请检查网络连接或稍后重试']);
        exit;
    }

    save_progress('download', '下载完成，正在解压...', 50);
    update_log("开始解压ZIP文件...");
    $zip = new ZipArchive();
    $openResult = $zip->open($zipFile);
    if ($openResult !== true) {
        @unlink($zipFile);
        update_log("无法打开ZIP文件，错误码: $openResult");
        echo json_encode(['code' => 1, 'msg' => '无法打开ZIP文件，错误码: ' . $openResult]);
        exit;
    }
    
    $extractResult = $zip->extractTo($tempDir);
    $zip->close();
    save_progress('extract', '解压完成，正在更新文件...', 60);
    update_log("解压结果: " . ($extractResult ? '成功' : '失败'));
    
    if (!$extractResult) {
        @unlink($zipFile);
        update_log("解压失败");
        echo json_encode(['code' => 1, 'msg' => '解压失败，请检查临时目录权限']);
        exit;
    }

    $extractDir = null;
    foreach (scandir($tempDir) as $item) {
        if ($item !== '.' && $item !== '..' && is_dir($tempDir . '/' . $item)) {
            $extractDir = $tempDir . '/' . $item;
            update_log("找到解压目录: $extractDir");
            break;
        }
    }

    if (!$extractDir) {
        @unlink($zipFile);
        update_log("无法找到解压后的文件目录");
        echo json_encode(['code' => 1, 'msg' => '无法找到解压后的文件目录']);
        exit;
    }

    $excludeDirs = ['admin', 'data', 'cache'];
    $excludeFiles = ['config.php', '.env'];
    $excludePatterns = ['.git', 'node_modules', 'vendor'];

    $copied = $skipped = 0;
    $errors = [];
    $totalFiles = 0;
    
    function countFiles($dir, $excludeDirs, $excludeFiles, $excludePatterns) {
        $count = 0;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            $skip = false;
            foreach ($excludePatterns as $p) {
                if (strpos($item, $p) !== false) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;
            if (is_dir($path)) {
                if (!in_array($item, $excludeDirs)) {
                    $count += countFiles($path, $excludeDirs, $excludeFiles, $excludePatterns);
                }
            } else {
                if (!in_array($item, $excludeFiles)) {
                    $count++;
                }
            }
        }
        return $count;
    }
    
    $totalFiles = countFiles($extractDir, $excludeDirs, $excludeFiles, $excludePatterns);
    update_log("待复制文件总数: $totalFiles");

    function copyDir($src, $dst, &$copied, &$skipped, &$errors, $excludeDirs, $excludeFiles, $excludePatterns, $totalFiles, &$lastPercent) {
        if (!is_dir($src)) {
            $errors[] = "源目录不存在: $src";
            return;
        }
        if (!is_dir($dst)) {
            $created = @mkdir($dst, 0777, true);
            if (!$created) {
                $errors[] = "无法创建目录: $dst";
                return;
            }
        }
        $files = scandir($src);
        foreach ($files as $item) {
            if ($item === '.' || $item === '..') continue;
            $srcPath = $src . '/' . $item;
            $dstPath = $dst . '/' . $item;
            foreach ($excludePatterns as $p) { 
                if (strpos($item, $p) !== false) { 
                    $skipped++;
                    continue 2; 
                } 
            }
            if (is_dir($srcPath)) {
                if (in_array($item, $excludeDirs)) { 
                    $skipped++;
                    continue; 
                }
                copyDir($srcPath, $dstPath, $copied, $skipped, $errors, $excludeDirs, $excludeFiles, $excludePatterns, $totalFiles, $lastPercent);
            } else {
                if (in_array($item, $excludeFiles)) { 
                    $skipped++;
                    continue; 
                }
                if (file_exists($dstPath)) {
                    @unlink($dstPath);
                }
                if (copy($srcPath, $dstPath)) {
                    $copied++;
                } else {
                    $skipped++;
                    $errors[] = "复制失败: $srcPath -> $dstPath";
                }
                $currentPercent = $totalFiles > 0 ? round(60 + ($copied / $totalFiles) * 35) : 90;
                if ($currentPercent > $lastPercent + 2) {
                    $lastPercent = $currentPercent;
                    save_progress('copy', "正在更新文件 ({$copied}/{$totalFiles})...", $currentPercent, $copied, $totalFiles);
                }
            }
        }
    }
    
    $lastPercent = 60;
    update_log("开始复制文件...");
    update_log("源目录: $extractDir");
    update_log("目标目录: $rootDir");
    update_log("排除目录: " . implode(', ', $excludeDirs));
    update_log("排除文件: " . implode(', ', $excludeFiles));
    
    copyDir($extractDir, $rootDir, $copied, $skipped, $errors, $excludeDirs, $excludeFiles, $excludePatterns, $totalFiles, $lastPercent);

    @unlink($zipFile);
    delTree($tempDir);
    @unlink($progressFile);
    
    save_progress('done', '更新完成！', 100, $copied, $totalFiles);
    update_log("复制完成: 成功=$copied, 跳过=$skipped");
    if (!empty($errors)) {
        update_log("错误列表: " . implode('; ', array_slice($errors, 0, 10)));
    }

    $msg = '更新成功！共复制 ' . $copied . ' 个文件';
    if ($skipped > 0) {
        $msg .= '，跳过 ' . $skipped . ' 个文件';
    }
    update_log($msg);
    echo json_encode(['code' => 0, 'msg' => $msg]);
    exit;
}

if ($action === 'progress') {
    $progress = get_progress();
    if ($progress) {
        echo json_encode(['code' => 0, 'data' => $progress]);
    } else {
        echo json_encode(['code' => 1, 'msg' => '无进度信息']);
    }
    exit;
}

if ($action === 'clean') {
    @unlink($progressFile);
    $rootDir = dirname(__DIR__);
    $tempDir = $rootDir . '/upload/zuki_update_temp';
    $zipFile = $rootDir . '/upload/zuki_update.zip';
    if (is_dir($tempDir)) delTree($tempDir);
    if (file_exists($zipFile)) @unlink($zipFile);
    echo json_encode(['code' => 0, 'msg' => '清理完成']);
    exit;
}

update_log("未知操作: $action");
echo json_encode(['code' => 1, 'msg' => '未知操作']);