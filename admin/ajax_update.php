<?php
if (function_exists('opcache_reset')) @opcache_reset();
error_reporting(E_ALL);
ini_set('display_errors', 1);

define("IN_ADMIN", true);
$nosecu = true;
@include_once("../includes/common.php");

$action = isset($_GET['act']) ? $_GET['act'] : '';
$source = isset($_REQUEST['source']) ? trim($_REQUEST['source']) : 'auto';
if (!in_array($source, ['auto', 'uhub', 'github'])) $source = 'auto';

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
        if ($data && (time() - $data['time']) < 300) return $data;
    }
    return null;
}

/**
 * 通用远程 GET 请求（cURL 实现，不受 allow_url_fopen 限制）
 */
function http_get($url, $timeout = 10) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_ENCODING       => '',
        CURLOPT_USERAGENT      => 'ZukiCloud-UpdateHub/1.0',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_errno($ch);
    curl_close($ch);
    if ($body === false || $err !== 0 || $code !== 200) return null;
    return $body;
}

function github_api($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_ENCODING       => '',
        CURLOPT_HTTPHEADER     => [
            'Accept: application/vnd.github.v3+json',
            'User-Agent: ZukiCloud-UpdateHub'
        ]
    ]);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $httpCode !== 200) return null;
    $data = @json_decode($body, true);
    return is_array($data) ? $data : null;
}

/**
 * 并行请求多个 GitHub API，返回第一个成功的结果
 */
function github_api_race(array $urls, $timeout = 8) {
    $mh = curl_multi_init();
    $handles = [];
    $urlMap = [];

    foreach ($urls as $i => $url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/vnd.github.v3+json',
                'User-Agent: ZukiCloud-UpdateHub'
            ]
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$i] = $ch;
        $urlMap[$i]  = $url;
    }

    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running > 0) {
            curl_multi_select($mh, 0.5);
        }
    } while ($running > 0 && $status === CURLM_OK);

    $result = null;
    foreach ($handles as $i => $ch) {
        $body = curl_multi_getcontent($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);

        if ($body !== false && $httpCode === 200) {
            $data = @json_decode($body, true);
            if (is_array($data)) {
                $result = [$urlMap[$i], $data];
                break;
            }
        }
    }
    curl_multi_close($mh);
    return $result;
}

$uhubApi = 'https://update.xiaoxuanzz.cloud/index.php?route=api/zukicloud';
if (isset($_REQUEST['uhub_url'])) {
    $input = rtrim(trim($_REQUEST['uhub_url']), '/');
    if (filter_var($input, FILTER_VALIDATE_URL) && (str_starts_with($input, 'http://') || str_starts_with($input, 'https://'))) {
        $uhubApi = $input;
    }
}

$ghRepo = 'xiaoxuanzz/zukicloud';
if (isset($_REQUEST['gh_repo'])) {
    $input = trim($_REQUEST['gh_repo']);
    if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]*\/[a-zA-Z0-9._-]{1,100}$/', $input)) {
        $ghRepo = $input;
    }
}

update_log("=== 操作: $action, 源: $source ===");

/* ========== Changelog ========== */
if ($action === 'changelog') {
    $limit = max(1, intval($_GET['limit'] ?? 20));
    $page  = max(1, intval($_GET['page'] ?? 1));
    $result = null;
    $usedSource = '';

    if (($source === 'auto' || $source === 'uhub') && $uhubApi) {
        $url = $uhubApi . '/changelog&limit=' . $limit . '&page=' . $page;
        update_log("私有节点日志: $url");
        $body = http_get($url, 10);
        if ($body !== false) {
            $data = @json_decode($body, true);
            if ($data && isset($data['logs'])) {
                $result = ['code' => 0, 'data' => [
                    'logs'   => $data['logs'],
                    'count'  => $data['count'] ?? count($data['logs']),
                    'page'   => $data['page'] ?? $page,
                    'limit'  => $data['limit'] ?? $limit,
                    'pages'  => $data['pages'] ?? $page
                ]];
                $usedSource = '私人推送节点';
                update_log("私有日志成功: " . count($data['logs']) . " 条");
            }
        }
        if (!$result) update_log("私有日志失败，降级 GitHub");
    }

    // GitHub 节点不获取更新日志
    if (!$result && ($source === 'auto' || $source === 'github')) {
        $result = ['code' => 0, 'data' => [
            'logs'  => [],
            'count' => 0,
            'page'  => $page,
            'limit' => $limit,
            'pages' => $page
        ]];
        $usedSource = 'GitHub (跳过日志)';
        update_log("GitHub 节点跳过日志获取");
    }

    if ($result) {
        if ($usedSource) $result['source'] = $usedSource;
        echo json_encode($result);
    } else {
        update_log("日志获取全部失败");
        echo json_encode(['code' => 1, 'msg' => '日志获取失败，所有节点均无法连接']);
    }
    exit;
}

