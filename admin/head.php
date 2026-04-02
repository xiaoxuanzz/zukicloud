<?php
@header("Content-Type: text/html; charset=UTF-8");
$currentPage = basename($_SERVER["SCRIPT_NAME"]);
$currentMod = isset($_GET["mod"]) ? $_GET["mod"] : "";
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title><?php echo $title ?></title>
  <link href="https://s4.zstatic.net/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://s4.zstatic.net/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"/>
  <link href="../assets/css/style.css?v=<?php echo VERSION ?>" rel="stylesheet"/>
  <link href="../assets/css/admin.css" rel="stylesheet"/>
  <script src="https://s4.zstatic.net/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
  <script src="https://s4.zstatic.net/ajax/libs/twitter-bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <style>
    .admin-sidebar select, .admin-sidebar .form-control { max-width: 100%; }
    .sidebar-nav .nav-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 5px 0; }
    
    /* Mobile menu toggle */
    .mobile-menu-toggle {
      display: none;
      position: fixed;
      top: 10px;
      right: 10px;
      left: auto;
      z-index: 10001;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: #fff;
      border: none;
      border-radius: 8px;
      width: 44px;
      height: 44px;
      font-size: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      cursor: pointer;
    }
    .mobile-menu-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 10000;
    }
    @media (max-width:767px) {
      .mobile-menu-toggle { display: flex; align-items: center; justify-content: center; }
      .admin-sidebar {
        position: fixed;
        top: 0;
        left: -260px;
        width: 260px;
        height: 100%;
        z-index: 10001;
        transition: left 0.3s ease;
        overflow-y: auto;
      }
      .admin-sidebar.open {
        left: 0;
      }
      .mobile-menu-overlay.open {
        display: block;
      }
      .admin-content {
        margin-left: 0 !important;
        padding: 10px;
        padding-top: 60px;
      }
    }
  </style>
</head>
<body class="admin-body">
<?php if($islogin==1){ ?>

<!-- Mobile menu toggle button -->
<button class="mobile-menu-toggle" onclick="toggleSidebar()">
  <i class="fa fa-bars"></i>
</button>
<div class="mobile-menu-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div style="display:flex;min-height:100vh;">
  <nav class="admin-sidebar" id="adminSidebar">
    <a class="sidebar-brand" href="https://space.bilibili.com/521205099" target="_blank">
      <span class="sb-icon"><i class="fa fa-cloud"></i></span><?php echo $conf["title"] ?>
    </a>
    <ul class="nav nav-pills nav-stacked sidebar-nav">
      <li class="<?php echo ($currentPage=="index.php") ? "active" : "" ?>"><a href="./"><i class="fa fa-tachometer"></i> 控制面板</a></li>
      <li class="<?php echo ($currentPage=="file.php") ? "active" : "" ?>"><a href="./file.php"><i class="fa fa-files-o"></i> 文件管理</a></li>
      <li class="<?php echo ($currentPage=="user.php") ? "active" : "" ?>"><a href="./user.php"><i class="fa fa-users"></i> 用户管理</a></li>
      <li class="nav-divider"></li>
      <li class="<?php echo ($currentPage=="set.php" && $currentMod=="site") ? "active" : "" ?>"><a href="./set.php?mod=site"><i class="fa fa-globe"></i> 网站信息</a></li>
      <li class="<?php echo ($currentPage=="set.php" && $currentMod=="upload") ? "active" : "" ?>"><a href="./set.php?mod=upload"><i class="fa fa-cloud-upload"></i> 上传设置</a></li>
      <li class="<?php echo ($currentPage=="set.php" && $currentMod=="user") ? "active" : "" ?>"><a href="./set.php?mod=user"><i class="fa fa-user-plus"></i> 用户设置</a></li>
      <li class="<?php echo ($currentPage=="set.php" && $currentMod=="admin") ? "active" : "" ?>"><a href="./set.php?mod=admin"><i class="fa fa-lock"></i> 管理员</a></li>
      <li class="<?php echo ($currentPage=="set_stor.php") ? "active" : "" ?>"><a href="./set_stor.php"><i class="fa fa-database"></i> 存储设置</a></li>
      <li class="nav-divider"></li>
      <li><a href="../" target="_blank"><i class="fa fa-external-link"></i> 前台首页</a></li>
      <li><a href="./index.php?logout=1"><i class="fa fa-sign-out"></i> 退出登录</a></li>
    </ul>
  </nav>
  <div class="admin-content">
<?php } else { ?>
  <nav class="navbar navbar-default navbar-static-top">
    <div class="container">
      <div class="navbar-header">
        <a class="navbar-brand brand-link" href="https://space.bilibili.com/521205099" target="_blank"><?php echo $title ?></a>
      </div>
    </div>
  </nav>
  <div class="container" style="padding-top:20px;">
<?php } ?>

