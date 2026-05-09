<?php
define("IN_ADMIN", true);
include("../includes/common.php");
$title = "存储设置";
if($islogin != 1) exit("<script>window.location.href='./login.php';</script>");

$storTypes = array(
    "local" => "本地存储",
    "aliyun" => "阿里云OSS",
    "qcloud" => "腾讯云COS",
    "upyun" => "又拍云",
    "qiniu" => "七牛云",
);

if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["do"] == "submit"){
    saveSetting("storage", $_POST["storage"]);
    if($_POST["storage"] == "aliyun"){
        saveSetting("aliyun_ak", $_POST["aliyun_ak"]);
        saveSetting("aliyun_sk", $_POST["aliyun_sk"]);
        saveSetting("aliyun_bucket", $_POST["aliyun_bucket"]);
        saveSetting("aliyun_endpoint", $_POST["aliyun_endpoint"]);
        saveSetting("aliyun_domain", $_POST["aliyun_domain"]);
    } elseif($_POST["storage"] == "qcloud"){
        saveSetting("qcloud_secretid", $_POST["qcloud_secretid"]);
        saveSetting("qcloud_secretkey", $_POST["qcloud_secretkey"]);
        saveSetting("qcloud_bucket", $_POST["qcloud_bucket"]);
        saveSetting("qcloud_region", $_POST["qcloud_region"]);
        saveSetting("qcloud_domain", $_POST["qcloud_domain"]);
    } elseif($_POST["storage"] == "upyun"){
        saveSetting("upyun_bucket", $_POST["upyun_bucket"]);
        saveSetting("upyun_operator", $_POST["upyun_operator"]);
        saveSetting("upyun_password", $_POST["upyun_password"]);
        saveSetting("upyun_domain", $_POST["upyun_domain"]);
    } elseif($_POST["storage"] == "qiniu"){
        saveSetting("qiniu_ak", $_POST["qiniu_ak"]);
        saveSetting("qiniu_sk", $_POST["qiniu_sk"]);
        saveSetting("qiniu_bucket", $_POST["qiniu_bucket"]);
        saveSetting("qiniu_domain", $_POST["qiniu_domain"]);
    }
    showmsg("存储设置保存成功！", 1);
}

ob_start();
?>

