<?php
/**
 * Zhifu Tron API - USDT批量转账示例
 * 
 * 本示例展示如何批量向多个地址转账USDT代币
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
    
    // 设置发送方钱包信息
    $fromAddress = 'YOUR_WALLET_ADDRESS'; // 例如: 'TJoq53NiXhrgC9G2KNvpKv2s6UkcdNRgFP'
    $privateKey = 'YOUR_PRIVATE_KEY'; // 重要: 替换为您的私钥，并确保安全保存
    
    // USDT合约地址 (TRON网络上的标准USDT合约地址)
    $usdtContract = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
    
    // 批量转账接收地址列表（示例数据，请替换为实际收款地址）
    $transfers = [
        ['to' => 'RECEIVER_ADDRESS_1', 'amount' => 0.5], // 例如: 'TFpS9NJ4Djm29RTmax3VonXL8HumgrC4zW'
        ['to' => 'RECEIVER_ADDRESS_2', 'amount' => 0.3], // 例如: 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE'
        ['to' => 'RECEIVER_ADDRESS_3', 'amount' => 1.2]  // 例如: 'TADDpYcFCz5E17tJC65S5dyLjW2UxPr6Mt'
    ];
    
    echo "===== USDT批量转账测试 =====\n\n";
    
    // 创建Tron实例
    $tron = new Tron($fullNode, $solidityNode, $eventServer);
    
    // 设置API密钥 (推荐使用，可以提高请求限制)
    // $tron->setApiKey('YOUR_TRONGRID_API_KEY');  // 在 https://www.trongrid.io/dashboard 申请
    
    // 设置发送方地址和私钥
    $tron->setAddress($fromAddress);
    $tron->setPrivateKey($privateKey);
    
    // 1. 验证钱包和私钥是否匹配
    echo "1. 验证钱包和私钥是否匹配\n";
    $isValid = $tron->validatePrivateKey($privateKey, $fromAddress);
    if (!$isValid) {
        throw new TronException('私钥与钱包地址不匹配，无法进行转账！');
    }
    echo "✓ 私钥验证通过\n\n";
    
    // 2. 查询TRX余额和能量情况
    echo "2. 查询账户资源情况\n";
    $trxBalance = $tron->getBalance(null, true);
    echo "TRX余额: " . $trxBalance . " TRX\n";
    
    // 查询账户资源情况
    try {
        $accountResource = $tron->getAccountResources($fromAddress);
        $energyLimit = $accountResource['EnergyLimit'] ?? $accountResource['energy_limit'] ?? 0;
        $energyUsed = $accountResource['EnergyUsed'] ?? $accountResource['energy_used'] ?? 0;
        $availableEnergy = $energyLimit - $energyUsed;
        
        echo "能量上限: " . $energyLimit . "\n";
        echo "已使用能量: " . $energyUsed . "\n";
        echo "可用能量: " . $availableEnergy . "\n";
    } catch (Exception $e) {
        echo "无法获取账户资源情况: " . $e->getMessage() . "\n";
        echo "将继续尝试转账，可能会消耗TRX作为能量费用\n";
    }
    
    // 检查TRX余额是否足够支付能量费用
    if ($trxBalance < 10) {
        echo "⚠️ 警告: TRX余额较低，批量转账可能需要更多的能量费用\n";
    }
    
    // 3. 查询发送方USDT余额
    echo "\n3. 查询发送方USDT余额\n";
    $usdtBalance = $tron->getUsdtBalance($fromAddress);
    echo "发送方当前USDT余额: " . $usdtBalance . " USDT\n\n";
    
    // 计算总转账金额
    $totalAmount = 0;
    foreach ($transfers as $transfer) {
        $totalAmount += $transfer['amount'];
    }
    
    echo "转账总金额: " . $totalAmount . " USDT\n";
    
    if ($usdtBalance < $totalAmount) {
        throw new TronException('USDT余额不足，无法完成转账！需要 ' . $totalAmount . ' USDT，但当前余额仅有 ' . $usdtBalance . ' USDT');
    }
    
    // 4. 执行USDT批量转账
    echo "\n4. 执行USDT批量转账\n";
    echo "从: " . $fromAddress . "\n";
    echo "总计转出: " . $totalAmount . " USDT 到 " . count($transfers) . " 个地址\n\n";
    
    // 获取TRC20合约对象
    $contract = $tron->contract($usdtContract);
    
    // 设置费用限制 - 注意这里使用TRX单位而不是Sun
    $feeLimit = 100; // 100 TRX - 批量转账可能需要更多能量
    echo "设置费用限制为: " . $feeLimit . " TRX\n";
    $contract->setFeeLimit($feeLimit);
    
    // 开始批量转账
    echo "开始批量转账中...\n";
    
    // 使用循环逐个转账替代批量转账方法
    $results = [];
    foreach ($transfers as $transfer) {
        try {
            // 单笔转账
            $result = $contract->transfer($transfer['to'], $transfer['amount']);
            $results[] = [
                'success' => isset($result['result']) && $result['result'] === true,
                'data' => $result,
                'to' => $transfer['to'],
                'amount' => $transfer['amount']
            ];
        } catch (Exception $e) {
            $results[] = [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => $transfer
            ];
        }
    }
    
    // 5. 输出转账结果
    echo "\n5. 批量转账结果\n";
    
    $successCount = 0;
    $failedCount = 0;
    
    foreach ($results as $index => $result) {
        $transfer = $transfers[$index];
        echo "转账 #" . ($index + 1) . " 到 " . $transfer['to'] . " " . $transfer['amount'] . " USDT: ";
        
        if ($result['success']) {
            $successCount++;
            echo "✅ 成功";
            if (isset($result['data']['txid'])) {
                echo " (交易ID: " . $result['data']['txid'] . ")";
            }
            echo "\n";
        } else {
            $failedCount++;
            echo "❌ 失败";
            if (isset($result['error'])) {
                echo " - " . $result['error'];
            }
            echo "\n";
        }
    }
    
    echo "\n总结: " . $successCount . " 笔成功, " . $failedCount . " 笔失败\n";
    
    // 6. 查询转账后余额
    $afterBalance = $tron->getUsdtBalance($fromAddress);
    echo "\n转账后USDT余额: " . $afterBalance . " USDT\n";
    echo "余额变化: " . ($usdtBalance - $afterBalance) . " USDT\n";
    
    echo "\n提示: 在实际应用中，请确保设置足够的费用限制，并考虑每次交易的能量消耗。\n";
    echo "可以在波场区块浏览器查看交易详情：https://tronscan.org/\n";
    
} catch (TronException $e) {
    echo "Tron错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
} 