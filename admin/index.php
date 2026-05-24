<?php
define("IN_ADMIN", true);
include("../includes/common.php");
$title = "ZuKizZ云存储 · 管理中心";
// Handle admin logout
if(isset($_GET["logout"])){
    setcookie("admin_token", "", time()-1, "/");
    setcookie("PHPSESSID", "", time()-1, "/");
    session_unset();
    session_destroy();
    exit("<script>window.location.href=\"./login.php\";</script>");
}

if($islogin != 1) exit("<script>window.location.href=\"./login.php\";</script>");

$mysqlversion = $DB->getColumn("select VERSION()");
$totalFiles = $DB->getColumn("SELECT COUNT(*) FROM pre_file");
$todayFiles = $DB->getColumn("SELECT COUNT(*) FROM pre_file WHERE DATE(addtime) = CURDATE()");
$totalUsers = $DB->getColumn("SELECT COUNT(*) FROM pre_user");
$totalSize = $DB->getColumn("SELECT COALESCE(SUM(size),0) FROM pre_file");
$totalDownloads = $DB->getColumn("SELECT COALESCE(SUM(count),0) FROM pre_file");

ob_start();
?>
<div class="row" style="margin-bottom:20px;">
    <div class="col-sm-6 col-md-3 col-xs-6" style="margin-bottom:14px;">
        <div class="stat-card">
            <div class="stat-num" id="count1"><?php echo $totalFiles ?></div>
            <div class="stat-label">文件总数</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3 col-xs-6" style="margin-bottom:14px;">
        <div class="stat-card">
            <div class="stat-num" id="count2"><?php echo $todayFiles ?></div>
            <div class="stat-label">今日上传</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3 col-xs-6" style="margin-bottom:14px;">
        <div class="stat-card">
            <div class="stat-num"><?php echo $totalDownloads ?></div>
            <div class="stat-label">下载次数</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3 col-xs-6" style="margin-bottom:14px;">
        <div class="stat-card">
            <div class="stat-num" id="count4"><?php echo $totalUsers ?></div>
            <div class="stat-label">用户总数</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7 col-xs-12">
        <div class="well" style="padding:0;">
            <table class="table" style="margin-bottom:0;">
                <thead><tr><th colspan="2" style="color:var(--zk-primary);font-weight:700;border:none;font-size:14px;">系统信息</th></tr></thead>
                <tbody>
                    <tr><td style="width:130px;color:var(--zk-text-sub);">PHP 版本</td><td><?php echo phpversion() ?></td></tr>
                    <tr><td style="color:var(--zk-text-sub);">MySQL 版本</td><td><?php echo $mysqlversion ?></td></tr>
                    <tr><td style="color:var(--zk-text-sub);">WEB 软件</td><td><?php echo $_SERVER["SERVER_SOFTWARE"] ?></td></tr>
                    <tr><td style="color:var(--zk-text-sub);">程序版本</td><td>ZuKizZ云存储 <?php echo VERSION ?></td></tr>
                    <tr><td style="color:var(--zk-text-sub);">服务器时间</td><td><?php echo $date ?></td></tr>
                    <tr><td style="color:var(--zk-text-sub);">上传限制</td><td><?php echo $conf["upload_size"] > 0 ? round($conf["upload_size"]/1024, 1) . " GB" : "无限制" ?></td></tr>
                    <tr><td style="color:var(--zk-text-sub);">存储用量</td><td><?php echo formatBytes($totalSize) ?></td></tr>
                    <tr><td style="color:var(--zk-text-sub);">联系作者</td><td><a href="https://space.bilibili.com/521205099" target="_blank" style="color:var(--zk-primary);text-decoration:none;">ZuKizZ</a></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-5 col-xs-12">
        <div class="well" style="padding:18px;">
            <h5 style="margin-bottom:14px;color:var(--zk-primary);">快捷操作</h5>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <a href="./file.php" class="btn btn-primary btn-raised" style="flex:1;min-width:110px;">文件</a>
                <a href="./user.php" class="btn btn-primary btn-raised" style="flex:1;min-width:110px;">用户</a>
                <a href="./set_stor.php" class="btn btn-primary btn-raised" style="flex:1;min-width:110px;">存储</a>
                <a href="../" target="_blank" class="btn btn-primary btn-raised" style="flex:1;min-width:110px;text-align:center;">前台</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $.ajax({type:"GET",url:"ajax.php?act=getcount",dataType:"json",success:function(d){
        if(d.count1)$("#count1").html(d.count1);
        if(d.count2)$("#count2").html(d.count2);
        if(d.count4)$("#count4").html(d.count4);
    }});

    // ── 自动更新检测（由页面底部独立检测模块负责，此处不再重复触发）──
    // 保留 closeUpdateModal2 / goUpdate 供 update.php 跳转返回时使用
    window.closeUpdateModal2 = function() {
        var m = document.getElementById('zk-update-modal');
        if (m) m.remove();
    };
    window.goUpdate = function() {
        window.location.href = './update.php';
    };
});

