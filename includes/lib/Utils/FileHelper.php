<?php
namespace lib\Utils;

/**
 * 文件操作工具类
 */
class FileHelper
{
    /**
     * 安全的mkdir - 使用0755权限
     */
    public static function safeMkdir($path, $recursive = true)
    {
        if (is_dir($path)) {
            return true;
        }
        
        if (mkdir($path, 0755, $recursive) === false) {
            throw new \RuntimeException('Failed to create directory: ' . $path);
        }
        
        // 确保权限正确设置
        chmod($path, 0755);
        
        return true;
    }
    
    /**
     * 路径遍历检查 - 使用realpath统一处理
     */
    public static function checkPathTraversal($inputPath, $basePath)
    {
        $basePath = realpath($basePath);
        if ($basePath === false) {
            throw new \InvalidArgumentException('Invalid base path');
        }
        
        // 规范化路径
        $inputPath = trim($inputPath, '/');
        $inputPath = preg_replace('#[/\\\\]+#', '/', $inputPath);
        
        // 检查是否包含..
        if (strpos($inputPath, '..') !== false) {
            return false;
        }
        
        $fullPath = $basePath . '/' . $inputPath;
        $realPath = realpath($fullPath);
        
        // 如果路径不存在，检查父目录
        if ($realPath === false) {
            $realPath = realpath(dirname($fullPath));
            if ($realPath === false) {
                return false;
            }
        }
        
        // 确保路径在basePath内
        if (strpos($realPath, $basePath) !== 0) {
            return false;
        }
        
        return $fullPath;
    }
    
    /**
     * 生成唯一文件名（避免覆盖）
     */
    public static function generateUniqueFilename($targetDir, $baseName)
    {
        $targetPath = rtrim($targetDir, '/') . '/' . $baseName;
        
        if (!file_exists($targetPath)) {
            return $targetPath;
        }
        
        $counter = 1;
        $dotPos = strrpos($baseName, '.');
        
        while (file_exists($targetPath)) {
            if ($dotPos === false) {
                $newName = $baseName . '(' . $counter . ')';
            } else {
                $ext = substr($baseName, $dotPos);
                $nameOnly = substr($baseName, 0, $dotPos);
                $newName = $nameOnly . '(' . $counter . ')' . $ext;
            }
            $targetPath = rtrim($targetDir, '/') . '/' . $newName;
            $counter++;
        }
        
        return $targetPath;
    }
    
    /**
     * 安全的文件大小获取
     */
    public static function getFileSize($path)
    {
        try {
            if (!file_exists($path)) {
                return 0;
            }
            $size = filesize($path);
            return $size !== false ? $size : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * 安全的文件修改时间获取
     */
    public static function getFileMTime($path)
    {
        try {
            if (!file_exists($path)) {
                return null;
            }
            $mtime = filemtime($path);
            return $mtime !== false ? $mtime : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 递归删除目录
     */
    public static function recursiveDelete($path)
    {
        if (!is_dir($path)) {
            return unlink($path);
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        
        return rmdir($path);
    }
    
    /**
     * 获取MIME类型
     */
    public static function getMimeType($path)
    {
        try {
            $mime = mime_content_type($path);
            return $mime !== false ? $mime : 'application/octet-stream';
        } catch (\Exception $e) {
            return 'application/octet-stream';
        }
    }
}
