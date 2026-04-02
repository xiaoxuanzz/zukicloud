<?php
$phpOk = version_compare(PHP_VERSION, '7.4.0', '>=');
$exts = [
    ['name' => 'PDO', 'ext' => 'pdo', 'loaded' => extension_loaded('pdo')],
    ['name' => 'PDO MySQL', 'ext' => 'pdo_mysql', 'loaded' => extension_loaded('pdo_mysql')],
    ['name' => 'mbstring', 'ext' => 'mbstring', 'loaded' => extension_loaded('mbstring')],
    ['name' => 'OpenSSL', 'ext' => 'openssl', 'loaded' => extension_loaded('openssl')],
    ['name' => 'GD', 'ext' => 'gd', 'loaded' => extension_loaded('gd')],
    ['name' => 'cURL', 'ext' => 'curl', 'loaded' => extension_loaded('curl')],
    ['name' => 'JSON', 'ext' => 'json', 'loaded' => extension_loaded('json')],
    ['name' => 'Fileinfo', 'ext' => 'fileinfo', 'loaded' => extension_loaded('fileinfo')],
    ['name' => 'Tokenizer', 'ext' => 'tokenizer', 'loaded' => extension_loaded('tokenizer')],
    ['name' => 'XML', 'ext' => 'xml', 'loaded' => extension_loaded('xml')],
    ['name' => 'bcmath', 'ext' => 'bcmath', 'loaded' => extension_loaded('bcmath')],
];

$allPassed = $phpOk && !in_array(false, array_column($exts, 'loaded'));
?>
<div class="section-title"><i class="fa fa-server"></i> 服务器环境检测</div>

<div class="check-item">
    <span><i class="fa fa-code"></i> PHP 版本</span>
    <span class="<?php echo $phpOk ? 'badge-ok' : 'badge-fail'; ?>">
        <i class="fa <?php echo $phpOk ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
        <?php echo PHP_VERSION; ?> <?php echo $phpOk ? '✓' : '✗ 需要≥7.4'; ?>
    </span>
</div>

<?php foreach ($exts as $e): ?>
<div class="check-item">
    <span><i class="fa fa-puzzle-piece"></i> <?php echo $e['name']; ?></span>
    <span class="<?php echo $e['loaded'] ? 'badge-ok' : 'badge-fail'; ?>">
        <i class="fa <?php echo $e['loaded'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
        <?php echo $e['loaded'] ? '已安装' : '未安装'; ?>
    </span>
</div>
<?php endforeach; ?>

<div class="check-item">
    <span><i class="fa fa-lock"></i> config.php 可写</span>
    <?php
    $base = dirname(__DIR__);
    $configWritable = is_writable($base . '/config.php') || !file_exists($base . '/config.php');
    ?>
    <span class="<?php echo $configWritable ? 'badge-ok' : 'badge-fail'; ?>">
        <i class="fa <?php echo $configWritable ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
        <?php echo $configWritable ? '可写' : '不可写'; ?>
    </span>
</div>

<?php if ($allPassed && $configWritable): ?>
    <div class="alert-custom alert-success-custom">
        <i class="fa fa-check-circle"></i>
        <span>环境检测通过，可以继续安装。</span>
    </div>
    <div class="btn-row">
        <a href="install.php?step=2" class="btn btn-next"><i class="fa fa-arrow-right"></i> 下一步</a>
    </div>
<?php else: ?>
    <div class="alert-custom alert-danger-custom">
        <i class="fa fa-exclamation-triangle"></i>
        <span>环境检测未通过，请修复标红项后重试。</span>
    </div>
    <div class="btn-row">
        <button class="btn btn-next" onclick="location.reload()"><i class="fa fa-refresh"></i> 重新检测</button>
    </div>
<?php endif; ?>
