<?php
$prevSettings = isset($_SESSION['install_settings']) ? $_SESSION['install_settings'] : null;
?>
<div class="section-title"><i class="fa fa-database"></i> 数据库配置</div>

<div class="alert-custom alert-warning-custom" style="font-size:13px;">
    <i class="fa fa-info-circle"></i>
    <span>请确保MySQL数据库已创建。如未创建，系统将尝试自动创建。</span>
</div>

<div class="form-group">
    <label><i class="fa fa-server"></i> 数据库地址</label>
    <input type="text" class="form-control" id="db_host" value="127.0.0.1" placeholder="localhost 或 127.0.0.1">
</div>
<div class="form-group">
    <label><i class="fa fa-plug"></i> 数据库端口</label>
    <input type="text" class="form-control" id="db_port" value="3306" placeholder="默认：3306">
</div>
<div class="form-group">
    <label><i class="fa fa-database"></i> 数据库名称</label>
    <input type="text" class="form-control" id="db_name" value="zukizz" placeholder="请输入数据库名称">
</div>
<div class="form-group">
    <label><i class="fa fa-user"></i> 数据库用户名</label>
    <input type="text" class="form-control" id="db_user" placeholder="请输入用户名" required>
</div>
<div class="form-group">
    <label><i class="fa fa-key"></i> 数据库密码</label>
    <input type="password" class="form-control" id="db_pwd" placeholder="请输入密码">
</div>

<button type="button" class="btn btn-next" id="btnTestDb" style="margin-bottom:16px">
    <i class="fa fa-plug"></i> 测试连接
</button>

<div id="dbResult" style="display:none"></div>

<div class="btn-row">
    <a href="install.php?step=1" class="btn btn-prev"><i class="fa fa-arrow-left"></i> 上一步</a>
    <a href="javascript:void(0)" class="btn btn-next" id="btnStep2Next"><i class="fa fa-arrow-right"></i> 下一步</a>
</div>

<script>
$(function(){
    $('#btnTestDb').click(function(){
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> 测试中...');
        window.install.showLoading();
        $.ajax({
            url: 'install/process.php?action=test_db',
            method: 'POST',
            data: {
                host: $('#db_host').val(),
                port: $('#db_port').val(),
                user: $('#db_user').val(),
                pwd: $('#db_pwd').val(),
                dbname: $('#db_name').val()
            },
            dataType: 'json',
            timeout: 15000
        }).done(function(res){
            window.install.hideLoading();
            if(res.success){
                $('#dbResult').html('<div class="alert-custom alert-success-custom"><i class="fa fa-check-circle"></i><span>' + res.message + '</span></div>').show();
            } else {
                $('#dbResult').html('<div class="alert-custom alert-danger-custom"><i class="fa fa-times-circle"></i><span>' + res.message + '</span></div>').show();
            }
        }).fail(function(){
            window.install.hideLoading();
            $('#dbResult').html('<div class="alert-custom alert-danger-custom"><i class="fa fa-times-circle"></i><span>网络错误</span></div>').show();
        });
        btn.prop('disabled', false).html('<i class="fa fa-plug"></i> 测试连接');
    });

    $('#btnStep2Next').click(function(){
        if(!$('#db_user').val()){
            window.install.showAlert('danger','请填写数据库用户名');
            return;
        }
        // 保存数据到session
        window.install.submitForm('test_db', {
            host: $('#db_host').val(),
            port: $('#db_port').val(),
            user: $('#db_user').val(),
            pwd: $('#db_pwd').val(),
            dbname: $('#db_name').val()
        }, function(){
            window.location.href = 'install.php?step=3';
        });
    });
});
</script>
