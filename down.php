<?php
$nosession=true;
$nosecu=true;
include("./includes/common.php");

$urlarr=explode('/',$_SERVER['PATH_INFO']);
if (($length = count($urlarr)) > 1) {
$url = $urlarr[$length-1];
}
$extension=explode('&',$url);
if (($length = count($extension)) > 1) {
$pwd = $extension[$length-1];
$url = $extension[0];
}

if(strpos($url,".")){
    $hash=substr($url,0,strpos($url,"."));
}else{
    $hash=$url;
}

$row = $DB->getRow("SELECT * FROM `pre_file` WHERE `hash`=:hash limit 1", [':hash'=>$hash]);
if(!$row)exit('404 Not Found');
if($row['block']>=1)exit('File is blocked!');

if($row['pwd']!=null && $row['pwd']!=$pwd){ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>输入密码</title>
<link href="https://s4.zstatic.net/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
<link href="https://s4.zstatic.net/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
<style>
body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
.pwd-card { background: #fff; border-radius: 16px; padding: 40px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.pwd-card h3 { color: #333; margin-bottom: 20px; }
.pwd-card .icon { font-size: 50px; color: #667eea; margin-bottom: 15px; }
.pwd-card input { font-size: 16px; padding: 12px; text-align: center; }
.pwd-card .btn { margin-top: 15px; padding: 10px 40px; font-size: 16px; background: linear-gradient(135deg, #667eea, #764ba2); border: none; }
.pwd-card .btn:hover { opacity: 0.9; }
.pwd-card .error { color: #e74c3c; margin-top: 10px; font-size: 13px; display: none; }
.pwd-card .back-link { color: #999; font-size: 13px; margin-top: 20px; display: block; }
</style>
</head>
<body>
<div class="pwd-card">
    <i class="fa fa-lock icon"></i>
    <h3>请输入提取密码</h3>
    <form id="pwdForm">
        <input type="password" class="form-control" id="pwdInput" placeholder="输入密码" autofocus>
        <div class="error" id="errorMsg">密码错误，请重试</div>
        <button type="submit" class="btn btn-primary btn-block">确认下载</button>
    </form>
    <a href="javascript:history.back();" class="back-link"><i class="fa fa-arrow-left"></i> 返回上一页</a>
</div>
<script>
document.getElementById('pwdForm').onsubmit = function(e){
    e.preventDefault();
    var pwd = document.getElementById('pwdInput').value;
    if(pwd){
        window.location.href = '<?php echo $siteurl."down.php/".$hash?>&' + encodeURIComponent(pwd);
    }
};
// Show error if revisiting with wrong password
var url = window.location.href;
if(url.indexOf('&') > -1){
    document.getElementById('errorMsg').style.display = 'block';
}
</script>
</body>
</html>
<?php
    exit;
}

if($stor->exists($hash))
{
    $DB->exec("UPDATE `pre_file` SET `lasttime`=NOW(), `count`=`count`+1 WHERE `id`='{$row['id']}'");
    
    // For password-protected files, show download page with redirect
    if($row['pwd']!=null && $row['pwd']!=''){
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>下载中</title>';
        echo '<link href="https://s4.zstatic.net/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">';
        echo '<style>body{background:linear-gradient(135deg,#667eea,#764ba2);min-height:100vh;display:flex;align-items:center;justify-content:center;}';
        echo '.card{background:#fff;border-radius:16px;padding:40px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.2);max-width:400px;}';
        echo '.card i{font-size:50px;color:#27ae60;}';
        echo '.card a{margin-top:15px;display:inline-block;}</style></head><body>';
        echo '<div class="card"><i class="fa fa-check-circle"></i>';
        echo '<h3 style="margin:15px 0;">下载已开始</h3>';
        echo '<p class="text-muted">' . htmlspecialchars($row['name']) . '</p>';
        echo '<a href="./" class="btn btn-primary"><i class="fa fa-home"></i> 返回文件列表</a></div>';
        echo '<script>setTimeout(function(){window.location.href="./"},3000);</script></body></html>';
        
        // Send file download headers
        file_output($hash, $row['type'], $row['size'], $row['name']);
    } else {
        file_output($hash, $row['type'], $row['size'], $row['name']);
    }
}
else{
    exit('File Not Found');
}
