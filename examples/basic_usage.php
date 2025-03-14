<?php
/**
 * Zhifu Tron API - 基本用法示例
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Zhifu\TronAPI\Tron;
use Zhifu\TronAPI\Exception\TronException;

// 初始化 Tron 对象
try {
    // 方法1：使用完整的Provider对象
    $fullNode = new \Zhifu\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
    $solidityNode = new \Zhifu\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
    $eventServer = new \Zhifu\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
    
    $tron = new Tron($fullNode, $solidityNode, $eventServer);
    
    // 方法2：简化版，直接使用URL字符串（库会自动创建Provider对象）
    // $tron = new Tron('https://api.trongrid.io', 'https://api.trongrid.io', 'https://api.trongrid.io');
    
    // 设置您的私钥（如果需要签名交易）
    // $tron->setPrivateKey('您的私钥');
    
    // 设置要使用的默认地址
    $tron->setAddress('您的TRX地址');
    
    echo "=== 基本账户信息 ===\n";
    // 获取账户信息
    $account = $tron->getAccount();
    echo "账户名称: " . ($account['account_name'] ?? '未设置') . "\n";
    echo "TRX余额: " . $tron->getBalance(null, true) . " TRX\n";
    
    echo "\n=== 查询TRC10代币余额 ===\n";
    // 查询TRC10代币余额（例如：1002000代表USDT）
    $trc10Balance = $tron->getTokenBalance(1002000, $tron->address);
    echo "TRC10代币余额: " . $trc10Balance . "\n";
    
    echo "\n=== 查询TRC20代币余额 ===\n";
    // 查询TRC20代币余额（例如：USDT合约地址）
    $usdtContractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'; // USDT合约地址
    $trc20Balance = $tron->getTokenBalance($usdtContractAddress, $tron->address);
    echo "TRC20 USDT余额: " . $trc20Balance . "\n";
    
    echo "\n=== 查询最新区块 ===\n";
    // 获取最新的区块
    $latestBlock = $tron->getCurrentBlock();
    echo "最新区块高度: " . $latestBlock['block_header']['raw_data']['number'] . "\n";
    
    // 下面的示例需要设置私钥才能执行
    if (false) {
        echo "\n=== 转账示例（需要私钥） ===\n";
        // 转账TRX（注意：此操作会真实发送交易，请谨慎测试）
        $transferResult = $tron->send('接收方地址', 1.0); // 发送1个TRX
        echo "转账结果: " . json_encode($transferResult, JSON_PRETTY_PRINT) . "\n";
        
        // 查询交易状态
        $txID = $transferResult['txid'];
        $status = $tron->getTransactionStatus($txID);
        echo "交易状态: " . json_encode($status, JSON_PRETTY_PRINT) . "\n";
        
        // 批量转账TRC20代币（示例）
        $transfers = [
            ['to' => '接收方地址1', 'amount' => 1.5],
            ['to' => '接收方地址2', 'amount' => 2.0]
        ];
        
        $batchResult = $tron->batchTransferTrc20($usdtContractAddress, $transfers);
        echo "批量转账结果: " . json_encode($batchResult, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (TronException $e) {
    echo "错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
} 