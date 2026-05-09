<?php
define("IN_ADMIN", true);
include("../includes/common.php");
$title = "检查更新";
if($islogin != 1) exit("<script>window.location.href=\"./login.php\";</script>");

ob_start();
?>
<style>
.update-container { max-width: 500px; margin: 0 auto; }
.version-card {
    background: var(--zk-surface, #fff); border-radius: 14px;
    padding: 24px; margin-bottom: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}
.version-title { font-size: 13px; color: var(--zk-text-sub, #64748b); margin-bottom: 8px; font-weight: 500; }
.version-num { font-size: 24px; font-weight: 700; color: var(--zk-text, #1e293b); font-family: 'JetBrains Mono', monospace; }
.version-num.new { color: var(--zk-primary, #5b8def); }
.version-status { font-size: 13px; margin-top: 6px; }
.version-status.latest { color: #10b981; }
.version-status.outdated { color: #f59e0b; }
.controls-row {
    display: flex; gap: 12px; align-items: center;
    background: var(--zk-surface, #fff); border-radius: 14px;
    padding: 16px 20px; margin-bottom: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}
.controls-row select {
    flex: 1; padding: 10px 14px; border: 2px solid var(--zk-border, #e2e8f0);
    border-radius: 10px; font-size: 14px; background: var(--zk-surface, #fff);
    color: var(--zk-text, #1e293b); cursor: pointer;
}
.controls-row select:focus { outline: none; border-color: var(--zk-primary, #5b8def); }
.controls-row .switch-wrap {
    display: flex; align-items: center; gap: 8px; cursor: pointer; white-space: nowrap;
}
.controls-row .switch {
    width: 44px; height: 24px; border-radius: 12px; background: #e2e8f0;
    position: relative; transition: all .2s;
}
.controls-row .switch::after {
    content: ''; position: absolute; top: 3px; left: 3px;
    width: 18px; height: 18px; border-radius: 50%; background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: all .2s;
}
.controls-row .switch.on { background: var(--zk-primary); }
.controls-row .switch.on::after { left: 23px; }
.result-box {
    display: none; padding: 16px 20px; border-radius: 12px;
    font-size: 14px; margin-top: 16px; border: 1px solid transparent;
}
.result-box.show { display: block; animation: fadeIn .3s ease; }
.result-box.warning { background: #fffbeb; color: #92400e; border-color: #fde68a; }
.result-box.success { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
.result-box.error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
.result-box.info { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
.result-actions { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
.progress-bar {
    height: 8px; background: #e2e8f0; border-radius: 4px; margin-top: 12px; overflow: hidden;
}
.progress-bar .fill {
    height: 100%; background: var(--zk-primary);
    border-radius: 4px; transition: width .3s ease;
}
.progress-text { font-size: 12px; color: var(--zk-text-sub, #64748b); margin-top: 6px; text-align: center; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="update-container">
    <div class="version-card">
        <div class="version-title">当前版本</div>
        <div class="version-num" id="localVersion">--</div>
        <div class="version-status" id="localStatus">获取中...</div>
    </div>
    
    <div class="version-card">
        <div class="version-title">最新版本</div>
        <div class="version-num" id="latestVersion">--</div>
        <div class="version-status" id="latestStatus">获取中...</div>
    </div>

    <div class="controls-row">
        <select id="nodeSelect" onchange="changeNode()">
            <option value="auto">自动</option>
            <option value="gitee">Gitee</option>
            <option value="github">GitHub</option>
        </select>
        <label class="switch-wrap" onclick="toggleAutoUpdate()">
            <div class="switch" id="autoSwitch"></div>
            <span style="font-size:14px;font-weight:500;color:var(--zk-text,#1e293b);">自动更新</span>
        </label>
    </div>

    <button class="btn btn-primary btn-lg btn-block" id="btnCheck" onclick="checkUpdate()">
        <i class="fa fa-refresh"></i> 检查更新
    </button>

    <div class="result-box" id="resultBox"></div>
</div>

<script>
var STORAGE_KEY = 'zuki_update_hash';
var NODE_KEY = 'zuki_update_node';
var AUTO_KEY = 'zuki_auto_update';
var _currentNode = 'auto';
var _latestInfo = null;
var _checking = false;
var _hasNewVersion = false;

var REPO_CONFIG = {
    gitee: { api: 'https://gitee.com/api/v5/repos/xiaoxuanzz/zukicloud/commits', name: 'Gitee', headers: {} },
    github: { api: 'https://api.github.com/repos/xiaoxuanzz/zukicloud/commits', name: 'GitHub', headers: { 'Accept': 'application/vnd.github.v3+json' } }
};
var BRANCH = 'main';

function init() {
    _currentNode = localStorage.getItem(NODE_KEY) || 'auto';
    document.getElementById('nodeSelect').value = _currentNode;
    
    var autoOn = localStorage.getItem(AUTO_KEY) === '1';
    document.getElementById('autoSwitch').classList.toggle('on', autoOn);
    
    loadLocalVersion();
    fetchLatestVersion();
}

function loadLocalVersion() {
    var hash = localStorage.getItem(STORAGE_KEY) || '';
    var el = document.getElementById('localVersion');
    var status = document.getElementById('localStatus');
    if (hash) {
        el.textContent = hash.substring(0, 7);
        status.textContent = '已记录';
        status.className = 'version-status latest';
    } else {
        el.textContent = '无记录';
        status.textContent = '首次使用';
        status.className = 'version-status outdated';
    }
}

function fetchLatestVersion() {
    var el = document.getElementById('latestVersion');
    var status = document.getElementById('latestStatus');
    el.textContent = '获取中...';
    status.textContent = '正在连接...';
    
    var sources = _currentNode === 'auto' ? ['github', 'gitee'] : [_currentNode];
    tryNextSource(sources, 0);
}

function tryNextSource(sources, idx) {
    if (idx >= sources.length) {
        document.getElementById('latestVersion').textContent = '获取失败';
        document.getElementById('latestStatus').textContent = '请检查网络';
        document.getElementById('latestStatus').className = 'version-status outdated';
        return;
    }
    
    var key = sources[idx];
    var src = REPO_CONFIG[key];
    var url = src.api + '?sha=' + BRANCH + '&per_page=1';
    
    var timer = setTimeout(function() { tryNextSource(sources, idx + 1); }, 10000);
    
    fetch(url, { headers: src.headers })
        .then(function(r) { clearTimeout(timer); if (!r.ok) throw new Error(); return r.json(); })
        .then(function(data) {
            if (Array.isArray(data) && data.length > 0) {
                var c = data[0];
                _latestInfo = { hash: c.sha, short: c.sha.substring(0, 7), message: c.commit.message.split('\n')[0] };
                document.getElementById('latestVersion').textContent = _latestInfo.short;
                document.getElementById('latestVersion').classList.add('new');
                
                var localHash = localStorage.getItem(STORAGE_KEY) || '';
                if (localHash && localHash === c.sha) {
                    document.getElementById('latestStatus').textContent = '已是最新';
                    document.getElementById('latestStatus').className = 'version-status latest';
                    _hasNewVersion = false;
                } else if (!localHash) {
                    document.getElementById('latestStatus').textContent = '新版本可用';
                    document.getElementById('latestStatus').className = 'version-status outdated';
                    _hasNewVersion = true;
                } else {
                    document.getElementById('latestStatus').textContent = '发现新版本';
                    document.getElementById('latestStatus').className = 'version-status outdated';
                    _hasNewVersion = true;
                }
            } else {
                tryNextSource(sources, idx + 1);
            }
        })
        .catch(function(e) { tryNextSource(sources, idx + 1); });
}

function changeNode() {
    var node = document.getElementById('nodeSelect').value;
    _currentNode = node;
    localStorage.setItem(NODE_KEY, node);
    fetchLatestVersion();
    document.getElementById('resultBox').className = 'result-box';
}

function toggleAutoUpdate() {
    var sw = document.getElementById('autoSwitch');
    var isOn = sw.classList.toggle('on');
    localStorage.setItem(AUTO_KEY, isOn ? '1' : '0');
    showToast(isOn ? '自动更新已开启' : '自动更新已关闭', isOn ? 'success' : 'info');
}

function checkUpdate() {
    if (_checking) return;
    _checking = true;
    var btn = document.getElementById('btnCheck');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 检查中...';
    
    var resultBox = document.getElementById('resultBox');
    resultBox.className = 'result-box';
    resultBox.innerHTML = '';
    
    var sources = _currentNode === 'auto' ? ['github', 'gitee'] : [_currentNode];
    tryCheckSource(sources, 0);
}

function tryCheckSource(sources, idx) {
    if (idx >= sources.length) {
        _checking = false;
        document.getElementById('btnCheck').disabled = false;
        document.getElementById('btnCheck').innerHTML = '<i class="fa fa-refresh"></i> 检查更新';
        showResult('error', '<strong>连接失败</strong><br>请检查网络或稍后重试');
        return;
    }
    
    var key = sources[idx];
    var src = REPO_CONFIG[key];
    var url = src.api + '?sha=' + BRANCH + '&per_page=1';
    var headers = { 'Accept': key === 'github' ? 'application/vnd.github.v3+json' : 'application/json' };
    
    var timer = setTimeout(function() { tryCheckSource(sources, idx + 1); }, 15000);
    
    fetch(url, { headers: headers })
        .then(function(r) { clearTimeout(timer); if (!r.ok) throw new Error(); return r.json(); })
        .then(function(data) {
            clearTimeout(timer);
            if (Array.isArray(data) && data.length > 0) {
                var c = data[0];
                var localHash = localStorage.getItem(STORAGE_KEY) || '';
                var localShort = localHash ? localHash.substring(0, 7) : '无记录';
                var short = c.sha.substring(0, 7);
                var msg = c.commit.message.split('\n')[0];
                
                if (localHash && localHash === c.sha) {
                    showResult('success', '<strong>已是最新版本</strong><br>当前版本: <code>' + short + '</code>');
                    _hasNewVersion = false;
                } else {
                    _hasNewVersion = true;
                    var html = '';
                    if (!localHash) {
                        html = '<strong>首次记录版本</strong><br>最新: <code>' + short + '</code><br>提交: ' + esc(msg.substring(0, 40));
                    } else {
                        html = '<strong>发现新版本！</strong><br>本地: <code>' + localShort + '</code> → 最新: <code>' + short + '</code><br>提交: ' + esc(msg.substring(0, 40));
                    }
                    html += '<div class="result-actions">';
                    html += '<button class="btn btn-primary btn-sm" onclick="showUpdateConfirm()"><i class="fa fa-cloud-download"></i> 立即更新</button>';
                    html += '<button class="btn btn-success btn-sm" onclick="saveVersion(\'' + c.sha + '\')"><i class="fa fa-check"></i> 标记已更新</button>';
                    if (localHash) html += '<button class="btn btn-default btn-sm" onclick="clearVersion()"><i class="fa fa-eraser"></i> 清除记录</button>';
                    html += '</div>';
                    showResult('warning', html);
                }
                _latestInfo = { hash: c.sha, short: short, message: msg };
                document.getElementById('latestVersion').textContent = short;
                document.getElementById('latestVersion').classList.add('new');
                document.getElementById('latestStatus').textContent = _hasNewVersion ? '新版本可用' : '已是最新';
                document.getElementById('latestStatus').className = _hasNewVersion ? 'version-status outdated' : 'version-status latest';
            } else {
                tryCheckSource(sources, idx + 1);
            }
        })
        .catch(function(e) { tryCheckSource(sources, idx + 1); });
    
    function showResult(type, html) {
        _checking = false;
        document.getElementById('btnCheck').disabled = false;
        document.getElementById('btnCheck').innerHTML = '<i class="fa fa-refresh"></i> 检查更新';
        var resultBox = document.getElementById('resultBox');
        resultBox.className = 'result-box show ' + type;
        resultBox.innerHTML = html;
    }
}

function showUpdateConfirm() {
    var html = '<div id="zk-update-confirm" style="position:fixed;inset:0;background:rgba(0,0,0,0);z-index:999999;display:flex;align-items:center;justify-content:center;">' +
        '<div style="background:var(--zk-surface,#fff);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.25);width:90%;max-width:440px;transform:scale(.9);transition:all .3s;">' +
        '<div style="padding:24px 24px 0;">' +
        '<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">' +
        '<div style="width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#f59e0b;">' +
        '<i class="fa fa-exclamation-triangle" style="font-size:22px;color:#fff;"></i></div>' +
        '<div><h3 style="font-size:18px;font-weight:700;color:var(--zk-text,#1e293b);margin:0 0 4px;">更新警告</h3>' +
        '<p style="font-size:13px;color:var(--zk-text-dim,#94a3b8);margin:0;">请仔细阅读以下内容</p></div></div>' +
        '<div style="background:#fffbeb;border-radius:10px;padding:14px 16px;margin-bottom:16px;border:1px solid #fde68a;">' +
        '<div style="font-size:14px;color:#92400e;line-height:1.7;">' +
        '<strong style="font-size:15px;">⚠️ 更新存在风险，请务必先备份！</strong><br><br>' +
        '• 建议在更新前完整备份网站数据和数据库<br>' +
        '• 更新过程可能会覆盖修改过的文件<br>' +
        '• 更新有概率导致文件丢失，若丢失文件 <strong>概不负责</strong><br>' +
        '• 更新完成后系统将自动刷新界面</div></div></div>' +
        '<div style="display:flex;gap:10px;padding:0 24px 24px;">' +
        '<button onclick="closeUpdateConfirm()" style="flex:1;padding:12px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;background:#f1f5f9;color:#64748b;">取消</button>' +
        '<button onclick="doUpdate()" style="flex:1;padding:12px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;background:#5b8def;color:#fff;">我已备份，继续更新</button></div></div></div>';
    
    var div = document.createElement('div');
    div.innerHTML = html;
    document.body.appendChild(div);
    
    setTimeout(function() {
        document.getElementById('zk-update-confirm').style.background = 'rgba(0,0,0,0.5)';
        document.getElementById('zk-update-confirm').querySelector('div').style.transform = 'scale(1)';
    }, 10);
}

window.closeUpdateConfirm = function() {
    var el = document.getElementById('zk-update-confirm');
    if (el) {
        el.style.background = 'rgba(0,0,0,0)';
        el.querySelector('div').style.transform = 'scale(.9)';
        setTimeout(function() { el.remove(); }, 200);
    }
};

function doUpdate() {
    closeUpdateConfirm();
    
    // 创建强制弹窗（无关闭按钮，无法点击空白退出）
    var progressHtml = '<div id="zk-progress-modal" style="position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:999999;display:flex;align-items:center;justify-content:center;">' +
        '<div style="background:var(--zk-surface,#fff);border-radius:20px;box-shadow:0 25px 80px rgba(0,0,0,.4);width:90%;max-width:420px;padding:32px;text-align:center;">' +
        '<div style="width:80px;height:80px;border-radius:50%;background:#5b8def;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">' +
        '<i class="fa fa-cloud-download" style="font-size:36px;color:#fff;"></i></div>' +
        '<h3 style="font-size:20px;font-weight:700;color:var(--zk-text,#1e293b);margin:0 0 24px;">正在更新...</h3>' +
        '<div style="background:#e2e8f0;border-radius:10px;height:12px;overflow:hidden;margin-bottom:16px;">' +
        '<div id="zk-progress-fill" style="height:100%;background:#5b8def;border-radius:10px;width:0%;transition:width .5s ease;"></div></div>' +
        '<p id="zk-progress-text" style="font-size:14px;color:var(--zk-text-sub,#64748b);margin:0;">正在连接更新服务器...</p>' +
        '<p style="font-size:12px;color:var(--zk-text-dim,#94a3b8);margin:16px 0 0;">更新完成后将自动刷新界面</p></div></div>';
    
    var div = document.createElement('div');
    div.innerHTML = progressHtml;
    document.body.appendChild(div);
    
    var progress = 0;
    var progressTimer = setInterval(function() {
        if (progress < 95) {
            progress += Math.random() * 5;
            if (progress > 95) progress = 95;
            var fill = document.getElementById('zk-progress-fill');
            if (fill) fill.style.width = progress + '%';
        }
    }, 300);
    
    // 超时处理
    var updateTimeout = setTimeout(function() {
        clearInterval(progressTimer);
        var modal = document.getElementById('zk-progress-modal');
        if (modal) {
            modal.querySelector('div').innerHTML = 
                '<div style="width:80px;height:80px;border-radius:50%;background:#ef4444;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">' +
                '<i class="fa fa-times" style="font-size:36px;color:#fff;"></i></div>' +
                '<h3 style="font-size:20px;font-weight:700;color:#991b1b;margin:0 0 16px;">更新超时</h3>' +
                '<p style="font-size:14px;color:#991b1b;margin:0 0 24px;">请求超时，请检查网络或服务器状态</p>' +
                '<button onclick="location.reload()" style="padding:12px 32px;background:var(--zk-primary);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;">刷新页面重试</button>';
        }
    }, 120000); // 2分钟超时
    
    fetch('ajax_update.php?act=update', { method: 'POST', signal: AbortSignal.timeout(120000) })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            clearInterval(progressTimer);
            clearTimeout(updateTimeout);
            var fill = document.getElementById('zk-progress-fill');
            var text = document.getElementById('zk-progress-text');
            if (fill) fill.style.width = '100%';
            
            if (data.code === 0) {
                if (text) text.textContent = '更新成功！正在刷新界面...';
                
                // 保存新版本
                if (_latestInfo && _latestInfo.hash) {
                    localStorage.setItem(STORAGE_KEY, _latestInfo.hash);
                    loadLocalVersion();
                }
                
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                var modal = document.getElementById('zk-progress-modal');
                if (modal) {
                    modal.querySelector('div').innerHTML = 
                        '<div style="width:80px;height:80px;border-radius:50%;background:#ef4444;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">' +
                        '<i class="fa fa-times" style="font-size:36px;color:#fff;"></i></div>' +
                        '<h3 style="font-size:20px;font-weight:700;color:#991b1b;margin:0 0 16px;">更新失败</h3>' +
                        '<p style="font-size:14px;color:#991b1b;margin:0 0 24px;">' + esc(data.msg) + '</p>' +
                        '<button onclick="location.reload()" style="padding:12px 32px;background:var(--zk-primary);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;">刷新页面重试</button>';
                }
            }
        })
        .catch(function(e) {
            clearInterval(progressTimer);
            clearTimeout(updateTimeout);
            var modal = document.getElementById('zk-progress-modal');
            if (modal) {
                modal.querySelector('div').innerHTML = 
                    '<div style="width:80px;height:80px;border-radius:50%;background:#ef4444;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">' +
                    '<i class="fa fa-times" style="font-size:36px;color:#fff;"></i></div>' +
                    '<h3 style="font-size:20px;font-weight:700;color:#991b1b;margin:0 0 16px;">更新失败</h3>' +
                    '<p style="font-size:14px;color:#991b1b;margin:0 0 24px;">' + esc(e.message) + '</p>' +
                    '<button onclick="location.reload()" style="padding:12px 32px;background:var(--zk-primary);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;">刷新页面重试</button>';
            }
        });
}

function saveVersion(hash) {
    localStorage.setItem(STORAGE_KEY, hash);
    loadLocalVersion();
    var resultBox = document.getElementById('resultBox');
    resultBox.className = 'result-box show success';
    resultBox.innerHTML = '<strong>版本已保存</strong>';
    showToast('版本已更新', 'success');
}

function clearVersion() {
    localStorage.removeItem(STORAGE_KEY);
    loadLocalVersion();
    document.getElementById('resultBox').className = 'result-box';
    showToast('记录已清除', 'info');
}

function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

init();
</script>
<?php
$content = ob_get_clean();
include "./head.php";
?>