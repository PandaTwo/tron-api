<?php
/**
 * Zhifu Tron API - TRX余额查询示例
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Zhifu\TronAPI\Tron;
use Zhifu\TronAPI\Exception\TronException;

// 使用命令行参数作为地址，如果没有提供则使用默认地址
$address = $argv[1] ?? 'TJoq53NiXhrgC9G2KNvpKv2s6UkcdNRgFP';

// 初始化 Tron 对象
try {
    // 定义可靠的节点列表（按优先级排序）
    $nodes = [
        '主网API (TronGrid)' => 'https://api.trongrid.io'
        // TronStack节点被移除，因为它有重定向问题
        // 如果需要添加更多节点，可以在这里添加
    ];
    
    echo "===== TRX余额查询 =====\n\n";
    echo "查询地址: " . $address . "\n\n";
    
    $successfulQuery = false;
    $trxBalance = 0;
    $usdtBalance = 0;
    
    foreach ($nodes as $nodeName => $nodeUrl) {
        echo "使用 {$nodeName} ({$nodeUrl}):\n";
        
        try {
            // 创建Tron实例
            $tron = new Tron($nodeUrl, $nodeUrl, $nodeUrl);
            
            // 设置地址
            $tron->setAddress($address);
            
            // 尝试获取余额
            $trxBalance = $tron->getBalance($address, true);
            echo "- TRX余额: " . $trxBalance . " TRX\n";
            
            // 尝试获取USDT余额
            try {
                $usdtBalance = $tron->getUsdtBalance($address);
                echo "- USDT余额: " . $usdtBalance . " USDT\n";
                $successfulQuery = true;
            } catch (Exception $e) {
                echo "- 无法获取USDT余额: " . $e->getMessage() . "\n";
            }
            
            // 如果成功获取了TRX余额，不需要尝试其他节点
            if ($trxBalance > 0 || $successfulQuery) {
                break;
            }
        } catch (Exception $e) {
            echo "- 查询错误: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    // 总结结果
    echo "\n===== 查询结果 =====\n";
    if ($trxBalance > 0 || $successfulQuery) {
        echo "✅ 查询成功！\n";
        echo "TRX余额: " . $trxBalance . " TRX\n";
        if ($usdtBalance > 0) {
            echo "USDT余额: " . $usdtBalance . " USDT\n";
        }
    } else {
        echo "❌ 所有节点查询失败\n";
    }
    
    echo "\n===== 使用建议 =====\n";
    echo "1. 如果余额显示为0，可能是因为:\n";
    echo "   - 地址确实没有TRX余额\n";
    echo "   - API节点可能有访问限制\n";
    echo "   - 请确认地址格式是否正确\n";
    echo "2. 如果查询频繁失败，可能是因为API限流，建议:\n";
    echo "   - 申请并使用TronGrid API密钥，这将提高请求成功率\n";
    echo "   - 将API密钥添加到Tron构造函数: new Tron(节点地址, 节点地址, 节点地址, null, 'YOUR_API_KEY')\n";
    echo "3. 波场地址余额查询建议:\n";
    echo "   - 使用波场区块浏览器(https://tronscan.org)验证余额\n";
    echo "   - 确保钱包中至少有5-10 TRX用于支付交易费用和能量\n";

} catch (TronException $e) {
    echo "Tron错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
} 