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

  <script>
  // ========== 主题切换功能（全部在 head 中定义，确保全局可用） ==========
  var _colorTimer = null;
  var _gradientTimer = null;

  function toggleThemePanel() {
    var panel = document.getElementById('themePanel');
    if (!panel) return;
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    // 同步颜色选择器的值
    var customColor = localStorage.getItem('theme-custom-color') || '#5b8def';
    var picker = document.getElementById('customColorPicker');
    if (picker) picker.value = customColor;
    var customColorMobile = document.getElementById('customColorPickerMobile');
    if (customColorMobile) customColorMobile.value = customColor;
    var gradientStart = localStorage.getItem('theme-gradient-start') || '#5b8def';
    var gradientEnd = localStorage.getItem('theme-gradient-end') || '#6366f1';
    var startPicker = document.getElementById('gradientStartPicker');
    var endPicker = document.getElementById('gradientEndPicker');
    if (startPicker) startPicker.value = gradientStart;
    if (endPicker) endPicker.value = gradientEnd;
    var gradientStartMobile = document.getElementById('gradientStartPickerMobile');
    var gradientEndMobile = document.getElementById('gradientEndPickerMobile');
    if (gradientStartMobile) gradientStartMobile.value = gradientStart;
    if (gradientEndMobile) gradientEndMobile.value = gradientEnd;
  }

  function toggleGradientPanel() {
    var panel = document.getElementById('gradientPanel');
    if (!panel) return;
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
  }

  function toggleGradientPanelMobile() {
    var panel = document.getElementById('gradientPanelMobile');
    if (panel) panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
  }

  function setTheme(themeClass) {
    if (_colorTimer) clearTimeout(_colorTimer);
    if (_gradientTimer) clearTimeout(_gradientTimer);
    document.body.classList.remove('theme-rose','theme-pink','theme-orange','theme-yellow','theme-lime','theme-emerald','theme-sky','theme-blue','theme-violet','theme-purple');
    if (themeClass && themeClass !== 'default') {
      document.body.classList.add(themeClass);
    }
    localStorage.setItem('theme-color', themeClass || 'default');
    localStorage.removeItem('theme-custom-color');
    localStorage.removeItem('theme-gradient-start');
    localStorage.removeItem('theme-gradient-end');
    var panel = document.getElementById('themePanel');
    if (panel) panel.style.display = 'none';
  }

  function delayedSetCustomColor(color) {
    if (_colorTimer) clearTimeout(_colorTimer);
    _colorTimer = setTimeout(function() { applyCustomColor(color); }, 500);
  }

  function applyCustomColor(color) {
    document.body.classList.remove('theme-rose','theme-pink','theme-orange','theme-yellow','theme-lime','theme-emerald','theme-sky','theme-blue','theme-violet','theme-purple');
    localStorage.removeItem('theme-gradient-start');
    localStorage.removeItem('theme-gradient-end');
    document.documentElement.style.setProperty('--zk-primary', color);
    document.documentElement.style.setProperty('--zk-gradient', color);
    document.documentElement.style.setProperty('--zk-primary-light', color);
    localStorage.setItem('theme-color', 'custom');
    localStorage.setItem('theme-custom-color', color);
  }

  function delayedSetGradient() {
    if (_gradientTimer) clearTimeout(_gradientTimer);
    _gradientTimer = setTimeout(function() { applyGradient(); }, 500);
  }

  function applyGradient() {
    var startPicker = document.getElementById('gradientStartPickerMobile') || document.getElementById('gradientStartPicker');
    var endPicker = document.getElementById('gradientEndPickerMobile') || document.getElementById('gradientEndPicker');
    var startColor = startPicker ? startPicker.value : '#5b8def';
    var endColor = endPicker ? endPicker.value : '#6366f1';
    document.body.classList.remove('theme-rose','theme-pink','theme-orange','theme-yellow','theme-lime','theme-emerald','theme-sky','theme-blue','theme-violet','theme-purple');
    var gradient = 'linear-gradient(135deg, ' + startColor + ', ' + endColor + ')';
    document.documentElement.style.setProperty('--zk-primary', startColor);
    document.documentElement.style.setProperty('--zk-gradient', gradient);
    document.documentElement.style.setProperty('--zk-primary-light', startColor);
    localStorage.setItem('theme-color', 'custom-gradient');
    localStorage.setItem('theme-custom-color', startColor);
    localStorage.setItem('theme-gradient-start', startColor);
    localStorage.setItem('theme-gradient-end', endColor);
  }

  function resetTheme() {
    if (_colorTimer) clearTimeout(_colorTimer);
    if (_gradientTimer) clearTimeout(_gradientTimer);
    document.body.classList.remove('theme-rose','theme-pink','theme-orange','theme-yellow','theme-lime','theme-emerald','theme-sky','theme-blue','theme-violet','theme-purple');
    document.documentElement.style.removeProperty('--zk-primary');
    document.documentElement.style.removeProperty('--zk-gradient');
    document.documentElement.style.removeProperty('--zk-primary-light');
    localStorage.removeItem('theme-color');
    localStorage.removeItem('theme-custom-color');
    localStorage.removeItem('theme-gradient-start');
    localStorage.removeItem('theme-gradient-end');
    location.reload();
  }

  // 应用保存的颜色主题
  document.addEventListener('DOMContentLoaded', function() {
    var savedTheme = localStorage.getItem('theme-color');
    if (!savedTheme || savedTheme === 'default') return;
    if (savedTheme === 'custom') {
      var customColor = localStorage.getItem('theme-custom-color');
      if (customColor) {
        document.documentElement.style.setProperty('--zk-primary', customColor);
        document.documentElement.style.setProperty('--zk-gradient', customColor);
        document.documentElement.style.setProperty('--zk-primary-light', customColor);
      }
    } else if (savedTheme === 'custom-gradient') {
      var startColor = localStorage.getItem('theme-gradient-start');
      var endColor = localStorage.getItem('theme-gradient-end');
      if (startColor && endColor) {
        var gradient = 'linear-gradient(135deg, ' + startColor + ', ' + endColor + ')';
        document.documentElement.style.setProperty('--zk-primary', startColor);
        document.documentElement.style.setProperty('--zk-gradient', gradient);
        document.documentElement.style.setProperty('--zk-primary-light', startColor);
      }
    } else {
      document.body.classList.remove('theme-rose','theme-pink','theme-orange','theme-yellow','theme-lime','theme-emerald','theme-sky','theme-blue','theme-violet','theme-purple');
      document.body.classList.add(savedTheme);
    }
  });

  // 监听其他标签页的主题变化
  window.addEventListener('storage', function(e) {
    if (e.key === 'theme-color' || e.key === 'theme-custom-color' || e.key === 'theme-gradient-start' || e.key === 'theme-gradient-end') {
      location.reload();
    }
  });

  // 点击其他地方关闭主题面板
  document.addEventListener('click', function(e) {
    var panel = document.getElementById('themePanel');
    var btn = document.querySelector('.theme-toggle-btn');
    if (panel && !panel.contains(e.target) && btn && !btn.contains(e.target)) {
      panel.style.display = 'none';
    }
  });
  </script>