/* ========== 检查更新 ========== */
if ($action === 'check') {
    $result = null;
    $usedSource = '';

    if (($source === 'auto' || $source === 'uhub') && $uhubApi) {
        $url = $uhubApi . '/check&_=' . time();
        update_log("私有检查: $url");
        $body = http_get($url, 10);
        $data = $body ? @json_decode($body, true) : null;
        if ($data && isset($data['latest'])) {
            $result = ['code' => 0, 'data' => [
                'version'  => $data['latest']['version'],
                'changelog'=> $data['latest']['changelog'] ?? '',
                'created_at'=> $data['latest']['created_at'] ?? '',
                'logs' => $data['latest']['changelog'] ? [['version' => $data['latest']['version'], 'changelog' => $data['latest']['changelog'], 'created_at' => $data['latest']['created_at'] ?? '', 'is_live' => 1]] : []
            ], 'source' => '私人推送节点'];
            $usedSource = '私人推送节点';
            update_log("私有检查成功: v" . $data['latest']['version']);
        } else { update_log("私有检查失败，降级 GitHub"); }
    }

    if (!$result && ($source === 'auto' || $source === 'github')) {
        $ghUrls = [
            'https://api.github.com/repos/' . $ghRepo . '/releases/latest',
            'https://api.github.com/repos/' . $ghRepo . '/commits?sha=main&per_page=1'
        ];
        $ghResult = github_api_race($ghUrls, 8);
        if ($ghResult) {
            list($ghUrl, $data) = $ghResult;
            if (isset($data['tag_name'])) {
                $tag = $data['tag_name'];
                $ver = preg_replace('/^v/i', '', $tag);
                $result = ['code' => 0, 'data' => [
                    'version'  => $ver, 'tag' => $tag,
                    'changelog'=> $data['body'] ?? $data['name'] ?? '',
                    'created_at'=> $data['published_at'] ?? '',
                    'logs' => [['version' => $ver, 'changelog' => $data['body'] ?? $data['name'] ?? '', 'created_at' => $data['published_at'] ?? '', 'is_live' => true]]
                ], 'source' => 'GitHub (releases)'];
                $usedSource = 'GitHub (releases)';
                update_log("GitHub release检查: v$ver");
            } elseif (isset($data[0]['sha'])) {
                $c = $data[0];
                $msg = $c['commit']['message'] ?? '';
                $result = ['code' => 0, 'data' => [
                    'version'  => substr($c['sha'], 0, 7), 'sha' => $c['sha'],
                    'changelog'=> $msg,
                    'created_at'=> $c['commit']['author']['date'] ?? '',
                    'logs' => [['version' => substr($c['sha'], 0, 7), 'changelog' => $msg, 'created_at' => $c['commit']['author']['date'] ?? '', 'is_live' => false]]
                ], 'source' => 'GitHub (commits)'];
                $usedSource = 'GitHub (commits)';
                update_log("GitHub commit检查: " . substr($c['sha'], 0, 7));
            }
        } else {
            update_log("GitHub 所有接口均超时");
        }
    }

    if (!$result) {
        if ($source === 'uhub') {
            echo json_encode(['code' => 1, 'msg' => '节点正在维护，暂未开放下载，可以尝试更换其他节点']);
        } else {
            echo json_encode(['code' => 1, 'msg' => '检查更新失败，所有节点均无法连接']);
        }
    } else {
        echo json_encode($result);
    }
    exit;
}

