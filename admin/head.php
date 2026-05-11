<?php
define("IN_ADMIN", true);
include("../includes/common.php");
$title = isset($title) ? $title : 'ZuKizZ云存储';
if (!isset($currentPage)) {
  $currentPage = basename($_SERVER["SCRIPT_NAME"]);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title><?php echo $title; ?></title>
  <link href="https://s4.zstatic.net/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://s4.zstatic.net/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"/>
  <link href="../assets/css/style.css?v=<?php echo VERSION ?>" rel="stylesheet"/>
  <link href="../assets/css/admin.css" rel="stylesheet"/>
  <script src="https://s4.zstatic.net/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
  <script src="https://s4.zstatic.net/ajax/libs/twitter-bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <style>
    .toast.info { background: var(--zk-primary); }
    .toast.success { background: #10b981; }
    .toast.warning { background: #f59e0b; }
    .toast.error { background: #ef4444; }
    .sidebar-nav .nav-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 5px 0; }
    @media (max-width:767px) {
      .admin-content { padding: 10px; padding-top: 60px; }
    }
    .admin-right-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 9998; }
    .admin-right-panel { position: fixed; top: 0; right: 0; width: 420px; height: 100%; transform: translateX(100%); transition: transform .25s ease; background: #fff; z-index: 9999; overflow: auto; box-shadow: -2px 0 8px rgba(0,0,0,0.15); }
    .admin-right-panel.open { transform: translateX(0); }
    @media (max-width:767px) {
      .admin-right-panel { width: 100%; height: 60vh; top: auto; bottom: 0; transform: translateY(100%); }
      .admin-right-panel.open { transform: translateY(0); }
    }
  </style>
  <script>
  // 立即应用前台设置的主题（避免闪烁）
  (function() {
    var savedTheme = localStorage.getItem('theme-color');
    if(savedTheme === 'custom'){
      var customColor = localStorage.getItem('theme-custom-color');
      if(customColor){
        document.documentElement.style.setProperty('--zk-primary', customColor);
        document.documentElement.style.setProperty('--zk-gradient', customColor);
      }
    } else if(savedTheme === 'custom-gradient'){
      var startColor = localStorage.getItem('theme-gradient-start');
      var endColor = localStorage.getItem('theme-gradient-end');
      if(startColor && endColor){
        var gradient = 'linear-gradient(135deg, ' + startColor + ', ' + endColor + ')';
        document.documentElement.style.setProperty('--zk-primary', startColor);
        document.documentElement.style.setProperty('--zk-gradient', gradient);
      }
    } else if(savedTheme && savedTheme !== 'default'){
      // 等待body加载后添加class
      document.addEventListener('DOMContentLoaded', function() {
        if(document.body) {
          document.body.classList.add(savedTheme);
        }
      });
    }
  })();
  // ===== Toast 通知（后台版）=====
  window._toastTimer = null;
  function showToast(msg, type, hideDelay) {
      type = type || 'info';
      hideDelay = hideDelay || 5000;
      // 创建容器（如果不存在）
      var container = document.getElementById('toast-container');
      if (!container) {
          container = document.createElement('div');
          container.id = 'toast-container';
          container.className = 'toast-container';
          document.body.appendChild(container);
      }
      // 清除旧的
      var old = document.getElementById('zk-toast');
      if (old) old.remove();
      if (window._toastTimer) { clearTimeout(window._toastTimer); window._toastTimer = null; }
      var icons = { success: 'fa-check-circle', error: 'fa-times-circle', danger: 'fa-times-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
      var el = document.createElement('div');
      el.id = 'zk-toast';
      el.className = 'toast ' + type;
      el.style.cssText = 'pointer-events:auto;cursor:pointer;';
      el.innerHTML = '<i class="fa ' + (icons[type]||icons.info) + '"></i><span>' + msg + '</span>';
      el.onclick = function() { el.classList.add('toast-out'); setTimeout(function(){ el.remove(); }, 300); };
      container.appendChild(el);
      // 动画启动
      requestAnimationFrame(function(){ el.style.opacity = '1'; });
      window._toastTimer = setTimeout(function() {
          el.classList.add('toast-out');
          setTimeout(function(){ if (el.parentNode) el.remove(); }, 300);
      }, hideDelay);
  }
  // ===== 确认框（后台版）=====
  function zkConfirm(opts) {
      var icon = opts.icon || 'warning';
      var title = opts.title || '确认操作';
      var subtitle = opts.subtitle || '';
      var confirmText = opts.confirmText || '确认';
      var cancelText = opts.cancelText || '取消';
      var confirmClass = opts.confirmClass || 'btn-primary';
      var onConfirm = opts.onConfirm || function(){};
      
      var modalHtml = '<div id="zk-confirm-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999998;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .3s;" onclick="closeZkConfirm()"><div style="background:var(--zk-surface,#fff);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:90%;max-width:420px;transform:scale(.9) translateY(20px);transition:all .3s;" onclick="event.stopPropagation()"><div style="display:flex;align-items:center;gap:12px;padding:20px 24px 0;"><div style="width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:' + (icon==='danger'?'#ef4444':'#f59e0b') + ';box-shadow:0 4px 12px rgba(0,0,0,.2);"><i class="fa fa-' + (icon==='danger'?'times':'exclamation-triangle') + '" style="font-size:22px;color:#fff;"></i></div><div><h3 style="font-size:18px;font-weight:700;color:var(--zk-text,#1e293b);margin:0 0 2px;">' + title + '</h3><p style="font-size:13px;color:var(--zk-text-dim,#94a3b8);margin:0;">' + subtitle + '</p></div></div><div style="display:flex;gap:10px;justify-content:flex-end;padding:16px 24px 20px;"><button onclick="closeZkConfirm()" style="padding:10px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;background:var(--zk-bg,#f5f7fa);color:var(--zk-text-sub,#64748b);">'+cancelText+'</button><button id="zk-confirm-ok" style="padding:10px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;' + (confirmClass==='btn-danger'?'background:#ef4444;color:#fff;':'background:var(--zk-primary,#5b8def);color:#fff;') + '">' + confirmText + '</button></div></div></div>';
      
      var overlay = document.createElement('div');
      overlay.id = 'zk-confirm-overlay';
      overlay.innerHTML = modalHtml;
      document.body.appendChild(overlay);
      
      // 显示动画
      setTimeout(function(){
          overlay.style.opacity = '1';
          overlay.querySelector('div').style.transform = 'scale(1) translateY(0)';
      }, 10);
      
      // 确认按钮
      document.getElementById('zk-confirm-ok').onclick = function() {
          closeZkConfirm();
          onConfirm();
      };
      
      // 点击遮罩关闭
      overlay.onclick = function(e) {
          if(e.target === overlay) closeZkConfirm();
      };
  }
  function closeZkConfirm() {
      var overlay = document.getElementById('zk-confirm-overlay');
      if(overlay) {
          overlay.style.opacity = '0';
          setTimeout(function(){ overlay.remove(); }, 300);
      }
  }
  </script>
</head>
<body class="admin-body">
<?php if($islogin==1){ ?>
<div style="display:flex;min-height:100vh;">
  <nav class="admin-sidebar" id="adminSidebar">
    <a class="sidebar-brand" href="https://space.bilibili.com/521205099" target="_blank">
      <span class="sb-icon"><i class="fa fa-cloud"></i></span><?php echo $conf["title"] ?? '' ?>
    </a>
    <ul class="nav nav-pills nav-stacked sidebar-nav">
      <li class="<?php echo ($currentPage=="index.php")? 'active':'' ?>"><a href="./">控制面板</a></li>
      <li class="<?php echo ($currentPage=="api_toggle.php")? 'active':'' ?>"><a href="./api_toggle.php">API 开关</a></li>
      <li class="<?php echo ($currentPage=="file.php")? 'active':'' ?>"><a href="./file.php">文件管理</a></li>
      <li class="<?php echo ($currentPage=="user.php")? 'active':'' ?>"><a href="./user.php">用户管理</a></li>
      <li class="nav-divider"></li>
      <li class="<?php echo ($currentPage=="set.php" && $currentMod=="site")? 'active':'' ?>"><a href="./set.php?mod=site">网站信息</a></li>
      <li class="<?php echo ($currentPage=="set.php" && $currentMod=="upload")? 'active':'' ?>"><a href="./set.php?mod=upload">上传设置</a></li>
      <li class="<?php echo ($currentPage=="set.php" && $currentMod=="user")? 'active':'' ?>"><a href="./set.php?mod=user">用户设置</a></li>
      <li class="<?php echo ($currentPage=="set.php" && $currentMod=="admin")? 'active':'' ?>"><a href="./set.php?mod=admin">管理员</a></li>
      <li class="<?php echo ($currentPage=="set_stor.php")? 'active':'' ?>"><a href="./set_stor.php">存储设置</a></li>
      <li class="nav-divider"></li>
      <li class="<?php echo ($currentPage=="update.php")? 'active':'' ?>"><a href="./update.php">检查更新</a></li>
      <li><a href=".." target="_blank">前台首页</a></li>
      <li><a href="./index.php?logout=1">退出登录</a></li>
    </ul>
  </nav>
  <div class="admin-content" style="flex:1;min-width:0;padding:20px;">
    <!-- 移动端侧边栏展开按钮 - 汉堡菜单动画 -->
    <button class="hamburger-btn" onclick="toggleAdminSidebar()" style="display:none;position:fixed;top:10px;right:10px;z-index:10002;background:var(--zk-primary);color:#fff;border:none;border-radius:8px;width:36px;height:36px;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.15);padding:0;">
      <span class="hamburger-icon">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
      </span>
    </button>
    <style>
    @media (max-width:767px) {
      .hamburger-btn { display: flex !important; align-items: center; justify-content: center; }
    }
    .hamburger-icon {
      display: flex; flex-direction: column; justify-content: center; align-items: center; width: 18px; height: 14px;
      position: relative;
    }
    .hamburger-line {
      display: block; width: 18px; height: 2px; background: #fff; border-radius: 2px;
      position: absolute; transition: all 0.3s ease;
    }
    .hamburger-line:nth-child(1) { top: 0; }
    .hamburger-line:nth-child(2) { top: 50%; transform: translateY(-50%); }
    .hamburger-line:nth-child(3) { bottom: 0; }
    
    /* 激活状态 - 三条线旋转成X */
    .hamburger-btn.active .hamburger-line:nth-child(1) {
      top: 50%; transform: translateY(-50%) rotate(45deg);
    }
    .hamburger-btn.active .hamburger-line:nth-child(2) {
      opacity: 0; transform: translateX(-5px);
    }
    .hamburger-btn.active .hamburger-line:nth-child(3) {
      bottom: 50%; transform: translateY(50%) rotate(-45deg);
    }
    </style>
    <!-- main content will be injected here by individual pages -->
    <?php
    if (isset($content)) {
      echo $content;
    } else {
      echo '<div class="panel panel-default" style="margin:20px;">
              <div class="panel-heading"><strong>控制面板</strong></div>
              <div class="panel-body">欢迎进入管理员后台。请选择左侧菜单进行操作。</div>
            </div>';
    }
    ?>
  </div>
  <!-- Right side panel container (hidden by default) -->
  <div id="admin-right-overlay" class="admin-right-overlay" onclick="closeRightPanel()"></div>
  <div id="admin-right-panel" class="admin-right-panel" aria-label="API 开关区域"></div>
</div>
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
  function toggleAdminSidebar(){
    var sidebar = document.getElementById('adminSidebar');
    if(!sidebar) return;
    sidebar.classList.toggle('open');
    
    // 切换汉堡按钮动画
    var btn = document.querySelector('.hamburger-btn');
    if(btn) btn.classList.toggle('active');
    
    var overlay = document.querySelector('.mobile-menu-overlay');
    if(!overlay){
      overlay = document.createElement('div');
      overlay.className = 'mobile-menu-overlay';
      overlay.onclick = function(){ toggleAdminSidebar(); };
      document.body.appendChild(overlay);
    }
    overlay.classList.toggle('open');
  }
  document.addEventListener('DOMContentLoaded', function(){
    var btn = document.querySelector('.hamburger-btn');
    if(btn) btn.style.display = window.innerWidth <= 767 ? 'flex' : 'none';
  });
  window.addEventListener('resize', function(){
    var btn = document.querySelector('.hamburger-btn');
    if(btn) btn.style.display = window.innerWidth <= 767 ? 'flex' : 'none';
  });
  </script>
  <script>
  function openRightPanel(){
    var panel = document.getElementById('admin-right-panel');
    var overlay = document.getElementById('admin-right-overlay');
    if (!panel || !overlay) return;
    if (!panel.dataset.loaded){
      fetch('./api_toggle_panel.php').then(r => r.text()).then(html => {
        panel.innerHTML = html;
        panel.dataset.loaded = '1';
        panel.classList.add('open');
        overlay.classList.add('open');
      }).catch(() => {
        panel.innerHTML = '<div style="padding:16px;">加载失败</div>';
        panel.classList.add('open');
        overlay.classList.add('open');
      });
    } else {
      panel.classList.add('open');
      overlay.classList.add('open');
    }
  }
  function closeRightPanel(){
    var panel = document.getElementById('admin-right-panel');
    var overlay = document.getElementById('admin-right-overlay');
    if (panel && overlay){ panel.classList.remove('open'); overlay.classList.remove('open'); }
  }
  // 监听 localStorage 变化，同步前台主题
  window.addEventListener('storage', function(e){
    if(e.key === 'theme-color' || e.key === 'theme-custom-color' || e.key === 'theme-gradient-start' || e.key === 'theme-gradient-end'){
      location.reload();
    }
  });
  </script>
</body>
</html>
