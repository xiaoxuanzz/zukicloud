<?php
define("IN_ADMIN", true);
include("../includes/common.php");

// Handle single delete
if(isset($_GET["del"])){
    $id = intval($_GET["del"]);
    $row = $DB->getRow("SELECT hash FROM pre_file WHERE id=$id");
    if($row){
        $hash = $row['hash'];
        error_log('[DELETE-ADMIN] 单删 hash='.$hash.' stor='.(empty($stor)?'空':'已初始化'));
        if(!empty($stor)){
            $exists = $stor->exists($hash);
            error_log('[DELETE-ADMIN] 文件存在: '.($exists?'是':'否'));
            $del_result = $stor->delete($hash);
            error_log('[DELETE-ADMIN] 删除结果: '.($del_result?'成功':'失败'));
        }
        $DB->exec("DELETE FROM pre_file WHERE id=$id");
    }
    header('Location: ./file.php?msg=deleted');
    exit;
}

// Handle batch delete
if(isset($_POST["batch_del"]) && !empty($_POST["ids"])){
    $ids = array_map('intval', $_POST["ids"]);
    $idStr = implode(",", $ids);
    $rows = $DB->getAll("SELECT id, hash FROM pre_file WHERE id IN ($idStr)");
    error_log('[DELETE-ADMIN] 批量删 '.count($rows).' 个文件 stor='.(empty($stor)?'空':'已初始化'));
    foreach($rows as $r){
        $hash = $r['hash'];
        if(!empty($stor)){
            $exists = $stor->exists($hash);
            $del_result = $stor->delete($hash);
            error_log('[DELETE-ADMIN] 批量 hash='.$hash.' exists='.( $exists?'是':'否').' del='.( $del_result?'成功':'失败'));
        }
        $DB->exec("DELETE FROM pre_file WHERE id=:id", [':id'=>$r['id']]);
    }
    header('Location: ./file.php?msg=deleted');
    exit;
}

$title = "文件管理";
include "./head.php";
if($islogin != 1) exit("<script>window.location.href=\"./login.php\";</script>");
?>
<style>
.table>tbody>tr>td { vertical-align: middle; max-width: 360px; word-break: break-all; }
.batch-actions { margin-bottom: 15px; }
.btn-batch-del {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 18px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(239,68,68,0.3);
    transition: all .2s;
}
.btn-batch-del:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239,68,68,0.4);
    color: #fff;
}
.btn-batch-del:active { transform: translateY(0); }
.btn-del-item {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: rgba(239,68,68,0.1);
    color: #ef4444;
    border: none;
    font-size: 13px;
    cursor: pointer;
    transition: all .2s;
}
.btn-del-item:hover {
    background: #ef4444;
    color: #fff;
    transform: translateY(-1px);
}
.batch-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    background: rgba(255,255,255,0.3);
    border-radius: 9px;
    font-size: 11px;
    line-height: 1;
}
</style>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var selectAllH = document.getElementById('selectAllH');
    var countEl = document.getElementById('selectedCount');
    function updateCount(){
        var checked = document.querySelectorAll('#fileForm input[name="ids[]"]:checked').length;
        if(countEl) countEl.textContent = checked;
    }
    if(selectAllH){
        selectAllH.addEventListener('change',function(){
            document.querySelectorAll('#fileForm input[name="ids[]"]').forEach(function(cb){cb.checked=selectAllH.checked;});
            updateCount();
        });
    }
    document.querySelectorAll('#fileForm input[name="ids[]"]').forEach(function(cb){
        cb.addEventListener('change',function(){
            var total = document.querySelectorAll('#fileForm input[name="ids[]"]').length;
            var checked = document.querySelectorAll('#fileForm input[name="ids[]"]:checked').length;
            if(selectAllH) selectAllH.checked = (checked === total);
            updateCount();
        });
    });
});
</script>

<?php if(isset($_GET["msg"]) && $_GET["msg"] == "deleted"){ ?>
<script>showToast('文件删除成功', 'success');</script>
<?php } ?>

<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-files-o"></i> 文件管理</h3></div>
<div class="panel-body">
<form method="post" id="fileForm">
<input type="hidden" name="batch_del" value="1">
<div class="batch-actions" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;margin:0;">
        <input type="checkbox" id="selectAllH"> 全选
    </label>
    <button type="submit" name="batch_del" value="1" class="btn-batch-del" onclick="event.preventDefault();var self=this;zkConfirm({icon:'danger',title:'确认删除',subtitle:'删除后不可恢复',confirmText:'确认删除',confirmClass:'btn-danger',onConfirm:function(){self.form.submit();}})">
        <i class="fa fa-trash-o"></i>
        <span>批量删除</span>
        <span class="batch-count" id="selectedCount">0</span>
    </button>
    <span class="text-muted" style="font-size:13px;">共 <b><?php echo $DB->getColumn("SELECT COUNT(*) FROM pre_file");?></b> 个文件</span>
