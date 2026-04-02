<?php
if (version_compare(PHP_VERSION, '7.1.0', '<')) {
    die('require PHP >= 7.1 !');
}
include("./includes/common.php");

if(isset($_GET['m']) && $_GET['m']=='mine'){
    $title = '我的文件 - ' . $conf['title'];
    $htext = '我上传的文件';
    if($islogin2){
        $sql = " uid='{$uid}'";
    }else{
        if($conf['userlogin']==1){
            $htext .= '<span class="text-muted" style="font-size:16px">（根据浏览器缓存记录，<a href="login.php">登录</a>后可永久保留记录）</span>';
        }else{
            $htext .= '<span class="text-muted" style="font-size:16px">（根据浏览器缓存记录）</span>';
        }
        if(isset($_SESSION['fileids']) && count($_SESSION['fileids'])>0){
            $ids = array_reverse($_SESSION['fileids']);
            if(count($ids) > 60){
                $ids = array_splice($ids, 0, 60);
            }
            $ids = implode(',',$ids);
            $sql = " id IN ($ids)";
        }else{
            $sql = " 1=2";
        }
    }
    $link = '&m=mine';
}else{
    $title = $conf['title'];
    $htext = '文件列表';
    $sql = " hide=0";
    $link = '';
}
$kw = isset($_GET['kw'])?daddslashes(trim(strip_tags($_GET['kw']))):null;
if($conf['filesearch']==1 && $kw){
    $sql.=" AND name LIKE '%{$kw}%'";
    $link .= '&kw='.$kw;
}

include SYSTEM_ROOT.'header.php';
?>
<div class="container">
    <div class="well bs-component">
        <h2><?php echo $htext?>
        <?php if($conf['filesearch']==1){?><span class="searchbox">
            <form class="form-inline" action="./" method="GET">
                <?php if(isset($_GET['m'])){?><input name="m" type="hidden" value="<?php echo htmlspecialchars($_GET['m'])?>"><?php }?>
				<input name="kw" class="form-control" type="search" placeholder="请输入搜索关键字" value="<?php echo $kw?>" required="">
				<button class="btn btn-default btn-raised btn-sm" type="submit"><i class="fa fa-search" aria-hidden="true"></i> 搜索</button>
			</form>
        </span><?php }?></h2>
        <div class="table-responsive">
       <table class="table table-striped table-hover filelist">
            <thead>
                <tr>
                    <th>#</th>
                    <th>操作</th>
                    <th>文件名</th>
                    <th>文件大小</th>
                    <th>文件格式</th>
                    <th>上传时间</th>
                    <th>上传者IP</th>
                </tr>
            </thead>
            <tbody>
<?php
$numrows=$DB->getColumn("SELECT count(*) from pre_file WHERE{$sql}");
$pagesize=15;
$pages=ceil($numrows/$pagesize);
$page=isset($_GET['page'])?intval($_GET['page']):1;
$offset=$pagesize*($page - 1);

$rs=$DB->query("SELECT * FROM pre_file WHERE{$sql} ORDER BY id DESC LIMIT $offset,$pagesize");
$i=1;
while($res = $rs->fetch())
{
	$fileurl = './down.php/'.$res['hash'].'.'.($res['type']?$res['type']:'file');
	$viewurl = './file.php?hash='.$res['hash'];
$shareurl = $siteurl.'down.php/'.$res['hash'].'.'.($res['type']?$res['type']:'file');
$actions = '<a href="'.$fileurl.'"><i class="fa fa-download"></i> 下载</a> <a href="'.$viewurl.'"><i class="fa fa-eye"></i> 查看</a> <a href="javascript:;" onclick="copyLink(\''.$shareurl.'\')" style="color:var(--zk-accent);"><i class="fa fa-share-alt"></i> 分享</a>';
if($islogin2 && $res['uid'] > 0 && $res['uid'] == $userrow['uid']){
    $actions .= ' <a href="javascript:;" onclick="deleteFile(\''.$res['hash'].'\')" style="color:#ef4444;"><i class="fa fa-trash"></i></a>';
}
echo '<tr><td><b>'.$i++.'</b></td><td>'.$actions.'</td><td class="filename"><span class="file-icon '.($res['type']?type_to_icon($res['type']):'other').'"><i class="fa '.type_to_icon($res['type']).'"></i></span>'.$res['name'].'</td><td>'.size_format($res['size']).'</td><td><span class="badge">'.($res['type']?$res['type']:'-').'</span></td><td>'.$res['addtime'].'</td><td>'.preg_replace('/\d+$/','*',$res['ip']).'</td></tr>';
}
if($numrows == 0) echo '<tr><td colspan="7" align="center" style="padding:40px;color:var(--zk-text-dim);"><i class="fa fa-inbox" style="font-size:36px;display:block;margin-bottom:12px;"></i>还没上传过任何文件<br><a href="upload.php" style="color:var(--zk-primary);">去上传 &rarr;</a></td></tr>';
?>
            </tbody>
        </table>
        </div>
        <div class="row">
        <div class="col-md-6"><br>共有 <?php echo $numrows?> 个文件&nbsp;&nbsp;当前第 <?php echo $page?> 页，共 <?php echo $pages?> 页</div>
        <div class="col-md-6"><nav>
  <ul class="pagination pagination-sm" style="float:right;">
