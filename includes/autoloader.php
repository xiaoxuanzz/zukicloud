<?php

/**
 * 自动载入函数 - 更新版支持命名空间
 */
class Autoloader
{
    private static $instance = null;
    private $prefixes = [];
    
    /**
     * 向PHP注册自动载入函数
     */
    public static function register()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        spl_autoload_register([self::$instance, 'autoload']);
        
        // 注册命名空间映射
        self::$instance->addNamespace('lib\Auth', SYSTEM_ROOT . 'lib/Auth');
        self::$instance->addNamespace('lib\Utils', SYSTEM_ROOT . 'lib/Utils');
        self::$instance->addNamespace('api\Config', ROOT . 'api/Config');
        self::$instance->addNamespace('api\Controllers', ROOT . 'api/Controllers');
        self::$instance->addNamespace('api\Auth', ROOT . 'api/Auth');
    }
    
    /**
     * 添加命名空间映射
     */
    public function addNamespace($namespace, $dir)
    {
        $this->prefixes[$namespace] = $dir;
    }
    
    /**
     * 根据类名载入所在文件
     */
    public function autoload($className)
    {
        // 处理命名空间
        foreach ($this->prefixes as $prefix => $dir) {
            if (strpos($className, $prefix) === 0) {
                $relativeClass = substr($className, strlen($prefix));
                $file = $dir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
                
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
        }
        
        // 兼容旧版无命名空间类
        $filePath = SYSTEM_ROOT . $className . '.php';
        $filePath = str_replace('\\', DIRECTORY_SEPARATOR, $filePath);
        
        if (file_exists($filePath)) {
            require_once $filePath;
            return true;
        }
        
        return false;
    }
}
