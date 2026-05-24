<?php
if (function_exists('opcache_reset')) {
    @opcache_reset();
    echo "[OK] opcache_reset() done.<br>";
} else {
    echo "[!] opcache_reset() not available.<br>";
}
if (function_exists('opcache_invalidate')) {
    @opcache_invaluate(__DIR__ . '/ajax_update.php', true);
    echo "[OK] opcache_invalidate() done.<br>";
} else {
    echo "[!] opcache_invalidate() not available.<br>";
}
@touch(__DIR__ . '/ajax_update.php');
echo "[OK] touch() done.<br>";
echo "Now try the update again.";
