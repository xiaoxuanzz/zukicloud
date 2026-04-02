<?php
$prev = isset($_SESSION['install_settings']) ? $_SESSION['install_settings'] : null;
?>
<div class="section-title"><i class="fa fa-cog"></i> 系统设置</div>

<div class="form-group">
    <label><i class="fa fa-cloud"></i> 网盘名称</label>
    <input type="text" class="form-control" id="site_name" value="<?php echo $prev ? htmlspecialchars($prev['site_name']) : 'ZuKizZ'; ?>" placeholder="ZuKizZ">
</div>
<div class="form-group">
    <label><i class="fa fa-align-left"></i> 网盘简介</label>
    <input type="text" class="form-control" id="site_desc" value="<?php echo $prev ? htmlspecialchars($prev['site_desc']) : ''; ?>" placeholder="安全、可靠的云存储服务">
</div>
<div class="form-group">
    <label><i class="fa fa-hdd-o"></i> 存储方式</label>
    <select class="form-select" id="storage">
        <option value="local" <?php echo (!$prev || $prev['storage']=='local') ? 'selected' : ''; ?>>本地存储</option>
        <option value="aliyun" <?php echo ($prev['storage']??'')=='aliyun' ? 'selected' : ''; ?>>阿里云OSS</option>
        <option value="tencent" <?php echo ($prev['storage']??'')=='tencent' ? 'selected' : ''; ?>>腾讯云COS</option>
        <option value="qiniu" <?php echo ($prev['storage']??'')=='qiniu' ? 'selected' : ''; ?>>七牛云</option>
        <option value="upyun" <?php echo ($prev['storage']??'')=='upyun' ? 'selected' : ''; ?>>又拍云</option>
    </select>
    <div class="form-text">后续可在后台修改</div>
</div>
<div class="form-group">
    <label><i class="fa fa-upload"></i> 单文件最大上传大小（MB）</label>
    <select class="form-select" id="upload_size">
        <option value="10" <?php echo ($prev['upload_size']??0)==10 ? 'selected' : ''; ?>>10 MB</option>
        <option value="50" <?php echo ($prev['upload_size']??0)==50 ? 'selected' : ''; ?>>50 MB</option>
        <option value="100" <?php echo (!$prev || $prev['upload_size']==100) ? 'selected' : ''; ?>>100 MB</option>
        <option value="200" <?php echo ($prev['upload_size']??0)==200 ? 'selected' : ''; ?>>200 MB</option>
        <option value="500" <?php echo ($prev['upload_size']??0)==500 ? 'selected' : ''; ?>>500 MB</option>
        <option value="1024" <?php echo ($prev['upload_size']??0)==1024 ? 'selected' : ''; ?>>1 GB</option>
    </select>
</div>
<div class="form-group">
    <label><i class="fa fa-user-plus"></i> 开放注册</label>
    <select class="form-select" id="reg_open">
        <option value="1" <?php echo (!$prev || $prev['reg_open']==1) ? 'selected' : ''; ?>>开放</option>
        <option value="0" <?php echo ($prev['reg_open']??0)==0 ? 'selected' : ''; ?>>关闭</option>
    </select>
    <div class="form-text">关闭后只有管理员可以创建账号</div>
</div>

<div class="btn-row">
    <a href="install.php?step=2" class="btn btn-prev"><i class="fa fa-arrow-left"></i> 上一步</a>
    <a href="javascript:void(0)" class="btn btn-next" id="btnStep3Next"><i class="fa fa-arrow-right"></i> 下一步</a>
</div>

<script>
$(function(){
    $('#btnStep3Next').click(function(){
        window.install.submitForm('save_settings', {
            site_name: $('#site_name').val(),
            site_desc: $('#site_desc').val(),
            storage: $('#storage').val(),
            upload_size: $('#upload_size').val(),
            reg_open: $('#reg_open').val()
        }, function(){
            window.location.href = 'install.php?step=4';
        });
    });
});
</script>
