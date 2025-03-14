<?php
/**
 * Zhifu Tron API - 生产环境余额查询工具
 * 
 * 使用TronGrid API密钥的余额查询示例，适合生产环境使用
 * 
 * 使用方法:
 * php production_balance.php <地址> <API密钥>
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Zhifu\TronAPI\Tron;
use Zhifu\TronAPI\Exception\TronException;

// 检查命令行参数
if (isset($argv[1]) && $argv[1] === '--help') {
    echo "使用方法: php production_balance.php <波场地址> <API密钥(可选)>\n";
    echo "例如: php production_balance.php TJoq53NiXhrgC9G2KNvpKv2s6UkcdNRgFP YOUR_API_KEY\n";
    exit;
}

// 获取命令行参数
$address = $argv[1] ?? 'TJoq53NiXhrgC9G2KNvpKv2s6UkcdNRgFP';
$apiKey = $argv[2] ?? null;

// 初始化 Tron 对象
try {
    echo "===== 波场生产环境余额查询 =====\n\n";
    
    // 显示使用的配置
    echo "节点: TronGrid (https://api.trongrid.io)\n";
    echo "地址: " . $address . "\n";
    echo "API密钥: " . ($apiKey ? "已设置" : "未设置 (建议设置API密钥以提高请求成功率)") . "\n\n";
    
    // 创建Tron实例
    $tron = new Tron(
        'https://api.trongrid.io',
        'https://api.trongrid.io',
        'https://api.trongrid.io',
        null,  // 私钥暂时为null
        $apiKey // 使用API密钥
    );
    
    // 设置地址
    $tron->setAddress($address);
    
    // 开始查询各种余额
    echo "正在查询余额...\n\n";
    
    // TRX余额
    try {
        $trxBalance = $tron->getBalance(null, true);
        echo "TRX余额: " . $trxBalance . " TRX\n";
        
        // 如果TRX余额少于5，给出警告
        if ($trxBalance < 5) {
            echo "⚠️ 警告: TRX余额较低，可能无法支付足够的交易费用\n";
            echo "建议至少保持5-10 TRX以确保交易正常进行\n";
        }
    } catch (Exception $e) {
        echo "❌ 获取TRX余额失败: " . $e->getMessage() . "\n";
    }
    
    // USDT余额
    try {
        $usdtBalance = $tron->getUsdtBalance();
        echo "USDT余额: " . $usdtBalance . " USDT\n";
    } catch (Exception $e) {
        echo "❌ 获取USDT余额失败: " . $e->getMessage() . "\n";
    }
    
    // 账户资源
    try {
        $resources = $tron->getAccountResources();
        
        echo "\n===== 账户资源 =====\n";
        
        // 带宽信息
        $freeNetLimit = $resources['freeNetLimit'] ?? 0;
        $freeNetUsed = $resources['freeNetUsed'] ?? 0;
        $freeNetRemaining = $freeNetLimit - $freeNetUsed;
        $netLimit = $resources['NetLimit'] ?? 0;
        $netUsed = $resources['NetUsed'] ?? 0;
        $netRemaining = $netLimit - $netUsed;
        
        echo "带宽:\n";
        echo "- 免费带宽: " . $freeNetRemaining . " / " . $freeNetLimit . " (" . 
             round(($freeNetLimit ? $freeNetUsed / $freeNetLimit * 100 : 0), 2) . "%已使用)\n";
        
        if ($netLimit > 0) {
            echo "- 质押带宽: " . $netRemaining . " / " . $netLimit . " (" . 
                 round(($netLimit ? $netUsed / $netLimit * 100 : 0), 2) . "%已使用)\n";
        }
        
        // 能量信息
        $energyLimit = $resources['EnergyLimit'] ?? 0;
        $energyUsed = $resources['EnergyUsed'] ?? 0;
        $energyRemaining = $energyLimit - $energyUsed;
        
        echo "能量:\n";
        if ($energyLimit > 0) {
            echo "- 可用能量: " . $energyRemaining . " / " . $energyLimit . " (" . 
                 round(($energyLimit ? $energyUsed / $energyLimit * 100 : 0), 2) . "%已使用)\n";
        } else {
            echo "- 无质押能量 (TRC20交易可能需要消耗TRX作为能量费用)\n";
        }
        
    } catch (Exception $e) {
        echo "\n❌ 获取账户资源失败: " . $e->getMessage() . "\n";
    }
    
    // 提供在线查看链接
    echo "\n===== 在线验证 =====\n";
    echo "您可以通过波场区块浏览器验证余额:\n";
    echo "https://tronscan.org/#/address/" . $address . "\n";
    
    echo "\n===== 使用建议 =====\n";
    echo "1. TRX余额很重要！至少保持5-10个TRX用于支付交易费用\n";
    echo "2. 如果您频繁进行TRC20转账，建议质押TRX获取能量\n";
    echo "   - 可以使用我们的stake_energy_demo.php脚本质押TRX\n";
    echo "3. 在生产环境中，始终使用API密钥以获得稳定的API访问\n";
    
} catch (TronException $e) {
    echo "Tron错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
} 