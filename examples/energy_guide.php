<?php
/**
 * Zhifu Tron API - 波场能量和资源管理指南
 * 
 * 本示例说明如何管理波场网络上的能量、带宽等资源
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
    
    // 创建Tron实例
    $tron = new Tron($fullNode, $solidityNode, $eventServer);
    
    echo "===== 波场网络能量和资源管理指南 =====\n\n";
    
    // 设置要查询的地址 (修改为您自己的地址)
    $address = 'TJoq53NiXhrgC9G2KNvpKv2s6UkcdNRgFP';
    $tron->setAddress($address);
    
    echo "1. 波场网络资源概述\n";
    echo "-----------------------------------\n";
    echo "波场网络上的交易需要消耗两种资源：\n";
    echo "- 带宽 (Bandwidth): 用于普通转账和网络操作\n";
    echo "- 能量 (Energy): 用于智能合约操作，如TRC20代币转账\n\n";
    echo "每个账户每天有一定的免费资源配额，超出部分需要消耗TRX或质押TRX获取\n\n";
    
    // 查询账户资源情况
    try {
        echo "2. 当前账户资源情况\n";
        echo "-----------------------------------\n";
        echo "地址: " . $address . "\n";
        
        // 查询TRX余额
        $trxBalance = $tron->getBalance($address, true);
        echo "TRX余额: " . $trxBalance . " TRX\n\n";
        
        // 查询账户资源情况
        $accountResource = $tron->getAccountResources($address);
        
        echo "带宽资源:\n";
        echo "- 免费带宽上限: " . ($accountResource['freeNetLimit'] ?? 0) . "\n";
        echo "- 已使用免费带宽: " . ($accountResource['freeNetUsed'] ?? 0) . "\n";
        echo "- 总带宽上限: " . ($accountResource['NetLimit'] ?? $accountResource['net_limit'] ?? 0) . "\n";
        echo "- 已使用带宽: " . ($accountResource['NetUsed'] ?? $accountResource['net_used'] ?? 0) . "\n\n";
        
        echo "能量资源:\n";
        echo "- 能量上限: " . ($accountResource['EnergyLimit'] ?? $accountResource['energy_limit'] ?? 0) . "\n";
        echo "- 已使用能量: " . ($accountResource['EnergyUsed'] ?? $accountResource['energy_used'] ?? 0) . "\n";
        
        // 计算可用资源百分比
        $availableBandwidth = ($accountResource['freeNetLimit'] ?? 0) - ($accountResource['freeNetUsed'] ?? 0);
        $bandwidthPercentage = ($accountResource['freeNetLimit'] ?? 0) > 0 ? 
            100 - (($accountResource['freeNetUsed'] ?? 0) / ($accountResource['freeNetLimit'] ?? 0) * 100) : 0;
        
        $availableEnergy = ($accountResource['EnergyLimit'] ?? $accountResource['energy_limit'] ?? 0) - 
            ($accountResource['EnergyUsed'] ?? $accountResource['energy_used'] ?? 0);
        $energyPercentage = ($accountResource['EnergyLimit'] ?? $accountResource['energy_limit'] ?? 0) > 0 ? 
            100 - (($accountResource['EnergyUsed'] ?? $accountResource['energy_used'] ?? 0) / 
            ($accountResource['EnergyLimit'] ?? $accountResource['energy_limit'] ?? 0) * 100) : 0;
        
        echo "可用带宽: " . $availableBandwidth . " (" . round($bandwidthPercentage, 2) . "%)\n";
        echo "可用能量: " . $availableEnergy . " (" . round($energyPercentage, 2) . "%)\n\n";
    } catch (Exception $e) {
        echo "无法获取账户资源情况: " . $e->getMessage() . "\n\n";
    }
    
    echo "3. 能量估算示例（以USDT转账为例）\n";
    echo "-----------------------------------\n";
    
    // USDT合约地址
    $usdtContract = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
    // 接收方地址（示例）
    $toAddress = 'TFpS9NJ4Djm29RTmax3VonXL8HumgrC4zW';
    // 转账金额
    $amount = 1.0;
    
    try {
        // 估算USDT转账所需能量
        $energyEstimate = $tron->estimateTrc20TransferEnergy(
            $usdtContract,
            $toAddress,
            $amount,
            $address
        );
        
        echo "USDT转账需要的能量:\n";
        echo "- 转账金额: " . $amount . " USDT\n";
        echo "- 预估所需能量: " . $energyEstimate['energy_used'] . " Energy\n";
        echo "- 能量价格: " . $energyEstimate['energy_price'] . " Sun/Energy\n";
        echo "- 建议费用限制: " . $energyEstimate['suggested_fee_limit'] . " Sun (约 " . 
            $energyEstimate['suggested_fee_limit_trx'] . " TRX)\n\n";
        
        // 检查是否有足够的免费能量
        if ($availableEnergy >= $energyEstimate['energy_used']) {
            echo "✓ 您有足够的免费能量完成此交易，无需额外支付TRX\n";
        } else {
            $neededTrx = ($energyEstimate['energy_used'] - $availableEnergy) * $energyEstimate['energy_price'] / 1000000;
            echo "⚠️ 您的免费能量不足，预计需要燃烧 " . round($neededTrx, 6) . " TRX\n";
            
            if ($trxBalance < $neededTrx) {
                echo "❌ 您的TRX余额不足，请确保至少有 " . round($neededTrx * 1.5, 6) . " TRX (含50%安全裕量)\n";
            }
        }
    } catch (Exception $e) {
        echo "能量估算失败: " . $e->getMessage() . "\n";
    }
    
    echo "\n4. 获取更多资源的方法\n";
    echo "-----------------------------------\n";
    echo "1) 质押TRX获取资源 (推荐方式):\n";
    echo "   - 质押可以获得长期的资源使用权\n";
    echo "   - 质押TRX不会被消耗，可以随时解除质押取回\n";
    echo "   - 通常1个TRX可以质押获得约420能量\n\n";
    
    echo "2) 直接使用TRX支付能量费用:\n";
    echo "   - 每次交易直接燃烧TRX\n";
    echo "   - 适合临时或低频交易\n";
    echo "   - 通常能量价格为 420 Sun/Energy (随网络状况变化)\n\n";
    
    echo "3) 使用TRON能量代表:\n";
    echo "   - TronWeb3在开发一种能量租赁方案\n";
    echo "   - 目前还不够成熟和广泛使用\n\n";
    
    echo "5. 能量使用建议\n";
    echo "-----------------------------------\n";
    echo "1) 适量质押TRX以获取稳定能量供应\n";
    echo "2) 避免在网络繁忙时段进行交易，能量价格可能更高\n";
    echo "3) 设置合理的fee_limit，太低可能导致交易失败，太高可能浪费TRX\n";
    echo "4) 使用能量估算功能来精确计算所需资源\n";
    echo "5) 保持足够的TRX余额，作为能量不足时的备用资金\n\n";
    
    echo "备注: 在实际生产环境中，建议为高频交易的账户质押足够的TRX以获取能量，\n";
    echo "这比每次燃烧TRX更经济高效。质押1000 TRX大约可以获得42万能量，\n";
    echo "足够支持每天数百次的TRC20代币转账。\n";
    
} catch (TronException $e) {
    echo "Tron错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
} 