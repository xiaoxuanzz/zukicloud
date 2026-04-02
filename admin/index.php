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

include "./head.php";
if($islogin != 1) exit("<script>window.location.href=\"./login.php\";</script>");

$mysqlversion = $DB->getColumn("select VERSION()");
$totalFiles = $DB->getColumn("SELECT COUNT(*) FROM pre_file");
$todayFiles = $DB->getColumn("SELECT COUNT(*) FROM pre_file WHERE DATE(addtime) = CURDATE()");
$totalUsers = $DB->getColumn("SELECT COUNT(*) FROM pre_user");
$totalSize = $DB->getColumn("SELECT COALESCE(SUM(size),0) FROM pre_file");
$totalDownloads = $DB->getColumn("SELECT COALESCE(SUM(count),0) FROM pre_file");

?>

<div class="row" style="margin-bottom:20px;">
    <div class="col-sm-6 col-md-3 col-xs-6" style="margin-bottom:14px;">
        <div class="stat-card">
            <div class="stat-icon cyan"><i class="fa fa-file-o"></i></div>
            <div class="stat-num" id="count1"><?php echo $totalFiles ?></div>
            <div class="stat-label">文件总数</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3 col-xs-6" style="margin-bottom:14px;">
        <div class="stat-card">
            <div class="stat-icon pink"><i class="fa fa-cloud-upload"></i></div>
            <div class="stat-num" id="count2"><?php echo $todayFiles ?></div>
            <div class="stat-label">今日上传</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3 col-xs-6" style="margin-bottom:14px;">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa fa-download"></i></div>
            <div class="stat-num"><?php echo $totalDownloads ?></div>
            <div class="stat-label">下载次数</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3 col-xs-6" style="margin-bottom:14px;">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fa fa-users"></i></div>
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
                    <tr><td style="color:var(--zk-text-sub);">程序版本</td><td>ZuKizZ云存储 v<?php echo VERSION ?></td></tr>
                    <tr><td style="color:var(--zk-text-sub);">服务器时间</td><td><?php echo $date ?></td></tr>
                    <tr><td style="color:var(--zk-text-sub);">上传限制</td><td><?php echo $conf["upload_size"] > 0 ? round($conf["upload_size"]/1024, 1) . " GB" : "无限制" ?></td></tr>
                    <tr><td style="color:var(--zk-text-sub);">存储用量</td><td><?php echo formatBytes($totalSize) ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-5 col-xs-12">
        <div class="well" style="padding:18px;">
            <h5 style="margin-bottom:14px;color:var(--zk-primary);"><i class="fa fa-bolt"></i> 快捷操作</h5>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <a href="./file.php" class="btn btn-primary btn-raised" style="flex:1;min-width:110px;"><i class="fa fa-files-o"></i> 文件</a>
                <a href="./user.php" class="btn btn-primary btn-raised" style="flex:1;min-width:110px;"><i class="fa fa-users"></i> 用户</a>
                <a href="./set.php" class="btn btn-primary btn-raised" style="flex:1;min-width:110px;"><i class="fa fa-cog"></i> 设置</a>
                <a href="./set_stor.php" class="btn btn-primary btn-raised" style="flex:1;min-width:110px;"><i class="fa fa-database"></i> 存储</a>
                <a href="../" target="_blank" class="btn btn-primary btn-raised" style="flex:1;min-width:110px;text-align:center;"><i class="fa fa-external-link"></i> 前台</a>
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
});
</script>
  </div>
</div>
</body>
</html>