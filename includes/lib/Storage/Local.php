<?php
namespace lib\Storage;
use \lib\IStorage;

class Local implements IStorage {
	private $path = null;
	private $errmsg;

	public function __construct($filepath) {
		clearstatcache(true);
		$base = rtrim(ROOT, '/\\') . DIRECTORY_SEPARATOR;
		$fullPath = $base . rtrim($filepath ?: 'upload', '/\\') . DIRECTORY_SEPARATOR;
		if(!is_dir($fullPath)){
			$fullPath = $base . 'upload' . DIRECTORY_SEPARATOR;
		}
		if(!is_dir($fullPath)){
			@mkdir($fullPath, 0755, true);
		}
		$this->path = $fullPath;
	}

	public function getClient(){
		return null;
	}
	
	public function errmsg(){
		return $this->errmsg;
	}

	public function exists($name) {
		return file_exists($this->path.$name);
	}

	public function get($name) {
		return file_get_contents($this->path.$name);
	}

	public function downfile($name, $range = false) {
		$start = $range[0];
		$end = $range[1];
		$read_buffer = 1024 * 200;
		$handle = fopen($this->path.$name, 'rb');
		if($start > 0){
			fseek($handle, $start, 0);
		}
		$cur = $start;
		while(!feof($handle) && $cur<=$end) {
			echo fread($handle, min($read_buffer, ($end - $cur) + 1));
			$cur += $read_buffer;
			flush();
		}
		fclose($handle);
		return true;
	}

	public function upload($name, $tmpfile, $content_type = null) {
		return move_uploaded_file($tmpfile, $this->path.$name);
	}

	public function savefile($name, $tmpfile, $content_type = null) {
		return rename($tmpfile, $this->path.$name);
	}

	public function getinfo($name) {
		$result['length'] = filesize($this->path.$name);
		if(function_exists("finfo_open")){
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$type = finfo_file($finfo, $this->path.$name);
			finfo_close($finfo);
			$result['content-length'] = $type;
		}else{
			$result['content-length'] = null;
		}
		return $result;
	}

	public function delete($name) {
		$file = $this->path . basename($name);
		if(file_exists($file)){
			$result = @unlink($file);
			if($result === false){
				error_log('[STORAGE] 删除失败: '.$file.' 权限: '.(is_writable($file)?'可写':'不可写'));
			}
			// 清理空文件夹
			$dir = dirname($file);
			while($dir && $dir !== rtrim($this->path, '/\\') && is_dir($dir) && count(scandir($dir)) <= 2){
				@rmdir($dir);
				$dir = dirname($dir);
			}
			return $result;
		} else {
			error_log('[STORAGE] 文件不存在: '.$file.' 存储路径: '.$this->path.' hash: '.$name);
		}
		return false;
	}

	public function getUploadParam($name, $filename, $max_file_size = 0){
		return false;
	}

	public function getDownUrl($name, $filename, $content_type = null){
		return false;
	}
}