// ── 独立检测模块（XMLHttpRequest，不依赖 jQuery，兼容所有浏览器）──
(function(){
    function doCheck() {
        var xhr = new XMLHttpRequest();
        var source = 'auto';
        try { source = localStorage.getItem('zuki_update_source') || 'auto'; } catch(e) {}
        var params = 'act=check&source=' + encodeURIComponent(source);
        try {
            var uhubUrl = localStorage.getItem('zuki_uhub_url');
            var ghRepo  = localStorage.getItem('zuki_gh_repo');
            if (uhubUrl) params += '&uhub_url=' + encodeURIComponent(uhubUrl);
            if (ghRepo)  params += '&gh_repo='  + encodeURIComponent(ghRepo);
        } catch(e) {}
        xhr.open('GET', 'ajax_update.php?' + params, true);
        xhr.onload = function() {
            if (xhr.status !== 200) return;
            var data;
            try { data = JSON.parse(xhr.responseText); } catch(e) { return; }
            if (data.code !== 0) return;
            var info = data.data || data;
            var remoteHash = info.hash || info.version || info.sha || '';
            if (!remoteHash) return;
            var localHash = '';
            try { localHash = localStorage.getItem('zuki_update_hash') || ''; } catch(e) {}
            var remoteVer = (info.version || info.tag || remoteHash).replace(/^v/i, '');
            if (localHash && (localHash === remoteHash || localHash === remoteVer)) return;

            showModal(info, data.source || source);
        };
        xhr.send();
    }

    function showModal(info, source) {
        var version = (info.version || info.tag || '').replace(/^v/i, '');
        if (!version && info.sha) version = info.sha.substring(0, 7);
        var ov = document.createElement('div');
        ov.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;z-index:999999;display:flex;align-items:center;justify-content:center;';
        var ch = '';
        if (info.changelog) {
            ch += '<div style="background:#f8fafc;border-radius:10px;padding:12px 16px;margin-bottom:8px;font-size:13px;color:#555;max-height:200px;overflow-y:auto;">';
            ch += '<div style="font-size:12px;font-weight:600;color:#1e293b;margin-bottom:6px;">更新内容</div>';
            var lines = info.changelog.split('\n');
            for (var i = 0; i < lines.length; i++) {
                var line = lines[i].trim();
                if (!line) continue;
                if (line.match(/^[#*\-]\s/)) line = line.replace(/^[#*\-]\s/, '');
                ch += '<div style="padding:2px 0;padding-left:12px;position:relative;"><span style="position:absolute;left:0;top:8px;width:5px;height:5px;border-radius:50%;background:#5b8def;"></span>' + escHtml(line) + '</div>';
                if (i > 19) { ch += '<div style="color:#94a3b8;font-size:11px;padding-top:4px;">...</div>'; break; }
            }
            ch += '</div>';
        }
        ov.innerHTML = '<div style="background:rgba(0,0,0,0.45);position:absolute;inset:0;z-index:-1;"></div>' +
            '<div style="background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.25);width:90%;max-width:380px;overflow:hidden;">' +
            '<div style="padding:20px 24px 24px;">' +
            '<div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">' +
            '<div style="width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#5b8def;"><i class="fa fa-cloud-download" style="font-size:18px;color:#fff;"></i></div>' +
            '<div><h3 style="font-size:17px;font-weight:700;color:#1e293b;margin:0 0 2px;">发现新版本</h3>' +
            '<p style="font-size:12px;color:#94a3b8;margin:0;">有可用的版本更新</p></div></div>' +
            '<div style="text-align:center;padding:8px 0 12px;">' +
            '<div style="font-size:12px;color:#94a3b8;">最新版本</div>' +
            '<div style="font-size:28px;font-weight:800;color:#5b8def;letter-spacing:1px;">v' + escHtml(version) + '</div></div>' +
            ch +
            '<div style="text-align:center;font-size:11px;color:#8b949e;padding:4px 0;">来源: ' + escHtml(source) + '</div>' +
            '<div style="display:flex;gap:10px;margin-top:14px;">' +
            '<button onclick="window.location.href=\'./update.php\';this.closest(\'#zk-modal-fallback\').remove()" style="flex:1;padding:12px 0;border-radius:10px;font-size:15px;font-weight:700;border:none;cursor:pointer;background:#5b8def;color:#fff;">立即更新</button>' +
            '<button onclick="this.closest(\'#zk-modal-fallback\').remove()" style="flex:1;padding:12px 0;border-radius:10px;font-size:15px;font-weight:600;border:1px solid #ddd;cursor:pointer;background:#f5f7fa;color:#64748b;">稍后更新</button></div></div></div>';
        ov.id = 'zk-modal-fallback';
        document.body.appendChild(ov);
    }

    function escHtml(s) {
        if (typeof s !== 'string') return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', doCheck);
    } else {
        doCheck();
    }
})();
</script>
<?php
$content = ob_get_clean();
include "./head.php";
?>