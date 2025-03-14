<?php

/**
 * Zhifu Tron API - 质押TRX获取能量示例 (质押2.0版本)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Zhifu\TronAPI\Tron;
use Zhifu\TronAPI\Exception\TronException;

// 检查命令行参数
$apiKey = null;
$useNode = 'trongrid';
$debug = false;

// 处理命令行参数
$options = getopt("n:k:dh", ["node:", "key:", "debug", "help"]);
if (isset($options['h']) || isset($options['help'])) {
    echo "使用方法: php stake_energy_demo.php [选项]\n";
    echo "选项:\n";
    echo "  -n, --node=节点名   使用特定节点 (trongrid, fullnode, nile, 或自定义URL)\n";
    echo "  -k, --key=API密钥   设置API密钥\n";
    echo "  -d, --debug         开启详细调试输出\n";
    echo "  -h, --help          显示帮助信息\n";
    exit;
}

// 获取节点参数
if (isset($options['n'])) {
    $useNode = $options['n'];
} elseif (isset($options['node'])) {
    $useNode = $options['node'];
}

// 获取API密钥
if (isset($options['k'])) {
    $apiKey = $options['k'];
} elseif (isset($options['key'])) {
    $apiKey = $options['key'];
}

// 是否开启调试模式
if (isset($options['d']) || isset($options['debug'])) {
    $debug = true;
    // 设置错误报告
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// 输出调试信息的辅助函数
function debug($message, $data = null) {
    global $debug;
    if ($debug) {
        echo "\n🔍 调试: " . $message . "\n";
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                print_r($data);
            } else {
                echo $data . "\n";
            }
        }
        echo "\n";
    }
}

// 初始化 Tron 对象
try {
    // 节点设置
    $nodes = [
        'trongrid' => 'https://api.trongrid.io',
        'fullnode' => 'https://fullnode.trongrid.io', // 尝试使用全节点
        'nile' => 'https://nile.trongrid.io', // 测试网节点
        'community' => 'https://api.tronex.io', // 社区节点，可能支持更多操作
        'custom' => $useNode // 如果用户输入的不是预定义节点名称，则视为自定义URL
    ];
    
    // 选择节点
    $nodeUrl = $nodes[$useNode] ?? $useNode;
    
    // 设置钱包信息 - 使用前请替换
    $address = 'YOUR_WALLET_ADDRESS';  // 例如：TJoq53NiXhrgC9G2KNvpKv2s6UkcdNRgFP
    $privateKey = 'YOUR_PRIVATE_KEY';  // 私钥是敏感信息，切勿泄露或在不安全的环境中使用
    
    echo "===== 波场TRX质押2.0获取能量示例 =====\n\n";
    echo "使用节点: " . $nodeUrl . "\n";
    if ($apiKey) {
        echo "API密钥: 已设置\n";
    } else {
        echo "API密钥: 未设置 (某些操作可能受限)\n";
    }
    if ($debug) {
        echo "调试模式: 已开启\n";
    }
    echo "\n";
    
    // 创建Tron实例
    $tron = new Tron($nodeUrl, $nodeUrl, $nodeUrl, null, $apiKey);
    
    // 设置地址和私钥
    $tron->setAddress($address);
    $tron->setPrivateKey($privateKey);
    
    debug("创建的Tron实例：", get_class($tron));
    debug("当前节点配置：", $nodeUrl);
    
    // 1. 查询当前账户基本信息
    echo "1. 查询当前账户基本信息\n";
    echo "地址: " . $address . "\n";
    
    // 查询TRX余额
    $trxBalance = $tron->getBalance($address, true);
    echo "TRX余额: " . $trxBalance . " TRX\n\n";
    
    if ($trxBalance < 5) {
        echo "⚠️ 警告: TRX余额较低，至少需要5 TRX才能进行质押操作\n";
        // 如果余额不足，可以直接退出
        if ($trxBalance < 1) {
            throw new TronException('TRX余额不足，无法进行质押操作');
        }
    }
    
    // 2. 查询当前能量情况
    echo "2. 查询当前能量情况\n";
    $beforeResource = $tron->getAccountResources($address);
    
    debug("资源查询结果：", $beforeResource);
    
    $beforeEnergyLimit = $beforeResource['EnergyLimit'] ?? $beforeResource['energy_limit'] ?? 0;
    $beforeEnergyUsed = $beforeResource['EnergyUsed'] ?? $beforeResource['energy_used'] ?? 0;
    $beforeAvailableEnergy = $beforeEnergyLimit - $beforeEnergyUsed;
    
    echo "质押前能量上限: " . $beforeEnergyLimit . "\n";
    echo "质押前已使用能量: " . $beforeEnergyUsed . "\n";
    echo "质押前可用能量: " . $beforeAvailableEnergy . "\n\n";
    
    // 3. 设置质押金额
    echo "3. 设置质押参数\n";
    $stakeAmount = 10.0; // 质押10个TRX (波场质押2.0最小要求2个TRX)
    echo "计划质押: " . $stakeAmount . " TRX\n";
    echo "质押所用资源类型: 1 (能量 Energy)\n";
    echo "预计获得能量: 约" . ($stakeAmount * 420) . " Energy\n\n"; // 每个TRX大约可以获得420能量
    
    // 质押注意事项
    echo "⚠️ 波场质押2.0说明:\n";
    echo "1. 质押2.0要求最小质押金额为2 TRX\n";
    echo "2. 质押2.0中能量资源类型代码为1(而不是旧版的3)\n";
    echo "3. 公共API节点(如TronGrid)可能不支持质押操作\n";
    echo "4. 如果使用公共API失败，建议使用全节点或私有节点\n";
    echo "5. 某些操作需要API密钥才能执行\n\n";
    
    // 确认是否继续
    echo "是否继续质押操作? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    if(trim(strtolower($line)) != 'y'){
        echo "已取消质押操作\n";
        exit;
    }
    
    // 4. 执行质押
    echo "4. 开始执行质押操作...\n";
    try {
        // 设置更详细的错误处理
        $result = [];
        try {
            echo "使用质押2.0方法请求质押能量...\n";
            debug("即将调用freezeBalanceForEnergyV2方法，参数：", [
                'trxAmount' => $stakeAmount,
                'receiverAddress' => $address,
                'resourceType' => 1 // 确认使用数字类型1而非字符串
            ]);
            
            $result = $tron->freezeBalanceForEnergyV2($stakeAmount, $address);
            
            // 打印详细的响应信息
            echo "API响应信息:\n";
            print_r($result);
            
            debug("API响应原始数据", $result);
            
            if ((isset($result['result']) && $result['result'] === true) || 
                isset($result['txid']) || isset($result['txID'])) {
                echo "✅ 质押操作已提交到区块链\n";
                $txid = $result['txid'] ?? $result['txID'] ?? null;
                
                if ($txid) {
                    echo "交易ID: " . $txid . "\n";
                    
                    // 等待交易确认，增加等待时间和重试次数
                    echo "等待交易确认中...\n";
                    $confirmed = false;
                    $maxTries = 10;
                    $tryCount = 0;
                    
                    while (!$confirmed && $tryCount < $maxTries) {
                        sleep(3); // 等待3秒
                        $tryCount++;
                        echo "尝试 {$tryCount}/{$maxTries} 检查交易状态...\n";
                        
                        try {
                            // 获取交易状态
                            $status = $tron->getTransactionStatus($txid);
                            debug("交易状态检查结果", $status);
                            
                            if ($status['success']) {
                                echo "交易已确认，区块高度: " . $status['block'] . "\n";
                                $confirmed = true;
                                break;
                            } else {
                                echo "交易状态: " . $status['status'] . "\n";
                                echo "信息: " . $status['message'] . "\n";
                                
                                // 如果已失败，不再继续等待
                                if ($status['status'] === 'failed') {
                                    echo "❌ 交易失败，不再继续等待\n";
                                    break;
                                }
                            }
                        } catch (Exception $e) {
                            echo "查询交易状态出错: " . $e->getMessage() . "\n";
                        }
                    }
                    
                    if (!$confirmed) {
                        echo "⚠️ 交易可能尚未被确认，但这不一定意味着失败\n";
                        echo "您可以稍后通过交易ID在区块浏览器上查询交易状态\n";
                        echo "交易ID: " . $txid . "\n";
                        echo "区块浏览器: https://tronscan.org/#/transaction/" . $txid . "\n";
                    }
                }
            } else {
                echo "❌ 质押操作失败\n";
                if (isset($result['message'])) {
                    echo "错误信息: " . $result['message'] . "\n";
                }
                
                // 详细分析问题
                echo "\n可能的问题分析:\n";
                echo "1. 公共API节点不支持质押操作 → 尝试使用全节点或私有节点\n";
                echo "2. 需要API密钥 → 申请TronGrid API密钥并使用 -k 参数设置\n";
                echo "3. 交易签名问题 → 检查私钥是否正确\n";
                echo "4. 质押金额不足 → 波场质押2.0最低要求2 TRX\n";
                echo "5. 资源类型格式问题 → 确保使用数字1而非字符串'ENERGY'\n";
            }
        } catch (Exception $e) {
            echo "❌ 质押过程中出错: " . $e->getMessage() . "\n";
            debug("异常详情", $e);
            
            echo "\n可能的解决方案:\n";
            echo "1. 使用其他节点尝试质押，例如: php stake_energy_demo.php -n fullnode\n";
            echo "2. 申请并使用API密钥: php stake_energy_demo.php -k YOUR_API_KEY\n";
            echo "3. 使用TRON官方钱包进行质押: https://www.tronlink.org\n";
            echo "4. 确保质押金额至少为2 TRX (当前设置: " . $stakeAmount . " TRX)\n";
            echo "5. 添加-d参数开启调试模式获取更多信息: php stake_energy_demo.php -d\n";
            
            if (strpos($e->getMessage(), 'number 3') !== false) {
                echo "\n⚠️ 检测到资源类型编码错误。代码已更新，但节点可能不支持质押操作。\n";
                echo "请尝试使用TronLink钱包进行质押: https://www.tronlink.org\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ 质押过程中出错: " . $e->getMessage() . "\n";
        debug("异常详情", $e);
    }
    
    // 5. 查询质押后的资源情况
    echo "\n5. 查询质押后的能量情况\n";
    try {
        // 短暂延迟确保数据更新
        sleep(5);
        
        $afterResource = $tron->getAccountResources($address);
        debug("质押后资源查询结果：", $afterResource);
        
        $afterEnergyLimit = $afterResource['EnergyLimit'] ?? $afterResource['energy_limit'] ?? 0;
        $afterEnergyUsed = $afterResource['EnergyUsed'] ?? $afterResource['energy_used'] ?? 0;
        $afterAvailableEnergy = $afterEnergyLimit - $afterEnergyUsed;
        
        echo "质押后能量上限: " . $afterEnergyLimit . "\n";
        echo "质押后已使用能量: " . $afterEnergyUsed . "\n";
        echo "质押后可用能量: " . $afterAvailableEnergy . "\n\n";
        
        echo "能量增加量: " . ($afterEnergyLimit - $beforeEnergyLimit) . "\n";
        
        // 更新TRX余额
        $afterTrxBalance = $tron->getBalance($address, true);
        echo "质押后TRX余额: " . $afterTrxBalance . " TRX\n";
        echo "质押金额: " . ($trxBalance - $afterTrxBalance) . " TRX\n";
    } catch (Exception $e) {
        echo "查询质押后资源情况失败: " . $e->getMessage() . "\n";
        debug("异常详情", $e);
    }
    
    // 6. 输出最终总结
    echo "\n6. 质押操作总结\n";
    echo "-----------------------------------\n";
    
    if ($afterEnergyLimit > $beforeEnergyLimit) {
        echo "✅ 质押操作成功，能量已增加\n";
    } else {
        echo "⚠️ 质押可能未成功，能量未增加\n";
        echo "可能原因:\n";
        echo "- 公共API不支持质押操作\n";
        echo "- 需要等待更长时间以反映变化\n";
        echo "- 交易可能仍在确认中\n";
        echo "- API密钥权限不足\n";
        echo "- 资源类型格式问题\n\n";
        
        echo "替代方案:\n";
        echo "1. 使用TRON官方钱包质押: https://www.tronlink.org\n";
        echo "2. 使用波场区块浏览器质押: https://tronscan.org\n";
        echo "3. 使用私有节点或全节点执行质押操作\n";
        echo "4. 添加-d参数重试以获取更多调试信息\n\n";
    }
    
    echo "-----------------------------------\n";
    echo "质押2.0小贴士:\n";
    echo "✓ 质押最低金额为2 TRX\n";
    echo "✓ 质押的TRX仍然属于您，可以解除质押后取回\n";
    echo "✓ 获得的能量可以用于执行智能合约操作，如TRC20代币转账\n";
    echo "✓ 质押产生的能量每天会重置未使用部分\n";
    
    echo "\n解除质押说明:\n";
    echo "当您不再需要这些能量时，可以使用以下代码解除质押:\n";
    echo "\$tron->unfreezeBalanceV2(\$stakeAmount, 1); // 1代表Energy资源类型 (质押2.0)\n";
    
} catch (TronException $e) {
    echo "Tron错误: " . $e->getMessage() . "\n";
    debug("Tron异常详情", $e);
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
    debug("异常详情", $e);
} 