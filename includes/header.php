<?php
@header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="renderer" content="webkit">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo $title?></title>
  <meta name="keywords" content="<?php echo $conf['keywords']?>">
  <meta name="description" content="<?php echo $conf['description']?>">
  <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
  <meta name="mobile-web-app-capable" content="yes">
  <link href="https://s4.zstatic.net/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
  <link href="https://s4.zstatic.net/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
  <?php if($is_file){?><link rel="stylesheet" href="https://s4.zstatic.net/ajax/libs/aplayer/1.10.1/APlayer.min.css"><link href="assets/css/ckplayer.css" rel="stylesheet"><?php }?>
  <link href="assets/css/style.css?v=<?php echo VERSION?>" rel="stylesheet">
</head>
<body>

<!-- 流体动画背景 -->
<div class="fluid-bg">
    <div class="fluid-blob"></div>
    <div class="fluid-blob"></div>
    <div class="fluid-blob"></div>
    <div class="fluid-blob"></div>
</div>

  <nav class="navbar navbar-default navbar-static-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
          </button>
          <a class="navbar-brand brand-link" href="https://space.bilibili.com/521205099" target="_blank" rel="noopener">
            <span class="zk-logo"><i class="fa fa-cloud"></i></span><?php echo $conf['title']?>
          </a>
        </div>
        <div class="navbar-collapse collapse">
          <div class="nav-glider-wrap">
            <div class="nav-glider-tabs">
              <a href="./" class="nav-glider-tab <?php echo checkIfActive('index,') ? 'active' : '' ?>" data-index="0"><i class="fa fa-th-large"></i> <span>文件列表</span></a>
              <a href="./upload.php" class="nav-glider-tab <?php echo checkIfActive('upload') ? 'active' : '' ?>" data-index="1"><i class="fa fa-cloud-upload"></i> <span>上传文件</span></a>
              <a href="./?m=mine" class="nav-glider-tab <?php echo checkIfActive('mine') ? 'active' : '' ?>" data-index="2"><i class="fa fa-folder-open"></i> <span>我的文件</span></a>
              <a href="javascript:;" class="nav-glider-tab" onclick="alert('Python模块暂未开放')"><i class="fa fa-terminal"></i> <span>Python</span></a>
              <!-- 【Python跳转替换】 将上面一行替换为下面的链接即可启用Python模块：
              <a class="nav-glider-tab brand-link" href="./python.php" target="_blank" rel="noopener" data-index="3"><i class="fa fa-terminal"></i> <span>Python</span></a>
              -->
              <a href="javascript:;" class="nav-glider-tab" onclick="alert('Android模块暂未开放')"><i class="fa fa-android"></i> <span>Android</span></a>
              <a href="javascript:;" class="nav-glider-tab" onclick="alert('Harmony模块暂未开放')"><i class="fa fa-link"></i> <span>Harmony</span></a>
              <a href="javascript:;" class="nav-glider-tab" onclick="alert('stm32/cc2530模块暂未开放')"><i class="fa fa-microchip"></i> <span>stm32/cc2530</span></a>
              <span class="nav-glider"></span>
            </div>
          </div>
          <ul class="nav navbar-nav navbar-right">
            <?php if($conf['userlogin']){?>
              <?php if($islogin2){?>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle user-nick" data-toggle="dropdown"><i class="fa fa-user-circle"></i> <?php echo htmlspecialchars($userrow['nickname'])?><b class="caret"></b></a>
                <ul class="dropdown-menu dropdown-menu-right">
                  <li><a href="./?m=mine"><i class="fa fa-folder-open"></i> 我的文件</a></li>
                  <li><a href="./upload.php"><i class="fa fa-cloud-upload"></i> 上传文件</a></li>
                  <li class="divider"></li>
                  <li><a href="./login.php?logout=1"><i class="fa fa-sign-out"></i> 退出登录</a></li>
                </ul>
              </li>
              <?php }else{?>
              <li><a href="./login.php"><i class="fa fa-sign-in"></i> 登录</a></li>
              <?php }?>
            <?php }?>
          </ul>
      </div>
    </div>
  </nav>

<!-- Toast 通知容器 -->
<div id="toast-container" class="toast-container"></div>

<!-- 通用 Modal 弹窗 -->
<div id="zk-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:999998;align-items:center;justify-content:center;" onclick="if(event.target===this)closeZkModal()">
  <div style="background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:360px;max-width:90vw;overflow:hidden;">
    <div style="display:flex;align-items:center;gap:12px;padding:20px 24px 0;">
      <div id="zk-modal-icon" style="width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fa fa-info-circle" style="font-size:22px;color:#fff;"></i></div>
      <div style="flex:1;min-width:0;">
        <h3 id="zk-modal-title" style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 2px;"></h3>
        <p id="zk-modal-subtitle" style="font-size:13px;color:#94a3b8;margin:0;"></p>
      </div>
    </div>
    <div id="zk-modal-body" style="padding:16px 24px 0;color:#64748b;font-size:14px;line-height:1.6;"></div>
    <div id="zk-modal-footer" style="display:flex;gap:10px;justify-content:flex-end;padding:16px 24px 20px;"></div>
  </div>
