<?php
/**
 * Zhifu Tron API - API密钥使用示例
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Zhifu\TronAPI\Tron;
use Zhifu\TronAPI\Exception\TronException;

// 初始化 Tron 对象
try {
    // 定义节点地址
    $fullNode = 'https://api.trongrid.io';
    $solidityNode = 'https://api.trongrid.io';
    $eventServer = 'https://api.trongrid.io';
    
    // 设置API密钥（从TronGrid获取：https://www.trongrid.io/dashboard）
    // 如果请求量大，需要使用API密钥
    $apiKey = 'YOUR_TRONGRID_API_KEY';  // 请替换为您从TronGrid获取的实际API密钥
    
    echo "===== API密钥使用示例 =====\n\n";
    
    // 方法1：在创建实例时设置API密钥
    echo "方法1：在创建实例时设置API密钥\n";
    $tron1 = new Tron($fullNode, $solidityNode, $eventServer, null, $apiKey);
    
    // 查询USDT余额
    $address = 'TJoq53NiXhrgC9G2KNvpKv2s6UkcdNRgFP';
    $usdt1 = $tron1->getUsdtBalance($address);
    echo "使用API密钥查询USDT余额: " . $usdt1 . " USDT\n\n";
    
    // 方法2：在创建实例后设置API密钥
    echo "方法2：在创建实例后设置API密钥\n";
    $tron2 = new Tron($fullNode, $solidityNode, $eventServer);
    $tron2->setApiKey($apiKey);
    
    // 查询USDT余额
    $usdt2 = $tron2->getUsdtBalance($address);
    echo "使用API密钥查询USDT余额: " . $usdt2 . " USDT\n\n";
    
    // 在实际应用中，API密钥应该存储在配置文件或环境变量中
    echo "注意: 在实际应用中，不要将API密钥硬编码在代码中。\n";
    echo "请使用配置文件或环境变量安全地存储API密钥。\n";
} catch (TronException $e) {
    echo "错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
} 