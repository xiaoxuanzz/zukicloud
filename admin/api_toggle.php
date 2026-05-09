<?php
/**
 * API 开关管理页面 - 重构版
 */
define("IN_ADMIN", true);
include("../includes/common.php");

$title = "API 开关管理";

// 检查管理员登录
if ($islogin != 1) {
    exit("<script>window.location.href='./login.php';</script>");
}

// 自动获取当前域名和端口，生成API地址
function getApiUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // 如果HTTP_HOST不包含端口，且不是标准端口，则添加端口
    if (strpos($host, ':') === false) {
        $port = $_SERVER['SERVER_PORT'] ?? 80;
        if (($protocol === 'http://' && $port != 80) || ($protocol === 'https://' && $port != 443)) {
            $host = $host . ':' . $port;
        }
    }
    
    // 获取网站根目录路径
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $basePath = substr($scriptPath, 0, strpos($scriptPath, '/admin'));
    if ($basePath === false) {
        $basePath = '';
    }
    
    return $protocol . $host . $basePath . '/api/';
}

$apiUrl = getApiUrl();

// 初始化配置管理器
require_once dirname(__DIR__) . '/includes/autoloader.php';
use api\Config\ConfigManager;

$configManager = new ConfigManager();
$configPath = dirname(__DIR__) . '/api/config.json';

// 当前状态
$enabled = $configManager->isApiEnabled();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enabled'])) {
    $newEnabled = (bool)$_POST['enabled'];
    $oldEnabled = $enabled;
    
    try {
        $configManager->setApiEnabled($newEnabled);
        $enabled = $newEnabled;
        
        // 记录操作日志
        $message = "API 已" . ($enabled ? '开启' : '关闭') . " (操作人: {$conf['admin_user']})";
        error_log($message);
        
        echo '<script>showToast("' . ($enabled ? 'API 已开启' : 'API 已关闭') . '", "success");</script>';
    } catch (\Exception $e) {
        echo '<script>showToast("保存失败: ' . addslashes($e->getMessage()) . '", "danger");</script>';
    }
}

// 获取或生成API密钥
$keysFile = dirname(__DIR__) . '/api/keys.txt';
$apiKey = '';
$apiKeyManager = new \lib\Auth\ApiKeyManager($keysFile);

try {
    $keys = $apiKeyManager->getKeys();
    $apiKey = $keys[0] ?? '';
} catch (\Exception $e) {
    // 忽略错误
}

// 如果API开启但没有密钥，自动生成
if (empty($apiKey) && $enabled) {
    try {
        $apiKey = $apiKeyManager->addKey();
        if (!$apiKey) {
            $apiKey = '';
        }
    } catch (\Exception $e) {
        // 忽略错误
    }
}

// 获取或生成管理员密钥
$adminKeyFile = dirname(__DIR__) . '/api/admin_key.txt';
$adminKey = '';
if (file_exists($adminKeyFile)) {
    $adminKey = trim(file_get_contents($adminKeyFile));
}
if (empty($adminKey)) {
    $adminKey = bin2hex(random_bytes(16));
    file_put_contents($adminKeyFile, $adminKey, LOCK_EX);
}

ob_start();
?>

