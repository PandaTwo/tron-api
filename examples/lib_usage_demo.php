<?php

/**
 * TronAPI Lib Demo
 * 演示如何使用整合到项目中的TronAPI库
 */

// 加载自动加载器
require_once __DIR__ . '/../vendor/autoload.php';

// 确保目录存在
if (!file_exists(__DIR__ . '/../src')) {
    echo "错误: src 目录不存在，请确保已正确安装库\n";
    exit(1);
}

// 使用整合的库
use Zhifu\TronAPI\Tron;
use Zhifu\TronAPI\TRC20Contract;

try {
    // 创建Tron实例
    $tron = new Tron();
    
    // 设置USDT合约地址 (TRON网络上的USDT合约)
    $contractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
    
    // 创建TRC20合约实例
    $contract = new TRC20Contract($tron, $contractAddress);
    
    // 设置手续费上限 (5 TRX)
    $contract->setFeeLimit(5);
    
    // 获取并显示代币信息
    echo "======= TRC20代币信息 =======\n";
    echo "合约地址: {$contractAddress}\n";
    
    try {
        $name = $contract->name();
        echo "代币名称: {$name}\n";
    } catch (Exception $e) {
        echo "无法获取代币名称: " . $e->getMessage() . "\n";
    }
    
    try {
        $symbol = $contract->symbol();
        echo "代币符号: {$symbol}\n";
    } catch (Exception $e) {
        echo "无法获取代币符号: " . $e->getMessage() . "\n";
    }
    
    try {
        $decimals = $contract->decimals();
        echo "代币精度: {$decimals}\n";
    } catch (Exception $e) {
        echo "无法获取代币精度: " . $e->getMessage() . "\n";
    }
    
    try {
        $totalSupply = $contract->totalSupply();
        echo "代币总供应量: {$totalSupply}\n";
    } catch (Exception $e) {
        echo "无法获取代币总供应量: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // 演示内存管理功能
    echo "======= 内存管理性能测试 =======\n";
    $startMemory = memory_get_usage();
    echo "初始内存使用: " . formatBytes($startMemory) . "\n";
    
    // 模拟多次合约调用
    for ($i = 0; $i < 10; $i++) {
        $contract->name();
        $contract->symbol();
        $contract->decimals();
        
        // 每3次调用清理一次缓存
        if ($i % 3 == 0) {
            TRC20Contract::clearCache();
            echo "第{$i}次迭代后清理缓存，内存使用: " . formatBytes(memory_get_usage()) . "\n";
        }
    }
    
    $endMemory = memory_get_usage();
    echo "最终内存使用: " . formatBytes($endMemory) . "\n";
    echo "内存增长: " . formatBytes($endMemory - $startMemory) . "\n";
    
    // 主动触发垃圾回收
    gc_collect_cycles();
    echo "垃圾回收后内存使用: " . formatBytes(memory_get_usage()) . "\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}

/**
 * 格式化字节数为人类可读格式
 * 
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
} 