</div>

<script>
// ===== Toast 通知（全局统一方案） =====
window._toastTimer = null;
function showToast(msg, type, hideDelay) {
    type = type || 'info';
    hideDelay = hideDelay || 5000;
    // 清除旧的
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
    // 动画启动
    requestAnimationFrame(function(){ el.style.opacity = '1'; });
    window._toastTimer = setTimeout(function() {
        el.classList.add('toast-out');
        setTimeout(function(){ if (el.parentNode) el.remove(); }, 300);
    }, hideDelay);
    // 同步更新 Vue 实例（如果存在）
    if (window._zkVue && window._zkVue.toast) {
        window._zkVue.toast = { show: true, msg: msg, type: type, hide: false };
        setTimeout(function(){ if(window._zkVue && window._zkVue.toast) window._zkVue.toast.hide = true; }, hideDelay - 500);
        setTimeout(function(){ if(window._zkVue && window._zkVue.toast) window._zkVue.toast.show = false; }, hideDelay);
    }
}

// ===== Modal 弹窗 =====
var zkModalOnConfirm = null;
var _iconColors = {success:'#10b981',error:'#ef4444',danger:'#ef4444',warning:'#f59e0b',info:'#6366f1'};
var _iconBgs = {success:'linear-gradient(135deg,#10b981,#059669)',error:'linear-gradient(135deg,#ef4444,#dc2626)',danger:'linear-gradient(135deg,#ef4444,#dc2626)',warning:'linear-gradient(135deg,#f59e0b,#d97706)',info:'linear-gradient(135deg,#6366f1,#4f46e5)'};
var _iconFA = {success:'check',error:'times',danger:'times',warning:'exclamation',info:'info-circle'};
function showZkModal(opts) {
    var m = document.getElementById('zk-modal');
    if (!m) return;
    var ic = document.getElementById('zk-modal-icon');
    var iconClass = opts.icon || 'info';
    ic.style.background = _iconBgs[iconClass] || _iconBgs.info;
    ic.innerHTML = '<i class="fa fa-' + (_iconFA[iconClass] || 'info-circle') + '" style="font-size:22px;color:#fff;"></i>';
    document.getElementById('zk-modal-title').textContent = opts.title || '';
    document.getElementById('zk-modal-subtitle').textContent = opts.subtitle || '';
    var body = document.getElementById('zk-modal-body');
    body.textContent = opts.body || '';
    body.style.display = opts.body ? '' : 'none';
    var footer = document.getElementById('zk-modal-footer');
    footer.innerHTML = '';
    if (opts.buttons) {
        opts.buttons.forEach(function(btn) {
            var b = document.createElement('button');
            b.textContent = btn.text;
            var cls = btn.class || 'btn-cancel';
            if(cls === 'btn-cancel'){b.style.cssText='padding:10px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;background:#f1f5f9;color:#64748b;';}
            else if(cls === 'btn-danger'){b.style.cssText='padding:10px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;';}
            else if(cls === 'btn-success'){b.style.cssText='padding:10px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;background:linear-gradient(135deg,#10b981,#059669);color:#fff;';}
            else{b.style.cssText='padding:10px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;';}
            b.onclick = function() { closeZkModal(); if (btn.onClick) btn.onClick(); };
            footer.appendChild(b);
        });
    }
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeZkModal() {
    var m = document.getElementById('zk-modal');
    if (m) m.style.display = 'none';
    document.body.style.overflow = '';
}
// 便捷方法
function zkConfirm(opts) {
    showZkModal({
        icon: opts.icon || 'warning',
        title: opts.title || '确认操作',
        subtitle: opts.subtitle || '',
        body: opts.body || '',
        buttons: [
            { text: opts.cancelText || '取消', class: 'btn-cancel' },
            { text: opts.confirmText || '确认', class: opts.confirmClass || 'btn-primary', onClick: opts.onConfirm }
        ]
    });
}
function zkAlert(opts) {
    showZkModal({
        icon: opts.icon || 'info',
        title: opts.title || '提示',
        subtitle: opts.subtitle || '',
        body: opts.body || '',
        buttons: [
            { text: opts.btnText || '确定', class: opts.btnClass || 'btn-primary', onClick: opts.onConfirm }
        ]
    });
}

// ===== Glider 导航定位 =====
(function() {
    function positionGlider() {
        var tabs = document.querySelectorAll('.nav-glider-tab');
        var glider = document.querySelector('.nav-glider');
        if (!tabs.length || !glider) return;
        var active = document.querySelector('.nav-glider-tab.active');
        if (!active) { active = tabs[0]; active.classList.add('active'); }
        var wrap = active.closest('.nav-glider-tabs');
        if (!wrap) return;
        var wrapRect = wrap.getBoundingClientRect();
        var tabRect = active.getBoundingClientRect();
        glider.style.left = (tabRect.left - wrapRect.left) + 'px';
        glider.style.width = tabRect.width + 'px';
    }
    document.addEventListener('DOMContentLoaded', positionGlider);
    window.addEventListener('resize', positionGlider);
    // 延迟一次确保字体加载完毕后重新定位
    setTimeout(positionGlider, 200);
})();
</script>