</div>

<div class="table-responsive">
<table class="table table-striped">
<thead><tr><th width="40"></th><th>ID</th><th>文件名</th><th>大小</th><th>下载</th><th>上传时间</th><th>操作</th></tr></thead>
<tbody>
<?php
$page = isset($_GET["page"]) ? intval($_GET["page"]) : 1;
$kw = isset($_GET["kw"]) ? trim($_GET["kw"]) : '';
$pagesize = 15;
$sql = " hide=0";
if(!empty($kw)){
    $kw = htmlspecialchars($kw);
    $sql .= " AND name LIKE '%{$kw}%'";
}
$numrows = $DB->getColumn("SELECT COUNT(*) FROM pre_file WHERE{$sql}");
$pages = ceil($numrows/$pagesize);
if($page > $pages) $page = $pages;
if($page < 1) $page = 1;
$offset = $pagesize*($page - 1);
$rs = $DB->query("SELECT * FROM pre_file WHERE{$sql} ORDER BY id DESC LIMIT $offset,$pagesize");
$i = ($page - 1) * $pagesize + 1;
while($res = $rs->fetch(PDO::FETCH_ASSOC)){
    $fileurl = '../down.php/'.$res['hash'].'.'.($res['type']?$res['type']:'file');
    $viewurl = '../file.php?hash='.$res['hash'];
    $shareurl = $siteurl.'down.php/'.$res['hash'].'.'.($res['type']?$res['type']:'file');
    echo "<tr>";
    echo "<td><input type=\"checkbox\" name=\"ids[]\" value=\"{$res['id']}\"></td>";
    echo "<td>{$res['id']}</td>";
    echo "<td class=\"filename\"><span class=\"file-icon ".type_to_icon($res['type'])."\"><i class=\"fa ".type_to_icon($res['type'])."\"></i></span>".htmlspecialchars($res['name'])."</td>";
    echo "<td>".size_format($res['size'])."</td>";
    echo "<td>".($res['count']?$res['count']:0)."</td>";
    echo "<td>{$res['addtime']}</td>";
    echo "<td>";
    echo "<a href=\"{$fileurl}\" target=\"_blank\" class=\"btn btn-xs btn-info\"><i class=\"fa fa-download\"></i></a> ";
    echo "<a href=\"{$viewurl}\" target=\"_blank\" class=\"btn btn-xs btn-success\"><i class=\"fa fa-eye\"></i></a> ";
    echo "<a href=\"javascript:void(0)\" onclick=\"var self=this;zkConfirm({icon:'danger',title:'确认删除',subtitle:'删除后不可恢复',confirmText:'确认删除',confirmClass:'btn-danger',onConfirm:function(){window.location.href='./file.php?del={$res['id']}';}})\" class=\"btn-del-item\"><i class=\"fa fa-trash-o\"></i></a>";
    echo "</td></tr>";
}
if($numrows == 0) echo '<tr><td colspan="7" align="center" style="padding:40px;color:var(--zk-text-dim)"><i class="fa fa-inbox" style="font-size:36px;display:block;margin-bottom:12px"></i>还没有上传任何文件<br><a href="../upload.php" style="color:var(--zk-primary)">去上传 &rarr;</a></td></tr>';
?>
</tbody>
</table>
</div>

<?php if($pages > 1){ ?>
<nav>
<ul class="pagination">
<li<?php if($page<=1){?> class="disabled"<?php }?>><a href="<?php echo '?page='.($page-1).(!empty($kw)?'&kw='.urlencode($kw):'')?>">&laquo;</a></li>
<?php for($p=1;$p<=$pages;$p++){?>
<li<?php if($page==$p){?> class="active"<?php }?>><a href="<?php echo '?page='.$p.(!empty($kw)?'&kw='.urlencode($kw):'')?>"><?php echo $p?></a></li>
<?php } ?>
<li<?php if($page>=$pages){?> class="disabled"<?php }?>><a href="<?php echo '?page='.($page+1).(!empty($kw)?'&kw='.urlencode($kw):'')?>">&raquo;</a></li>
</ul>
</nav>
<?php } ?>

</form>
</div>
</div>

<script>
var selectAll = document.getElementById('selectAll');
if(selectAll){
    selectAll.addEventListener('change', function(){
        var checked = this.checked;
        document.querySelectorAll('#fileForm input[name="ids[]"]').forEach(function(cb){ cb.checked = checked; });
    });
}
</script>
<?php include "./footer.php"; ?>
