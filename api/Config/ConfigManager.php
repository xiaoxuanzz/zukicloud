<?php
namespace api\Config;

/**
 * 配置管理类 - 支持内存缓存
 */
class ConfigManager
{
    private $configFile;
    private $config = null;
    private $cacheTimeout = 30; // 缓存30秒
    private $lastLoadTime = 0;
    private $defaultConfig = [
        'api_enabled' => true
    ];
    
    public function __construct($configFile = null)
    {
        if ($configFile === null) {
            $configFile = dirname(__DIR__) . '/config.json';
        }
        $this->configFile = $configFile;
    }
    
    /**
     * 获取配置值
     */
    public function get($key, $default = null)
    {
        $config = $this->loadConfig();
        return isset($config[$key]) ? $config[$key] : $default;
    }
    
    /**
     * 设置配置值
     */
    public function set($key, $value)
    {
        $config = $this->loadConfig();
        $config[$key] = $value;
        return $this->saveConfig($config);
    }
    
    /**
     * 获取所有配置
     */
    public function getAll()
    {
        return $this->loadConfig();
    }
    
    /**
     * 保存整个配置数组
     */
    public function save(array $config)
    {
        return $this->saveConfig($config);
    }
    
    /**
     * 检查API是否启用
     */
    public function isApiEnabled()
    {
        return (bool)$this->get('api_enabled', true);
    }
    
    /**
     * 设置API启用状态
     */
    public function setApiEnabled($enabled)
    {
        return $this->set('api_enabled', (bool)$enabled);
    }
    
    /**
     * 加载配置文件（带内存缓存）
     */
    private function loadConfig()
    {
        $now = time();
        
        // 使用内存缓存，避免每次请求都读取文件
        if ($this->config !== null && ($now - $this->lastLoadTime) < $this->cacheTimeout) {
            return $this->config;
        }
        
        if (!file_exists($this->configFile)) {
            $this->config = $this->defaultConfig;
            $this->lastLoadTime = $now;
            return $this->config;
        }
        
        $content = file_get_contents($this->configFile);
        if ($content === false) {
            throw new \RuntimeException('Failed to read config file: ' . $this->configFile);
        }
        
        $config = json_decode($content, true);
        if (!is_array($config)) {
            $config = $this->defaultConfig;
        }
        
        $this->config = array_merge($this->defaultConfig, $config);
        $this->lastLoadTime = $now;
        
        return $this->config;
    }
    
    /**
     * 保存配置到文件
     */
    private function saveConfig(array $config)
    {
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode config to JSON');
        }
        
        if (file_put_contents($this->configFile, $json, LOCK_EX) === false) {
            throw new \RuntimeException('Failed to write config file: ' . $this->configFile);
        }
        
        $this->config = $config;
        $this->lastLoadTime = time();
        
        return true;
    }
    
    /**
     * 清除内存缓存
     */
    public function clearCache()
    {
        $this->config = null;
        $this->lastLoadTime = 0;
    }
    
    /**
     * 触发配置变更通知（可用于WebSocket或轮询）
     */
    public function notifyChange($key, $oldValue, $newValue)
    {
        $notificationFile = dirname($this->configFile) . '/.config_notify';
        $data = [
            'key' => $key,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'timestamp' => time()
        ];
        
        // 写入通知文件，供其他进程读取
        file_put_contents($notificationFile, json_encode($data) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