<script>
function toggleSidebar() {
  document.getElementById("adminSidebar").classList.toggle("open");
  document.getElementById("sidebarOverlay").classList.toggle("open");
}
// Close sidebar when clicking a link on mobile
document.querySelectorAll("#adminSidebar a").forEach(function(a) {
  a.addEventListener("click", function() {
    if (window.innerWidth <= 767) {
      toggleSidebar();
    }
  });
});
</script>

<style>
/* 修复 Bootstrap 3 与自定义 Modal 的 CSS 冲突 */
#zk-modal.modal-overlay {
    position: fixed !important;
    top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
    background: rgba(0,0,0,0.5) !important;
    z-index: 999998 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    opacity: 0; visibility: hidden; pointer-events: none;
    transition: opacity .3s ease, visibility .3s ease;
}
#zk-modal.modal-overlay.show {
    opacity: 1 !important; visibility: visible !important; pointer-events: auto !important;
}
#zk-modal .modal {
    display: flex !important; flex-direction: column !important;
    align-self: center !important;
    background: #fff !important; border-radius: 16px !important;
    box-shadow: 0 20px 60px rgba(0,0,0,.2) !important;
    width: 360px !important; max-width: 90vw !important;
    margin: auto !important;
    transform: scale(.9) translateY(20px) !important;
    transition: all .3s ease;
}
#zk-modal.modal-overlay.show .modal { transform: scale(1) translateY(0) !important; pointer-events: auto !important; }
#zk-modal .modal .modal-header {
    display: flex !important; align-items: center !important; gap: 12px;
    padding: 20px 24px 0 !important;
}
#zk-modal .modal .modal-icon {
    width: 48px !important; height: 48px !important; border-radius: 50% !important;
    display: flex !important; align-items: center !important; justify-content: center !important;
    flex-shrink: 0;
}
#zk-modal .modal .modal-icon i { font-size: 22px !important; color: #fff !important; }
#zk-modal .modal .modal-icon.success { background: linear-gradient(135deg, #10b981, #059669) !important; }
#zk-modal .modal .modal-icon.error, #zk-modal .modal .modal-icon.danger { background: linear-gradient(135deg, #ef4444, #dc2626) !important; }
#zk-modal .modal .modal-icon.warning { background: linear-gradient(135deg, #f59e0b, #d97706) !important; }
#zk-modal .modal .modal-icon.info { background: linear-gradient(135deg, #6366f1, #4f46e5) !important; }
#zk-modal .modal h3 { font-size: 18px !important; font-weight: 700 !important; color: #1e293b !important; margin: 0 !important; }
#zk-modal .modal .modal-header-text p { font-size: 13px !important; color: #94a3b8 !important; margin: 0 !important; }
#zk-modal .modal .modal-body { padding: 16px 24px 24px !important; }
#zk-modal .modal .modal-body p { color: #64748b !important; font-size: 14px !important; }
#zk-modal .modal .modal-footer {
    display: flex !important; gap: 10px !important; justify-content: flex-end !important;
    padding: 0 24px 20px !important;
}
#zk-modal .modal .modal-footer .btn {
    padding: 10px 24px !important; border-radius: 10px !important;
    font-size: 14px !important; font-weight: 600 !important;
    border: none !important; cursor: pointer !important;
    pointer-events: auto !important;
}
#zk-modal .modal .modal-footer .btn-cancel { background: #f1f5f9 !important; color: #64748b !important; }
#zk-modal .modal .modal-footer .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626) !important; color: #fff !important; }
#zk-modal .modal .modal-footer .btn-primary { background: linear-gradient(135deg, #6366f1, #4f46e5) !important; color: #fff !important; }
#zk-modal .modal .modal-footer .btn-success { background: linear-gradient(135deg, #10b981, #059669) !important; color: #fff !important; }
</style>

<!-- Toast 通知容器 -->
<div id="toast-container" class="toast-container"></div>

