<?php
define('IN_ADMIN', true);
include("./includes/common.php");

$hash = null;
if(isset($_GET['hash'])){
    $hash = daddslashes($_GET['hash']);
} elseif(isset($_SERVER['PATH_INFO'])){
    $pathinfo = trim($_SERVER['PATH_INFO'], '/');
    $parts = explode('.', $pathinfo);
    if(!empty($parts[0])){
        $hash = $parts[0];
    }
}

if(!$hash){
    echo "error";
    exit;
}

$row = $DB->getRow("SELECT * FROM pre_file WHERE hash=:hash", array(':hash'=>$hash));
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
            echo "<form method='get'><input type='password' name='pwd' placeholder='请输入提取密码'><input type='hidden' name='hash' value='$hash'><button type='submit'>确认</button></form>";
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
$type = $row['type'];
$minetype = minetype($type);
$vtype = get_view_type($type);
$downurl = $siteurl.'down.php/'.$row['hash'].'.'.$type.'?view=1';
?>
<!DOCTYPE html>
<html>
<head>
    <base href="<?php echo $siteurl;?>">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($filename); ?> - <?php echo $conf['title'];?></title>
    <link href="https://s4.zstatic.net/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://s4.zstatic.net/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"/>
    <?php if($vtype == 'audio'){ ?>
    <link href="https://s4.zstatic.net/ajax/libs/aplayer/1.10.1/APlayer.min.css" rel="stylesheet"/>
    <?php } ?>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        html,body{width:100%;height:100%;overflow:hidden;background:#111;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}

        /* image */
        .img-wrap{width:100%;height:100%;position:relative;overflow:hidden;touch-action:none;}
        .img-wrap img{position:absolute;left:0;top:0;transform-origin:0 0;user-select:none;-webkit-user-drag:none;will-change:transform;}
        .img-toolbar{position:fixed;bottom:20px;left:50%;transform:translateX(-50%);display:flex;align-items:center;gap:6px;background:rgba(0,0,0,.8);border-radius:28px;padding:6px 14px;z-index:10;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);user-select:none;box-shadow:0 4px 20px rgba(0,0,0,.4);}
        .img-toolbar button{width:36px;height:36px;border:none;border-radius:50%;background:rgba(255,255,255,.12);color:#fff;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;flex-shrink:0;}
        .img-toolbar button:hover{background:rgba(255,255,255,.25);}
        .img-toolbar button:active{background:rgba(255,255,255,.4);}
        .img-toolbar .zoom-val{color:rgba(255,255,255,.85);font-size:12px;min-width:40px;text-align:center;font-variant-numeric:tabular-nums;}
        .img-toolbar input[type=range]{-webkit-appearance:none;appearance:none;width:110px;height:4px;border-radius:2px;background:rgba(255,255,255,.25);outline:none;cursor:pointer;}
        .img-toolbar input[type=range]::-webkit-slider-thumb{-webkit-appearance:none;appearance:none;width:18px;height:18px;border-radius:50%;background:#fff;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,.3);}
        .img-filename{position:fixed;top:0;left:0;right:0;background:rgba(0,0,0,.6);color:#fff;text-align:center;padding:10px;font-size:13px;z-index:10;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

        /* video */
        .video-wrap{width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#000;}
        .video-wrap video{max-width:100%;max-height:100%;outline:none;}
        .video-filename{position:fixed;top:0;left:0;right:0;background:rgba(0,0,0,.6);color:#fff;text-align:center;padding:10px;font-size:13px;z-index:10;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

        /* audio */
        .audio-wrap{width:100%;height:100%;display:flex;align-items:center;justify-content:center;}
        #player{width:100%;max-width:600px;}
    </style>
</head>
<body>
<?php if($vtype == 'image'){ ?>
    <div class="img-filename"><?php echo htmlspecialchars($filename);?></div>
    <div class="img-wrap" id="imgWrap">
        <img id="previewImg" src="<?php echo $downurl;?>" draggable="false">
    </div>
    <div class="img-toolbar">
        <button id="btnZoomOut" title="缩小"><i class="fa fa-minus"></i></button>
        <input type="range" id="zoomSlider" min="10" max="500" value="100">
        <button id="btnZoomIn" title="放大"><i class="fa fa-plus"></i></button>
        <span class="zoom-val" id="zoomVal">100%</span>
        <button id="btnReset" title="重置"><i class="fa fa-refresh"></i></button>
    </div>
    <script>
    (function(){
        var img = document.getElementById('previewImg');
        var wrap = document.getElementById('imgWrap');
        var slider = document.getElementById('zoomSlider');
        var zoomVal = document.getElementById('zoomVal');
        var btnIn = document.getElementById('btnZoomIn');
        var btnOut = document.getElementById('btnZoomOut');
        var btnReset = document.getElementById('btnReset');

        var s = 1, X = 0, Y = 0, bw = 0, bh = 0;
        var MIN = 0.1, MAX = 5;

        function fit(){
            var w = img.naturalWidth, h = img.naturalHeight;
            if(!w || !h) return;
            var r = Math.min(wrap.clientWidth / w, wrap.clientHeight / h, 1);
            bw = w * r; bh = h * r;
            img.style.width = bw + 'px';
            img.style.height = bh + 'px';
            s = 1;
            X = (wrap.clientWidth - bw) / 2;
            Y = (wrap.clientHeight - bh) / 2;
            draw();
        }

        function draw(){
            img.style.transform = 'translate(' + X + 'px,' + Y + 'px) scale(' + s + ')';
            zoomVal.textContent = Math.round(s * 100) + '%';
            slider.value = Math.max(MIN, Math.min(MAX, Math.round(s * 100)));
        }

        function clamp(){
            var iw = bw * s, ih = bh * s, ww = wrap.clientWidth, wh = wrap.clientHeight;
            if(iw > ww) X = Math.max(ww - iw, Math.min(0, X));
            if(ih > wh) Y = Math.max(wh - ih, Math.min(0, Y));
        }

        function doZoom(ns, fx, fy){
            var os = s;
            s = Math.max(MIN, Math.min(MAX, ns));
            var r = s / os;
            X = (1 - r) * fx + r * X;
            Y = (1 - r) * fy + r * Y;
            clamp();
            draw();
        }

        function doReset(){
            s = 1;
            X = (wrap.clientWidth - bw) / 2;
            Y = (wrap.clientHeight - bh) / 2;
            draw();
        }

        img.onload = fit;
        if(img.complete) fit();

        btnIn.onclick = function(){ doZoom(s * 1.3, wrap.clientWidth / 2, wrap.clientHeight / 2); };
        btnOut.onclick = function(){ doZoom(s / 1.3, wrap.clientWidth / 2, wrap.clientHeight / 2); };
        btnReset.onclick = doReset;
        slider.oninput = function(){ doZoom(this.value / 100, wrap.clientWidth / 2, wrap.clientHeight / 2); };

        wrap.onwheel = function(e){
            e.preventDefault();
            var r = wrap.getBoundingClientRect();
            doZoom(s * (e.deltaY > 0 ? 0.9 : 1.1), e.clientX - r.left, e.clientY - r.top);
        };

        // Mouse drag
        var drag = 0, mx0, my0, X0, Y0;
        wrap.onmousedown = function(e){
            if(e.button || s <= 1) return;
            drag = 1; mx0 = e.clientX; my0 = e.clientY; X0 = X; Y0 = Y;
            e.preventDefault();
        };
        document.onmousemove = function(e){
            if(!drag) return;
            X = X0 + e.clientX - mx0;
            Y = Y0 + e.clientY - my0;
            clamp(); draw();
        };
        document.onmouseup = function(){ drag = 0; };

        // Touch
        var tc = {}, ld = 0, pinch = 0, td = 0, txs, tys, tX0, tY0;

        function tDist(){
            var a = Object.values(tc);
            return a.length < 2 ? 0 : Math.hypot(a[0].x - a[1].x, a[0].y - a[1].y);
        }

        wrap.addEventListener('touchstart', function(e){
            for(var i = 0; i < e.changedTouches.length; i++){
                var t = e.changedTouches[i];
                tc[t.identifier] = {x: t.clientX, y: t.clientY};
            }
            var n = Object.keys(tc).length;
            if(n === 2){ pinch = 1; td = 0; ld = tDist(); e.preventDefault(); }
            else if(n === 1 && !pinch && s > 1){
                var t0 = e.changedTouches[0];
                td = 1; txs = t0.clientX; tys = t0.clientY; tX0 = X; tY0 = Y;
            }
        }, {passive: false});

        wrap.addEventListener('touchmove', function(e){
            for(var i = 0; i < e.changedTouches.length; i++){
                var t = e.changedTouches[i];
                tc[t.identifier] = {x: t.clientX, y: t.clientY};
            }
            var n = Object.keys(tc).length;
            if(n === 2 && pinch){
                e.preventDefault();
                var d = tDist();
                var k = Object.keys(tc);
                var mx = (tc[k[0]].x + tc[k[1]].x) / 2;
                var my = (tc[k[0]].y + tc[k[1]].y) / 2;
                var r = wrap.getBoundingClientRect();
                doZoom(s * (d / ld), mx - r.left, my - r.top);
                ld = d;
            } else if(n === 1 && td && s > 1){
                e.preventDefault();
                var t0 = e.changedTouches[0];
                X = tX0 + t0.clientX - txs;
                Y = tY0 + t0.clientY - tys;
                clamp(); draw();
            }
        }, {passive: false});

        function tEnd(e){
            for(var i = 0; i < e.changedTouches.length; i++) delete tc[e.changedTouches[i].identifier];
            var n = Object.keys(tc).length;
            if(n < 2){ pinch = 0; ld = 0; }
            if(n === 0) td = 0;
        }
        wrap.addEventListener('touchend', tEnd);
        wrap.addEventListener('touchcancel', tEnd);

        window.onresize = function(){ s <= 1 ? fit() : (clamp(), draw()); };
    })();
    </script>

<?php }elseif($vtype == 'audio'){ ?>
    <div class="audio-wrap">
        <div id="player"></div>
    </div>
    <script src="https://s4.zstatic.net/ajax/libs/aplayer/1.10.1/APlayer.min.js"></script>
    <script>
        new APlayer({
            container: document.getElementById('player'),
            autoplay: true,
            theme: '#FADFA3',
            loop: 'all',
            preload: 'auto',
            volume: 0.7,
            audio: [{
                name: '<?php echo htmlspecialchars($filename); ?>',
                url: '<?php echo $downurl;?>',
                type: '<?php echo $minetype;?>'
            }]
        });
    </script>

<?php }elseif($vtype == 'video'){ ?>
    <div class="video-filename"><?php echo htmlspecialchars($filename);?></div>
    <div class="video-wrap">
        <video id="videoPlayer" controls playsinline webkit-playsinline preload="auto"></video>
    </div>
    <?php if($type=='m3u8'){?>
    <script src="https://s4.zstatic.net/ajax/libs/hls.js/1.2.4/hls.min.js"></script>
    <?php }?>
    <?php if($type=='flv'||$type=='f4v'){?>
    <script src="https://s4.zstatic.net/ajax/libs/flv.js/1.6.2/flv.min.js"></script>
    <?php }?>
    <script>
    (function(){
        var video = document.getElementById('videoPlayer');
        var url = '<?php echo $downurl;?>';
        var type = '<?php echo $type;?>';

        if(type === 'm3u8' && typeof Hls !== 'undefined' && Hls.isSupported()){
            var hls = new Hls();
            hls.loadSource(url);
            hls.attachMedia(video);
            hls.on(Hls.Events.MANIFEST_PARSED, function(){ video.play(); });
        } else if((type === 'flv' || type === 'f4v') && typeof flvjs !== 'undefined' && flvjs.isSupported()){
            var player = flvjs.createPlayer({type:'flv', url: url});
            player.attachMediaElement(video);
            player.load();
            player.play();
        } else if(video.canPlayType('video/' + (type === 'mp4' ? 'mp4' : (type === 'webm' ? 'webm' : (type === 'ogg' ? 'ogg' : ''))))){
            video.src = url;
            video.play();
        } else {
            video.src = url;
            video.play();
        }
    })();
    </script>
<?php } ?>
</body>
</html>
