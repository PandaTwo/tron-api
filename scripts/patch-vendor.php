<?php
/**
 * 修补iexbase/tron-api库中的内存泄漏问题
 * 
 * 该脚本用于修复底层依赖库中的内存溢出问题，可以在安装后自动执行
 */

// 找出vendor目录位置
$vendorDir = __DIR__ . '/../vendor';
if (!is_dir($vendorDir)) {
    $vendorDir = dirname(__DIR__, 4) . '/vendor'; // 尝试作为依赖项安装时的位置
}

// 目标文件路径
$targetFile = $vendorDir . '/iexbase/tron-api/src/TRC20Contract.php';

if (!file_exists($targetFile)) {
    echo "目标文件不存在: $targetFile\n";
    exit(1);
}

// 备份原始文件
$backupFile = $targetFile . '.bak';
if (!file_exists($backupFile)) {
    copy($targetFile, $backupFile);
    echo "已备份原始文件: $backupFile\n";
}

// 读取文件内容
$content = file_get_contents($targetFile);

// 修补文件：添加内存优化和变量清理代码
$content = preg_replace(
    '/(function transfer\(string \$to, \$amount, \?string \$from = null\)(?:.|\n)*?{)/i',
    '$1' . PHP_EOL . '        // 避免内存泄漏，手动清理内部变量' . PHP_EOL . '        $this->clearLocalVars();' . PHP_EOL,
    $content
);

// 添加clearLocalVars方法
$content = str_replace(
    'class TRC20Contract',
    'class TRC20Contract' . PHP_EOL . '{' . PHP_EOL . '    /**' . PHP_EOL . '     * 清理内部变量以防止内存泄漏' . PHP_EOL . '     */' . PHP_EOL . '    private function clearLocalVars()' . PHP_EOL . '    {' . PHP_EOL . '        if (isset($GLOBALS["GC_COLLECT_CYCLES"]) && $GLOBALS["GC_COLLECT_CYCLES"] === true) {' . PHP_EOL . '            return;' . PHP_EOL . '        }' . PHP_EOL . '        ' . PHP_EOL . '        $GLOBALS["GC_COLLECT_CYCLES"] = true;' . PHP_EOL . '        if (function_exists("gc_collect_cycles")) {' . PHP_EOL . '            gc_collect_cycles();' . PHP_EOL . '        }' . PHP_EOL . '        $GLOBALS["GC_COLLECT_CYCLES"] = false;' . PHP_EOL . '    }' . PHP_EOL . PHP_EOL . '    ',
    $content
);

// 在析构函数中添加清理
if (strpos($content, 'function __destruct()') === false) {
    $content = str_replace(
        'class TRC20Contract',
        'class TRC20Contract' . PHP_EOL . '{' . PHP_EOL . '    /**' . PHP_EOL . '     * 析构函数 - 清理资源' . PHP_EOL . '     */' . PHP_EOL . '    public function __destruct()' . PHP_EOL . '    {' . PHP_EOL . '        $this->clearLocalVars();' . PHP_EOL . '    }' . PHP_EOL . PHP_EOL . '    ',
        $content
    );
}

// 在关键方法中添加垃圾回收调用
$methodsToFix = [
    'function balanceOf',
    'function allowance',
    'function approve',
    'function name',
    'function symbol',
    'function decimals',
    'function totalSupply'
];

foreach ($methodsToFix as $method) {
    $content = preg_replace(
        '/(' . preg_quote($method) . '.*?{.*?return .*?;)\s*}/is',
        '$1' . PHP_EOL . '        $this->clearLocalVars();' . PHP_EOL . '    }',
        $content
    );
}

// 保存修改后的文件
file_put_contents($targetFile, $content);
echo "已成功修补文件: $targetFile\n";

// 修改composer.json，添加post-install-cmd脚本
$composerFile = __DIR__ . '/../composer.json';
if (file_exists($composerFile)) {
    $composerContent = file_get_contents($composerFile);
    $composerJson = json_decode($composerContent, true);
    
    if (!isset($composerJson['scripts'])) {
        $composerJson['scripts'] = [];
    }
    
    if (!isset($composerJson['scripts']['post-install-cmd'])) {
        $composerJson['scripts']['post-install-cmd'] = [];
    }
    
    // 添加修补脚本
    if (!in_array('php scripts/patch-vendor.php', $composerJson['scripts']['post-install-cmd'])) {
        $composerJson['scripts']['post-install-cmd'][] = 'php scripts/patch-vendor.php';
        file_put_contents($composerFile, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "已更新composer.json，添加安装后自动修补脚本\n";
    }
}

echo "修补完成！\n"; 