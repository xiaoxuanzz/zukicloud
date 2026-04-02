<?php
include("./includes/common.php");

$title = '上传文件 - '.$conf['title'];
include SYSTEM_ROOT.'header.php';

$csrf_token = $GLOBALS['_csrf_token'];

// Check if upload code is required
$upload_code_open = isset($conf['upload_code_open']) ? intval($conf['upload_code_open']) : 0;
$upload_code = isset($conf['upload_code']) ? $conf['upload_code'] : '';
$code_verified = false;
if($upload_code_open == 1 && !empty($upload_code)){
    if(isset($_SESSION['upload_code_verified']) && $_SESSION['upload_code_verified'] === true){
        $code_verified = true;
    }
} else {
    $code_verified = true; // No code required
}

// Handle upload code verification
if(isset($_POST['verify_code'])){
    $user_code = trim($_POST['code_input'] ?? '');
    if(hash_equals($upload_code, $user_code)){
        $_SESSION['upload_code_verified'] = true;
        $code_verified = true;
    } else {
        $code_error = "验证码错误";
    }
}

// Convert upload_size to human readable
function formatUploadSize($mb) {
    if ($mb <= 0) return "无限制";
    if ($mb >= 1024) {
        return round($mb / 1024, 1) . " GB";
    }
    return $mb . " MB";
}
?>
<style>
.upload-box {
    min-height: 350px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    border: 3px dashed #d0d7de;
    border-radius: 16px;
    background: #fafbfc;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 20px 0;
}
.upload-box:hover, .upload-box.drag-over {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102,126,234,0.05), rgba(118,75,162,0.05));
    transform: scale(1.01);
}
.upload-box .upload-icon {
    font-size: 64px;
    color: #667eea;
    margin-bottom: 20px;
}
.upload-box .upload-text-main {
    font-size: 22px;
    color: #333;
    font-weight: 600;
    margin-bottom: 10px;
}
.upload-box .upload-text-sub {
    font-size: 15px;
    color: #999;
}
.upload-box input[type="file"] {
    display: none;
}
.upload-options {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 15px;
    align-items: center;
    justify-content: center;
}
.upload-options .checkbox {
    margin: 0;
}
/* Compact tips bar */
.tips-bar {
    background: var(--zk-card, #fff);
    border: 1px solid var(--zk-border, #e5e7eb);
    border-radius: 10px;
    padding: 10px 15px;
    margin: 15px auto;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 5px 15px;
    font-size: 13px;
    color: #666;
    max-width: 600px;
}
.tips-bar .tip-divider {
    color: #ddd;
    margin: 0 5px;
}
.tips-bar .tip-item i {
    margin-right: 3px;
}
.tips-bar .tip-item b {
    color: var(--zk-text, #333);
}
/* Password tooltip */
.pwd-frame {
    max-width: 220px;
    position: relative;
    display: inline-block;
}
.pwd-frame input {
    transition: border-color 0.3s;
}
.pwd-tooltip {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-top: 8px;
    padding: 8px 14px;
    background: #333;
    color: #fff;
    font-size: 12px;
    border-radius: 8px;
    white-space: nowrap;
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    animation: fadeIn 0.2s ease;
}
.pwd-tooltip::before {
    content: '';
    position: absolute;
    top: -6px;
    left: 50%;
    transform: translateX(-50%);
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-bottom: 6px solid #333;
}
/* Vue 初始化前隐藏所有模板表达式 */
[v-cloak] {
    display: none;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateX(-50%) translateY(-4px); }
    to { opacity: 1; transform: translateX(-50%) translateY(0); }
}
/* Toast notification */
.toast-msg {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    padding: 12px 24px;
    border-radius: 10px;
    font-size: 14px;
    z-index: 99999;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    animation: toastIn 0.3s ease;
    transition: opacity 0.3s, transform 0.3s;
}
.toast-msg.toast-error {
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    color: #fff;
}
.toast-msg.toast-success {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
}
.toast-msg.toast-hide {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
}
@keyframes toastIn {
    from { opacity: 0; transform: translateX(-50%) translateY(-20px); }
    to { opacity: 1; transform: translateX(-50%) translateY(0); }
}

/* ========== 移动端适配 ========== */
@media (max-width: 767px) {
    .upload-box {
        min-height: 200px;
        padding: 30px 15px;
        margin: 10px 0;
    }
    .upload-box .upload-icon {
        font-size: 42px;
        margin-bottom: 12px;
    }
    .upload-box .upload-text-main {
        font-size: 17px;
    }
    .upload-box .upload-text-sub {
        font-size: 13px;
    }
    .upload-options {
        flex-direction: column;
        gap: 8px;
    }
    .pwd-frame {
        max-width: 100%;
        width: 100%;
    }
    .tips-bar {
        font-size: 12px;
        padding: 8px 10px;
        gap: 4px 10px;
    }
    .upload-loading {
        flex-direction: column;
        padding: 14px 16px;
        gap: 10px;
    }
    .upload-loading .loading-info .filename {
        max-width: 200px;
    }
    .upload-loading .loading-info {
        align-items: center;
        text-align: center;
    }
    .row [class*="col-xs-"] {
        font-size: 13px !important;
    }
    .batch-file-list {
        max-height: 150px;
        font-size: 12px;
    }
    .toast-msg {
        left: 10px;
        right: 10px;
        transform: none;
        font-size: 13px;
        padding: 10px 16px;
    }
    .toast-msg.toast-hide {
        transform: translateY(-20px);
    }
}
</style>
<div class="container" id="app" v-cloak>
    <!-- Upload code verification modal -->
    <?php if(!$code_verified){ ?>
    <div style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:99999;">
        <div style="background:#fff;border-radius:16px;padding:40px;max-width:400px;width:90%;text-align:center;">
            <i class="fa fa-lock" style="font-size:48px;color:#667eea;margin-bottom:20px;"></i>
            <h4 style="margin-bottom:20px;">请输入上传验证码</h4>
            <?php if(isset($code_error)){ ?>
            <div class="alert alert-danger" style="padding:8px;"><?php echo htmlspecialchars($code_error) ?></div>
            <?php } ?>
            <form method="post">
                <input type="hidden" name="verify_code" value="1">
                <div class="form-group">
                    <input type="text" name="code_input" class="form-control" placeholder="请输入验证码" autofocus autocomplete="off" style="text-align:center;font-size:18px;">
                </div>
                <button type="submit" class="btn btn-primary btn-lg btn-block">验证</button>
            </form>
        </div>
    </div>
    <script>var forbid = true;</script>
    <?php } ?>
    <!-- Toast notification -->
    <div class="toast-msg" :class="[toast.type, toast.hide ? 'toast-hide' : '']" v-if="toast.show">{{toast.msg}}</div>
    <div class="row">
      <div class="col-sm-12">
        <div class="well infobox" align="center" id="fileInput" :style="{background: background}">
        
        <!-- Progress bar (shown during upload) -->
        <div class="progress-bar-frame" id="progressBarFrame" style="width:100%;margin-bottom:15px;display:none;">
            <!-- 加载动画（读取文件 vs 上传中） -->
            <div class="upload-loading" style="width:100%;justify-content:center;">
                <!-- 读取文件：彩色粒子 -->
                <div v-if="isReading" class="poly-loader" :class="'phase-' + (shapeIndex % 4)"></div>
                <!-- 上传中：Uiverse loader -->
                <div v-else class="loader"></div>
                <div class="loading-info">
                    <span class="filename">{{filename}}</span>
                    <span class="status">{{statusText}}</span>
                </div>
            </div>
            <!-- 进度条 -->
            <div class="progress progress-striped active"><div class="progress-bar" :style="{ width: progress + '%' }"></div></div>
            <div class="row" style="margin-top:8px;">
                <div class="col-xs-4" style="text-align:left;color:#ef4444;font-weight:700;font-size:16px;" id="percentage"><span v-if="progress>0">{{progress}}%</span></div>
                <div class="col-xs-4" style="text-align:center;color:#f97316;font-weight:600;" id="uploadspeed">{{uploadspeed}}</div>
                <div class="col-xs-4" style="text-align:right;color:#eab308;font-weight:700;font-size:16px;">{{batchDone}}/{{batchTotal}}</div>
            </div>
            <!-- Batch file list -->
            <div v-if="batchTotal > 1" class="batch-file-list" style="margin-top:12px;max-height:200px;overflow-y:auto;">
                <table style="width:100%;font-size:13px;">
                    <tr v-for="item in fileQueue" :style="{'background': item.status==='done' ? '#f0fdf4' : item.status==='error' ? '#fef2f2' : '#f8fafc', 'border-bottom':'1px solid #f1f5f9'}">
                        <td style="padding:6px 8px;width:30px;text-align:center;">
                            <i v-if="item.status==='done'" class="fa fa-check-circle" style="color:#10b981;"></i>
                            <i v-else-if="item.status==='error'" class="fa fa-times-circle" style="color:#ef4444;"></i>
                            <i v-else-if="item.status==='uploading'" class="fa fa-spinner fa-spin" style="color:#6366f1;"></i>
                            <i v-else class="fa fa-clock-o" style="color:#94a3b8;"></i>
                        </td>
                        <td style="padding:6px 8px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{item.name}}</td>
                        <td style="padding:6px 8px;text-align:right;width:60px;">
                            <span v-if="item.status==='done'" style="color:#10b981;">完成</span>
                            <span v-else-if="item.status==='error'" style="color:#ef4444;">失败</span>
                            <span v-else-if="item.status==='uploading'" style="color:#6366f1;">上传中</span>
                            <span v-else style="color:#94a3b8;">等待</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Alert message -->
        <div class="alert alert-dismissible" :class="'alert-'+alert.type" v-if="showtype==2">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <strong>{{alert.msg}}</strong>
        </div>

        <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $csrf_token?>">
        <input type="file" id="file" name="myfile" @change="selectFile" multiple style="position:absolute;opacity:0;width:0;height:0;">

        <?php if($conf['forcelogin']==1 && !$islogin2){?>
        <!-- Login required -->
        <div class="upload-box" @click="window.location.href='./login.php'">
            <i class="fa fa-sign-in upload-icon"></i>
            <div class="upload-text-main">请先登录后上传</div>
            <div class="upload-text-sub">点击登录</div>
        </div>
        <script>var forbid = true;</script>
        <?php }else{?>
        <!-- Clickable upload box -->
        <div class="upload-box" id="uploadBox" @click="clickUpload" @drop.prevent="onDrop" @dragover.prevent="onDragOver" @dragleave.prevent="onDragLeave">
            <i class="fa fa-cloud-upload upload-icon"></i>
            <div class="upload-text-main">点击选择文件</div>
            <div class="upload-text-sub">或拖拽文件到此处上传</div>
        </div>
        <?php }?>

        <div class="upload-options">
            <div class="checkbox">
                <label><input type="checkbox" id="show" v-model="input.show"> 在前台文件列表显示</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" id="ispwd" v-model="input.ispwd" @change="if(input.ispwd){ input.pwd=''; $nextTick(function(){ document.getElementById('pwd_input').focus() }) }"> 设定提取密码</label>
            </div>
            <div class="pwd-frame" id="pwd_frame" v-if="input.ispwd">
                <input type="text" class="form-control" id="pwd_input" placeholder="请输入提取密码" autocomplete="off" v-model="input.pwd" @focus="showPwdTip=true" @blur="showPwdTip=false">
                <div class="pwd-tooltip" v-if="showPwdTip">密码只能为字母或数字</div>
            </div>
        </div>

        <!-- Compact tips bar -->
        <div class="tips-bar">
            <span class="tip-item"><i class="fa fa-shield"></i> <b>IP：<?php echo $clientip?></b></span>
            <span class="tip-divider">|</span>
            <?php if($conf['upload_size'] > 0){?>
            <span class="tip-item"><i class="fa fa-cloud-upload"></i> 限制：<?php echo formatUploadSize($conf['upload_size'])?></span>
            <span class="tip-divider">|</span>
            <?php }?>
            <?php if($conf['videoreview']==1){?>
            <span class="tip-item"><i class="fa fa-film"></i> 视频需审核</span>
            <span class="tip-divider">|</span>
            <?php }?>
            <span class="tip-item"><i class="fa fa-lock"></i> 不公开文件信息</span>
        </div>

      </div>
    </div>
  </div>

<div class="colorful_loading_frame">
  <div class="colorful_loading"><i class="rect1"></i><i class="rect2"></i><i class="rect3"></i><i class="rect4"></i><i class="rect5"></i></div>
</div>
<?php include SYSTEM_ROOT.'footer.php';?>
<script src="https://s4.zstatic.net/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://s4.zstatic.net/ajax/libs/vue/2.6.14/vue.min.js"></script>
<script src="https://s4.zstatic.net/ajax/libs/spark-md5/3.0.2/spark-md5.min.js"></script>
<script>var upload_max_filesize = '<?php echo $conf['upload_size']?>';var upload_concurrent = <?php echo max(1, min(50, intval($conf['upload_concurrent']))) ?>;</script>
<script src="./assets/js/uploadnew.js?v=6&t=<?php echo time(); ?>"></script>
</body>
</html>