<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">存储设置</h3></div>
<div class="panel-body">
    <form method="post" class="form-horizontal">
        <input type="hidden" name="do" value="submit">
        
        <div class="form-group">
            <label class="col-sm-2 control-label">存储类型</label>
            <div class="col-sm-10">
                <select name="storage" id="storageType" class="storage-select" onchange="toggleStorageForm()">
                    <?php foreach($storTypes as $k => $v): ?>
                    <option value="<?php echo $k ?>" <?php echo $conf["storage"]==$k ? "selected" : "" ?>><?php echo $v ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="help-block" style="margin-top:8px;">切换存储类型前，请确认已有文件的情况下请勿随意变更，否则之前上传的文件全部无法下载</p>
            </div>
        </div>
        
        <div id="form-local" class="storage-form" style="display:none;">
        </div>
        
        <div id="form-aliyun" class="storage-form" style="display:none;">
            <h4>阿里云OSS配置</h4>
            <div class="form-group">
                <label class="col-sm-2 control-label">AccessKey ID</label>
                <div class="col-sm-10"><input type="text" name="aliyun_ak" class="form-control" value="<?php echo htmlspecialchars($conf["aliyun_ak"]??'') ?>" autocomplete="off"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">AccessKey Secret</label>
                <div class="col-sm-10"><input type="text" name="aliyun_sk" class="form-control" value="<?php echo htmlspecialchars($conf["aliyun_sk"]??'') ?>" autocomplete="off"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Bucket名称</label>
                <div class="col-sm-10"><input type="text" name="aliyun_bucket" class="form-control" value="<?php echo htmlspecialchars($conf["aliyun_bucket"]??'') ?>" autocomplete="off"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Endpoint</label>
                <div class="col-sm-10"><input type="text" name="aliyun_endpoint" class="form-control" value="<?php echo htmlspecialchars($conf["aliyun_endpoint"]??'') ?>" placeholder="如：oss-cn-hangzhou.aliyuncs.com" autocomplete="off"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">自定义域名</label>
                <div class="col-sm-10"><input type="text" name="aliyun_domain" class="form-control" value="<?php echo htmlspecialchars($conf["aliyun_domain"]??'') ?>" placeholder="可选" autocomplete="off"></div>
            </div>
        </div>
        
        <div id="form-qcloud" class="storage-form" style="display:none;">
            <h4>腾讯云COS配置</h4>
            <div class="form-group">
                <label class="col-sm-2 control-label">SecretId</label>
                <div class="col-sm-10"><input type="text" name="qcloud_secretid" class="form-control" value="<?php echo htmlspecialchars($conf["qcloud_secretid"]??'') ?>" autocomplete="off"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">SecretKey</label>
                <div class="col-sm-10"><input type="text" name="qcloud_secretkey" class="form-control" value="<?php echo htmlspecialchars($conf["qcloud_secretkey"]??'') ?>" autocomplete="off"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Bucket名称</label>
                <div class="col-sm-10"><input type="text" name="qcloud_bucket" class="form-control" value="<?php echo htmlspecialchars($conf["qcloud_bucket"]??'') ?>" autocomplete="off"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">地域</label>
                <div class="col-sm-10"><input type="text" name="qcloud_region" class="form-control" value="<?php echo htmlspecialchars($conf["qcloud_region"]??'') ?>" placeholder="如：ap-guangzhou" autocomplete="off"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">自定义域名</label>
                <div class="col-sm-10"><input type="text" name="qcloud_domain" class="form-control" value="<?php echo htmlspecialchars($conf["qcloud_domain"]??'') ?>" placeholder="可选" autocomplete="off"></div>
            </div>
        </div>
        
        <div id="form-upyun" class="storage-form" style="display:none;">
            <h4>又拍云配置</h4>
            <div class="form-group">
                <label class="col-sm-2 control-label">服务名称</label>
                <div class="col-sm-10"><input type="text" name="upyun_bucket" class="form-control" value="<?php echo htmlspecialchars($conf["upyun_bucket"]??'') ?>" autocomplete="off"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">操作员账号</label>
                <div class="col-sm-10"><input type="text" name="upyun_operator" class="form-control" value="<?php echo htmlspecialchars($conf["upyun_operator"]??'') ?>" autocomplete="off"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">操作员密码</label>
                <div class="col-sm-10"><input type="password" name="upyun_password" class="form-control" value="<?php echo htmlspecialchars($conf["upyun_password"]??'') ?>" autocomplete="new-password"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">自定义域名</label>
                <div class="col-sm-10"><input type="text" name="upyun_domain" class="form-control" value="<?php echo htmlspecialchars($conf["upyun_domain"]??'') ?>" placeholder="可选" autocomplete="off"></div>
            </div>
        </div>
        
        <div id="form-qiniu" class="storage-form" style="display:none;">
            <h4>七牛云配置</h4>
            <div class="form-group">
                <label class="col-sm-2 control-label">AccessKey</label>
                <div class="col-sm-10"><input type="text" name="qiniu_ak" class="form-control" value="<?php echo htmlspecialchars($conf["qiniu_ak"]??'') ?>" autocomplete="off"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">SecretKey</label>
                <div class="col-sm-10"><input type="text" name="qiniu_sk" class="form-control" value="<?php echo htmlspecialchars($conf["qiniu_sk"]??'') ?>" autocomplete="off"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">存储空间名称</label>
                <div class="col-sm-10"><input type="text" name="qiniu_bucket" class="form-control" value="<?php echo htmlspecialchars($conf["qiniu_bucket"]??'') ?>" autocomplete="off"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">自定义域名</label>
                <div class="col-sm-10"><input type="text" name="qiniu_domain" class="form-control" value="<?php echo htmlspecialchars($conf["qiniu_domain"]??'') ?>" placeholder="可选" autocomplete="off"></div>
            </div>
        </div>
        
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-primary btn-lg">保存设置</button>
            </div>
        </div>
    </form>
    
    <!-- 本地存储提示（移到最下面） -->
    <div style="margin-top:30px; padding:15px; color:#666; font-size:14px; border-top:1px solid #eee;">
        本地存储无需额外配置，文件将直接保存在服务器上。
    </div>
</div>

<script>
function toggleStorageForm(){
    var val = document.getElementById("storageType").value;
    var forms = document.querySelectorAll(".storage-form");
    for(var i=0; i<forms.length; i++){ forms[i].style.display = "none"; }
    var target = document.getElementById("form-" + val);
    if(target) target.style.display = "block";
}
toggleStorageForm();
</script>
<?php
$content = ob_get_clean();
include "./head.php";
?>