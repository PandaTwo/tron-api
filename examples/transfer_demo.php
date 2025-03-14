<?php
/**
 * Zhifu Tron API - TRC20代币转账示例
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
    
    // 设置钱包信息(使用前请替换为您自己的信息)
    $fromAddress = 'YOUR_WALLET_ADDRESS'; // 您的钱包地址
    $privateKey = 'YOUR_PRIVATE_KEY';     // 您的私钥(敏感信息，请勿分享)
    $toAddress = 'RECEIVER_ADDRESS';      // 接收方地址
    $usdtAmount = 0.12;                   // 转账金额
    
    // USDT合约地址(TRON主网)
    $usdtContract = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
    
    echo "===== TRC20代币转账测试 =====\n\n";
    
    // 创建Tron实例
    $tron = new Tron($fullNode, $solidityNode, $eventServer);
    
    // 设置API密钥 (可选，如果有的话)
    // $tron->setApiKey('您的API密钥');
    
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
    
    // 查询TRX余额
    $trxBalance = $tron->getBalance(null, true); // 转换为TRX单位
    echo "TRX余额: " . $trxBalance . " TRX\n";
    
    // 查询账户资源情况
    try {
        echo "正在查询账户资源情况...\n";
        $accountResource = $tron->getAccountResources($fromAddress);
        
        // 如果使用新API (v1)
        if (isset($accountResource['EnergyLimit']) || isset($accountResource['energy_limit'])) {
            $energyLimit = $accountResource['EnergyLimit'] ?? $accountResource['energy_limit'] ?? 0;
            $energyUsed = $accountResource['EnergyUsed'] ?? $accountResource['energy_used'] ?? 0;
            $availableEnergy = $energyLimit - $energyUsed;
            
            echo "能量上限: " . $energyLimit . "\n";
            echo "已使用能量: " . $energyUsed . "\n";
            echo "可用能量: " . $availableEnergy . "\n";
        } 
        // 如果是旧API返回格式
        else if (isset($accountResource['freeNetLimit'])) {
            echo "带宽上限: " . ($accountResource['freeNetLimit'] ?? 0) . "\n";
            echo "已使用带宽: " . ($accountResource['freeNetUsed'] ?? 0) . "\n";
            echo "能量上限: " . ($accountResource['EnergyLimit'] ?? 0) . "\n";
            echo "已使用能量: " . ($accountResource['EnergyUsed'] ?? 0) . "\n";
        }
        // 未知格式
        else {
            echo "获取到资源信息，但格式未知:\n";
            print_r($accountResource);
        }
    } catch (Exception $e) {
        echo "无法获取账户资源情况: " . $e->getMessage() . "\n";
        echo "将继续尝试转账，可能会消耗TRX作为能量费用\n";
        
        // 尝试获取账户基本信息
        try {
            $accountInfo = $tron->getAccount($fromAddress);
            if (isset($accountInfo['balance'])) {
                $trxBalance = $tron->fromTron($accountInfo['balance']);
                echo "已通过账户信息API获取TRX余额: " . $trxBalance . " TRX\n";
            }
        } catch (Exception $e2) {
            echo "无法获取账户基本信息: " . $e2->getMessage() . "\n";
        }
    }
    
    // 检查TRX余额是否足够支付能量费用
    if ($trxBalance < 1) { // 假设至少需要1个TRX来支付能量费用
        echo "⚠️ 警告: TRX余额较低，可能无法支付足够的能量费用\n";
    }
    
    // 3. 查询发送方USDT余额
    echo "\n3. 查询发送方USDT余额\n";
    $beforeBalance = $tron->getUsdtBalance($fromAddress);
    echo "发送方当前USDT余额: " . $beforeBalance . " USDT\n\n";
    
    if ($beforeBalance < $usdtAmount) {
        throw new TronException('USDT余额不足，无法完成转账！');
    }
    
    // 4. 执行USDT转账
    echo "4. 执行USDT转账\n";
    echo "从: " . $fromAddress . "\n";
    echo "到: " . $toAddress . "\n";
    echo "金额: " . $usdtAmount . " USDT\n";
    echo "开始转账中...\n";
    
    // 获取TRC20合约对象
    $contract = $tron->contract($usdtContract);
    
    // 设置更高的fee_limit，避免Out of Energy错误
    // 使用能量估算功能自动计算所需费用
    try {
        echo "\n正在估算交易所需能量...\n";
        $energyEstimate = $tron->estimateTrc20TransferEnergy(
            $usdtContract,
            $toAddress,
            $usdtAmount,
            $fromAddress
        );
        
        $energyNeeded = $energyEstimate['energy_used'];
        $suggestedFeeLimit = $energyEstimate['suggested_fee_limit'];
        $feeLimitTrx = $energyEstimate['suggested_fee_limit_trx'];
        
        echo "预估所需能量: " . $energyNeeded . " Energy\n";
        echo "建议设置费用限制: " . $suggestedFeeLimit . " Sun (约 " . $feeLimitTrx . " TRX)\n";
        
        // 设置为波场网络允许的最大限额 1000 TRX
        // 重要：setFeeLimit方法期望的是TRX值，而不是Sun值
        $maxFeeLimitTrx = 1000; // 直接设置为1000 TRX，不需要乘以1000000
        
        echo "⚠️ 使用最大允许的费用限制: " . $maxFeeLimitTrx . " TRX\n";
        
        // 设置智能合约费用限制
        $contract->setFeeLimit($maxFeeLimitTrx);
        echo "已设置费用限制为: " . $maxFeeLimitTrx . " TRX\n";
    } catch (Exception $e) {
        echo "能量估算失败: " . $e->getMessage() . "\n";
        echo "将使用最大费用限制 (1000 TRX)...\n";
        // 设置最大费用限制 - 直接使用TRX单位
        $contract->setFeeLimit(1000); // 直接设置为1000 TRX
    }
    
    // 开始转账，USDT小数位是6位
    $result = $contract->transfer($toAddress, $usdtAmount);
    
    // 5. 输出交易结果
    echo "\n5. 转账结果\n";
    if (isset($result['result']) && $result['result'] === true) {
        echo "✅ 转账已成功提交到区块链网络\n";
        echo "交易ID: " . $result['txid'] . "\n";
        
        // 等待交易确认
        echo "\n等待交易确认中...\n";
        sleep(5); // 等待5秒
        
        // 获取交易状态
        $status = $tron->getTransactionStatus($result['txid']);
        if ($status['success']) {
            echo "交易已确认，区块高度: " . $status['block'] . "\n";
            echo "消耗的能量: " . ($status['energy_used'] ?? '未知') . "\n";
            
            // 查询转账后余额
            $afterBalance = $tron->getUsdtBalance($fromAddress);
            echo "转账后USDT余额: " . $afterBalance . " USDT\n";
            echo "余额变化: " . ($beforeBalance - $afterBalance) . " USDT\n";
        } else {
            echo "交易状态: " . $status['status'] . "\n";
            echo "信息: " . $status['message'] . "\n";
        }
    } else {
        echo "❌ 转账失败\n";
        if (isset($result['message'])) {
            echo "错误信息: " . $result['message'] . "\n";
        }
        var_dump($result);
    }
    
    echo "\n提示: 实际应用中，转账后应该等待几个区块确认后再认为交易最终成功\n";
    echo "可以在波场区块浏览器查看交易详情：https://tronscan.org/#/transaction/" . ($result['txid'] ?? '') . "\n";
    
} catch (TronException $e) {
    echo "Tron错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
} 