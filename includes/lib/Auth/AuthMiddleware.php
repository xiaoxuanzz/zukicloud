<?php
namespace lib\Auth;

/**
 * 统一认证中间件 - 支持API密钥认证和管理员密钥认证两级权限
 * 增加详细调试日志
 */
class AuthMiddleware
{
    private $apiKeyManager;
    private $adminKey;
    private $apiKey;
    
    public function __construct($keysFile = null, $adminKeyFile = null)
    {
        $this->apiKeyManager = new ApiKeyManager($keysFile);
        
        if ($adminKeyFile === null) {
            $adminKeyFile = dirname(__DIR__, 3) . '/api/admin_key.txt';
        }
        $this->adminKey = $this->loadAdminKey($adminKeyFile);
    }
    
    /**
     * 加载管理员密钥
     */
    private function loadAdminKey($adminKeyFile)
    {
        if (!file_exists($adminKeyFile)) {
            return null;
        }
        $key = trim(file_get_contents($adminKeyFile));
        return empty($key) ? null : $key;
    }
    
    /**
     * 验证API密钥（普通用户权限）
     */
    public function authenticateApi()
    {
        $providedKey = $this->extractApiKey();
        
        $this->debugLog('authenticateApi', [
            'providedKey' => $providedKey ? 'SET (length: ' . strlen($providedKey) : 'NOT SET',
            'keysFile' => $this->apiKeyManager->getKeysFile(),
            'validKeys' => $this->apiKeyManager->getKeys()
        ]);
        
        if (empty($providedKey)) {
            $this->respondUnauthorized('Missing API key');
        }
        
        if (!$this->apiKeyManager->isValidKey($providedKey)) {
            $this->respondUnauthorized('Invalid API key');
        }
        
        return true;
    }
    
    /**
     * 验证管理员密钥（管理员权限）
     */
    public function authenticateAdmin()
    {
        $providedKey = $this->extractAdminKey();
        
        $this->debugLog('authenticateAdmin', [
            'providedKey' => $providedKey ? 'SET (length: ' . strlen($providedKey) : 'NOT SET',
            'adminKey' => $this->adminKey ? 'SET' : 'NOT SET'
        ]);
        
        if (empty($providedKey)) {
            $this->respondUnauthorized('Missing admin key');
        }
        
        if (!$this->isValidAdminKey($providedKey)) {
            $this->respondUnauthorized('Invalid admin key');
        }
        
        return true;
    }
    
    /**
     * 提取API密钥（支持URL参数、Header、Authorization三种方式）
     */
    private function extractApiKey()
    {
        // 1. 从URL参数 api_key 获取（最可靠，兼容所有服务器）
        if (isset($_GET['api_key'])) {
            $key = trim($_GET['api_key']);
            $this->debugLog('extractApiKey', ['source' => 'URL api_key', 'key' => $key]);
            return $key;
        }
        
        // 2. 从 X-API-KEY header 获取
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            $key = trim($_SERVER['HTTP_X_API_KEY']);
            $this->debugLog('extractApiKey', ['source' => 'X-API-KEY', 'key' => $key]);
            return $key;
        }
        
        // 3. 从 Authorization: Bearer <token> 获取
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s+(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                $key = trim($matches[1]);
                $this->debugLog('extractApiKey', ['source' => 'Authorization', 'key' => $key]);
                return $key;
            }
        }
        
        $this->debugLog('extractApiKey', ['source' => 'NONE', 'key' => 'NULL']);
        return null;
    }
    
    /**
     * 提取管理员密钥
     */
    private function extractAdminKey()
    {
        // 管理员密钥可以通过 X-Admin-Key header 或 query parameter 传递
        if (isset($_SERVER['HTTP_X_ADMIN_KEY'])) {
            return trim($_SERVER['HTTP_X_ADMIN_KEY']);
        }
        
        if (isset($_GET['admin_key'])) {
            return trim($_GET['admin_key']);
        }
        
        return null;
    }
    
    /**
     * 验证管理员密钥
     */
    private function isValidAdminKey($key)
    {
        if (empty($this->adminKey)) {
            return false;
        }
        return hash_equals($this->adminKey, $key);
    }
    
    /**
     * 返回未授权响应
     */
    private function respondUnauthorized($message)
    {
        $this->debugLog('respondUnauthorized', ['message' => $message]);
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);
        exit;
    }
    
    /**
     * 记录调试日志
     */
    private function debugLog($method, $data)
    {
        $logFile = dirname(__DIR__, 3) . '/api/auth_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $log = "[$timestamp] $method: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
        @file_put_contents($logFile, $log, FILE_APPEND);
    }
}
