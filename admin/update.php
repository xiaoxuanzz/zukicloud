<?php
define("IN_ADMIN", true);
include("../includes/common.php");
$title = "检查更新";
if($islogin != 1) exit("<script>window.location.href=\"./login.php\";</script>");

ob_start();
?>
<style>
.update-container { max-width: 400px; margin: 20px auto; font-size: 14px; }
.version-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
.version-label { color: #666; }
.version-value { font-family: monospace; font-weight: bold; }
.controls { margin: 15px 0; }
.controls button { padding: 8px 16px; border: 1px solid #ccc; background: #f5f5f5; border-radius: 4px; cursor: pointer; }
.controls button:hover { background: #eee; }
.btn-primary { background: #337ab7; color: #fff; border-color: #2e6da4; }
.btn-primary:hover { background: #286090; }
.result-box { margin-top: 15px; padding: 10px; border-radius: 4px; display: none; animation: fadeSlideIn .3s ease; }
.result-box.show { display: block; }
.result-box.warning { background: #fcf8e3; border: 1px solid #faebcc; color: #8a6d3b; }
.result-box.success { background: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; }
.result-box.error { background: #f2dede; border: 1px solid #ebccd1; color: #a94442; }

/* ── 进度模态框动画 ── */
.progress-modal {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,.45); z-index: 999998;
    justify-content: center; align-items: center;
    animation: modalFadeIn .25s ease;
}
.progress-box {
    background: #fff; border-radius: 16px; padding: 36px 40px 32px;
    width: 400px; text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,.35);
    animation: modalBounceIn .35s cubic-bezier(.34,1.56,.64,1);
    position: relative;
}
@keyframes modalFadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes modalBounceIn {
    0%   { opacity: 0; transform: scale(.85) translateY(20px); }
    60%  { transform: scale(1.02) translateY(-3px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes fadeSlideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeSlideOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(-8px); } }

.progress-box h3 { margin: 0 0 24px; font-size: 18px; color: #333; }

/* ── 进度条 ── */
.progress-bar {
    height: 26px; background: #f0f0f0; border-radius: 13px;
    overflow: hidden; margin-bottom: 12px; position: relative;
    box-shadow: inset 0 1px 3px rgba(0,0,0,.12);
}
.progress-fill {
    height: 100%; width: 0%; border-radius: 13px;
    background: linear-gradient(135deg, #337ab7 0%, #5bc0de 50%, #28a745 100%);
    background-size: 200% 100%;
    transition: width .35s cubic-bezier(.4,0,.2,1);
    position: relative;
}
/* 条纹滚动动画 */
.progress-fill::after {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: repeating-linear-gradient(
        -45deg, transparent, transparent 10px,
        rgba(255,255,255,.25) 10px, rgba(255,255,255,.25) 20px
    );
    animation: stripeMove 1s linear infinite;
}
/* 运行中才显示条纹 */
.progress-bar:not(.idle) .progress-fill::after { animation-play-state: running; }
.progress-bar.idle .progress-fill::after { animation-play-state: paused; }

@keyframes stripeMove { 0% { background-position: 0 0; } 100% { background-position: 28px 0; } }

/* 光泽效果 */
.progress-fill::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(180deg, rgba(255,255,255,.3) 0%, transparent 50%, rgba(0,0,0,.08) 100%);
    pointer-events: none;
}

/* 100% 完成时发光 */
.progress-fill.done-glow {
    box-shadow: 0 0 12px rgba(40,167,69,.5);
}

.progress-percent {
    position: absolute; left: 0; right: 0; top: 0; bottom: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; color: #333;
    text-shadow: 0 1px 2px rgba(255,255,255,.8);
    transition: color .3s;
}

.progress-info {
    font-size: 13px; color: #666; line-height: 1.9;
    background: #f9f9f9; padding: 12px 14px; border-radius: 10px;
    animation: fadeSlideIn .4s ease .1s both;
}
.progress-info .row { display: flex; justify-content: space-between; }
.progress-info .row span:first-child { color: #999; }
.progress-info .row span:last-child { font-weight: 600; color: #333; font-family: monospace; transition: color .3s; }

.progress-status {
    margin-top: 16px; font-size: 13px; color: #888; min-height: 22px;
    transition: color .3s, font-weight .2s;
    animation: fadeSlideIn .4s ease .15s both;
}
.progress-status.active { color: #337ab7; font-weight: 600; }
.progress-status.success { color: #28a745; font-weight: 700; }
.progress-status.error { color: #dc3545; font-weight: 700; }

/* 完成时的庆祝动画 */
@keyframes pulseGreen {
    0%   { box-shadow: 0 0 0 0 rgba(40,167,69,.4); }
    70%  { box-shadow: 0 0 0 12px rgba(40,167,69,0); }
    100% { box-shadow: 0 0 0 0 rgba(40,167,69,0); }
}
.progress-box.complete { animation: pulseGreen 1s ease-out; }

.controls { margin-top: 18px; display: flex; gap: 10px; justify-content: center; }
.controls button {
    padding: 9px 20px; border: 1px solid #ccc; background: #f5f5f5;
    border-radius: 6px; cursor: pointer; font-size: 13px;
    transition: background .2s, transform .1s, box-shadow .2s;
}
.controls button:hover { background: #eee; }
.controls button:active { transform: scale(.97); }
.controls button:disabled { opacity: .45; cursor: not-allowed; }
.btn-primary { background: #337ab7; color: #fff; border-color: #2e6da4; }
.btn-primary:hover { background: #286090; box-shadow: 0 2px 8px rgba(51,122,183,.3); }
</style>

<div class="update-container">
    <div class="version-row">
        <span class="version-label">当前版本:</span>
        <span class="version-value" id="localVersion">--</span>
    </div>
    <div class="version-row">
        <span class="version-label">最新版本:</span>
        <span class="version-value" id="latestVersion">--</span>
    </div>
    <div class="controls">
        <button id="btnCheck" onclick="checkUpdate()">检查更新</button>
        <label style="margin-left:15px;cursor:pointer;">
            <input type="checkbox" id="autoCheck" onchange="toggleAuto()"> 自动检查
        </label>
    </div>
    <div class="result-box" id="resultBox"></div>
</div>

<div class="progress-modal" id="progressModal">
    <div class="progress-box" id="progressBox">
        <h3 style="margin:0 0 20px;">正在更新...</h3>
        <div class="progress-bar idle" id="progressBarContainer">
            <div class="progress-fill" id="progressFill"></div>
            <div class="progress-percent" id="progressPercent">0%</div>
        </div>
        <div class="progress-info" id="progressInfo">
            <div class="row"><span>总大小</span><span id="totalSize">--</span></div>
            <div class="row"><span>已下载</span><span id="downloaded">--</span></div>
            <div class="row"><span>下载速度</span><span id="speed">--</span></div>
        </div>
        <div class="progress-status" id="progressStatus">准备中...</div>
    </div>
</div>

<script>
var STORAGE_KEY = 'zuki_update_hash';
var AUTO_KEY = 'zuki_auto_check';
var _latestInfo = null;
var _checking = false;
var _autoTimer = null;
var _lastDownloaded = 0;
var _lastTime = 0;
var BRANCH = 'main';
var _loadingDots = '';
var _loadingTimer = null;

function init() {
    var autoOn = localStorage.getItem(AUTO_KEY) === '1';
    document.getElementById('autoCheck').checked = autoOn;
    loadLocalVersion();
    fetchLatestVersion();
}

function loadLocalVersion() {
    var hash = localStorage.getItem(STORAGE_KEY) || '';
    document.getElementById('localVersion').textContent = hash ? hash.substring(0, 7) : '无记录';
}

function startLoadingDots(el) {
    _loadingDots = '';
    _loadingTimer = setInterval(function() {
        _loadingDots = _loadingDots.length >= 5 ? '' : _loadingDots + '.';
        if (el) el.textContent = '获取中' + _loadingDots;
    }, 400);
}

function stopLoadingDots() {
    if (_loadingTimer) { clearInterval(_loadingTimer); _loadingTimer = null; }
}

function toggleAuto() {
    var cb = document.getElementById('autoCheck');
    var isOn = cb.checked;
    localStorage.setItem(AUTO_KEY, isOn ? '1' : '0');
    if (isOn) startAutoCheck(); else stopAutoCheck();
}

function startAutoCheck() {
    stopAutoCheck();
    _autoTimer = setInterval(function() { checkUpdate(); }, 3600000);
}

function stopAutoCheck() {
    if (_autoTimer) { clearInterval(_autoTimer); _autoTimer = null; }
}

function fetchLatestVersion() {
    var el = document.getElementById('latestVersion');
    el.textContent = '获取中...';
    startLoadingDots(el);
    
    var url = 'https://api.github.com/repos/xiaoxuanzz/zukicloud/commits?sha=' + BRANCH + '&per_page=1';
    var timer = setTimeout(function() { 
        stopLoadingDots();
        document.getElementById('latestVersion').textContent = '获取失败'; 
    }, 10000);
    
    fetch(url, { headers: { 'Accept': 'application/vnd.github.v3+json' } })
        .then(function(r) { clearTimeout(timer); if (!r.ok) throw new Error(); return r.json(); })
        .then(function(data) {
            stopLoadingDots();
            if (Array.isArray(data) && data.length > 0) {
                var c = data[0];
                _latestInfo = { hash: c.sha, short: c.sha.substring(0, 7), message: c.commit.message.split('\n')[0] };
                document.getElementById('latestVersion').textContent = _latestInfo.short;
            } else {
                document.getElementById('latestVersion').textContent = '获取失败'; 
            }
        })
        .catch(function(e) { 
            stopLoadingDots();
            document.getElementById('latestVersion').textContent = '获取失败'; 
        });
}

function checkUpdate() {
    if (_checking) return;
    _checking = true;
    var btn = document.getElementById('btnCheck');
    btn.disabled = true;
    btn.textContent = '检查中...';
    
    var url = 'https://api.github.com/repos/xiaoxuanzz/zukicloud/commits?sha=' + BRANCH + '&per_page=1';
    var timer = setTimeout(function() { 
        _checking = false;
        btn.disabled = false;
        btn.textContent = '检查更新';
        showResult('error', '<strong>连接失败</strong> - 请检查网络');
    }, 15000);
    
    fetch(url, { headers: { 'Accept': 'application/vnd.github.v3+json' } })
        .then(function(r) { clearTimeout(timer); if (!r.ok) throw new Error(); return r.json(); })
        .then(function(data) {
            _checking = false;
            btn.disabled = false;
            btn.textContent = '检查更新';
            if (Array.isArray(data) && data.length > 0) {
                var c = data[0];
                var localHash = localStorage.getItem(STORAGE_KEY) || '';
                var localShort = localHash ? localHash.substring(0, 7) : '无记录';
                var short = c.sha.substring(0, 7);
                if (localHash && localHash === c.sha) {
                    showResult('success', '<strong>已是最新版本</strong> (' + short + ')');
                } else {
                    var html = localHash ? '<strong>发现新版本!</strong><br>本地:' + localShort + ' → 最新:' + short : '<strong>首次记录版本</strong><br>最新:' + short;
                    html += '<br><br>';
                    html += '<button class="btn btn-primary" onclick="showUpdateConfirm()">立即更新</button> ';
                    html += '<button onclick="saveVersion(\'' + c.sha + '\')">仅记录版本</button>';
                    if (localHash) html += ' <button onclick="clearVersion()">清除记录</button>';
                    showResult('warning', html);
                }
                _latestInfo = { hash: c.sha, short: short, message: c.commit.message.split('\n')[0] };
                document.getElementById('latestVersion').textContent = short;
            } else {
                showResult('error', '<strong>获取版本信息失败</strong>');
            }
        })
        .catch(function(e) { 
            _checking = false;
            btn.disabled = false;
            btn.textContent = '检查更新';
            showResult('error', '<strong>连接失败</strong> - 请检查网络');
        });
}

function showResult(type, html) {
    var resultBox = document.getElementById('resultBox');
    resultBox.className = 'result-box show ' + type;
    resultBox.innerHTML = html;
}

function showUpdateConfirm() {
    if (!confirm('更新可能覆盖修改过的文件，建议先备份数据库。确定要更新吗？')) return;
    doUpdate();
}

function doUpdate() {
    var modal = document.getElementById('progressModal');
    modal.style.display = 'block';
    modal.style.position = 'fixed';
    modal.style.top = '50%';
    modal.style.left = '50%';
    modal.style.transform = 'translate(-50%, -50%)';
    modal.style.zIndex = '999999';

    var barContainer = document.getElementById('progressBarContainer');
    barContainer.classList.remove('idle');

    document.getElementById('progressStatus').textContent = '准备中...';
    document.getElementById('progressStatus').className = 'progress-status active';
    document.getElementById('totalSize').textContent = '--';
    document.getElementById('downloaded').textContent = '--';
    document.getElementById('speed').textContent = '--';
    document.getElementById('progressFill').style.width = '0%';
    document.getElementById('progressFill').classList.remove('done-glow');
    document.getElementById('progressPercent').textContent = '0%';
    document.getElementById('progressBox').className = 'progress-box';
    _lastDownloaded = 0;
    _lastTime = 0;
    
var progressTimer = setInterval(function() {
        fetch('ajax_update.php?act=progress')
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (resp.code === 0 && resp.data) {
                    var data = resp.data;
                    var fill = document.getElementById('progressFill');
                    var percent = document.getElementById('progressPercent');
                    fill.style.width = data.percent + '%';
                    percent.textContent = data.percent + '%';
                    document.getElementById('progressStatus').textContent = data.msg;

                    if (data.percent >= 100) {
                        barContainer.classList.add('idle');
                        fill.classList.add('done-glow');
                        document.getElementById('progressBox').classList.add('complete');
                        document.getElementById('progressStatus').className = 'progress-status success';
                    } else if (data.percent < 0) {
                        barContainer.classList.add('idle');
                        document.getElementById('progressStatus').className = 'progress-status error';
                    } else {
                        document.getElementById('progressStatus').className = 'progress-status active';
                    }
                    if (data.total && data.total > 0) {
                        document.getElementById('totalSize').textContent = formatSize(data.total);
                    }
                    if (data.downloaded && data.downloaded > 0) {
                        document.getElementById('downloaded').textContent =
                            formatSize(data.downloaded) + '/' + (data.total ? formatSize(data.total) : '?');
                    }
                    if (data.downloaded && data.total) {
                        var now = Date.now() / 1000;
                        if (_lastDownloaded > 0 && _lastTime > 0) {
                            var deltaBytes = data.downloaded - _lastDownloaded;
                            var deltaTime = Math.max(0.1, now - _lastTime);
                            document.getElementById('speed').textContent = formatSize(Math.floor(deltaBytes / deltaTime)) + '/s';
                        }
                        _lastDownloaded = data.downloaded;
                        _lastTime = now;
                    }
                }
            })
            .catch(function() {});
    }, 1000);

    var _lastDownloaded = 0;
    var _lastTime = 0;

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + 'B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + 'KB';
        return (bytes / 1048576).toFixed(1) + 'MB';
    }
    
    var updateTimeout = setTimeout(function() {
        clearInterval(progressTimer);
        document.getElementById('progressStatus').innerHTML = '<span style="color:red;">更新超时，请重试</span>';
    }, 600000);
    
    fetch('ajax_update.php?act=update', { method: 'POST' })
        .then(function(r) { 
            if (!r.ok) throw new Error('HTTP ' + r.status); 
            return r.json(); 
        })
        .then(function(data) {
            clearInterval(progressTimer);
            clearTimeout(updateTimeout);

            barContainer.classList.add('idle');

            if (data.code === 0) {
                document.getElementById('progressFill').classList.add('done-glow');
                document.getElementById('progressBox').classList.add('complete');
                document.getElementById('progressStatus').className = 'progress-status success';
                document.getElementById('progressStatus').textContent = '✔ 更新成功！正在刷新...';
                if (_latestInfo && _latestInfo.hash) {
                    localStorage.setItem(STORAGE_KEY, _latestInfo.hash);
                    loadLocalVersion();
                }
                setTimeout(function() {
                    modal.style.animation = 'fadeSlideOut .3s ease forwards';
                    setTimeout(function() { modal.style.display = 'none'; window.location.href = './update.php'; }, 300);
                }, 2000);
            } else {
                document.getElementById('progressStatus').className = 'progress-status error';
                document.getElementById('progressStatus').textContent = '✘ 更新失败: ' + (data.msg || '未知错误');
            }
        })
        .catch(function(e) {
            clearInterval(progressTimer);
            clearTimeout(updateTimeout);
            var errMsg = e.message === 'Failed to fetch' ? '无法连接到服务器，请检查网络' : e.message;
            document.getElementById('progressStatus').innerHTML = '<span style="color:red;">错误: ' + errMsg + '</span>';
        });
}

function saveVersion(hash) {
    localStorage.setItem(STORAGE_KEY, hash);
    loadLocalVersion();
    showResult('success', '<strong>版本已保存</strong>');
}

function clearVersion() {
    localStorage.removeItem(STORAGE_KEY);
    loadLocalVersion();
    document.getElementById('resultBox').className = 'result-box';
}

init();
</script>
<?php
$content = ob_get_clean();
include "./head.php";
?>