<?php
$first=1;
$prev=$page-1;
$next=$page+1;
$last=$pages;
if ($page>1)
{
echo '<li><a href="index.php?page='.$first.$link.'">首页</a></li>';
echo '<li><a href="index.php?page='.$prev.$link.'">&laquo;</a></li>';
} else {
echo '<li class="disabled"><a>首页</a></li>';
echo '<li class="disabled"><a>&laquo;</a></li>';
}
$start=$page-10>1?$page-10:1;
$end=$page+10<$pages?$page+10:$pages;
for ($i=$start;$i<$page;$i++)
echo '<li><a href="index.php?page='.$i.$link.'">'.$i .'</a></li>';
echo '<li class="disabled"><a>'.$page.'</a></li>';
for ($i=$page+1;$i<=$end;$i++)
echo '<li><a href="index.php?page='.$i.$link.'">'.$i .'</a></li>';
echo '';
if ($page<$pages)
{
echo '<li><a href="index.php?page='.$next.$link.'">&raquo;</a></li>';
echo '<li><a href="index.php?page='.$last.$link.'">尾页</a></li>';
} else {
echo '<li class="disabled"><a>&raquo;</a></li>';
echo '<li class="disabled"><a>尾页</a></li>';
}
?>
  </ul>
</nav></div>
</div>
    </div>
<?php include SYSTEM_ROOT.'footer.php';?>
<script>
<?php if($islogin2){ ?>
var csrf_token = '<?php echo $GLOBALS['_csrf_token']; ?>';
function deleteFile(hash){
    zkConfirm({
        icon: 'danger',
        title: '确认删除文件',
        subtitle: '删除后不可恢复，请谨慎操作',
        confirmText: '确认删除',
        confirmClass: 'btn-danger',
        onConfirm: function(){
            $.ajax({
                type: 'POST',
                url: 'ajax.php?act=deleteFile',
                data: {hash: hash, csrf_token: csrf_token},
                dataType: 'json',
                success: function(data){
                    if(data.code == 0){
                        showToast('删除成功', 'success');
                        setTimeout(function(){ location.reload(); }, 800);
                    } else {
                        showToast(data.msg || '删除失败', 'error');
                    }
                },
                error: function(){ showToast('网络错误，请重试', 'error'); }
            });
        }
    });
}
<?php } ?>
function copyLink(url) {
    var $tmp = $('<input>');
    $('body').append($tmp);
    $tmp.val(url).select();
    document.execCommand('copy');
    $tmp.remove();
    // Toast 提示
    var toast = $('<div style="position:fixed;bottom:40px;left:50%;transform:translateX(-50%);background:rgba(99,102,241,0.95);color:#fff;padding:12px 28px;border-radius:12px;font-size:14px;z-index:9999;box-shadow:0 8px 24px rgba(99,102,241,0.3);opacity:0;transition:opacity .3s;"><i class="fa fa-check-circle"></i> 链接已复制到剪贴板</div>');
    $('body').append(toast);
    setTimeout(function(){ toast.css('opacity',1); }, 10);
    setTimeout(function(){ toast.css('opacity',0); setTimeout(function(){ toast.remove(); }, 300); }, 2000);
}
</script>
<?php if(!empty($conf['gonggao'])){?>
<link href="https://s4.zstatic.net/ajax/libs/snackbarjs/1.1.0/snackbar.min.css" rel="stylesheet">
<script src="https://s4.zstatic.net/ajax/libs/snackbarjs/1.1.0/snackbar.min.js"></script>
<script src="https://s4.zstatic.net/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
<script>
$(function() {
    if(!$.cookie('gonggao')){
        $.snackbar({content: "<?php echo $conf['gonggao']?>", timeout: 10000});
        var cookietime = new Date(); 
        cookietime.setTime(cookietime.getTime() + (60*60*1000));
        $.cookie('gonggao', false, { expires: cookietime });
    }
});
</script>
<?php }?>
</body>
</html>