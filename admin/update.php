<?php
define("IN_ADMIN", true);
include("../includes/common.php");
$title = "检查更新";
if($islogin != 1) exit("<script>window.location.href=\"./login.php\";</script>");

ob_start();
?>
<style>
.update-container { max-width: 600px; margin: 20px auto; font-size: 14px; }
.version-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
.version-label { color: #666; }
.version-value { font-family: monospace; font-weight: bold; }
.controls { margin: 15px 0; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.controls button { padding: 6px 14px; border: 1px solid #ccc; background: #f5f5f5; border-radius: 4px; cursor: pointer; font-size: 13px; }
.controls button:hover { background: #eee; }
.controls button:active { transform: scale(.97); }
.controls button:disabled { opacity: .45; cursor: not-allowed; }
.btn-primary { background: #337ab7; color: #fff; border: 2px solid #2e6da4; border-radius: 6px; }
.btn-primary:hover { background: #286090; box-shadow: 0 2px 8px rgba(51,122,183,.3); }
.btn-primary:disabled { opacity: .45; cursor: not-allowed; }

.source-row { margin-bottom: 16px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.source-select {
    padding: 7px 32px 7px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 13px;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M2 4l4 4 4-4' fill='none' stroke='%23666' stroke-width='1.5'/%3E%3C/svg%3E") no-repeat right 10px center;
    cursor: pointer;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
}
.source-select:focus { border-color: #5b8def; box-shadow: 0 0 0 2px rgba(91,141,239,.2); }
.source-hint { font-size: 12px; color: #999; }

.result-box { margin-top: 15px; padding: 10px; border-radius: 4px; display: none; animation: fadeSlideIn .3s ease; }
.result-box.show { display: block; }
.result-box.warning { background: #fcf8e3; border: 1px solid #faebcc; color: #8a6d3b; }
.result-box.success { background: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; }
.result-box.error { background: #f2dede; border: 1px solid #ebccd1; color: #a94442; }

.changelog-section { margin-top: 20px; border-top: 1px solid #eee; padding-top: 16px; }
.changelog-tabs { display:flex; gap:0; margin-bottom:12px; border-bottom:2px solid #eee; }
.changelog-tab { padding:8px 18px; font-size:13px; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px; color:#999; transition:all .15s; }
.changelog-tab:hover { color:#333; }
.changelog-tab.active { color:#5b8def; border-bottom-color:#5b8def; font-weight:600; }
.changelog-content { min-height: 60px; }
.changelog-entry { margin-bottom:8px; padding:10px 12px; background:#f9f9f9; border-radius:6px; font-size:13px; }
.changelog-entry .ver { font-weight:700; color:#5b8def; font-family:monospace; }
.changelog-entry .time { color:#999; font-size:11px; margin-left:8px; }
.changelog-entry .msg { margin-top:4px; color:#555; line-height:1.5; white-space:pre-wrap; font-size:12px; }
.changelog-loading { text-align:center; padding:24px; color:#999; }

.progress-modal {
     display: none; justify-content: center; align-items: center;
     position: fixed; top: 0; left: 0; width: 100%; height: 100%;
     background: rgba(0,0,0,.45); z-index: 999998;
 }
 .progress-box {
     background: #fff; border-radius: 16px; padding: 36px 40px 32px;
     width: 320px; text-align: center;
     box-shadow: 0 20px 60px rgba(0,0,0,.35);
     position: relative;
 }
 .progress-box h3 { margin: 0 0 20px; font-size: 18px; color: #333; }
 .progress-status { margin-top: 16px; font-size: 13px; color: #888; min-height: 22px; }
 .progress-status.active { color: #337ab7; font-weight: 600; }
 .progress-status.success { color: #28a745; font-weight: 700; }
 .progress-status.error { color: #dc3545; font-weight: 700; }

@keyframes fadeSlideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="update-container" id="changelog-anchor">
<div class="source-row">
     <select id="sourceSelector" class="source-select">
       <option value="auto">自动切换</option>
       <option value="uhub">私人推送节点</option>
       <option value="github">GitHub 节点</option>
     </select>
     <span class="source-hint" id="sourceHint">自动模式：优先私人节点，失败时自动切换至 GitHub</span>
   </div>

   <div class="link-config" id="linkConfig" style="margin-bottom:16px;padding:12px;background:#f9f9f9;border-radius:6px;display:none;">
     <div style="font-size:13px;font-weight:600;margin-bottom:8px;color:#333;">自定义更新链接</div>
     <div style="margin-bottom:8px;">
       <div style="font-size:12px;color:#666;margin-bottom:4px;">私人推送节点 URL</div>
       <input type="text" id="uhubUrlInput" placeholder="https://update.xiaoxuanzz.cloud/index.php?route=api/zukicloud/latest" style="width:100%;padding:7px 10px;border:1px solid #ccc;border-radius:4px;font-size:13px;box-sizing:border-box;">
     </div>
     <div style="margin-bottom:8px;">
       <div style="font-size:12px;color:#666;margin-bottom:4px;">GitHub 仓库 (owner/repo)</div>
       <input type="text" id="ghRepoInput" placeholder="xiaoxuanzz/zukicloud" style="width:100%;padding:7px 10px;border:1px solid #ccc;border-radius:4px;font-size:13px;box-sizing:border-box;">
     </div>
<div style="font-size:12px;color:#999;">修改后点击"保存"生效，非作者提供的新链接可能导致无法更新</div>
      <button id="btnSaveLinks" onclick="saveCustomLinks()" style="margin-top:8px;padding:5px 14px;font-size:12px;cursor:pointer;">保存链接</button>
    </div>

  <div class="version-row">
      <span class="version-label">当前版本:</span>
      <span class="version-value" id="localVersion">--</span>
  </div>
  <div class="version-row">
      <span class="version-label">最新版本:</span>
      <span class="version-value" id="latestVersion">--</span>
  </div>
<div class="controls">
       <button id="btnEditLinks" onclick="toggleLinkConfig()" style="font-size:13px;">修改更新链接</button>
       <button id="btnCheck" onclick="checkUpdate()">检查更新</button>
       <button id="btnUpdateNow" onclick="showUpdateConfirm()" style="display:none;">立即更新</button>
       <label style="margin-left:10px;cursor:pointer;font-size:13px;">
           <input type="checkbox" id="autoCheck" onchange="toggleAutoCheck()"> 自动检查
       </label>
   </div>
  <div class="result-box" id="resultBox"></div>

<div class="changelog-section" id="changelogSection">
     <div style="font-size:14px;font-weight:700;color:#333;margin-bottom:8px;">历史更新日志</div>
<div class="changelog-tabs" id="changelogTabs">
        <div class="changelog-tab active" data-filter="all" onclick="switchChangelogTab(this,'all')">全部</div>
       <div style="margin-left:auto;font-size:11px;color:#999;" id="changelogCount"></div>
     </div>
    <div class="changelog-content" id="changelogList">
      <div class="changelog-loading">加载中...</div>
    </div>
    <div style="text-align:center;margin-top:10px;">
      <button class="btn-primary" id="btnLoadMore" onclick="loadMoreChangelog()" style="display:none;">加载更多</button>
      <span id="changelogNoMore" style="font-size:12px;color:#999;display:none;">-- 已加载全部 --</span>
    </div>
  </div>
</div>

<div class="progress-modal" id="progressModal">
     <div class="progress-box" id="progressBox">
         <div id="progressText">正在更新<span id="progressDots"></span></div>
         <div class="progress-status" id="progressStatus">准备中...</div>
     </div>
 </div>

<script>
var STORAGE_KEY = 'zuki_update_hash';
var AUTO_CHECK_KEY = 'zuki_auto_check';
var SOURCE_KEY  = 'zuki_update_source';
var UHUB_URL_KEY = 'zuki_uhub_url';
var GH_REPO_KEY  = 'zuki_gh_repo';
var _latestInfo = null;
var _checking = false;
var _lastDownloaded = 0;
var _lastTime = 0;
var _currentSource = 'auto';

var _defaultUhubUrl = 'https://update.xiaoxuanzz.cloud/index.php?route=api/zukicloud';
var _defaultGhRepo  = 'xiaoxuanzz/zukicloud';
var _customUhubUrl = null;
var _customGhRepo  = null;

function init() {
      loadLocalVersion();
      restoreSource();
      restoreAutoCheck();
      restoreCustomLinks();
      updateSourceHint();
      fetchChangelog();
      fetchLatestVersion();

     document.getElementById('sourceSelector').addEventListener('change', function() {
         _currentSource = this.value;
         localStorage.setItem(SOURCE_KEY, _currentSource);
         updateSourceHint();
         fetchChangelog();
         fetchLatestVersion();
         // 切换源后自动检查
         checkUpdate();
     });

     // 进入页面强制检查一次
     setTimeout(function() { checkUpdate(); }, 500);
 }

function restoreSource() {
    var saved = localStorage.getItem(SOURCE_KEY);
    if (saved && (saved === 'auto' || saved === 'uhub' || saved === 'github')) {
        _currentSource = saved;
        var sel = document.getElementById('sourceSelector');
        if (sel) {
            for (var i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value === saved) {
                    sel.selectedIndex = i;
                    break;
                }
            }
        }
    }
}

function restoreAutoCheck() {
      var autoOn = localStorage.getItem(AUTO_CHECK_KEY) === '1';
      var cb = document.getElementById('autoCheck');
      if (cb) cb.checked = autoOn;
  }

 function restoreCustomLinks() {
     var savedUhub = localStorage.getItem(UHUB_URL_KEY);
     var savedGh   = localStorage.getItem(GH_REPO_KEY);
     var input1 = document.getElementById('uhubUrlInput');
     var input2 = document.getElementById('ghRepoInput');
     if (input1) input1.value = savedUhub || _defaultUhubUrl;
     if (input2) input2.value = savedGh   || _defaultGhRepo;
     _customUhubUrl = savedUhub || null;
     _customGhRepo  = savedGh  || null;
 }

function toggleLinkConfig() {
      var el = document.getElementById('linkConfig');
      var btn = document.getElementById('btnEditLinks');
      if (!el) return;
      if (el.style.display === 'none') {
          el.style.display = 'block';
          if (btn) btn.textContent = '隐藏更新链接';
      } else {
          el.style.display = 'none';
          if (btn) btn.textContent = '修改更新链接';
      }
  }

 function saveCustomLinks() {
     var input1 = document.getElementById('uhubUrlInput');
     var input2 = document.getElementById('ghRepoInput');
     var uhub = input1 ? input1.value.trim() : '';
     var gh   = input2 ? input2.value.trim() : '';

     if (uhub && uhub !== _defaultUhubUrl) {
         if (!confirm('你修改了私人推送节点链接，非作者提供的新链接可能导致无法更新。\n确定要保存吗？')) return;
         localStorage.setItem(UHUB_URL_KEY, uhub);
         _customUhubUrl = uhub;
     } else {
         localStorage.removeItem(UHUB_URL_KEY);
         _customUhubUrl = null;
     }

     if (gh && gh !== _defaultGhRepo) {
         if (!confirm('你修改了 GitHub 仓库，非作者提供的新仓库可能导致无法更新。\n确定要保存吗？')) return;
         localStorage.setItem(GH_REPO_KEY, gh);
         _customGhRepo = gh;
     } else {
         localStorage.removeItem(GH_REPO_KEY);
         _customGhRepo = null;
     }

     alert('链接已保存，刷新后生效');
 }

 function getAjaxParams() {
     var p = {};
     if (_customUhubUrl) p.uhub_url = _customUhubUrl;
     if (_customGhRepo)  p.gh_repo  = _customGhRepo;
     return p;
 }

 function buildQuery(base, params) {
     if (!params || Object.keys(params).length === 0) return base;
     var sep = base.indexOf('?') === -1 ? '?' : '&';
     return base + sep + Object.keys(params).map(function(k) {
         return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
     }).join('&');
 }

function updateSourceHint() {
     var hint = document.getElementById('sourceHint');
     if (!hint) return;
     if (_currentSource === 'auto') {
         hint.textContent = '自动模式：优先私人节点，失败时自动切换至 GitHub';
     } else if (_currentSource === 'uhub') {
         hint.textContent = '仅使用私人推送节点';
     } else {
         hint.textContent = '仅使用 GitHub 节点';
     }
 }

function loadLocalVersion() {
    var hash = localStorage.getItem(STORAGE_KEY) || '';
    document.getElementById('localVersion').textContent = hash ? hash.substring(0, 7) : '无记录';
}

function startLoadingDots(el) {
    if (!el) return;
    el.dataset.dots = '0';
    el._loadingTimer = setInterval(function() {
        var dots = parseInt(el.dataset.dots || '0');
        el.textContent = '获取中' + '.'.repeat((dots + 1) % 4 + 1);
        el.dataset.dots = String(dots + 1);
    }, 400);
}

function stopLoadingDots(el) {
    if (el && el._loadingTimer) { clearInterval(el._loadingTimer); el._loadingTimer = null; }
}

function fetchChangelog() {
    var listEl = document.getElementById('changelogList');
    var countEl = document.getElementById('changelogCount');
    var sectionEl = document.getElementById('changelogSection');
    if (listEl) listEl.innerHTML = '<div class="changelog-loading">加载中...</div>';
    if (countEl) countEl.textContent = '';
    if (sectionEl) sectionEl.style.display = 'block';

var limit = 20;
     var page = 1;
     var url = buildQuery('ajax_update.php?act=changelog&source=' + _currentSource + '&limit=' + limit + '&page=' + page + '&_=' + Date.now(), getAjaxParams());
     fetch(url)
        .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function(data) {
            if (data.code === 0) {
                processChangelogData(data.data);
            } else {
                throw new Error(data.msg || '获取日志失败');
            }
        })
        .catch(function(err) {
            console.error('日志获取失败:', err);
            if (listEl) listEl.innerHTML = '<div class="changelog-loading">获取日志失败：' + escapeHtml(err.message) + '</div>';
            if (countEl) countEl.textContent = '';
        });
}

function processChangelogData(data) {
    if (!data || !data.logs || data.logs.length === 0) {
        var listEl = document.getElementById('changelogList');
        if (listEl) listEl.innerHTML = '<div class="changelog-loading">暂无日志</div>';
        var countEl = document.getElementById('changelogCount');
        if (countEl) countEl.textContent = '共 0 条';
        return;
    }

    if (!window._clState) window._clState = { allLogs: [], page: 1, filter: 'all', limit: 20 };
    window._clState.allLogs = data.logs;
    window._clState.total = data.count || data.logs.length;
    window._clState.page = data.page || 1;
    window._clState.limit = data.limit || 20;
    window._clState.pages = data.pages || 1;
    window._clState.filter = 'all';

    applyChangelogFilter();

    var countEl = document.getElementById('changelogCount');
    if (countEl) countEl.textContent = '共 ' + window._clState.total + ' 条';

    var btnMore = document.getElementById('btnLoadMore');
    var noMore = document.getElementById('changelogNoMore');
    if (btnMore && noMore) {
        if (window._clState.page >= window._clState.pages) {
            btnMore.style.display = 'none';
            noMore.style.display = 'inline';
        } else {
            btnMore.style.display = 'inline-block';
            noMore.style.display = 'none';
        }
    }
}

function applyChangelogFilter() {
    var state = window._clState;
    if (!state) return;
    var filtered = state.allLogs;
    if (state.filter === 'live') filtered = filtered.filter(function(l) { return l.is_live; });
    if (state.filter === 'unpush') filtered = filtered.filter(function(l) { return !l.is_live; });

    var start = (state.page - 1) * state.limit;
    var pageItems = filtered.slice(start, start + state.limit);

    var html = '', count = 0;
    pageItems.forEach(function(log) {
        count++;
        var lines = log.changelog ? log.changelog.split('\n').filter(function(l) { return l.trim(); }) : [];
        html += '<div class="changelog-entry">';
        html += '<div><span class="ver">v' + escapeHtml(log.version || '') + '</span><span class="time">' + escapeHtml(log.created_at ? log.created_at.substring(0, 10) : '') + '</span></div>';
        if (lines.length > 0) {
            lines.forEach(function(line) { html += '<div class="msg">' + escapeHtml(line) + '</div>'; });
        } else {
            html += '<div class="msg" style="color:#999;">暂无更新说明</div>';
        }
        html += '</div>';
    });

    var listEl = document.getElementById('changelogList');
    if (listEl) {
        if (count === 0) {
            listEl.innerHTML = '<div class="changelog-loading">暂无符合条件的日志</div>';
        } else {
            listEl.innerHTML = html;
        }
    }

    var btnMore = document.getElementById('btnLoadMore');
    var noMore = document.getElementById('changelogNoMore');
    if (btnMore && noMore) {
        if (start + pageItems.length >= filtered.length && state.page >= state.pages) {
            btnMore.style.display = 'none';
            noMore.style.display = 'inline';
        } else {
            btnMore.style.display = 'inline-block';
            noMore.style.display = 'none';
        }
    }
}

function switchChangelogTab(el, filter) {
    if (el) {
        var container = el.closest('.changelog-section');
        if (container) container.querySelectorAll('.changelog-tab').forEach(function(t) { t.classList.remove('active'); });
        el.classList.add('active');
    }
    if (window._clState) {
        window._clState.filter = filter;
        window._clState.page = 1;
        applyChangelogFilter();
    }
}

function loadMoreChangelog() {
    if (!window._clState) return;
    var nextPage = window._clState.page + 1;
    if (nextPage > window._clState.pages) return;
    var limit = window._clState.limit;
    var url = buildQuery('ajax_update.php?act=changelog&source=' + _currentSource + '&limit=' + limit + '&page=' + nextPage + '&_=' + Date.now(), getAjaxParams());
     fetch(url)
        .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function(data) {
            if (data.code === 0 && data.data && data.data.logs && data.data.logs.length > 0) {
                var d = data.data;
                window._clState.allLogs = window._clState.allLogs.concat(d.logs);
                window._clState.page = nextPage;
                window._clState.total = d.count || window._clState.total;
                window._clState.pages = d.pages || window._clState.pages;
                applyChangelogFilter();
            }
        })
        .catch(function(err) { console.error('加载更多日志失败:', err); });
}

function fetchLatestVersion() {
    var el = document.getElementById('latestVersion');
    if (!el) return;
    el.textContent = '获取中...';
    startLoadingDots(el);

var url = buildQuery('ajax_update.php?act=check&source=' + _currentSource + '&_=' + Date.now(), getAjaxParams());
     fetch(url)
         .then(function(r) {
             stopLoadingDots(el);
             if (!r.ok) throw new Error('HTTP ' + r.status);
             return r.json();
         })
         .then(function(resp) {
            stopLoadingDots(el);
            if (resp.code === 0 && resp.data && resp.data.version) {
                var ver = resp.data.version;
                _latestInfo = { hash: ver, short: ver };
                el.textContent = ver;
            } else if (resp.code === 0 && resp.data && resp.data.sha) {
                var sha = resp.data.sha.substring(0, 7);
                _latestInfo = { hash: resp.data.sha, short: sha };
                el.textContent = sha;
            } else {
                el.textContent = '获取失败';
            }
        })
        .catch(function(err) {
            stopLoadingDots(el);
            console.error('获取最新版本失败:', err);
            if (el) el.textContent = '获取失败';
        });
}

function checkUpdate() {
    if (_checking) return;
    _checking = true;
    var btn = document.getElementById('btnCheck');
    if (btn) { btn.disabled = true; btn.textContent = '检查中...'; }

    var timer = setTimeout(function() {
        _checking = false;
        if (btn) { btn.disabled = false; btn.textContent = '检查更新'; }
        showResult('error', '<strong>连接超时</strong> - 请检查网络');
    }, 15000);

var url = buildQuery('ajax_update.php?act=check&source=' + _currentSource + '&_=' + Date.now(), getAjaxParams());
     fetch(url)
         .then(function(r) { clearTimeout(timer); return r.json(); })
        .then(function(resp) {
            _checking = false;
            if (btn) { btn.disabled = false; btn.textContent = '检查更新'; }
            if (resp.code === 0 && resp.data) {
                var sourceName = resp.source || '后端代理';
                processCheckResult(resp.data, sourceName);
            } else {
                showResult('error', '<strong>检查失败</strong> - ' + (resp.msg || '未知错误'));
            }
        })
        .catch(function(err) {
            clearTimeout(timer);
            _checking = false;
            if (btn) { btn.disabled = false; btn.textContent = '检查更新'; }
            showResult('error', '<strong>连接失败</strong> - ' + err.message);
        });
}

function processCheckResult(data, sourceName) {
    if (!data) {
        showResult('success', '<strong>当前已是最新版本</strong>（暂无更新日志）');
        return;
    }

    var hasLogs = data.logs && Array.isArray(data.logs) && data.logs.length > 0;
    var hasVersion = data.version || data.tag || data.sha;

    if (!hasLogs && !hasVersion && !data.message) {
        showResult('success', '<strong>当前已是最新版本</strong>（暂无更新日志）');
        return;
    }

    var latest = data;
    var localHash = localStorage.getItem(STORAGE_KEY) || '';
    var displayVer = latest.version || latest.tag || latest.sha || '未知';
    displayVer = displayVer.replace(/^v/i, '');

    updateLatestVersionDisplay(displayVer);

    if (localHash) {
        var localCompare = localHash.replace(/^v/i, '');
        var remoteCompare = displayVer;
        var isSame = (localCompare === remoteCompare) || (localHash === (latest.version || latest.tag || latest.sha));
        if (isSame) {
            showResult('success', '<strong>已是最新版本</strong> (' + escapeHtml(localHash.substring(0, 7)) + ')');
            return;
        }
    }

    var html = '';
    if (localHash) {
        html = '<strong style="color:#f0883e;">发现新版本！</strong><br>本地: ' + escapeHtml(localHash.substring(0, 7)) + '  最新: ' + escapeHtml(displayVer);
    } else {
        html = '<strong>首次记录版本</strong><br>最新: ' + escapeHtml(displayVer);
    }

    if (latest.changelog) {
        var lines = latest.changelog.split('\n');
        html += '<div style="margin-top:12px;text-align:left;font-size:13px;max-height:200px;overflow-y:auto;">';
        lines.forEach(function(line) { line = line.trim(); if (line) html += '<div style="margin:2px 0;font-size:12px;color:#555;">' + escapeHtml(line) + '</div>'; });
        html += '</div>';
    } else if (data.logs && data.logs.length > 0) {
        var recent = data.logs.slice(0, 3);
        html += '<div style="margin-top:12px;text-align:left;font-size:13px;max-height:200px;overflow-y:auto;">';
        recent.forEach(function(log) {
            var lines = log.changelog ? log.changelog.split('\n') : [];
            html += '<div style="margin-bottom:8px;padding:8px;background:#f9f9f9;border-radius:6px;">';
            html += '<div style="font-weight:600;color:#333;margin-bottom:4px;">v' + escapeHtml(log.version || log.sha || '') + ' <span style="color:#999;font-size:11px;">' + escapeHtml(log.created_at ? log.created_at.substring(0, 10) : '') + '</span></div>';
            if (lines.length > 0) {
                lines.forEach(function(line) { line = line.trim(); if (line) html += '<div style="margin:2px 0;font-size:12px;color:#555;">' + escapeHtml(line) + '</div>'; });
            } else {
                html += '<div style="font-size:12px;color:#999;">暂无更新说明</div>';
            }
            html += '</div>';
        });
        html += '</div>';
        renderCheckChangelogPanel(data.logs);
    }

    html += '<div style="margin-top:10px;font-size:11px;color:#8b949e;">来源: ' + escapeHtml(sourceName) + '</div>';
    html += '<br>';
    html += '<button class="btn-primary" onclick="showUpdateConfirm()">立即更新</button> ';
    html += '<button onclick="saveVersion(\'' + escapeHtml(displayVer) + '\')">仅记录版本</button>';
    if (localHash) html += ' <button onclick="clearVersion()">清除记录</button>';

    _latestInfo = { hash: displayVer, short: displayVer };
    showResult('warning', html);
}

function updateLatestVersionDisplay(ver) {
    var el = document.getElementById('latestVersion');
    if (el) el.textContent = ver || '--';
}

function renderCheckChangelogPanel(logs) {
    if (!logs || logs.length === 0) return;
    if (!window._clStateChk) window._clStateChk = { allLogs: [], filter: 'all' };
    window._clStateChk.allLogs = logs;
    window._clStateChk.filter = 'all';
    applyCheckChangelogFilter();
}

function applyCheckChangelogFilter() {
    var state = window._clStateChk;
    if (!state) return;
    var filtered = state.allLogs;
    if (state.filter === 'live') filtered = filtered.filter(function(l) { return l.is_live; });
    if (state.filter === 'unpush') filtered = filtered.filter(function(l) { return !l.is_live; });

    var html = '';
    filtered.forEach(function(log) {
        var lines = log.changelog ? log.changelog.split('\n').filter(function(l) { return l.trim(); }) : [];
        html += '<div class="changelog-entry">';
        html += '<div><span class="ver">v' + escapeHtml(log.version || log.sha || '') + '</span><span class="time">' + escapeHtml(log.created_at ? log.created_at.substring(0, 10) : '') + '</span></div>';
        if (lines.length > 0) {
            lines.forEach(function(line) { html += '<div class="msg">' + escapeHtml(line) + '</div>'; });
        } else {
            html += '<div class="msg" style="color:#999;">暂无更新说明</div>';
        }
        html += '</div>';
    });

    var listEl = document.getElementById('changelogList');
    if (listEl) {
        if (html === '') {
            listEl.innerHTML = '<div class="changelog-loading">暂无符合条件的日志</div>';
        } else {
            listEl.innerHTML = html;
        }
    }
}

function showUpdateConfirm() {
    if (!confirm('更新可能覆盖修改过的文件，建议先备份数据库。确定要更新吗？')) return;
    doUpdate();
}

function doUpdate() {
     var modal = document.getElementById('progressModal');
     if (!modal) return;
     modal.style.display = 'flex';
     var ps = document.getElementById('progressStatus');
     if (ps) { ps.textContent = '准备中...'; ps.className = 'progress-status active'; }

     // 启动 loading 动画
     var dotsEl = document.getElementById('progressDots');
     var dotTimer = null;
     if (dotsEl) {
         var dotIdx = 0;
         dotTimer = setInterval(function() {
             dotIdx = (dotIdx + 1) % 4;
             dotsEl.textContent = '.'.repeat(dotIdx);
         }, 400);
     }

     var updateTimeout = setTimeout(function() {
         if (dotTimer) clearInterval(dotTimer);
         if (ps) ps.textContent = '更新超时，请重试';
         modal.style.display = 'none';
     }, 600000);

     var url = buildQuery('ajax_update.php?act=update&source=' + _currentSource, getAjaxParams());
     fetch(url, { method: 'POST' }).then(function(r) {
         if (!r.ok) throw new Error('HTTP ' + r.status);
         return r.json();
     }).then(function(data) {
         clearTimeout(updateTimeout);
         if (dotTimer) clearInterval(dotTimer);
         if (data.code === 0) {
             if (dotsEl) dotsEl.textContent = '';
             if (ps) { ps.className = 'progress-status success'; ps.textContent = '更新成功！正在刷新...'; }
             if (_latestInfo && _latestInfo.hash) {
                 localStorage.setItem(STORAGE_KEY, _latestInfo.hash);
                 loadLocalVersion();
             }
             setTimeout(function() {
                 if (modal) modal.style.display = 'none';
                 window.location.href = './update.php';
             }, 2000);
         } else {
             if (dotsEl) dotsEl.textContent = '';
             if (ps) { ps.className = 'progress-status error'; ps.textContent = '更新失败: ' + (data.msg || '未知错误'); }
         }
     }).catch(function(e) {
         clearTimeout(updateTimeout);
         if (dotTimer) clearInterval(dotTimer);
         if (dotsEl) dotsEl.textContent = '';
         if (ps) { ps.className = 'progress-status error'; ps.textContent = '无法连接到服务器'; }
     });
 }

function saveVersion() {
    var verEl = document.getElementById('latestVersion');
    var hash = verEl ? verEl.textContent : '';
    if (hash && hash !== '--' && hash !== '获取中...' && hash !== '获取失败') {
        if (_latestInfo && _latestInfo.hash) hash = _latestInfo.hash;
        localStorage.setItem(STORAGE_KEY, hash);
        loadLocalVersion();
    }
    showResult('success', '<strong>版本已保存</strong>');
}

function clearVersion() {
    localStorage.removeItem(STORAGE_KEY);
    loadLocalVersion();
    var rb = document.getElementById('resultBox');
    if (rb) rb.className = 'result-box';
}

function showResult(type, html) {
    var resultBox = document.getElementById('resultBox');
    if (!resultBox) return;
    resultBox.className = 'result-box show ' + type;
    resultBox.innerHTML = html;
}

function escapeHtml(s) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(s));
    return div.innerHTML;
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + 'B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + 'KB';
    return (bytes / 1048576).toFixed(1) + 'MB';
}

init();
</script>
<?php
$content = ob_get_clean();
include "./head.php";
?>