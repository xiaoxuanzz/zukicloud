<?php
// Admin UI panel content for API toggle (rendered inside the main admin layout)
define("IN_ADMIN", true);
include("../includes/common.php");
if($islogin != 1) exit("<script>window.location.href=\"./login.php\";</script>");
?>
<div class="panel panel-primary">
<div class="panel-heading">
    <h3 class="panel-title">
        <i class="fa fa-toggle-on"></i> API 开关
        <a href="/api/docs.html" target="_blank" style="color:#337ab7; font-size:14px; margin-left:15px; text-decoration:underline;">
            <i class="fa fa-book"></i> 查看API开发文档
        </a>
    </h3>
</div>
<div class="panel-body">
  <div class="form-group">
    <label class="control-label">当前状态</label>
    <p class="form-control-static" id="apiStatusText">加载中...</p>
  </div>
  <div class="form-group">
    <label class="control-label">管理员密钥</label>
    <input id="adminKeyInputPanel" type="password" class="form-control" placeholder="请输入管理员密钥" autocomplete="new-password" style="background:#1e293b !important; color:#f1f5f9 !important; border-color:#334155 !important;" />
  </div>
  <div class="form-group">
    <button onclick="adminToggle(true)" class="btn btn-danger" style="margin-right:8px;"><i class="fa fa-check-circle"></i> 开启 API</button>
    <button onclick="adminToggle(false)" class="btn btn-default"><i class="fa fa-ban"></i> 关闭 API</button>
  </div>
  <div id="adminPanelMsg" style="margin-top:8px; color:#c00; min-height:20px;"></div>
</div>
</div>
<script>
function refreshStatus(){
  fetch('../api/index.php?action=status')
    .then(r => r.json())
    .then(d => {
      const s = (d && d.api_enabled) ? '已开启' : '已关闭';
      const badge = document.getElementById('apiglobal-status');
      const statusText = document.getElementById('apiStatusText');
      if (badge) badge.textContent = s;
      if (statusText) statusText.textContent = s;
    }).catch(() => {
      const badge = document.getElementById('apiglobal-status');
      const statusText = document.getElementById('apiStatusText');
      if (badge) badge.textContent = '未知';
      if (statusText) statusText.textContent = '未知';
    });
}
function adminToggle(enable){
  const key = document.getElementById('adminKeyInputPanel').value;
  if(!key){ document.getElementById('adminPanelMsg').textContent = '请输入管理员密钥'; return; }
  fetch('../api/index.php?action=admin_toggle', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      // 不在请求头中直接显示密钥，避免闪烁
      'X-ADMIN-KEY': key
    },
    body: JSON.stringify({ enabled: enable })
  }).then(async (r) => {
    const data = await r.json().catch(()=>null);
    if (r.ok) {
      document.getElementById('adminPanelMsg').innerHTML = '<span style="color:green"><i class="fa fa-check"></i> 操作成功</span>';
      refreshStatus();
    } else {
      document.getElementById('adminPanelMsg').textContent = '错误: ' + (data?.error || '请检查管理员密钥');
    }
  }).catch((e) => {
    document.getElementById('adminPanelMsg').textContent = '请求失败: ' + String(e);
  });
}
// 暗黑模式初始化（避免闪烁）
(function(){
  if(document.documentElement.getAttribute('data-theme') === 'dark'){
    var panel = document.querySelector('.panel');
    if (panel) {
      panel.style.backgroundColor = '#1e293b';
      panel.style.color = '#f1f5f9';
    }
    var inputs = document.querySelectorAll('input');
    inputs.forEach(function(input){
      input.style.backgroundColor = '#1e293b';
      input.style.color = '#f1f5f9';
      input.style.borderColor = '#334155';
    });
    var btns = document.querySelectorAll('.btn');
    btns.forEach(function(btn){
      if(btn && btn.classList.contains('btn-default')){
        btn.style.backgroundColor = '#334155';
        btn.style.color = '#f1f5f9';
      }
    });
  }
})();
document.addEventListener('DOMContentLoaded', refreshStatus);
</script>
