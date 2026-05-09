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
    
    // 自动更新检测
    if (localStorage.getItem('zuki_auto_update') === '1') {
        var giteeRepo = 'xiaoxuanzz/zukicloud';
        var branch = 'main';
        var gApiUrl = 'https://gitee.com/api/v5/repos/' + giteeRepo + '/commits?sha=' + encodeURIComponent(branch) + '&per_page=1';
        fetch(gApiUrl, { headers: { 'Accept': 'application/json' } })
            .then(function(r) { if (!r.ok) throw new Error(); return r.json(); })
            .then(function(data) {
                if (Array.isArray(data) && data.length > 0) {
                    var c = data[0];
                    var localHash = localStorage.getItem('zuki_update_hash') || '';
                    if (localHash !== c.sha) {
                        var shortHash = c.sha.substring(0, 7);
                        var msg = c.commit.message.split('\n')[0];
                        showUpdateModal(shortHash, msg);
                    }
                }
            })
            .catch(function() {});
    }
    
    function showUpdateModal(newVersion, newMsg) {
        var localVersion = localStorage.getItem('zuki_update_hash') || '无记录';
        var localShort = localVersion.substring ? localVersion.substring(0, 7) : localVersion;
        
        var modalHtml = '<div id="zk-update-modal" style="position:fixed;inset:0;background:rgba(0,0,0,0);z-index:999999;display:flex;align-items:center;justify-content:center;">' +
            '<div style="background:var(--zk-surface,#fff);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.25);width:90%;max-width:420px;transform:scale(.9);transition:all .3s;">' +
            '<div style="padding:24px 24px 0;">' +
            '<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">' +
            '<div style="width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:var(--zk-primary);box-shadow:0 4px 12px rgba(0,0,0,.2);">' +
            '<i class="fa fa-cloud-download" style="font-size:22px;color:#fff;"></i></div>' +
            '<div><h3 style="font-size:18px;font-weight:700;color:var(--zk-text,#1e293b);margin:0 0 4px;">发现新版本</h3>' +
            '<p style="font-size:13px;color:var(--zk-text-dim,#94a3b8);margin:0;">是否立即更新？</p></div></div>' +
            '<div style="background:var(--zk-bg,#f8fafc);border-radius:10px;padding:12px 16px;margin-bottom:16px;">' +
            '<div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span style="color:var(--zk-text-sub,#64748b);font-size:13px;">本地版本</span><code style="font-size:13px;">' + esc(localShort) + '</code></div>' +
            '<div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span style="color:var(--zk-text-sub,#64748b);font-size:13px;">最新版本</span><code style="font-size:13px;color:var(--zk-primary,#5b8def);">' + esc(newVersion) + '</code></div>' +
            '<div style="display:flex;gap:4px 8px;flex-wrap:wrap;"><span style="color:var(--zk-text-sub,#64748b);font-size:12px;">提交:</span><span style="font-size:12px;color:var(--zk-text,#1e293b);word-break:break-all;">' + esc(newMsg.substring(0, 50)) + '</span></div></div></div>' +
            '<div style="display:flex;gap:10px;padding:0 24px 24px;">' +
            '<button onclick="closeUpdateModal()" style="flex:1;padding:12px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;background:var(--zk-bg,#f5f7fa);color:var(--zk-text-sub,#64748b);">取消</button>' +
            '<button onclick="confirmUpdate()" style="flex:1;padding:12px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;background:var(--zk-primary);color:#fff;">立即更新</button></div></div></div>';
        
        var modal = document.createElement('div');
        modal.innerHTML = modalHtml;
        document.body.appendChild(modal);
        
        setTimeout(function() {
            document.getElementById('zk-update-modal').style.background = 'rgba(0,0,0,0.5)';
            document.getElementById('zk-update-modal').querySelector('div').style.transform = 'scale(1)';
        }, 10);
    }
    
    window.closeUpdateModal = function() {
        var modal = document.getElementById('zk-update-modal');
        if (modal) {
            modal.style.background = 'rgba(0,0,0,0)';
            modal.querySelector('div').style.transform = 'scale(.9)';
            setTimeout(function() { modal.remove(); }, 200);
        }
    };
    
    window.confirmUpdate = function() {
        closeUpdateModal();
        window.location.href = './update.php';
    };
    
    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
});
</script>
<?php
$content = ob_get_clean();
include "./head.php";
?>