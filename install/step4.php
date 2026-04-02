<?php
$prev = isset($_SESSION['install_admin']) ? $_SESSION['install_admin'] : null;
?>
<div class="section-title"><i class="fa fa-user-shield"></i> 管理员账号</div>

<div class="form-group">
    <label><i class="fa fa-user"></i> 管理员用户名</label>
    <input type="text" class="form-control" id="admin_user" value="<?php echo $prev ? htmlspecialchars($prev['user']) : ''; ?>" placeholder="请输入用户名" required>
</div>
<div class="form-group">
    <label><i class="fa fa-envelope"></i> 管理员邮箱</label>
    <input type="email" class="form-control" id="admin_email" value="<?php echo $prev ? htmlspecialchars($prev['email']) : ''; ?>" placeholder="请输入邮箱（选填）">
</div>
<div class="form-group">
    <label><i class="fa fa-lock"></i> 管理员密码</label>
    <input type="password" class="form-control" id="admin_pwd" placeholder="请输入密码（至少6位）" required>
</div>
<div class="form-group">
    <label><i class="fa fa-lock"></i> 确认密码</label>
    <input type="password" class="form-control" id="admin_pwd2" placeholder="请再次输入密码" required>
</div>

<div class="alert-custom alert-warning-custom">
    <i class="fa fa-info-circle"></i>
    <span>密码至少6位字符，安装后可在后台修改。</span>
</div>

<div class="btn-row">
    <a href="install.php?step=3" class="btn btn-prev"><i class="fa fa-arrow-left"></i> 上一步</a>
    <a href="javascript:void(0)" class="btn btn-next" id="btnStep4Next"><i class="fa fa-arrow-right"></i> 下一步</a>
</div>

<script>
$(function(){
    $('#btnStep4Next').click(function(){
        var u = $('#admin_user').val().trim();
        var p = $('#admin_pwd').val();
        var p2 = $('#admin_pwd2').val();
        if(!u){ window.install.showAlert('danger','请输入用户名'); return; }
        if(!p){ window.install.showAlert('danger','请输入密码'); return; }
        if(p.length < 6){ window.install.showAlert('danger','密码至少6位'); return; }
        if(p !== p2){ window.install.showAlert('danger','两次密码不一致'); return; }
        
        window.install.submitForm('save_admin', {
            admin_user: u,
            admin_email: $('#admin_email').val().trim(),
            admin_pwd: p,
            admin_pwd2: p2
        }, function(){
            window.location.href = 'install.php?step=5';
        });
    });
});
</script>
