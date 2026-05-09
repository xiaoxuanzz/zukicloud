<?php
define("IN_ADMIN", true);
include("../includes/common.php");

header('Content-Type: application/json');

$action = $_GET['act'] ?? '';

if ($action === 'update') {
    $giteeRepo = 'xiaoxuanzz/zukicloud';
    $githubRepo = 'xiaoxuanzz/zukicloud';
    $branch = 'main';
    $zipUrl = '';
    $source = '';
    
    // 使用网站根目录作为临时目录
    $rootDir = dirname(dirname(__DIR__));
    $tempDir = $rootDir . '/zuki_update_temp';
    $zipFile = $rootDir . '/zuki_update.zip';
    
    // 清理旧文件
    if (is_dir($tempDir)) delTree($tempDir);
    if (file_exists($zipFile)) @unlink($zipFile);
    
    @mkdir($tempDir, 0777, true);
    
    // 尝试 Gitee
    $apiUrl = 'https://gitee.com/api/v5/repos/' . $giteeRepo . '/zipball?sha=' . $branch;
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $giteeCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    
    // 如果 Gitee 失败（401/404），尝试 GitHub
    if ($giteeCode === 401 || $giteeCode === 404 || $giteeCode !== 302) {
        $source = 'GitHub';
        // 尝试 main 分支
        $apiUrl = 'https://api.github.com/repos/' . $githubRepo . '/zipball/' . $branch;
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.github.v3+json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        
        // 如果 main 404，尝试 master
        if ($httpCode === 404) {
            $branch = 'master';
            $apiUrl = 'https://api.github.com/repos/' . $githubRepo . '/zipball/' . $branch;
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.github.v3+json']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);
        }
    } else {
        $source = 'Gitee';
        $httpCode = $giteeCode;
    }
    
    // 检查是否成功
    if ($httpCode === 302 || $httpCode === 301) {
        $headers = substr($response, 0, $headerSize);
        if (preg_match('/Location:\s*(.+)/i', $headers, $matches)) {
            $zipUrl = trim($matches[1]);
        } else {
            echo json_encode(['code' => 1, 'msg' => '无法获取下载链接']);
            exit;
        }
    } elseif ($httpCode === 200) {
        $body = substr($response, $headerSize);
        if (substr($body, 0, 2) === 'PK') {
            file_put_contents($zipFile, $body);
            proceedToExtract($zipFile, $tempDir, $source);
            exit;
        }
    } else {
        echo json_encode(['code' => 1, 'msg' => '获取下载链接失败: HTTP ' . $httpCode . ' (尝试: ' . $source . ', 分支: ' . $branch . ')']);
        exit;
    }
    
    // 下载 ZIP 文件
    $ch = curl_init($zipUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    
    $zipContent = curl_exec($ch);
    $downloadCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($downloadCode !== 200 || empty($zipContent)) {
        @rmdir($tempDir);
        @unlink($zipFile);
        echo json_encode(['code' => 1, 'msg' => '下载失败: HTTP ' . $downloadCode]);
        exit;
    }
    
    if (substr($zipContent, 0, 2) !== 'PK') {
        @rmdir($tempDir);
        @unlink($zipFile);
        echo json_encode(['code' => 1, 'msg' => '下载的不是有效的 ZIP 文件']);
        exit;
    }
    
    file_put_contents($zipFile, $zipContent);
    proceedToExtract($zipFile, $tempDir, $source);
    exit;
}

function proceedToExtract($zipFile, $tempDir, $source) {
    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) {
        @rmdir($tempDir);
        @unlink($zipFile);
        echo json_encode(['code' => 1, 'msg' => '无法打开 ZIP 文件']);
        exit;
    }
    
    $zip->extractTo($tempDir);
    $zip->close();
    
    $extractDir = null;
    $items = scandir($tempDir);
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..' && is_dir($tempDir . '/' . $item)) {
            $extractDir = $tempDir . '/' . $item;
            break;
        }
    }
    
    if (!$extractDir) {
        @rmdir($tempDir);
        @unlink($zipFile);
        echo json_encode(['code' => 1, 'msg' => '无法找到解压后的文件目录']);
        exit;
    }
    
    $excludeDirs = ['admin', 'data', 'cache'];
    $excludeFiles = ['config.php', '.env'];
    $excludePatterns = ['.git', 'node_modules', 'vendor'];
    
    $rootDir = dirname(dirname(__DIR__));
    $copied = 0;
    $skipped = 0;
    
    function copyDirectory($src, $dst, &$copied, &$skipped, $excludeDirs, $excludeFiles, $excludePatterns) {
        if (!is_dir($src)) return;
        @mkdir($dst, 0777, true);
        
        $items = scandir($src);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $srcPath = $src . '/' . $item;
            $dstPath = $dst . '/' . $item;
            
            foreach ($excludePatterns as $pattern) {
                if (strpos($item, $pattern) !== false) {
                    $skipped++;
                    continue 2;
                }
            }
            
            if (is_dir($srcPath)) {
                if (in_array($item, $excludeDirs)) {
                    $skipped++;
                    continue;
                }
                copyDirectory($srcPath, $dstPath, $copied, $skipped, $excludeDirs, $excludeFiles, $excludePatterns);
            } else {
                if (in_array($item, $excludeFiles)) {
                    $skipped++;
                    continue;
                }
                if (copy($srcPath, $dstPath)) {
                    $copied++;
                } else {
                    $skipped++;
                }
            }
        }
    }
    
    copyDirectory($extractDir, $rootDir, $copied, $skipped, $excludeDirs, $excludeFiles, $excludePatterns);
    
    @unlink($zipFile);
    delTree($tempDir);
    
    echo json_encode(['code' => 0, 'msg' => '更新成功！（来源: ' . $source . '）共复制 ' . $copied . ' 个文件，跳过 ' . $skipped . ' 个文件']);
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