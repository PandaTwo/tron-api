<?php
/**
 * Zhifu Tron API - 余额查询工具
 * 
 * 本脚本使用多种方法查询波场地址的TRX和USDT余额
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Zhifu\TronAPI\Tron;
use Zhifu\TronAPI\Exception\TronException;
use Zhifu\TronAPI\Provider\HttpProvider;

// 要查询的地址 (可以作为命令行参数传入)
$address = $argv[1] ?? 'YOUR_WALLET_ADDRESS'; // 如果未提供参数，使用这个默认地址

// USDT合约地址
$usdtContract = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';

echo "===== 波场网络余额查询工具 =====\n\n";
echo "查询地址: " . $address . "\n\n";

/**
 * 通过不同节点查询同一个地址的余额，以验证数据正确性
 */
$nodes = [
    'trongrid' => 'https://api.trongrid.io',
    'trongrid_backup' => 'https://api.trongrid.io',
    'nile' => 'https://nile.trongrid.io'
    // tronstack 节点由于重定向问题被移除
];

// 创建日志函数
function logEntry($title, $value, $extra = '') {
    echo str_pad($title . ': ', 30) . $value . ($extra ? ' ' . $extra : '') . "\n";
}

// 用于调试的函数，打印嵌套结构的前几层
function debugPrintArray($array, $depth = 1) {
    if (!is_array($array)) {
        echo "不是数组\n";
        return;
    }
    
    foreach ($array as $key => $value) {
        echo str_repeat("  ", $depth) . "[{$key}] => ";
        
        if (is_array($value)) {
            echo "数组(" . count($value) . "项)\n";
            
            // 仅当深度小于2时打印下一层
            if ($depth < 2) {
                debugPrintArray($value, $depth + 1);
            }
        } else if (is_string($value)) {
            echo (strlen($value) > 50) ? substr($value, 0, 47) . "..." : $value;
            echo "\n";
        } else {
            echo gettype($value) . ": " . (string)$value . "\n";
        }
    }
}

// 用于追踪成功查询的变量
$successfulQueries = [];

foreach ($nodes as $nodeName => $nodeUrl) {
    echo "\n## 使用 {$nodeName} 节点 ({$nodeUrl}) ##\n";
    
    try {
        // 只使用 v1 API 版本，因为它更稳定且我们已经修复了它的支持
        $provider = new HttpProvider($nodeUrl);
        $tron = new Tron($nodeUrl, $nodeUrl, $nodeUrl);
        $tron->setApiVersion('v1');
        
        echo "\n使用API版本: v1\n";
        $tron->setAddress($address);
        
        try {
            // 方法1: 使用getBalance
            $balanceRaw = $tron->getBalance($address);
            $balanceTrx = $tron->getBalance($address, true);
            logEntry("TRX余额 (原始值)", $balanceRaw, "sun");
            logEntry("TRX余额 (TRX单位)", $balanceTrx, "TRX");
            
            if ($balanceTrx > 0) {
                $successfulQueries[$nodeName] = [
                    'trx' => $balanceTrx,
                    'node' => $nodeUrl
                ];
            }
            
            // 方法2：通过getAccount获取余额的原始数据
            try {
                $account = $tron->getAccount($address);
                
                // 如果存在data数组，尝试直接提取第一个元素的余额
                if (isset($account['data']) && is_array($account['data']) && !empty($account['data'])) {
                    logEntry("账户API数据结构", "正常", "(包含" . count($account['data']) . "个账户)");
                    logEntry("账户API余额", $account['data'][0]['balance'] ?? 'N/A', "sun");
                } else {
                    logEntry("账户API数据结构", "空数据或无效", "");
                }
            } catch (Exception $e) {
                logEntry("账户API错误", $e->getMessage(), "");
            }
            
            // 查询USDT余额
            try {
                $usdtBalance = $tron->getUsdtBalance($address);
                logEntry("USDT余额", $usdtBalance, "USDT");
                
                if ($usdtBalance > 0) {
                    $successfulQueries[$nodeName]['usdt'] = $usdtBalance;
                }
            } catch (Exception $e) {
                logEntry("USDT查询错误", $e->getMessage(), "");
            }
            
            // 检查资源情况
            try {
                $resources = $tron->getAccountResources($address);
                
                if (!empty($resources)) {
                    logEntry("能量上限", $resources['EnergyLimit'] ?? $resources['energy_limit'] ?? 'N/A', "");
                    logEntry("已使用能量", $resources['EnergyUsed'] ?? $resources['energy_used'] ?? 'N/A', "");
                    logEntry("带宽上限", $resources['NetLimit'] ?? $resources['net_limit'] ?? 'N/A', "");
                    logEntry("已使用带宽", $resources['NetUsed'] ?? $resources['net_used'] ?? 'N/A', "");
                }
            } catch (Exception $e) {
                logEntry("资源查询错误", $e->getMessage(), "");
            }
        } catch (Exception $e) {
            echo "查询出错: " . $e->getMessage() . "\n";
        }
    } catch (Exception $e) {
        echo "连接 {$nodeName} 失败: " . $e->getMessage() . "\n";
    }
}

// 总结
echo "\n===== 查询结果总结 =====\n";
if (!empty($successfulQueries)) {
    echo "成功获取余额的节点:\n";
    foreach ($successfulQueries as $nodeName => $data) {
        echo "- {$nodeName} ({$data['node']}): TRX余额 = {$data['trx']} TRX";
        if (isset($data['usdt'])) {
            echo ", USDT余额 = {$data['usdt']} USDT";
        }
        echo "\n";
    }
    
    // 选择第一个成功的节点作为最终结果
    $firstSuccessful = reset($successfulQueries);
    $firstNodeName = key($successfulQueries);
    
    echo "\n最终查询结果 (从 {$firstNodeName} 节点):\n";
    echo "TRX余额: " . $firstSuccessful['trx'] . " TRX\n";
    if (isset($firstSuccessful['usdt'])) {
        echo "USDT余额: " . $firstSuccessful['usdt'] . " USDT\n";
    }
} else {
    echo "❌ 所有节点查询失败或余额为零\n";
    echo "可能的原因:\n";
    echo "- 地址确实没有TRX余额\n";
    echo "- 节点API访问受限（可能需要API密钥）\n";
    echo "- 地址格式不正确\n";
}

echo "\n===== 建议 =====\n";
echo "1. API 密钥的重要性:\n";
echo "   - 申请并使用TronGrid API密钥可以提高稳定性\n";
echo "   - 向Tron构造函数传入API密钥: new Tron(节点, 节点, 节点, null, 'YOUR_API_KEY')\n";
echo "2. 节点选择建议:\n";
echo "   - 主网地址使用主网节点查询 (trongrid)\n";
echo "   - 测试网地址使用对应的测试网节点 (shasta, nile)\n";
echo "3. 地址验证:\n";
echo "   - 使用波场区块浏览器验证余额: https://tronscan.org/#/address/" . $address . "\n";
echo "   - 确保钱包中有足够的TRX (至少5-10个)用于支付交易费用\n"; 