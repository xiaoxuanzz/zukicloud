<?php
$db = isset($_SESSION['install_db']) ? $_SESSION['install_db'] : null;
$settings = isset($_SESSION['install_settings']) ? $_SESSION['install_settings'] : null;
$admin = isset($_SESSION['install_admin']) ? $_SESSION['install_admin'] : null;

if (!$db || !$admin) {
    echo '<div class="alert-custom alert-danger-custom"><i class="fa fa-exclamation-triangle"></i><span>缺少配置信息，请返回重新填写。</span></div>';
    echo '<div class="btn-row"><a href="install.php?step=2" class="btn btn-prev"><i class="fa fa-arrow-left"></i> 返回</a></div>';
    exit;
}
?>
<div class="section-title"><i class="fa fa-check-circle"></i> 确认安装信息</div>

<div class="info-card" style="background:rgba(255,255,255,0.06);border-radius:12px;padding:16px 20px;margin-bottom:16px;">
    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
        <span style="color:rgba(255,255,255,0.5);font-size:13px;"><i class="fa fa-server"></i> 数据库</span>
        <span style="color:#fff;font-size:14px;"><?php echo htmlspecialchars($db['host']); ?>:<?php echo $db['port']; ?></span>
    </div>
    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
        <span style="color:rgba(255,255,255,0.5);font-size:13px;"><i class="fa fa-database"></i> 数据库名</span>
        <span style="color:#fff;font-size:14px;"><?php echo htmlspecialchars($db['dbname']); ?></span>
    </div>
    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
        <span style="color:rgba(255,255,255,0.5);font-size:13px;"><i class="fa fa-user"></i> 管理员</span>
        <span style="color:#fff;font-size:14px;"><?php echo htmlspecialchars($admin['user']); ?></span>
    </div>
    <?php if ($settings): ?>
    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
        <span style="color:rgba(255,255,255,0.5);font-size:13px;"><i class="fa fa-cloud"></i> 网盘名称</span>
        <span style="color:#fff;font-size:14px;"><?php echo htmlspecialchars($settings['site_name']); ?></span>
    </div>
    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
        <span style="color:rgba(255,255,255,0.5);font-size:13px;"><i class="fa fa-hdd-o"></i> 存储方式</span>
        <span style="color:#fff;font-size:14px;"><?php
            $storageNames = ['local'=>'本地存储','aliyun'=>'阿里云OSS','tencent'=>'腾讯云COS','qiniu'=>'七牛云','upyun'=>'又拍云'];
            echo $storageNames[$settings['storage']] ?? $settings['storage'];
        ?></span>
    </div>
    <div style="display:flex;justify-content:space-between;">
        <span style="color:rgba(255,255,255,0.5);font-size:13px;"><i class="fa fa-upload"></i> 最大上传</span>
        <span style="color:#fff;font-size:14px;"><?php echo $settings['upload_size']; ?> MB</span>
    </div>
    <?php endif; ?>
</div>

<div class="alert-custom alert-warning-custom">
    <i class="fa fa-exclamation-triangle"></i>
    <span>点击"开始安装"后将执行数据库初始化，已存在的数据表可能会被覆盖。</span>
</div>

<div class="btn-row">
    <a href="install.php?step=4" class="btn btn-prev"><i class="fa fa-arrow-left"></i> 上一步</a>
    <a href="javascript:void(0)" class="btn btn-install" id="btnInstall"><i class="fa fa-play"></i> 开始安装</a>
</div>

<script>
$(function(){
    $('#btnInstall').click(function(){
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> 安装中，请勿关闭...');
        window.install.submitForm('install', {}, function(res){
            window.location.href = 'install.php?step=6';
        });
    });
});
</script>
