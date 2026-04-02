<?php
define("IN_ADMIN", true);
include("../includes/common.php");
$title = "站点设置";
include "./head.php";
if($islogin != 1) exit("<script>window.location.href=\"./login.php\";</script>");

$mod = isset($_GET["mod"]) ? $_GET["mod"] : "";

if($mod == "site"){
    if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["do"] == "submit"){
        saveSetting("title", $_POST["title"]);
        saveSetting("keywords", $_POST["keywords"]);
        saveSetting("description", $_POST["description"]);
        saveSetting("gonggao", $_POST["gonggao"]);
        showmsg("网站信息设置成功！", 1);
    }
    ?>
    <div class="panel panel-primary">
    <div class="panel-heading"><h3 class="panel-title">网站信息设置</h3></div>
    <div class="panel-body">
        <form method="post" class="form-horizontal">
        <input type="hidden" name="do" value="submit">
        <div class="form-group">
            <label class="col-sm-2 control-label">网站标题</label>
            <div class="col-sm-10"><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($conf["title"]) ?>"></div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">网站关键词</label>
            <div class="col-sm-10"><input type="text" name="keywords" class="form-control" value="<?php echo htmlspecialchars($conf["keywords"]) ?>"></div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">网站描述</label>
            <div class="col-sm-10"><textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($conf["description"]) ?></textarea></div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">网站公告</label>
            <div class="col-sm-10"><textarea name="gonggao" class="form-control" rows="3"><?php echo htmlspecialchars($conf["gonggao"]) ?></textarea></div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10"><button type="submit" class="btn btn-primary">保存设置</button></div>
        </div>
        </form>
    </div>
    </div>
    <?php

} elseif($mod == "upload"){
    if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["do"] == "submit"){
        saveSetting("upload_size", intval($_POST["upload_size"]));
        saveSetting("type_block", $_POST["type_block"]);
        saveSetting("videoreview", intval($_POST["videoreview"]));
        saveSetting("upload_code_open", intval($_POST["upload_code_open"]));
        saveSetting("upload_code", trim($_POST["upload_code"]));
        saveSetting("upload_concurrent", min(50, max(1, intval($_POST["upload_concurrent"]))));
        showmsg("上传设置成功！", 1);
    }
    ?>
    <div class="panel panel-primary">
    <div class="panel-heading"><h3 class="panel-title">上传设置</h3></div>
    <div class="panel-body">
        <form method="post" class="form-horizontal">
        <input type="hidden" name="do" value="submit">
        <div class="form-group">
            <label class="col-sm-2 control-label">上传大小限制(MB)</label>
            <div class="col-sm-10"><input type="number" name="upload_size" class="form-control" value="<?php echo $conf["upload_size"] ?>" min="0"></div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">禁止上传格式</label>
            <div class="col-sm-10"><input type="text" name="type_block" class="form-control" value="<?php echo htmlspecialchars($conf["type_block"]) ?>" placeholder="exe|bat|cmd">
            <p class="help-block">多个用|分隔</p></div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">视频审核</label>
            <div class="col-sm-10">
                <label class="radio-inline"><input type="radio" name="videoreview" value="1" <?php echo $conf["videoreview"]==1 ? "checked" : "" ?>> 开启</label>
                <label class="radio-inline"><input type="radio" name="videoreview" value="0" <?php echo $conf["videoreview"]==0 ? "checked" : "" ?>> 关闭</label>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">上传验证码</label>
            <div class="col-sm-10">
                <label class="radio-inline"><input type="radio" name="upload_code_open" value="1" <?php echo $conf["upload_code_open"]==1 ? "checked" : "" ?>> 开启</label>
                <label class="radio-inline"><input type="radio" name="upload_code_open" value="0" <?php echo $conf["upload_code_open"]==0 ? "checked" : "" ?>> 关闭</label>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">验证码内容</label>
            <div class="col-sm-10"><input type="text" name="upload_code" class="form-control" value="<?php echo htmlspecialchars($conf["upload_code"]) ?>" placeholder="留空则不启用验证码">
            <p class="help-block">开启上传验证码后，用户上传文件时需输入此验证码</p></div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">并发上传数</label>
            <div class="col-sm-10"><input type="number" name="upload_concurrent" class="form-control" value="<?php echo max(1, min(50, intval($conf["upload_concurrent"]))) ?>" min="1" max="50">
            <p class="help-block">批量上传时同时上传的文件数量，最大50个（建议3-10）</p></div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10"><button type="submit" class="btn btn-primary">保存设置</button></div>
        </div>
        </form>
    </div>
    </div>
    <?php

} elseif($mod == "user"){
    if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["do"] == "submit"){
        saveSetting("userlogin", intval($_POST["userlogin"]));
        saveSetting("forcelogin", intval($_POST["forcelogin"]));
        showmsg("用户设置成功！", 1);
    }
    ?>
    <div class="panel panel-primary">
    <div class="panel-heading"><h3 class="panel-title">用户设置</h3></div>
    <div class="panel-body">
        <form method="post" class="form-horizontal">
        <input type="hidden" name="do" value="submit">
        <div class="form-group">
            <label class="col-sm-2 control-label">用户注册</label>
            <div class="col-sm-10">
                <label class="radio-inline"><input type="radio" name="userlogin" value="1" <?php echo $conf["userlogin"]==1 ? "checked" : "" ?>> 开启</label>
                <label class="radio-inline"><input type="radio" name="userlogin" value="0" <?php echo $conf["userlogin"]==0 ? "checked" : "" ?>> 关闭</label>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">强制登录</label>
            <div class="col-sm-10">
                <label class="radio-inline"><input type="radio" name="forcelogin" value="1" <?php echo $conf["forcelogin"]==1 ? "checked" : "" ?>> 是</label>
                <label class="radio-inline"><input type="radio" name="forcelogin" value="0" <?php echo $conf["forcelogin"]==0 ? "checked" : "" ?>> 否</label>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10"><button type="submit" class="btn btn-primary">保存设置</button></div>
        </div>
        </form>
    </div>
    </div>
    <?php

} elseif($mod == "admin"){
    if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["do"] == "submit"){
        $user = trim($_POST["admin_user"]);
        $oldpwd = $_POST["oldpwd"];
        $newpwd = $_POST["newpwd"];
        $newpwd2 = $_POST["newpwd2"];
        if(empty($user)) showmsg("用户名不能为空！", 3);
        saveSetting("admin_user", $user);
        if(!empty($newpwd)){
            if($oldpwd != $conf["admin_pwd"]) showmsg("旧密码不正确！", 3);
            if($newpwd != $newpwd2) showmsg("两次输入的密码不一致！", 3);
            saveSetting("admin_pwd", $newpwd);
        }
        showmsg("修改成功！即将退出重新登录", 1, "./index.php?logout=1");
    }
    ?>
    <div class="panel panel-primary">
    <div class="panel-heading"><h3 class="panel-title">管理员账号设置</h3></div>
    <div class="panel-body">
        <form method="post" class="form-horizontal">
        <input type="hidden" name="do" value="submit">
        <div class="form-group">
            <label class="col-sm-2 control-label">管理员用户名</label>
            <div class="col-sm-10"><input type="text" name="admin_user" class="form-control" value="<?php echo htmlspecialchars($conf["admin_user"]) ?>"></div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">旧密码</label>
            <div class="col-sm-10"><input type="password" name="oldpwd" class="form-control" placeholder="不修改请留空"></div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">新密码</label>
            <div class="col-sm-10"><input type="password" name="newpwd" class="form-control" placeholder="不修改请留空"></div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">确认新密码</label>
            <div class="col-sm-10"><input type="password" name="newpwd2" class="form-control"></div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10"><button type="submit" class="btn btn-primary">保存设置</button></div>
        </div>
        </form>
    </div>
    </div>
    <?php

} else {
    ?>
    <h3>站点设置</h3>
    <div class="row" style="margin-top:20px;">
        <div class="col-xs-6 col-sm-4 col-md-3" style="margin-bottom:15px;">
            <a href="?mod=site" class="btn btn-primary btn-lg btn-block">
                <i class="fa fa-globe"></i><br>网站信息
            </a>
        </div>
        <div class="col-xs-6 col-sm-4 col-md-3" style="margin-bottom:15px;">
            <a href="?mod=upload" class="btn btn-success btn-lg btn-block">
                <i class="fa fa-cloud-upload"></i><br>上传设置
            </a>
        </div>
        <div class="col-xs-6 col-sm-4 col-md-3" style="margin-bottom:15px;">
            <a href="?mod=user" class="btn btn-info btn-lg btn-block">
                <i class="fa fa-users"></i><br>用户设置
            </a>
        </div>
        <div class="col-xs-6 col-sm-4 col-md-3" style="margin-bottom:15px;">
            <a href="?mod=admin" class="btn btn-warning btn-lg btn-block">
                <i class="fa fa-lock"></i><br>管理员设置
            </a>
        </div>
        <div class="col-xs-6 col-sm-4 col-md-3" style="margin-bottom:15px;">
            <a href="./set_stor.php" class="btn btn-danger btn-lg btn-block">
                <i class="fa fa-database"></i><br>存储设置
            </a>
        </div>
    </div>
    <?php
}
?>

</body>
</html>