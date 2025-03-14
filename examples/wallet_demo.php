<?php
/**
 * Zhifu Tron API - 钱包管理功能示例
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Zhifu\TronAPI\Tron;
use Zhifu\TronAPI\Exception\TronException;

// 辅助函数：格式化地址显示
function formatAddress($address) {
    if (is_array($address)) {
        if (isset($address['base58'])) {
            return $address['base58'];
        } elseif (isset($address[0])) {
            return $address[0];
        } else {
            return json_encode($address);
        }
    }
    return $address;
}

// 初始化 Tron 对象
try {
    // 使用主网节点
    $fullNode = 'https://api.trongrid.io';
    $solidityNode = 'https://api.trongrid.io';
    $eventServer = 'https://api.trongrid.io';
    
    $tron = new Tron($fullNode, $solidityNode, $eventServer);
    
    echo "===== 钱包管理功能演示 =====\n\n";
    
    // 1. 生成新的钱包地址
    echo "1. 生成新的钱包地址\n";
    $wallet = $tron->generateWallet();
    echo "私钥: " . $wallet['privateKey'] . "\n";
    echo "地址 (Base58): " . $wallet['address'] . "\n";
    echo "地址 (Hex): " . $wallet['hexAddress'] . "\n";
    if (!empty($wallet['publicKey'])) {
        echo "公钥: " . $wallet['publicKey'] . "\n";
    }
    echo "\n";
    
    // 2. 根据私钥获取地址
    echo "2. 根据私钥获取地址\n";
    $privateKey = $wallet['privateKey']; // 使用刚生成的私钥
    $addressInfo = $tron->getAddressFromPrivateKey($privateKey);
    echo "地址 (Base58): " . $addressInfo['address'] . "\n";
    echo "地址 (Hex): " . $addressInfo['hexAddress'] . "\n";
    if (!empty($addressInfo['publicKey'])) {
        echo "公钥: " . $addressInfo['publicKey'] . "\n";
    }
    echo "\n";
    
    // 3. 验证私钥和地址是否匹配
    echo "3. 验证私钥和地址是否匹配\n";
    $isValid = $tron->validatePrivateKey($privateKey, $wallet['address']);
    echo "验证结果: " . ($isValid ? '匹配' : '不匹配') . "\n\n";
    
    // 4. 验证生成的私钥和地址是否一一对应
    echo "4. 私钥和地址一一对应性验证\n";
    echo "原始地址: " . $wallet['address'] . "\n";
    echo "从私钥恢复的地址: " . $addressInfo['address'] . "\n";
    echo "地址比较结果: " . ($wallet['address'] === $addressInfo['address'] ? '一致' : '不一致') . "\n";
    
    echo "原始Hex地址: " . $wallet['hexAddress'] . "\n";
    echo "从私钥恢复的Hex地址: " . $addressInfo['hexAddress'] . "\n";
    echo "Hex地址比较结果: " . ($wallet['hexAddress'] === $addressInfo['hexAddress'] ? '一致' : '不一致') . "\n\n";
    
    // 5. 测试多个钱包一一对应关系 - 生成几个钱包进行验证
    echo "5. 测试多个钱包的私钥和地址一一对应关系\n";
    $testCount = 3;
    for ($i = 0; $i < $testCount; $i++) {
        $testWallet = $tron->generateWallet();
        $testAddressInfo = $tron->getAddressFromPrivateKey($testWallet['privateKey']);
        
        echo "测试钱包 #" . ($i + 1) . ":\n";
        echo "  私钥: " . substr($testWallet['privateKey'], 0, 10) . "...\n";
        echo "  原始地址: " . $testWallet['address'] . "\n";
        echo "  恢复地址: " . $testAddressInfo['address'] . "\n";
        echo "  验证结果: " . ($testWallet['address'] === $testAddressInfo['address'] ? '匹配' : '不匹配') . "\n\n";
    }
    
} catch (TronException $e) {
    echo "错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
} 