<style>
@media (max-width: 767px) {
    .api-key-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .api-key-group .input-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .api-key-group .input-group-btn {
        display: flex;
        gap: 8px;
        width: 100%;
    }
    .api-key-group .input-group-btn .btn {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}
</style>

<div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="fa fa-toggle-on"></i> API 开关管理
            </h3>
        </div>
    <div class="panel-body">
        <!-- 当前状态 -->
        <div class="form-group">
            <label class="col-sm-2 control-label">当前状态</label>
            <div class="col-sm-10">
                <p class="form-control-static">
                    <?php if ($enabled): ?>
                        <span class="label label-success" style="font-size:14px;">
                            已开启
                        </span>
                    <?php else: ?>
                        <span class="label label-danger" style="font-size:14px;">
                            已关闭
                        </span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <!-- 操作表单 -->
        <form method="post" class="form-horizontal" style="margin-top:20px;">
            <div class="form-group">
                <label class="col-sm-2 control-label">操作</label>
                <div class="col-sm-10">
                    <label class="radio-inline">
                        <input type="radio" name="enabled" value="1" <?php echo $enabled ? 'checked' : '' ?>> 开启
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="enabled" value="0" <?php echo !$enabled ? 'checked' : '' ?>> 关闭
                    </label>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" class="btn btn-primary btn-lg">保存设置</button>
                </div>
            </div>
        </form>
        
        <?php if ($enabled): ?>
        <hr style="margin:30px 0;">
        <h4 style="margin-bottom:20px;"><i class="fa fa-info-circle"></i> API 信息</h4>
        
        <!-- API 地址 -->
        <div class="form-group">
            <label class="col-sm-2 control-label">API 地址</label>
            <div class="col-sm-10">
                <div class="input-group">
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($apiUrl) ?>" readonly>
                    <span class="input-group-btn">
                        <button class="btn btn-default" onclick="copyText('<?php echo htmlspecialchars($apiUrl) ?>')" type="button">复制</button>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- API 密钥 -->
        <div class="form-group">
            <label class="col-sm-2 control-label">API 密钥</label>
            <div class="col-sm-10">
                <div class="input-group api-key-group">
                    <input type="text" id="apiKeyInput" class="form-control" value="<?php echo htmlspecialchars($apiKey) ?>" readonly>
                    <span class="input-group-btn">
                        <button class="btn btn-default" onclick="copyText(document.getElementById('apiKeyInput').value)" style="margin-right:8px;">复制</button>
                        <button class="btn btn-warning" onclick="regenerateApiKey()">重新生成</button>
                    </span>
                </div>
                <?php if ($apiKey): ?>
                <p class="help-block">在请求头中添加: <code>X-API-KEY: <?php echo htmlspecialchars(substr($apiKey, 0, 8)) ?>...</code></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 管理员密钥 -->
        <div class="form-group">
            <label class="col-sm-2 control-label">管理员密钥</label>
            <div class="col-sm-10">
                <div class="input-group api-key-group">
                    <input type="text" id="adminKeyInput" class="form-control" value="<?php echo htmlspecialchars($adminKey) ?>" readonly>
                    <span class="input-group-btn">
                        <button class="btn btn-default" onclick="copyText(document.getElementById('adminKeyInput').value)" style="margin-right:8px;">复制</button>
                        <button class="btn btn-warning" onclick="regenerateAdminKey()">重新生成</button>
                    </span>
                </div>
                <p class="help-block">用于管理操作，在请求头中添加: <code>X-Admin-Key: <?php echo htmlspecialchars(substr($adminKey, 0, 8)) ?>...</code></p>
            </div>
        </div>
        
        <!-- API 使用示例 -->
        <div class="form-group">
            <label class="col-sm-2 control-label">使用示例</label>
            <div class="col-sm-10">
                <pre style="background:#f5f5f5;padding:15px;border-radius:6px;overflow-x:auto;"><code>curl -X POST "<?php echo htmlspecialchars($apiUrl) ?>?action=upload" \
  -H "X-API-KEY: <?php echo htmlspecialchars($apiKey) ?>" \
  -F "file=@/path/to/file.jpg"</code></pre>
            </div>
        </div>
        
        <!-- API 开发文档链接 -->
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <a href="/api/docs.html" target="_blank">API开发文档（接口说明、调用示例、错误码）</a>
            </div>
        </div>
        
        <!-- API 实例调用界面链接 -->
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <a href="/api/example.html" target="_blank">API实例调文档</a>
            </div>
        </div>
		
		<div class="form-group">
		    <div class="col-sm-offset-2 col-sm-10">
		        <a href="../api_test.html" target="_blank">API调用测试界面（第三方编写时参考）</a>
		    </div>
		</div>
        
        <!-- 可用接口列表 -->
        <div class="form-group">
            <label class="col-sm-2 control-label">可用接口</label>
            <div class="col-sm-10">
                <table class="table table-bordered" style="margin-top:10px;">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>方法</th>
                            <th>说明</th>
                            <th>权限</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>upload</code></td>
                            <td>POST</td>
                            <td>上传文件</td>
                            <td><span class="label label-info">API密钥</span></td>
                        </tr>
                        <tr>
                            <td><code>list</code></td>
                            <td>GET</td>
                            <td>列出文件</td>
                            <td><span class="label label-info">API密钥</span></td>
                        </tr>
                        <tr>
                            <td><code>file</code></td>
                            <td>GET</td>
                            <td>下载文件</td>
                            <td><span class="label label-info">API密钥</span></td>
                        </tr>
                        <tr>
                            <td><code>status</code></td>
                            <td>GET</td>
                            <td>查询状态</td>
                            <td><span class="label label-default">公开</span></td>
                        </tr>
                        <tr>
                            <td><code>admin_toggle</code></td>
                            <td>POST</td>
                            <td>开关API</td>
                            <td><span class="label label-danger">管理员</span></td>
                        </tr>
                        <tr>
                            <td><code>regenerate_key</code></td>
                            <td>POST</td>
                            <td>重新生成密钥</td>
                            <td><span class="label label-danger">管理员</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyText(text) {
    var dummy = document.createElement("textarea");
    document.body.appendChild(dummy);
    dummy.value = text;
    dummy.select();
    document.execCommand("copy");
    document.body.removeChild(dummy);
    showToast('已复制到剪贴板', 'success');
}

function regenerateApiKey() {
    zkConfirm({
        icon: 'warning',
        title: '确认重新生成API密钥',
        subtitle: '旧密钥将失效，确定要重新生成吗？',
        confirmText: '确认重新生成',
        confirmClass: 'btn-danger',
        onConfirm: function() {
            fetch('../api/?action=regenerate_key', {
                headers: {
                    'X-Admin-Key': document.getElementById('adminKeyInput').value
                }
            })
            .then(r => r.json())
            .then(d => {
                if (d.key) {
                    document.getElementById('apiKeyInput').value = d.key;
                    showToast('API密钥已重新生成', 'success');
                } else {
                    showToast('生成失败: ' + (d.error || '未知错误'), 'error');
                }
            })
            .catch(e => {
                showToast('请求失败: ' + e.message, 'error');
            });
        }
    });
}

function regenerateAdminKey() {
    zkConfirm({
        icon: 'warning',
        title: '确认重新生成管理员密钥',
        subtitle: '旧密钥将失效，确定要重新生成吗？',
        confirmText: '确认重新生成',
        confirmClass: 'btn-danger',
        onConfirm: function() {
            // 这里应该调用一个专门的管理员接口来重新生成密钥
            showToast('请联系系统管理员手动更新管理员密钥', 'warning');
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include "./head.php";
?>
