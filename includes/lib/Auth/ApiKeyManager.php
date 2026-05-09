<?php
namespace lib\Auth;

/**
 * API密钥管理类
 */
class ApiKeyManager
{
    private $keysFile;
    private $keys = null;
    
    public function __construct($keysFile = null)
    {
        if ($keysFile === null) {
            $keysFile = dirname(__DIR__, 3) . '/api/keys.txt';
        }
        $this->keysFile = $keysFile;
    }
    
    /**
     * 验证API密钥是否有效
     */
    public function isValidKey($key)
    {
        if (empty($key)) {
            return false;
        }
        
        $keys = $this->loadKeys();
        
        // 如果keys.txt不存在或为空，仅在开发环境允许访问
        if (empty($keys)) {
            return $this->isDevelopmentMode();
        }
        
        // Trim the provided key (in case it has whitespace)
        $key = trim($key);
        
        return in_array($key, $keys, true);
    }
    
    /**
     * 添加新密钥
     */
    public function addKey($key = null)
    {
        if ($key === null) {
            $key = bin2hex(random_bytes(16));
        }
        
        $keys = $this->loadKeys();
        $keys[] = $key;
        
        if ($this->saveKeys($keys)) {
            return $key;
        }
        
        return false;
    }
    
    /**
     * 重新生成密钥（删除旧的，创建新的）
     */
    public function regenerateKey()
    {
        $newKey = bin2hex(random_bytes(16));
        
        if (file_put_contents($this->keysFile, $newKey . PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException('Failed to write API key file');
        }
        
        $this->keys = [$newKey]; // 重置缓存
        return $newKey;
    }
    
    /**
     * 获取所有密钥
     */
    public function getKeys()
    {
        return $this->loadKeys();
    }
    
    /**
     * 获取密钥文件路径
     */
    public function getKeysFile()
    {
        return $this->keysFile;
    }
    
    /**
     * 加载密钥列表
     */
    private function loadKeys()
    {
        if ($this->keys !== null) {
            return $this->keys;
        }
        
        if (!file_exists($this->keysFile)) {
            $this->keys = [];
            return [];
        }
        
        $content = file_get_contents($this->keysFile);
        if ($content === false) {
            throw new \RuntimeException('Failed to read keys file');
        }
        
        // Trim the entire content first, then split by newlines
        $content = trim($content);
        $this->keys = array_filter(
            array_map('trim', explode("\n", $content)),
            function($key) { return !empty($key); }
        );
        
        return $this->keys;
    }
    
    /**
     * 保存密钥列表
     */
    private function saveKeys(array $keys)
    {
        $content = implode(PHP_EOL, $keys) . PHP_EOL;
        
        if (file_put_contents($this->keysFile, $content, LOCK_EX) === false) {
            return false;
        }
        
        $this->keys = $keys;
        return true;
    }
    
    /**
     * 检查是否为开发模式
     */
    private function isDevelopmentMode()
    {
        // 可以通过环境变量或配置文件控制
        return isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development';
    }
}