</head>
<body>

<!-- 流体动画背景 -->
<div class="fluid-bg">
    <div class="fluid-blob"></div>
    <div class="fluid-blob"></div>
    <div class="fluid-blob"></div>
    <div class="fluid-blob"></div>
</div>

  <nav class="navbar navbar-default navbar-static-top" style="position:relative;">
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
              <a class="nav-glider-tab brand-link" href="https://www.bilibili.com/video/BV1HRXfBNE4q/" target="_blank" rel="noopener" data-index="3"><i class="fa fa-terminal"></i> <span>Python</span></a>
              <a href="javascript:;" class="nav-glider-tab" onclick="alert('Android模块暂未开放')"><i class="fa fa-android"></i> <span>Android</span></a>
              <a href="javascript:;" class="nav-glider-tab" onclick="alert('Harmony模块暂未开放')"><i class="fa fa-link"></i> <span>Harmony</span></a>
              <a class="nav-glider-tab brand-link" href="https://www.bilibili.com/video/BV1iK9FBoEWv" target="_blank"><i class="fa fa-microchip"></i> <span>stm32/cc2530</span></a>
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
          <!-- 桌面端主题设置按钮 -->
          <div class="theme-selector" style="display:inline-block;margin-left:10px;">
            <button class="theme-toggle-btn" onclick="toggleThemePanel()" title="主题设置" style="width:auto;padding:0 12px;display:flex;align-items:center;gap:6px;height:34px;background:#fff;color:#333;border:1px solid #ddd;border-radius:8px;cursor:pointer;">
              <span style="font-size:13px;">主题设置</span>
              <i class="fa fa-caret-down"></i>
            </button>
            <div class="theme-panel" id="themePanel" style="display:none;position:absolute;top:42px;right:0;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:12px;box-shadow:0 8px 24px rgba(0,0,0,0.12);z-index:10000;width:260px;">
              <div style="font-size:12px;color:#666;margin-bottom:8px;padding:0 4px;">主题颜色</div>
              <div style="display:flex;flex-direction:column;gap:2px;margin-bottom:8px;">
                <a href="javascript:void(0)" onclick="setTheme('theme-rose')" style="color:#e11d48;font-size:12px;padding:4px 8px;text-decoration:none;">玫瑰色</a>
                <a href="javascript:void(0)" onclick="setTheme('theme-pink')" style="color:#f472b6;font-size:12px;padding:4px 8px;text-decoration:none;">粉色</a>
                <a href="javascript:void(0)" onclick="setTheme('theme-orange')" style="color:#fb923c;font-size:12px;padding:4px 8px;text-decoration:none;">橙色</a>
                <a href="javascript:void(0)" onclick="setTheme('theme-yellow')" style="color:#facc15;font-size:12px;padding:4px 8px;text-decoration:none;">黄色</a>
                <a href="javascript:void(0)" onclick="setTheme('theme-lime')" style="color:#84cc16;font-size:12px;padding:4px 8px;text-decoration:none;">青柠</a>
                <a href="javascript:void(0)" onclick="setTheme('theme-emerald')" style="color:#10b981;font-size:12px;padding:4px 8px;text-decoration:none;">翡翠</a>
                <a href="javascript:void(0)" onclick="setTheme('theme-sky')" style="color:#0ea5e9;font-size:12px;padding:4px 8px;text-decoration:none;">天蓝</a>
                <a href="javascript:void(0)" onclick="setTheme('theme-blue')" style="color:#3b82f6;font-size:12px;padding:4px 8px;text-decoration:none;">蓝色</a>
                <a href="javascript:void(0)" onclick="setTheme('theme-violet')" style="color:#8b5cf6;font-size:12px;padding:4px 8px;text-decoration:none;">紫罗兰</a>
                <a href="javascript:void(0)" onclick="setTheme('theme-purple')" style="color:#a78bfa;font-size:12px;padding:4px 8px;text-decoration:none;">紫色</a>
              </div>
              <div style="display:flex;gap:6px;align-items:center;padding:0 4px;margin-bottom:8px;">
                <input type="color" id="customColorPicker" value="#5b8def" oninput="delayedSetCustomColor(this.value)" style="width:32px;height:32px;border:2px solid #ddd;border-radius:6px;cursor:pointer;padding:2px;">
                <span style="font-size:12px;color:#666;flex:1;">自定义颜色（实时）</span>
                <button onclick="toggleGradientPanel()" id="gradientToggle" style="background:none;border:1px solid #ddd;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;color:#666;">渐变</button>
              </div>
              <div id="gradientPanel" style="display:none;padding:8px;background:#f9f9f9;border-radius:6px;margin-bottom:8px;">
                <div style="font-size:11px;color:#666;margin-bottom:6px;">自定义渐变（起始色 / 结束色）</div>
                <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px;">
                  <input type="color" id="gradientStartPicker" value="#5b8def" oninput="delayedSetGradient()" style="width:32px;height:32px;border:2px solid #ddd;border-radius:6px;cursor:pointer;padding:2px;">
                  <span style="font-size:12px;color:#666;">/</span>
                  <input type="color" id="gradientEndPicker" value="#6366f1" oninput="delayedSetGradient()" style="width:32px;height:32px;border:2px solid #ddd;border-radius:6px;cursor:pointer;padding:2px;">
                </div>
                <button onclick="applyGradient()" style="width:100%;padding:6px;border:1px solid #ddd;border-radius:6px;background:#fff;color:#333;font-size:12px;cursor:pointer;">应用渐变</button>
              </div>
              <div style="border-top:1px solid #eee;padding-top:8px;text-align:center;margin-top:8px;">
                <button onclick="resetTheme()" style="background:none;border:none;color:#999;font-size:12px;cursor:pointer;">恢复默认</button>
              </div>
            </div>
          </div>
          <!-- 移动端主题选择区域 -->
          <div class="mobile-theme-selector" style="display:none;padding:12px 14px;border-top:1px solid #eee;">
            <div style="font-size:12px;color:#666;margin-bottom:8px;">主题颜色</div>
            <button class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleThemePanel()" style="padding:6px 12px;border:1px solid #ddd;border-radius:6px;background:#fff;color:#333;cursor:pointer;font-size:12px;width:100%;">
              <span id="currentThemeText">主题设置</span> <i class="fa fa-caret-down"></i>
            </button>
            <div style="display:flex;gap:6px;align-items:center;padding:0 4px;margin-bottom:8px;">
              <input type="color" id="customColorPickerMobile" value="#5b8def" oninput="delayedSetCustomColor(this.value)" style="width:32px;height:32px;border:2px solid #ddd;border-radius:6px;cursor:pointer;padding:2px;">
              <span style="font-size:12px;color:#666;flex:1;">自定义颜色（实时）</span>
              <button onclick="toggleGradientPanelMobile()" id="gradientToggleMobile" style="background:none;border:1px solid #ddd;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;color:#666;">渐变</button>
            </div>
            <div id="gradientPanelMobile" style="display:none;padding:8px;background:#f9f9f9;border-radius:6px;margin-bottom:8px;">
              <div style="font-size:11px;color:#666;margin-bottom:6px;text-align:center;">自定义渐变（起始色 / 结束色）</div>
              <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px;">
                <input type="color" id="gradientStartPickerMobile" value="#5b8def" oninput="delayedSetGradient()" style="width:32px;height:32px;border:2px solid #ddd;border-radius:6px;cursor:pointer;padding:2px;">
                <span style="font-size:12px;color:#666;">/</span>
                <input type="color" id="gradientEndPickerMobile" value="#6366f1" oninput="delayedSetGradient()" style="width:32px;height:32px;border:2px solid #ddd;border-radius:6px;cursor:pointer;padding:2px;">
              </div>
              <button onclick="applyGradient()" style="width:100%;padding:6px;border:1px solid #ddd;border-radius:6px;background:#fff;color:#333;font-size:12px;cursor:pointer;">应用渐变</button>
            </div>
            <div style="text-align:center;margin-top:8px;">
              <button onclick="resetTheme()" style="background:none;border:none;color:#999;font-size:12px;cursor:pointer;">恢复默认</button>
            </div>
          </div>
        </div>
      </div>
  </nav>

