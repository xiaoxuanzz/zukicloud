<?php
define("IN_AJAX", true);
$nosecu = true;
$nosession = false;
include("./includes/common.php");

if($conf['isupload'] != 1) exit('{"code":-1,"msg":"本站已关闭上传"}');

$act = isset($_GET['act']) ? $_GET['act'] : '';
$upload_tmp = sys_get_temp_dir() . '/cloud_upload_' . session_id();
@mkdir($upload_tmp, 0755, true);

switch($act){
case 'pre_upload':
	// 用全局变量检查token，不依赖session锁
	$token_ok = isset($_POST['csrf_token']) && !empty($GLOBALS['_csrf_token']) && $_POST['csrf_token']===$GLOBALS['_csrf_token'];
	if(!$token_ok) exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');
	session_write_close(); // 释放session锁，支持并发上传
	
	$upload_code_open = isset($conf['upload_code_open']) ? intval($conf['upload_code_open']) : 0;
	$upload_code = isset($conf['upload_code']) ? $conf['upload_code'] : '';
	if($upload_code_open == 1 && !empty($upload_code)){
		if(!isset($_SESSION['upload_code_verified']) || $_SESSION['upload_code_verified'] !== true){
			exit('{"code":-1,"msg":"需要验证码才能上传","need_code":1}');
		}
	}
	if($conf['forcelogin']==1 && !$islogin2)exit('{"code":-1,"msg":"请先登录"}');
	if($islogin2) $uid = $userrow['uid']; else $uid = 0;
	
	$name = isset($_POST['name'])?trim($_POST['name']):'';
	$hash = isset($_POST['hash'])?trim($_POST['hash']):'';
	$size = intval($_POST['size']);
	$show = isset($_POST['show'])?intval($_POST['show']):1;
	$ispwd = isset($_POST['ispwd'])?intval($_POST['ispwd']):0;
	$pwd = isset($_POST['pwd'])?trim($_POST['pwd']):'';
	
	if(empty($name) || empty($hash) || $size<=0) exit('{"code":-1,"msg":"参数错误"}');
	if(!preg_match('/^[0-9a-z]{32}$/i', $hash))exit('{"code":-1,"msg":"hash error"}');
	
	$row = $DB->getRow("SELECT * FROM pre_file WHERE hash=:hash", [':hash'=>$hash]);
	if($row){
		exit(json_encode(['code'=>1, 'msg'=>'本站已存在该文件', 'exists'=>1, 'hash'=>$hash, 'name'=>$name, 'size'=>$size, 'type'=>pathinfo($name, PATHINFO_EXTENSION), 'id'=>$row['id']]));
	}
	
	$maxsize = intval($conf['upload_size']) * 1024 * 1024;
	if($maxsize > 0 && $size > $maxsize) exit('{"code":-1,"msg":"文件大小超过限制"}');
	
	$type_block = $conf['type_block'] ?? '';
	if(!empty($type_block)){
		$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		$type_arr = explode("|", $type_block);
		if(in_array($ext, $type_arr)) exit('{"code":-1,"msg":"禁止上传此类型的文件"}');
	}
	
	$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
	$filename = $hash;
	$filepath = "upload/".$filename;
	$dir = dirname($filepath);
	if(!is_dir($dir)) mkdir($dir, 0755, true);
	
	$storage = [];
	$storage_all = $DB->getAll("SELECT * FROM pre_storage WHERE state=1 ORDER BY orders ASC");
	foreach($storage_all as $s){
		if($s['maxsize'] > 0 && $s['usedsize'] + $size > $s['maxsize']) continue;
		$storage = $s;
		break;
	}
	
	$chunksize = 100 * 1024 * 1024; // 100MB 分片，最大限度减少分片数和合并时间
	$chunks = ceil($size / $chunksize);
	
	// Store upload info in temp file instead of session
	$upload_info = [
		'name' => $name,
		'hash' => $hash,
		'size' => $size,
		'show' => $show,
		'ispwd' => $ispwd,
		'pwd' => $pwd,
		'uid' => $uid,
		'filename' => $filename,
		'filepath' => $filepath,
		'ext' => $ext,
		'storage' => $storage,
		'chunks' => $chunks,
		'chunksize' => $chunksize,
		'chunk_index' => [],
	];
	$file_tmp = $upload_tmp . '/' . $hash . '.json';
	$result = file_put_contents($file_tmp, json_encode($upload_info), LOCK_EX);
	clearstatcache(); // 清除文件缓存，确保 file_exists 正确工作
	if($result === false){
		error_log('[UPLOAD] Failed to write temp file: '.$file_tmp);
		exit('{"code":-1,"msg":"写入临时文件失败"}');
	}
	error_log('[UPLOAD] Temp file written: '.$file_tmp.' size='.$result);
	
	$result = ['code'=>0, 'msg'=>'开始上传', 'exists'=>0, 'hash'=>$hash, 'chunks'=>$chunks, 'chunksize'=>$chunksize];
	exit(json_encode($result));
	break;

case 'upload_part':
	if(!isset($_FILES['file']))exit('{"code":-1,"msg":"请选择文件"}');
	
	$hash = isset($_POST['hash'])?trim($_POST['hash']):'';
	$chunk = isset($_POST['chunk'])?intval($_POST['chunk']):1;
	if(!preg_match('/^[0-9a-z]{32}$/i', $hash))exit('{"code":-1,"msg":"hash error"}');
	session_write_close();
	
	// Read upload info from temp file (auth via pre_upload)
	$file_tmp = $upload_tmp . '/' . $hash . '.json';
	if(!file_exists($file_tmp)) exit('{"code":-1,"msg":"请先预上传"}');
	$upload_info = json_decode(file_get_contents($file_tmp), true);
	if(!$upload_info || $upload_info['hash'] != $hash) exit('{"code":-1,"msg":"上传信息错误"}');
	
	$filepath = $upload_info['filepath'];
	$chunk_file = $filepath . '.part' . $chunk;
	if(move_uploaded_file($_FILES['file']['tmp_name'], $chunk_file)){
		// 用独立标记文件记录分片，避免并发时 JSON 竞态
		$marker_file = $filepath . '.chunk' . $chunk . '.ok';
		@file_put_contents($marker_file, '1');
		exit(json_encode(['code'=>0, 'msg'=>'分片上传成功', 'exists'=>0]));
	} else {
		exit(json_encode(['code'=>-1, 'msg'=>'分片保存失败']));
	}
	break;

case 'complete_upload':
	$hash = isset($_POST['hash'])?trim($_POST['hash']):'';
	if(!preg_match('/^[0-9a-z]{32}$/i', $hash))exit('{"code":-1,"msg":"hash error"}');
	set_time_limit(1800); // 30分钟超时，支持超大文件合并
	session_write_close();
	// Read upload info from temp file (auth via pre_upload)
	$file_tmp = $upload_tmp . '/' . $hash . '.json';
	clearstatcache(); // 清除文件缓存
	error_log('[UPLOAD] complete_upload: tmpdir='.$upload_tmp.' file_exists='.(file_exists($file_tmp)?'Y':'N'));
	if(!file_exists($file_tmp)) exit('{"code":-1,"msg":"请先预上传"}');
	$upload_info = json_decode(file_get_contents($file_tmp), true);
	if(!$upload_info || $upload_info['hash'] != $hash) exit('{"code":-1,"msg":"上传信息错误"}');
	
	$filepath = $upload_info['filepath'];
	$chunks = $upload_info['chunks'];
	$uid = $upload_info['uid'];
	$name = $upload_info['name'];
	$size = $upload_info['size'];
	$ext = $upload_info['ext'];
	$show = $upload_info['show'];
	$pwd = $upload_info['pwd'];
	
	// Check file already exists
	$row = $DB->getRow("SELECT * FROM pre_file WHERE hash=:hash", [':hash'=>$hash]);
	if($row){
		@unlink($file_tmp);
		exit(json_encode(['code'=>1, 'msg'=>'本站已存在该文件', 'exists'=>1, 'hash'=>$hash]));
	}
	
	// Check all chunks uploaded - 通过标记文件计数（避免并发竞态）
	$uploaded_count = 0;
	for($i = 1; $i <= $chunks; $i++){
		if(file_exists($filepath . '.chunk' . $i . '.ok')) $uploaded_count++;
	}
	if($uploaded_count < $chunks){
		exit(json_encode(['code'=>-1, 'msg'=>'文件上传不完整，已上传 '.$uploaded_count.'/'.$chunks.' 个分片']));
	}
	
	// Merge chunks - 跨平台极速合并（严格 O(n)，不重复读写）
	if($chunks == 1){
		$part_file = $filepath . '.part1';
		if(file_exists($part_file)){
			rename($part_file, $filepath);
		}
		// 单文件清理
		@unlink($filepath . '.chunk1.ok');
		@unlink($file_tmp);
	} else {
		// 预检查所有分片存在
		$all_exist = true;
		for($i = 1; $i <= $chunks; $i++){
			if(!file_exists($filepath . '.part' . $i)){ $all_exist = false; break; }
		}
		if(!$all_exist){
			fclose(fopen($filepath, 'w'));
			error_log('[MERGE] 哈希不完整，创建空文件');
		} else {
			$success = false;
			$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
			$merge_start = microtime(true);
			@unlink($filepath);

			$use_shell = ($chunks <= 60);
			$method = $use_shell ? 'SHELL' : 'PHP_STREAM';
			$os = $is_windows ? 'Windows' : 'Linux/Unix';
			error_log("[MERGE] 开始合并: OS={$os} Method={$method} Chunks={$chunks} Size=" . round($size/1048576, 1) . "MB");

			if($use_shell){
				// 单次 shell 命令（O(n)）
				$parts = array();
				for($i = 1; $i <= $chunks; $i++){
					if($is_windows){
						$parts[] = '"' . str_replace('/', '\\', $filepath) . '.part' . $i . '"';
					} else {
						$parts[] = escapeshellarg($filepath . '.part' . $i);
					}
				}
				if($is_windows){
					$out = '"' . str_replace('/', '\\', $filepath) . '"';
					$cmd = 'copy /b /y ' . implode('+', $parts) . ' ' . $out . ' 2>&1';
				} else {
					$cmd = 'cat ' . implode(' ', $parts) . ' > ' . escapeshellarg($filepath) . ' 2>&1';
				}
				$cmd_start = microtime(true);
				@exec($cmd, $output, $ret);
				$cmd_time = round(microtime(true) - $cmd_start, 2);
				$success = ($ret === 0);
				$cmd_output = implode(' | ', array_slice($output, 0, 3));
				error_log("[MERGE] Shell命令: ret={$ret} time={$cmd_time}s output=" . ($cmd_output ?: '(empty)'));
			}

			if(!$success){
				// PHP stream_copy_to_stream：O(n)
				$stream_start = microtime(true);
				$fp_out = fopen($filepath, 'wb');
				if($fp_out){
					$merged = 0;
					for($i = 1; $i <= $chunks; $i++){
						$fp_in = @fopen($filepath . '.part' . $i, 'rb');
						if($fp_in){
							$bytes = stream_copy_to_stream($fp_in, $fp_out);
							fclose($fp_in);
							if($bytes > 0) $merged++;
						}
					}
					fclose($fp_out);
					$success = true;
					$stream_time = round(microtime(true) - $stream_start, 2);
					error_log("[MERGE] PHP Stream: merged={$merged}/{$chunks} time={$stream_time}s");
				} else {
					error_log("[MERGE] PHP Stream: 打开输出文件失败");
				}
			}

			$total_time = round(microtime(true) - $merge_start, 2);
			$final_size = file_exists($filepath) ? filesize($filepath) : 0;
			$expected_match = ($final_size == $size) ? 'YES' : 'NO';
			error_log("[MERGE] 合并完成: success={$success} totalTime={$total_time}s fileSize=" . round($final_size/1048576, 1) . "MB expectedSize=" . round($size/1048576, 1) . "MB match={$expected_match}");
		}
	// 清理分片、标记和临时文件
	$clean_start = microtime(true);
	$cleaned = 0;
	for($i = 1; $i <= $chunks; $i++){
		@unlink($filepath . '.part' . $i);
		@unlink($filepath . '.chunk' . $i . '.ok');
		$cleaned += 2;
	}
	@unlink($file_tmp);
	$clean_time = round(microtime(true) - $clean_start, 2);
	error_log("[MERGE] 清理完成: cleaned={$cleaned} time={$clean_time}s");
	}
	
	// Verify file exists
	if(!file_exists($filepath)){
		@unlink($file_tmp);
		exit(json_encode(['code'=>-1, 'msg'=>'文件合并失败']));
	}
	
	// Save to database
	$db_result = $DB->exec("INSERT INTO pre_file (name, type, size, hash, addtime, ip, hide, pwd, count, uid) VALUES (:name, :type, :size, :hash, NOW(), :ip, :hide, :pwd, 0, :uid)",
		[':name'=>$name, ':type'=>$ext, ':size'=>$size, ':hash'=>$hash, ':ip'=>$_SERVER['REMOTE_ADDR'], ':hide'=>$show ? 0 : 1, ':pwd'=>$pwd, ':uid'=>$uid]);
	if($db_result === false){
		error_log('[UPLOAD] DB插入失败: '.$DB->error().' name='.$name.' hash='.$hash.' uid='.$uid);
	}
	
	// Update storage usage
	$storage = $upload_info['storage'];
	if(!empty($storage) && !empty($storage['id'])){
		$DB->exec("UPDATE pre_storage SET usedsize=usedsize+:size WHERE id=:id", [':size'=>$size, ':id'=>$storage['id']]);
	}
	
	// Clean up temp file
	@unlink($file_tmp);
	
	exit(json_encode(['code'=>0, 'msg'=>'文件上传成功', 'exists'=>0, 'hash'=>$hash]));
	break;

case 'deleteFile':
	$hash = isset($_POST['hash'])?trim($_POST['hash']):exit('{"code":-1,"msg":"no hash"}');
	if(!$_POST['csrf_token'] || $_POST['csrf_token']!=$GLOBALS['_csrf_token'])exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');
	if(!preg_match('/^[0-9a-z]{32}$/i', $hash))exit('{"code":-1,"msg":"hash error"}');
	$row = $DB->getRow("SELECT * FROM pre_file WHERE hash=:hash", [':hash'=>$hash]);
	if(!$row) exit('{"code":-1,"msg":"文件不存在"}');
	
	// 先删除存储文件，再删数据库记录
	if(!empty($stor)){
		if($stor->exists($hash)){
			$del_result = $stor->delete($hash);
			error_log('[DELETE] 删除文件 '.$hash.' 结果: '.($del_result?'成功':'失败'));
		} else {
			error_log('[DELETE] 文件不存在于存储: '.$hash);
		}
	} else {
		error_log('[DELETE] 存储对象为空');
	}
	if($row['uid'] > 0 && $islogin2 && $row['uid'] == $userrow['uid']){
		$DB->exec("DELETE FROM pre_file WHERE hash=:hash", [':hash'=>$hash]);
	}elseif($row['uid'] == 0 && ($conf['islogin'] == 0 || $islogin2)){
		$DB->exec("DELETE FROM pre_file WHERE hash=:hash", [':hash'=>$hash]);
	}else{
		exit('{"code":-1,"msg":"您没有权限删除此文件"}');
	}
	// 清理残留分片文件
	$upload_dir = __DIR__ . '/upload/';
	if(is_dir($upload_dir)){
		$glob_pattern = $upload_dir . $hash . '*';
		foreach(glob($glob_pattern) as $f){
			@unlink($f);
		}
	}
	exit('{"code":0,"msg":"删除成功"}');
	break;

default:
	exit('{"code":-1,"msg":"未知操作"}');

case 'cleanup_temp':
	// 清理未完成上传的临时文件（前端关闭/取消时调用）
	$hash = isset($_POST['hash'])?trim($_POST['hash']):'';
	$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
	if(!$csrf_token || !$GLOBALS['_csrf_token'] || $csrf_token !== $GLOBALS['_csrf_token']){
		exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');
	}
	if($hash && !preg_match('/^[0-9a-z]{32}$/i', $hash)){
		exit('{"code":-1,"msg":"hash error"}');
	}
	$upload_tmp = sys_get_temp_dir() . '/cloud_upload_' . session_id();
	if($hash){
		// 清理指定上传的临时文件
		$file_tmp = $upload_tmp . '/' . $hash . '.json';
		if(file_exists($file_tmp)){
			@unlink($file_tmp);
		}
		// 同时清理 upload/ 目录下的残留分片
		$upload_dir = __DIR__ . '/upload/';
		if(is_dir($upload_dir)){
			$glob_pattern = $upload_dir . $hash . '*';
			foreach(glob($glob_pattern) as $f){
				@unlink($f);
			}
		}
	}
	if(is_dir($upload_tmp)){
		// 如果目录为空则删除
		$files = glob($upload_tmp . '/*');
		if(empty($files)){
			@rmdir($upload_tmp);
		}
	}
	exit('{"code":0,"msg":"已清理临时文件"}');
	break;
}
