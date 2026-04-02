<?php
/**
 * Zuki网盘系统安装向导
 */

// 已安装保护
if (file_exists('install.lock')) {
    exit('你已经成功安装，如需重新安装，请手动删除install目录下install.lock文件！');
}

session_start();
error_reporting(0);
date_default_timezone_set("PRC");

$step = isset($_GET['step']) ? max(1, min(6, intval($_GET['step']))) : 1;
$steps = [
    1 => '环境检测',
    2 => '数据库配置',
    3 => '系统设置',
    4 => '管理员账号',
    5 => '安装确认',
    6 => '安装结果',
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZuKizZ - 安装向导</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            position: relative;
            overflow-x: hidden;
        }
        /* 流体背景动画 */
        .fluid-bg {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            z-index: -1;
        }
        .fluid-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            animation: float 12s ease-in-out infinite;
        }
        .fluid-blob:nth-child(1) { width: 500px; height: 500px; background: rgba(102,126,234,0.5); top: -10%; left: -5%; animation-delay: 0s; }
        .fluid-blob:nth-child(2) { width: 600px; height: 600px; background: rgba(240,147,251,0.4); bottom: -15%; right: -10%; animation-delay: -4s; }
        .fluid-blob:nth-child(3) { width: 400px; height: 400px; background: rgba(67,233,123,0.3); top: 50%; left: 20%; animation-delay: -8s; }
        .fluid-blob:nth-child(4) { width: 350px; height: 350px; background: rgba(250,112,154,0.35); top: 15%; right: 20%; animation-delay: -6s; }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(40px, -30px) scale(1.05); }
            66% { transform: translate(-25px, 40px) scale(0.95); }
        }
        /* 卡片 */
        .install-card {
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 24px;
            padding: 0;
            width: 100%;
            max-width: 640px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            animation: cardIn 0.5s ease-out;
            overflow: hidden;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* 头部 */
        .card-top {
            background: rgba(255,255,255,0.08);
            padding: 32px 36px 24px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .card-top .logo-icon {
            width: 56px; height: 56px;
            background: rgba(255,255,255,0.15);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            border: 1px solid rgba(255,255,255,0.15);
        }
        .card-top .logo-icon i { font-size: 26px; color: #fff; }
        .card-top h1 { font-size: 22px; font-weight: 700; color: #fff; margin-bottom: 4px; }
        .card-top p { font-size: 14px; color: rgba(255,255,255,0.65); }

        /* 步骤条 */
        .steps-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 36px 0;
            gap: 0;
        }
        .step-item {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            border: 2px solid rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255,255,255,0.4);
            transition: all 0.3s;
            flex-shrink: 0;
        }
        .step-item.active { background: #fff; color: #764ba2; border-color: #fff; box-shadow: 0 0 16px rgba(255,255,255,0.3); }
        .step-item.done { background: rgba(255,255,255,0.25); color: #fff; border-color: rgba(255,255,255,0.4); }
        .step-line { width: 32px; height: 2px; background: rgba(255,255,255,0.15); flex-shrink: 0; transition: background 0.3s; }
        .step-line.done { background: rgba(255,255,255,0.4); }

        /* 内容区 */
        .card-body {
            padding: 28px 36px 32px;
        }
        .section-title {
            font-size: 17px;
            font-weight: 600;
            color: #fff;
            text-align: center;
            margin-bottom: 24px;
        }

        /* 表单 */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: rgba(255,255,255,0.8);
            margin-bottom: 6px;
        }
        .form-group label i { margin-right: 5px; width: 16px; text-align: center; }
        .form-control, .form-select {
            width: 100%;
            height: 44px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 10px;
            padding: 0 14px;
            font-size: 14px;
            color: #fff;
            outline: none;
            transition: all 0.3s;
        }
        .form-control::placeholder { color: rgba(255,255,255,0.35); }
        .form-control:focus, .form-select:focus {
            background: rgba(255,255,255,0.16);
            border-color: rgba(255,255,255,0.4);
            box-shadow: 0 0 0 3px rgba(255,255,255,0.08);
        }
        .form-select option { background: #4a3d8f; color: #fff; }
        .form-text { font-size: 12px; color: rgba(255,255,255,0.5); margin-top: 4px; }

        /* 按钮 */
        .btn-next, .btn-prev, .btn-install {
            height: 48px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
        }
        .btn-next, .btn-install {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.25);
            color: #fff;
            width: 100%;
        }
        .btn-next:hover, .btn-install:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        .btn-prev {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.7);
            width: 100%;
        }
        .btn-prev:hover { background: rgba(255,255,255,0.14); color: #fff; }
        .btn-row { display: flex; gap: 12px; margin-top: 4px; }
        .btn-row .btn-prev { flex: 0 0 auto; width: auto; padding: 0 24px; }
        .btn-row .btn-next, .btn-row .btn-install { flex: 1; }

        /* 检测项 */
        .check-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: rgba(255,255,255,0.06);
            border-radius: 10px;
            margin-bottom: 8px;
            font-size: 14px;
            color: rgba(255,255,255,0.85);
        }
        .check-item .badge-ok {
            color: #4ade80; font-size: 13px;
        }
        .check-item .badge-fail {
            color: #f87171; font-size: 13px;
        }
        .check-item .badge-warn {
            color: #fbbf24; font-size: 13px;
        }

        /* 按钮组 */
        .btn-group-custom { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }

        /* 提示框 */
        .alert-custom {
            border-radius: 10px;
            font-size: 13px;
            padding: 12px 16px;
            margin-bottom: 18px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert-custom i { margin-top: 2px; flex-shrink: 0; }
        .alert-success-custom { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: #86efac; }
        .alert-danger-custom { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; }
        .alert-warning-custom { background: rgba(251,191,36,0.15); border: 1px solid rgba(251,191,36,0.3); color: #fcd34d; }

        /* 加载动画 */
        .loading-overlay {
            display: none;
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4);
            border-radius: 24px;
            z-index: 10;
            align-items: center;
            justify-content: center;
        }
        .loading-overlay.show { display: flex; }
        .spinner {
            width: 48px; height: 48px;
            border: 4px solid rgba(255,255,255,0.2);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* 成功页 */
        .success-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        .success-title {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }
        .success-sub {
            font-size: 15px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 24px;
        }
        .info-card {
            background: rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 12px;
            text-align: left;
        }
        .info-card .label {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            margin-bottom: 4px;
        }
        .info-card .value {
            font-size: 15px;
            font-weight: 600;
            color: #fff;
        }

        .footer-text {
            text-align: center;
            font-size: 12px;
            color: rgba(255,255,255,0.35);
            padding: 16px 0 20px;
        }

        @media (max-width: 640px) {
            .card-body { padding: 20px 24px 24px; }
            .card-top { padding: 24px 24px 20px; }
            .steps-bar { padding: 20px 24px 0; }
        }
    </style>
</head>
<body>
    <div class="fluid-bg">
        <div class="fluid-blob"></div>
        <div class="fluid-blob"></div>
        <div class="fluid-blob"></div>
        <div class="fluid-blob"></div>
    </div>

    <div class="d-flex align-items-center justify-content-center min-vh-100 p-3">
        <div class="install-card" id="installCard">
            <div class="card-top">
                <div class="logo-icon"><i class="fa fa-cloud"></i></div>
                <h1>ZuKizZ</h1>
                <p>安装向导 - <?php echo $steps[$step]; ?></p>
            </div>

            <div class="steps-bar">
                <?php foreach ($steps as $i => $title): ?>
                    <?php if ($i > 1): ?><div class="step-line <?php echo $step > $i ? 'done' : ''; ?>"></div><?php endif; ?>
                    <div class="step-item <?php echo $step == $i ? 'active' : ($step > $i ? 'done' : ''); ?>">
                        <?php echo $step > $i ? '<i class="fa fa-check" style="font-size:11px"></i>' : $i; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card-body">
                <div class="loading-overlay" id="loadingOverlay">
                    <div class="spinner"></div>
                </div>
                <div id="stepContent">
                    <?php
                    $stepFile = 'install/step' . $step . '.php';
                    if (file_exists($stepFile)) {
                        include $stepFile;
                    } else {
                        echo '<div class="alert-custom alert-danger-custom"><i class="fa fa-exclamation-triangle"></i><span>步骤文件缺失：' . $stepFile . '</span></div>';
                    }
                    ?>
                </div>
            </div>

            <div class="footer-text">
                Powered by <strong>ZuKizZ</strong>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script>
    $(function(){
        window.install = {
            showLoading: function(){ $('#loadingOverlay').addClass('show'); },
            hideLoading: function(){ $('#loadingOverlay').removeClass('show'); },
            showAlert: function(type, msg){
                var icons = {success:'check-circle',danger:'times-circle',warning:'exclamation-triangle',info:'info-circle'};
                var cls = 'alert-' + type + '-custom';
                var html = '<div class="alert-custom ' + cls + '" style="animation:cardIn .3s"><i class="fa fa-' + icons[type] + '"></i><span>' + msg + '</span></div>';
                $('#stepContent').prepend(html);
                setTimeout(function(){ $('.alert-custom').first().fadeOut(300, function(){ $(this).remove(); }); }, 5000);
            },
            submitForm: function(action, data, callback){
                this.showLoading();
                $.ajax({
                    url: 'install/process.php?action=' + action,
                    method: 'POST',
                    data: data,
                    dataType: 'json',
                    timeout: 30000
                }).done(function(res){
                    window.install.hideLoading();
                    if(res.success){
                        if(res.redirect){
                            window.location.href = res.redirect;
                        } else if(callback){
                            callback(res);
                        }
                    } else {
                        window.install.showAlert('danger', res.message || '操作失败');
                    }
                }).fail(function(xhr){
                    window.install.hideLoading();
                    var msg = '网络错误，请重试';
                    try { var r = JSON.parse(xhr.responseText); if(r.message) msg = r.message; } catch(e){}
                    window.install.showAlert('danger', msg);
                });
            }
        };
    });
    </script>
</body>
</html>