/* ========== 更新下载 ========== */
if ($action === 'update') {
    save_progress('start', '开始更新...', 5);
    update_log("检查PHP环境...");

    if (!class_exists('ZipArchive')) { echo json_encode(['code' => 1, 'msg' => '请启用php_zip扩展']); exit; }
    if (!function_exists('curl_init')) { echo json_encode(['code' => 1, 'msg' => '请启用php_curl扩展']); exit; }

    $rootDir  = dirname(__DIR__);
    $tempDir  = $rootDir . '/upload/zuki_update_temp';
    $zipFile  = $rootDir . '/upload/zuki_update.zip';

    update_log("根目录: $rootDir, 策略: $source");

    if (!is_dir($rootDir . '/upload')) @mkdir($rootDir . '/upload', 0777, true);
    save_progress('prepare', '准备中...', 10);

    // 清理
    if (is_dir($tempDir)) { foreach (scandir($tempDir) as $i) if ($i !== '.' && $i !== '..') { $p = $tempDir . '/' . $i; is_dir($p) ? delTree($p) : @unlink($p); } @rmdir($tempDir); }
    if (file_exists($zipFile)) @unlink($zipFile);
    if (!is_dir($tempDir) && !@mkdir($tempDir, 0777, true)) { echo json_encode(['code' => 1, 'msg' => '无法创建临时目录']); exit; }

    $downloaded = false;
    $lastError  = '';

    // 下载源排序
    $sources = ($source === 'auto') ? ['uhub', 'github'] : (($source === 'uhub') ? ['uhub', 'github'] : ['github']);

    foreach ($sources as $src) {
        if ($downloaded) break;

        if ($src === 'uhub' && $uhubApi) {
            update_log("尝试私有节点下载...");
            save_progress('prepare', '获取私有下载链接...', 12);
            $infoBody = http_get($uhubApi . '/latest', 10);
            $info = $infoBody ? @json_decode($infoBody, true) : null;
            if ($info && isset($info['download_url']) && $info['download_url']) {
                $zipUrl = $info['download_url'];
                update_log("私有下载链接: $zipUrl");
                $fp = @fopen($zipFile, 'w');
                if ($fp) {
                    $ch = curl_init();
                    curl_setopt_array($ch, [CURLOPT_URL => $zipUrl, CURLOPT_FILE => $fp, CURLOPT_TIMEOUT => 300, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5, CURLOPT_ENCODING => '', CURLOPT_USERAGENT => 'Mozilla/5.0', CURLOPT_HEADER => false, CURLOPT_FAILONERROR => false, CURLOPT_NOPROGRESS => false]);
                    $lp = 15;
                    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($res, $ds, $dl) use ($zipFile, &$lp) { if ($ds > 0 && $dl > 0) { $p = round(15 + ($dl / $ds) * 40); if ($p > $lp + 1) { $lp = $p; save_progress('download', '私有节点下载中...', $p, $dl, $ds); } } return 0; });
                    curl_exec($ch);
                    $hc = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $ce = curl_error($ch); $cn = curl_errno($ch);
                    curl_close($ch); fclose($fp);
                    if ($cn !== 0) { $lastError = "cURL: $ce"; @unlink($zipFile); }
                    elseif ($hc !== 200) { $lastError = "HTTP $hc"; @unlink($zipFile); }
                    elseif (filesize($zipFile) < 100) { $lastError = '文件过小'; @unlink($zipFile); }
                    else { $downloaded = true; update_log("私有节点下载成功: " . filesize($zipFile) . " bytes"); save_progress('download', '下载完成', 55); }
                }
            } else {
                $lastError = '私有节点未返回下载地址';
                update_log("私有节点失败: 无 download_url，原始响应: " . ($infoBody ? substr($infoBody, 0, 200) : '空'));
            }
        }

        if (!$downloaded && $src === 'github') {
            update_log("GitHub 下载...");
            $release = github_api('https://api.github.com/repos/' . $ghRepo . '/releases/latest');
            $zipUrl = null;
            if ($release && isset($release['zipball_url'])) {
                $zipUrl = $release['zipball_url'];
                update_log("使用 release zip: $zipUrl");
            }

            $branches = ['main', 'master'];
            foreach ($branches as $br) {
                if ($downloaded) break;
                if (!$zipUrl) {
                    $zipUrl = 'https://github.com/' . $ghRepo . '/archive/refs/heads/' . $br . '.zip';
                    update_log("使用 archive zip: $zipUrl");
                }
                save_progress('download', '下载中...', 15, 0, 0);
                $fp = @fopen($zipFile, 'w');
                if (!$fp) { $lastError = '无法创建临时文件'; continue; }
                $ch = curl_init();
                curl_setopt_array($ch, [CURLOPT_URL => $zipUrl, CURLOPT_FILE => $fp, CURLOPT_TIMEOUT => 300, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5, CURLOPT_ENCODING => '', CURLOPT_USERAGENT => 'Mozilla/5.0', CURLOPT_HTTPHEADER => ['Accept: application/zip'], CURLOPT_HEADER => false, CURLOPT_FAILONERROR => false, CURLOPT_NOPROGRESS => false]);
                $lp = 15;
                curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($res, $ds, $dl) use ($zipFile, &$lp) { if ($ds > 0 && $dl > 0) { $p = round(15 + ($dl / $ds) * 40); if ($p > $lp + 1) { $lp = $p; save_progress('download', '下载中...', $p, $dl, $ds); } } return 0; });
                curl_exec($ch);
                $hc = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $ce = curl_error($ch); $cn = curl_errno($ch);
                curl_close($ch); fclose($fp);
                if ($cn !== 0) { $lastError = "cURL: $ce"; @unlink($zipFile); continue; }
                if ($hc !== 200) { $lastError = "HTTP $hc"; @unlink($zipFile); continue; }
                if (!file_exists($zipFile)) { $lastError = '文件不存在'; continue; }
                $fs = filesize($zipFile);
                if ($fs < 100) { $lastError = '文件过小'; @unlink($zipFile); continue; }
                $hdr = file_get_contents($zipFile, false, null, 0, 2);
                if ($hdr !== 'PK') { $lastError = '非ZIP文件'; @unlink($zipFile); continue; }
                $downloaded = true;
                update_log("GitHub 下载成功: $fs bytes from $zipUrl");
                save_progress('download', '下载完成', 55);
                break;
            }
        }
    }

    if (!$downloaded) { echo json_encode(['code' => 1, 'msg' => '下载失败: ' . $lastError]); exit; }

    // 解压
    save_progress('download', '解压中...', 50);
    update_log("解压...");
    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) { @unlink($zipFile); echo json_encode(['code' => 1, 'msg' => '无法打开ZIP']); exit; }
    $zip->extractTo($tempDir);
    $zip->close();
    save_progress('extract', '解压完成，更新文件...', 60);

    $extractDir = null;
    foreach (scandir($tempDir) as $item) { if ($item !== '.' && $item !== '..' && is_dir($tempDir . '/' . $item)) { $extractDir = $tempDir . '/' . $item; break; } }
    if (!$extractDir) { @unlink($zipFile); echo json_encode(['code' => 1, 'msg' => '找不到解压目录']); exit; }

    $excludeDirs = [];
    $excludeFiles = ['config.php', basename(__FILE__), 'opcache_clear.php'];
    $excludePatterns = [];

    function copyDir($src, $dst, &$copied, &$skipped, &$errs, $ed, $ef, $ep, $total, &$lp) {
        if (!is_dir($src)) { $errs[] = "无源目录: $src"; return; }
        if (!is_dir($dst) && !@mkdir($dst, 0777, true)) { $errs[] = "无法创建: $dst"; return; }
        @chmod($dst, 0777);
        $items = @scandir($src);
        if (!$items) { $errs[] = "无法读取: $src"; return; }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $sp = $src . '/' . $item; $dp = $dst . '/' . $item;
            foreach ($ep as $p) if (strpos($item, $p) !== false) { $skipped++; continue 2; }
            if (is_dir($sp)) {
                if (!in_array($item, $ed)) {
                    copyDir($sp, $dp, $copied, $skipped, $errs, $ed, $ef, $ep, $total, $lp);
                } else { $skipped++; }
            } else {
                if (in_array($item, $ef)) { $skipped++; continue; }
                $content = @file_get_contents($sp);
                if ($content === false) { $skipped++; $errs[] = "读取失败: $sp"; continue; }
                if (file_exists($dp)) {
                    @chmod($dp, 0777);
                    $retries = 3;
                    $unlinked = false;
                    while ($retries > 0) {
                        if (@unlink($dp)) { $unlinked = true; break; }
                        usleep(200000);
                        $retries--;
                    }
                    if (!$unlinked) { $skipped++; $errs[] = "无法删除: $dp"; continue; }
                }
                $written = @file_put_contents($dp, $content);
                if ($written === false) { $skipped++; $errs[] = "写入失败: $dp"; continue; }
                @chmod($dp, 0644);
                $copied++;
                update_log("已覆盖: $item");
                $pct = $total > 0 ? round(60 + ($copied / $total) * 35) : 90;
                if ($pct > $lp + 2) { $lp = $pct; save_progress('copy', "更新中 ($copied/$total)...", $pct, $copied, $total); }
            }
        }
    }

    $copied = $skipped = 0; $errors = [];
    $lp = 60;
    $totalFiles = 0;
    update_log("复制文件...");
    copyDir($extractDir, $rootDir, $copied, $skipped, $errors, $excludeDirs, $excludeFiles, $excludePatterns, $totalFiles, $lp);

    @unlink($zipFile); delTree($tempDir); @unlink($progressFile);
    save_progress('done', '更新完成！', 100, $copied, $totalFiles);
    update_log("完成: 成功=$copied, 跳过=$skipped");
    $msg = '更新成功！共复制 ' . $copied . ' 个文件' . ($skipped > 0 ? "，跳过 $skipped 个" : '');
    update_log($msg);
    echo json_encode(['code' => 0, 'msg' => $msg]);
    exit;
}

/* ========== 进度 ========== */
if ($action === 'progress') {
    $p = get_progress();
    echo json_encode($p ? ['code' => 0, 'data' => $p] : ['code' => 1, 'msg' => '无进度信息']);
    exit;
}

/* ========== 清理 ========== */
if ($action === 'clean') {
    @unlink($progressFile);
    $rd = dirname(__DIR__); $td = $rd . '/upload/zuki_update_temp'; $zf = $rd . '/upload/zuki_update.zip';
    if (is_dir($td)) delTree($td); if (file_exists($zf)) @unlink($zf);
    echo json_encode(['code' => 0, 'msg' => '清理完成']);
    exit;
}

update_log("未知操作: $action");
echo json_encode(['code' => 1, 'msg' => '未知操作']);