<!-- 通用 Modal 弹窗 -->
<div id="zk-modal" class="modal-overlay" onclick="if(event.target===this)closeZkModal()">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon" id="zk-modal-icon"><i class="fa fa-info-circle"></i></div>
      <div class="modal-header-text">
        <h3 id="zk-modal-title"></h3>
        <p id="zk-modal-subtitle"></p>
      </div>
    </div>
    <div class="modal-body">
      <p id="zk-modal-body"></p>
    </div>
    <div class="modal-footer" id="zk-modal-footer"></div>
  </div>
</div>

<script>
// ===== Toast 通知（全局统一方案） =====
window._toastTimer = null;
function showToast(msg, type, hideDelay) {
    type = type || 'info';
    hideDelay = hideDelay || 5000;
    var old = document.getElementById('zk-toast');
    if (old) old.remove();
    if (window._toastTimer) { clearTimeout(window._toastTimer); window._toastTimer = null; }
    var container = document.getElementById('toast-container');
    if (!container) return;
    var icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
    var el = document.createElement('div');
    el.id = 'zk-toast';
    el.className = 'toast ' + type;
    el.style.cssText = 'pointer-events:auto;cursor:pointer;';
    el.innerHTML = '<i class="fa ' + (icons[type]||icons.info) + '"></i><span>' + msg + '</span>';
    el.onclick = function() { el.classList.add('toast-out'); setTimeout(function(){ el.remove(); }, 300); };
    container.appendChild(el);
    requestAnimationFrame(function(){ el.style.opacity = '1'; });
    window._toastTimer = setTimeout(function() {
        el.classList.add('toast-out');
        setTimeout(function(){ if (el.parentNode) el.remove(); }, 300);
    }, hideDelay);
}
var zkModalOnConfirm = null;
function showZkModal(opts) {
    console.log('[zkModal] showZkModal called', opts);
    var m = document.getElementById('zk-modal');
    if (!m) { console.error('[zkModal] #zk-modal not found!'); return; }
    var icon = document.getElementById('zk-modal-icon');
    var title = document.getElementById('zk-modal-title');
    var subtitle = document.getElementById('zk-modal-subtitle');
    var body = document.getElementById('zk-modal-body');
    var footer = document.getElementById('zk-modal-footer');
    if (!icon || !title || !footer) { console.error('[zkModal] modal child elements not found!'); return; }
    icon.className = 'modal-icon ' + (opts.icon || 'info');
    icon.innerHTML = '<i class="fa fa-' + (opts.icon === 'success' ? 'check' : opts.icon === 'error' || opts.icon === 'danger' ? 'times' : opts.icon === 'warning' ? 'exclamation' : 'info-circle') + '"></i>';
    title.textContent = opts.title || '';
    subtitle.textContent = opts.subtitle || '';
    body.textContent = opts.body || '';
    body.style.display = opts.body ? '' : 'none';
    footer.innerHTML = '';
    if (opts.buttons) {
        opts.buttons.forEach(function(btn) {
            var b = document.createElement('button');
            b.className = 'btn ' + (btn.class || 'btn-cancel');
            b.textContent = btn.text;
            b.onclick = function() { 
                console.log('[zkModal] button clicked:', btn.text, 'has onClick:', !!btn.onClick);
                closeZkModal(); 
                if (btn.onClick) { 
                    try { btn.onClick(); } catch(e) { console.error('[zkModal] onClick error:', e); }
                }
            };
            footer.appendChild(b);
        });
    }
    m.classList.add('show');
    document.body.style.overflow = 'hidden';
    console.log('[zkModal] modal shown');
}
function closeZkModal() {
    var m = document.getElementById('zk-modal');
    if (m) m.classList.remove('show');
    document.body.style.overflow = '';
    console.log('[zkModal] modal closed');
}
function zkConfirm(opts) {
    showZkModal({
        icon: opts.icon || 'warning', title: opts.title || '确认操作', subtitle: opts.subtitle || '', body: opts.body || '',
        buttons: [
            { text: opts.cancelText || '取消', class: 'btn-cancel' },
            { text: opts.confirmText || '确认', class: opts.confirmClass || 'btn-primary', onClick: opts.onConfirm }
        ]
    });
}
function zkAlert(opts) {
    showZkModal({
        icon: opts.icon || 'info', title: opts.title || '提示', subtitle: opts.subtitle || '', body: opts.body || '',
        buttons: [ { text: opts.btnText || '确定', class: opts.btnClass || 'btn-primary', onClick: opts.onConfirm } ]
    });
}
</script>
