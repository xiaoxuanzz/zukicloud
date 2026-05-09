<?php
define('IN_ADMIN', true);
include("../includes/common.php");
$title = "用户管理";
if($islogin != 1) exit("<script>window.location.href='./login.php';</script>");

// Handle actions
if(isset($_GET['enable'])){
    $uid = intval($_GET['enable']);
    $DB->exec("UPDATE pre_user SET enable=IF(enable=1,0,1) WHERE uid=$uid");
    header('Location: ./user.php');
    exit;
}
if(isset($_GET['del'])){
    $uid = intval($_GET['del']);
    $DB->exec("DELETE FROM pre_user WHERE uid=$uid");
    header('Location: ./user.php');
    exit;
}

ob_start();
?>
<style>
.table>tbody>tr>td { vertical-align: middle; }
</style>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">用户管理</h3></div>
<div class="panel-body">
  <div class="table-responsive">
<table class="table table-striped">
<thead><tr><th>UID</th><th>昵称</th><th>类型</th><th>注册IP</th><th>最后登录</th><th>状态</th><th>操作</th></tr></thead>
<tbody>
<?php
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$total = $DB->getColumn("SELECT COUNT(*) FROM pre_user");
$rows = $DB->getAll("SELECT * FROM pre_user ORDER BY uid DESC LIMIT $limit OFFSET $offset");
foreach($rows as $row){
    // Fix type display
    $status = $row['enable'] == 1 ? '<span class="label label-success">正常</span>' : '<span class="label label-danger">禁用</span>';
    $lasttime = $row['lasttime'] ? date('Y-m-d H:i', strtotime($row['lasttime'])) : '从未登录';
    $isAdmin = ($row['nickname'] == $conf['admin_user']);
    $type = $isAdmin ? '<span class="label label-danger">管理员账号</span>' : '<span class="label label-info">普通用户</span>';
    
    echo "<tr>";
    echo "<td>{$row['uid']}</td>";
    echo "<td>" . htmlspecialchars($row['nickname']) . "</td>";
    echo "<td>{$type}</td>";
    echo "<td>{$row['regip']}</td>";
    echo "<td>{$lasttime}</td>";
    echo "<td>{$status}</td>";
    echo "<td>";
    echo "<a href='./user.php?enable={$row['uid']}' style=\"margin-right:8px;\">" . ($row['enable'] == 1 ? '禁用' : '启用') . '</a> ';
    echo "<a href='./user.php?del={$row['uid']}' onclick=\"return confirm('确定删除此用户？');\" style=\"color:#ef4444;\">删除</a>";
    echo "</td></tr>";
}
?>
</tbody>
</table>
</div>
<?php
$pages = ceil($total / $limit);
if($pages > 1){
    echo "<ul class='pagination'>";
    for($i = 1; $i <= $pages; $i++){
        echo "<li class='" . ($i == $page ? "active" : "") . "'><a href='?page=$i'>$i</a></li>";
    }
    echo "</ul>";
}
?>
<div class="text-muted">共 <?php echo $total?> 个用户</div>
</div></div>
<?php
$content = ob_get_clean();
include "./head.php";
?>
