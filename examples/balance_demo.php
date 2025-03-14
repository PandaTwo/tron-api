<?php
/**
 * Zhifu Tron API - 余额查询示例
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Zhifu\TronAPI\Tron;
use Zhifu\TronAPI\Exception\TronException;

// 初始化 Tron 对象
try {
    // 使用主网节点
    $fullNode = 'https://api.trongrid.io';
    $solidityNode = 'https://api.trongrid.io';
    $eventServer = 'https://api.trongrid.io';
    
    $tron = new Tron($fullNode, $solidityNode, $eventServer);
    
    // 设置API版本
    $tron->setApiVersion('v1');
    
    echo "===== USDT余额查询示例 =====\n\n";
    
    // 示例地址 - 有10.0701 USDT余额的地址
    $address = 'TJoq53NiXhrgC9G2KNvpKv2s6UkcdNRgFP';
    $tron->setAddress($address);
    
    echo "查询地址: " . $address . "\n\n";
    
    // 2. 查询USDT余额
    echo "查询USDT余额\n";
    try {
        // 获取USDT余额
        $usdtBalance = $tron->getUsdtBalance();
        echo "USDT余额: " . $usdtBalance . " USDT\n";
    } catch (TronException $e) {
        echo "查询USDT余额失败: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // 3. 查询其他TRC20代币余额
    echo "查询其他TRC20代币余额\n";
    try {
        // 以WBTC合约为例（请替换为您想查询的合约地址）
        $wbtcContract = 'THbVQp8kMjStKNnf2iCY6NEzThKMK5aBHg'; // WBTC合约地址
        $wbtcBalance = $tron->getTokenBalance($wbtcContract, $address);
        echo "WBTC余额: " . $wbtcBalance . " WBTC\n";
    } catch (TronException $e) {
        echo "查询WBTC余额失败: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // 4. 批量查询多个地址余额
    echo "批量查询多个地址USDT余额\n";
    $addresses = [
        'TYPrKF2sevXuE86Xo3Y2mhFnjseiUcybny',
        'TGjYzgCyPobsNS9n6WcbdLVR9dH7mWqFx7'
    ];
    
    foreach ($addresses as $addr) {
        echo "地址: " . $addr . "\n";
        try {
            $usdtBal = $tron->getUsdtBalance($addr);
            echo "  USDT余额: " . $usdtBal . " USDT\n";
        } catch (TronException $e) {
            echo "  查询余额失败: " . $e->getMessage() . "\n";
        }
    }
    
} catch (TronException $e) {
    echo "错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
} 