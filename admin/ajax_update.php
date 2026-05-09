<?php
define("IN_ADMIN", true);
include("../includes/common.php");

header('Content-Type: application/json');

$action = $_GET['act'] ?? '';

if ($action === 'update') {
    $giteeRepo = 'xiaoxuanzz/zukicloud';
    $branch = 'main';
    $zipUrl = 'https://gitee.com/' . $giteeRepo . '/repository/archive/' . $branch . '.zip';
    
    $tempDir = sys_get_temp_dir() . '/zuki_update_' . time();
    $zipFile = $tempDir . '.zip';
    
    @mkdir($tempDir, 0777, true);
    
    // 下载文件
    $ch = curl_init($zipUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    
    $zipContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 || empty($zipContent)) {
        @rmdir($tempDir);
        @unlink($zipFile);
        echo json_encode(['code' => 1, 'msg' => '下载失败: HTTP ' . $httpCode]);
        exit;
    }
    
    // 检查 ZIP 头
    if (substr($zipContent, 0, 2) !== 'PK') {
        @rmdir($tempDir);
        @unlink($zipFile);
        echo json_encode(['code' => 1, 'msg' => '下载的文件不是有效的 ZIP 压缩包']);
        exit;
    }
    
    // 保存 ZIP 文件
    file_put_contents($zipFile, $zipContent);
    
    // 解压
    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) {
        @rmdir($tempDir);
        @unlink($zipFile);
        echo json_encode(['code' => 1, 'msg' => '无法打开 ZIP 文件']);
        exit;
    }
    
    $zip->extractTo($tempDir);
    $zip->close();
    
    // 查找解压后的目录
    $extractDir = null;
    $items = scandir($tempDir);
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..' && is_dir($tempDir . '/' . $item)) {
            if (strpos($item, 'zukicloud') !== false) {
                $extractDir = $tempDir . '/' . $item;
                break;
            }
        }
    }
    
    if (!$extractDir) {
        @rmdir($tempDir);
        @unlink($zipFile);
        echo json_encode(['code' => 1, 'msg' => '无法找到解压后的文件目录']);
        exit;
    }
    
    // 获取排除的文件和目录
    $excludeDirs = ['admin', 'data', 'cache', 'includes/config.php'];
    $excludePatterns = ['.git', '.env', 'node_modules', 'vendor'];
    
    // 复制文件覆盖
    $rootDir = dirname(dirname(__DIR));
    $copied = 0;
    $skipped = 0;
    
    function copyDirectory($src, $dst, &$copied, &$skipped, $excludeDirs, $excludePatterns) {
        if (!is_dir($src)) return;
        
        @mkdir($dst, 0777, true);
        
        $items = scandir($src);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $srcPath = $src . '/' . $item;
            $dstPath = $dst . '/' . $item;
            
            // 检查排除
            $skip = false;
            foreach ($excludePatterns as $pattern) {
                if (strpos($item, $pattern) !== false) {
                    $skip = true; break;
                }
            }
            if ($skip) { $skipped++; continue; }
            
            if (is_dir($srcPath)) {
                if (in_array($item, $excludeDirs)) {
                    $skipped++;
                    continue;
                }
                copyDirectory($srcPath, $dstPath, $copied, $skipped, $excludeDirs, $excludePatterns);
            } else {
                if (copy($srcPath, $dstPath)) {
                    $copied++;
                } else {
                    $skipped++;
                }
            }
        }
    }
    
    copyDirectory($extractDir, $rootDir, $copied, $skipped, $excludeDirs, $excludePatterns);
    
    // 清理临时文件
    @unlink($zipFile);
    delTree($tempDir);
    
    echo json_encode(['code' => 0, 'msg' => '更新成功！共复制 ' . $copied . ' 个文件，跳过 ' . $skipped . ' 个文件']);
    exit;
}

function delTree($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            delTree($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}