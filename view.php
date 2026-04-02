<?php
define('IN_ADMIN', true);
include("./includes/common.php");

if(!isset($_GET['hash']) || !isset($_GET['id'])){
    echo "error";
    exit;
}
$hash = daddslashes($_GET['hash']);
$id = intval($_GET['id']);
$row = $DB->getRow("SELECT * FROM pre_file WHERE id=:id AND hash=:hash", array(':id'=>$id, ':hash'=>$hash));
if(!$row){
    echo "error";
    exit;
}
if($row['pwd']!=null){
    if(!isset($_SESSION['pwd_'.$hash]) || $_SESSION['pwd_'.$hash]!=$row['pwd']){
        $error = "密码错误";
        if(isset($_GET['pwd'])){
            if($row['pwd']==$_GET['pwd']){
                $_SESSION['pwd_'.$hash] = $row['pwd'];
                $error = "";
            } else {
                $error = "密码错误";
            }
        }
        if($error){
            echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>密码错误</title></head><body>";
            echo "<form method='get'><input type='password' name='pwd' placeholder='请输入提取密码'><input type='hidden' name='id' value='$id'><input type='hidden' name='hash' value='$hash'><button type='submit'>确认</button></form>";
            echo "<p>$error</p></body></html>";
            exit;
        }
    }
}else{
    if($row['ontime']!=0 && $row['ontime']<time()){
        exit("文件已过期");
    }
}

$filename = $row['name'];
$minetype = minetype($row['type']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($filename); ?> - <?php echo $conf['title'];?></title>
    <link href="https://s4.zstatic.net/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://s4.zstatic.net/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"/>
    <link href="https://s4.zstatic.net/ajax/libs/aplayer/1.10.1/APlayer.min.css" rel="stylesheet"/>
    <link href="./assets/css/style.css?v=<?php echo VERSION;?>" rel="stylesheet">
    <script src="https://s4.zstatic.net/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <script src="https://s4.zstatic.net/ajax/libs/layer/2.3/layer.js"></script>
</head>
<body>
    <div class="view">
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <?php if(in_array($row['type'],array('mp4','m3u8','mp3'))){ ?>
                        <div class="video_view">
                            <div id="player"></div>
                        </div>
                        <script src="https://s4.zstatic.net/ajax/libs/aplayer/1.10.1/APlayer.min.js"></script>
                        <script src="https://s4.zstatic.net/ajax/libs/hls.js/1.2.4/hls.min.js"></script>
                        <script src="https://s4.zstatic.net/ajax/libs/flv.js/1.6.2/flv.min.js"></script>
                        <script>
                            var ap = new APlayer({
                                container: document.getElementById('player'),
                                autoplay: true,
                                theme: '#FADFA3',
                                loop: 'all',
                                order: 'list',
                                preload: 'auto',
                                volume: 0.7,
                                audio: [{
                                    name: '<?php echo htmlspecialchars($filename); ?>',
                                    url: '<?php echo $siteurl.'down.php/'.$row['hash'];?>',
                                    type: '<?php echo $minetype;?>'
                                }]
                            });
                            var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
                            if(isMobile){
                                ap.play();
                            }
                        </script>
                    <?php }else{ ?>
                        <div class="image_view">
                            <img src="<?php echo $siteurl.'down.php/'.$row['hash'];?>" class="image" alt="<?php echo htmlspecialchars($filename);?>">
                        </div>
                    <?php }?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://s4.zstatic.net/ajax/libs/twitter-bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>
</html>