<style>
.theme-selector {
  position: absolute;
  top: 50%;
  right: 20px;
  transform: translateY(-50%);
  z-index: 9999;
}
.theme-toggle-btn {
  transition: all 0.2s;
}
.theme-toggle-btn:hover {
  background: #f5f5f5 !important;
}
@media (max-width:767px) {
  .theme-selector { display: none !important; }
  .navbar-collapse.in .mobile-theme-selector,
  .navbar-collapse.collapsing .mobile-theme-selector {
    display: block !important;
  }
}
</style>

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
// ===== Toast 通知 =====
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
    if (window._zkVue && window._zkVue.toast) {
        window._zkVue.toast = { show: true, msg: msg, type: type, hide: false };
        setTimeout(function(){ if(window._zkVue && window._zkVue.toast) window._zkVue.toast.hide = true; }, hideDelay - 500);
        setTimeout(function(){ if(window._zkVue && window._zkVue.toast) window._zkVue.toast.show = false; }, hideDelay);
    }
}

// ===== Modal 弹窗 =====
var zkModalOnConfirm = null;
var _iconColors = {success:'#10b981',error:'#ef4444',danger:'#ef4444',warning:'#f59e0b',info:'#6366f1'};
var _iconBgs = {success:'#10b981',error:'#ef4444',danger:'#ef4444',warning:'#f59e0b',info:'#6366f1'};
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
            else if(cls === 'btn-danger'){b.style.cssText='padding:10px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;background:#ef4444;color:#fff;';}
            else if(cls === 'btn-success'){b.style.cssText='padding:10px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;background:#10b981;color:#fff;';}
            else{b.style.cssText='padding:10px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;background:#6366f1;color:#fff;';}
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
    setTimeout(positionGlider, 200);
})();
</script>
</body>
</html>
