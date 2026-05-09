<?php
/**
 * 后台管理 AJAX 接口 - 重构版
 * 修复编码错误，统一错误处理
 */
define('IN_ADMIN', true);
include("../includes/common.php");

if($islogin == 1) {
    // 已登录
} else {
    exit("<script language='javascript'>window.location.href='../login.php';</script>");
}

$act = isset($_GET['act']) ? daddslashes($_GET['act']) : null;

if(!checkRefererHost()) {
    exit(json_encode(['code' => 403, 'msg' => '非法请求来源']));
}

@header('Content-Type: application/json; charset=UTF-8');

switch($act) {
    case 'getcount':
        handleGetCount();
        break;
    case 'set':
        handleSetSettings();
        break;
    case 'iptype':
        handleIpType();
        break;
    case 'userList':
        handleUserList();
        break;
    case 'setUserEnable':
        handleSetUserEnable();
        break;
    case 'saveUserInfo':
        handleSaveUserInfo();
        break;
    case 'delUser':
        handleDelUser();
        break;
    default:
        exit(json_encode(['code' => -4, 'msg' => '未知操作']));
}

/**
 * 获取统计数据
 */
function handleGetCount() {
    global $DB;
    
    $thtime = date("Y-m-d") . ' 00:00:00';
    $lastday = date("Y-m-d", strtotime("-1 day")) . ' 00:00:00';
    
    $count1 = $DB->getColumn("SELECT count(*) from pre_file");
    $count2 = $DB->getColumn("SELECT count(*) from pre_file WHERE addtime>='$thtime'");
    $count3 = $DB->getColumn("SELECT count(*) from pre_file WHERE addtime>='$lastday' AND addtime<'$thtime'");
    $count4 = $DB->getColumn("SELECT count(*) from pre_user");
    
    $result = [
        "code" => 0,
        "count1" => (int)$count1,
        "count2" => (int)$count2,
        "count3" => (int)$count3,
        "count4" => (int)$count4
    ];
    
    exit(json_encode($result, JSON_UNESCAPED_UNICODE));
}

/**
 * 保存设置
 */
function handleSetSettings() {
    global $DB;
    
    foreach($_POST as $k => $v) {
        if (is_array($v)) {
            $v = implode(',', $v);
        }
        saveSetting($k, $v);
    }
    
    exit(json_encode(['code' => 0, 'msg' => '保存成功']));
}

/**
 * 获取IP类型
 */
function handleIpType() {
    $result = [
        ['name' => '0_X_FORWARDED_FOR', 'ip' => real_ip(0), 'city' => get_ip_city(real_ip(0))],
        ['name' => '1_X_REAL_IP', 'ip' => real_ip(1), 'city' => get_ip_city(real_ip(1))],
        ['name' => '2_REMOTE_ADDR', 'ip' => real_ip(2), 'city' => get_ip_city(real_ip(2))]
    ];
    
    exit(json_encode($result, JSON_UNESCAPED_UNICODE));
}

/**
 * 用户列表
 */
function handleUserList() {
    global $DB;
    
    $sql = " 1=1";
    $type_arr = ['qq' => 'QQ', 'wx' => '微信'];
    
    if(isset($_POST['dstatus']) && $_POST['dstatus'] > -1) {
        $dstatus = intval($_POST['dstatus']);
        $sql .= " AND `enable`={$dstatus}";
    }
    
    if(isset($_POST['kw']) && !empty($_POST['kw'])) {
        $type = intval($_POST['type']);
        $kw = trim(daddslashes($_POST['kw']));
        
        switch($type) {
            case 1:
                $sql .= " AND `uid`='{$kw}'";
                break;
            case 2:
                $sql .= " AND `openid`='{$kw}'";
                break;
            case 3:
                $sql .= " AND `nickname` LIKE '%{$kw}%'";
                break;
            case 4:
                $sql .= " AND `loginip`='{$kw}'";
                break;
        }
    }
    
    $offset = intval($_POST['offset'] ?? 0);
    $limit = intval($_POST['limit'] ?? 10);
    
    $total = $DB->getColumn("SELECT count(*) from pre_user WHERE {$sql}");
    $list = $DB->getAll("SELECT * FROM pre_user WHERE {$sql} ORDER BY uid DESC LIMIT {$offset}, {$limit}");
    
    $list2 = [];
    foreach($list as $row) {
        $row['type'] = $type_arr[$row['type']] ?? $row['type'];
        $list2[] = $row;
    }
    
    exit(json_encode(['total' => (int)$total, 'rows' => $list2], JSON_UNESCAPED_UNICODE));
}

/**
 * 设置用户启用状态
 */
function handleSetUserEnable() {
    global $DB;
    
    $uid = intval($_POST['uid'] ?? 0);
    $enable = intval($_POST['enable'] ?? 0);
    
    if ($uid <= 0) {
        exit(json_encode(['code' => -1, 'msg' => '无效的用户ID']));
    }
    
    $sql = "UPDATE pre_user SET enable='$enable' WHERE uid='$uid'";
    
    if($DB->exec($sql) !== false) {
        exit(json_encode(['code' => 0, 'msg' => '修改用户状态成功']));
    } else {
        exit(json_encode(['code' => -1, 'msg' => '修改用户状态失败: ' . $DB->error()]));
    }
}

/**
 * 保存用户信息
 */
function handleSaveUserInfo() {
    global $DB;
    
    $uid = intval($_POST['uid'] ?? 0);
    $level = intval($_POST['level'] ?? 0);
    
    if ($uid <= 0) {
        exit(json_encode(['code' => -1, 'msg' => '无效的用户ID']));
    }
    
    $sql = "UPDATE pre_user SET level='$level' WHERE uid='$uid'";
    
    if($DB->exec($sql) !== false) {
        exit(json_encode(['code' => 0, 'msg' => '修改用户成功']));
    } else {
        exit(json_encode(['code' => -1, 'msg' => '修改用户失败: ' . $DB->error()]));
    }
}

/**
 * 删除用户
 */
function handleDelUser() {
    global $DB;
    
    $uid = intval($_POST['uid'] ?? 0);
    
    if ($uid <= 0) {
        exit(json_encode(['code' => -1, 'msg' => '无效的用户ID']));
    }
    
    $row = $DB->getRow("SELECT * FROM pre_user WHERE uid='$uid' LIMIT 1");
    
    if(!$row) {
        exit(json_encode(['code' => -1, 'msg' => '当前用户不存在！']));
    }
    
    $sql = "DELETE FROM pre_user WHERE uid='$uid'";
    
    if($DB->exec($sql)) {
        exit(json_encode(['code' => 0, 'msg' => '删除用户成功']));
    } else {
        exit(json_encode(['code' => -1, 'msg' => '删除用户失败: ' . $DB->error()]));
    }
}
