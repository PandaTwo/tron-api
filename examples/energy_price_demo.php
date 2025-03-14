<?php
/**
 * Zhifu Tron API - 能量价格和兑换比例示例
 * 
 * 本示例演示如何获取TRON网络的能量价格和计算TRX与能量的兑换比例
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Zhifu\TronAPI\Tron;
use Zhifu\TronAPI\Exception\TronException;

/**
 * 格式化能量数值，将大数值转换为带K单位的形式
 * 
 * @param int $energyValue 能量数值
 * @return string 格式化后的字符串
 */
function formatEnergyValue(int $energyValue): string
{
    if ($energyValue >= 1000) {
        // 将值除以1000并四舍五入到整数
        $formattedValue = round($energyValue / 1000);
        return number_format($formattedValue) . ' K';
    }
    
    return number_format($energyValue);
}

try {
    // 初始化 Tron 对象
    $fullNode = 'https://api.trongrid.io';
    $solidityNode = 'https://api.trongrid.io';
    $eventServer = 'https://api.trongrid.io';
    
    $tron = new Tron($fullNode, $solidityNode, $eventServer);
    
    echo "===== 波场(TRON)能量价格和兑换比例 =====\n\n";
    
    // 获取网络总质押量
    $totalEnergyStaked = $tron->getTotalEnergyStaked();
    echo "TRON网络能量总质押量: " . number_format($totalEnergyStaked) . " TRX\n";
    echo "每日总能量供应量: 180,000,000,000 能量\n\n";
    
    // 获取当前TRON网络API返回的能量价格(不是质押获得能量的价格)
    echo "1. 获取当前TRON网络API能量价格\n";
    
    // 定义备用价格数据（来自官方示例）
    $fallbackPriceData = [
        "prices" => "0:100,1575871200000:10,1606537680000:40,1614238080000:140,1635739080000:280,1681895880000:420"
    ];
    
    // 尝试从API获取价格，如果失败则使用备用数据
    try {
        // 尝试从API获取价格
        $energyPrice = $tron->getCurrentEnergyPrice();
        echo "当前能量价格: " . $energyPrice . " sun/能量\n";
    } catch (Exception $e) {
        echo "从API获取价格失败: " . $e->getMessage() . "\n";
        echo "使用备用价格数据...\n";
        
        // 从备用数据中提取最新价格
        $pricesStr = $fallbackPriceData['prices'];
        $pricePoints = explode(',', $pricesStr);
        $latestPricePoint = end($pricePoints);
        list(, $latestPrice) = explode(':', $latestPricePoint);
        
        echo "当前能量价格(备用数据): " . $latestPrice . " sun/能量\n";
        
        // 更新当前能量价格以便后续计算
        $energyPrice = (int)$latestPrice;
    }
    
    echo "注意: 此价格是消耗TRX购买能量的价格，不是质押TRX获得能量的兑换比例\n\n";
    
    // 2. 计算1 TRX可以通过质押兑换多少能量
    $oneTrxEnergy = $tron->calculateEnergyFromTrx(1.0);
    echo "2. 计算质押兑换比例\n";
    echo "1 TRX 质押可获得约 " . formatEnergyValue($oneTrxEnergy) . " 能量\n";
    
    $thousandTrxEnergy = $tron->calculateEnergyFromTrx(1000.0);
    echo "1000 TRX 质押可获得约 " . formatEnergyValue($thousandTrxEnergy) . " 能量\n";
    echo "质押兑换公式: 用户质押的TRX数量 ÷ 网络总质押量(" . number_format($totalEnergyStaked) . " TRX) × 180,000,000,000\n\n";
    
    // 3. 计算常见TRX数量可获得的能量
    echo "3. 不同TRX数量质押可获得的能量\n";
    echo "-----------------------------------------------------\n";
    echo "TRX 数量    |    能量数量         |    每K能量需要TRX\n";
    echo "-----------------------------------------------------\n";
    
    $trxAmounts = [1, 5, 10, 20, 50, 100, 500, 1000];
    foreach ($trxAmounts as $trx) {
        $energy = $tron->calculateEnergyFromTrx($trx);
        $energyInK = round($energy / 1000);
        $trxPerKEnergy = $energyInK > 0 ? number_format($trx / $energyInK, 2) : "N/A";
        
        echo sprintf("%10s    |    %-16s |    %s TRX/K\n", 
               $trx . " TRX", 
               formatEnergyValue($energy) . " 能量",
               $trxPerKEnergy);
    }
    echo "-----------------------------------------------------\n\n";
    
    // 4. 计算获取特定能量需要的TRX数量
    echo "4. 获取特定能量需要质押的TRX数量\n";
    echo "---------------------------------------------------------------------\n";
    echo "需要能量            |    需要TRX     |     与质押比例对照\n";
    echo "---------------------------------------------------------------------\n";
    
    // 使用更有意义的能量数值，与第3部分对应
    $energyValues = [
        11 * 1000,          // 1 TRX
        56 * 1000,          // 5 TRX
        112 * 1000,         // 10 TRX
        224 * 1000,         // 20 TRX
        559 * 1000,         // 50 TRX
        1118 * 1000,        // 100 TRX
        5591 * 1000,        // 500 TRX
        11183 * 1000        // 1000 TRX
    ];
    
    foreach ($energyValues as $energy) {
        $trxNeeded = $tron->calculateTrxForEnergy($energy);
        $referenceText = "";
        
        // 添加对照说明
        if ($energy == 11 * 1000) {
            $referenceText = "≈ 1 TRX质押获得的能量";
        } elseif ($energy == 11183 * 1000) {
            $referenceText = "≈ 1000 TRX质押获得的能量";
        }
        
        echo sprintf("%20s    |    %-12s |    %s\n", 
               formatEnergyValue($energy) . " 能量", 
               number_format($trxNeeded, 2) . " TRX",
               $referenceText);
    }
    echo "---------------------------------------------------------------------\n";
    echo "说明: 数据表明11,183 K能量需要约1000 TRX，与上表「1000 TRX质押获得11,183 K能量」一致\n\n";
    
    // 5. 获取完整的能量价格历史
    echo "5. 获取完整的能量价格历史\n";
    
    try {
        $priceInfo = $tron->getEnergyPrice();
        
        if ($priceInfo['success']) {
            echo "API调用成功\n";
            
            if (!empty($priceInfo['price_history'])) {
                echo "历史价格变动记录 (注意: 这是API返回的消耗价格，不是质押兑换比例):\n";
                echo "-----------------------------------------\n";
                echo "      时间      |  价格(sun)  |  1 TRX可兑换能量(旧算法)\n";
                echo "-----------------------------------------\n";
                
                foreach ($priceInfo['price_history'] as $pricePoint) {
                    echo sprintf("%17s | %11s | %17s\n", 
                           $pricePoint['date'], 
                           $pricePoint['price'],
                           formatEnergyValue($pricePoint['energy_per_trx']));
                }
                echo "-----------------------------------------\n";
            } else {
                echo "没有历史价格记录\n";
            }
        } else {
            echo "获取价格历史失败: " . ($priceInfo['message'] ?? "未知错误") . "\n";
            if (isset($priceInfo['error'])) {
                echo "错误详情: " . $priceInfo['error'] . "\n";
            }
            
            // 使用备用数据显示价格历史
            echo "\n使用备用价格数据显示历史记录:\n";
            echo "-----------------------------------------\n";
            echo "      时间戳      |  价格(sun)  \n";
            echo "-----------------------------------------\n";
            
            $pricesStr = $fallbackPriceData['prices'];
            $pricePoints = explode(',', $pricesStr);
            
            foreach ($pricePoints as $point) {
                list($timestamp, $price) = explode(':', $point);
                $date = $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp/1000) : '初始价格';
                echo sprintf("%17s | %11s\n", $date, $price);
            }
            echo "-----------------------------------------\n";
            echo "注: 此数据来自备用源，可能不是最新的\n";
        }
    } catch (Exception $e) {
        echo "获取价格历史时发生错误: " . $e->getMessage() . "\n";
        
        // 使用备用数据显示价格历史
        echo "\n使用备用价格数据显示历史记录:\n";
        echo "-----------------------------------------\n";
        echo "      时间戳      |  价格(sun)  \n";
        echo "-----------------------------------------\n";
        
        $pricesStr = $fallbackPriceData['prices'];
        $pricePoints = explode(',', $pricesStr);
        
        foreach ($pricePoints as $point) {
            list($timestamp, $price) = explode(':', $point);
            $date = $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp/1000) : '初始价格';
            echo sprintf("%17s | %11s\n", $date, $price);
        }
        echo "-----------------------------------------\n";
        echo "注: 此数据来自备用源，可能不是最新的\n";
    }
    
    // 6. 实际应用：估算USDT转账所需能量和费用
    echo "\n6. 实际应用: 估算USDT转账所需能量和费用\n";
    
    $usdtContract = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'; // USDT合约地址
    $toAddress = 'TFpS9NJ4Djm29RTmax3VonXL8HumgrC4zW';    // 示例接收地址
    $amount = 10.5;                                       // 转账金额
    
    try {
        echo "估算转账 " . $amount . " USDT 所需能量...\n";
        
        $energyEstimate = $tron->estimateTrc20TransferEnergy(
            $usdtContract,
            $toAddress,
            $amount
        );
        
        echo "估算方法: " . $energyEstimate['estimation_method'] . "\n";
        echo "预估所需能量: " . formatEnergyValue($energyEstimate['energy_used']) . " 能量\n";
        echo "能量单价: " . $energyEstimate['energy_price'] . " sun/能量\n";
        echo "建议费用限制: " . number_format($energyEstimate['suggested_fee_limit']) . " sun (约 " 
             . $energyEstimate['suggested_fee_limit_trx'] . " TRX)\n";
        
        // 计算如果质押TRX获取能量，需要多少TRX
        $trxNeededForStaking = $tron->calculateTrxForEnergy($energyEstimate['energy_used']);
        echo "如果质押TRX获取能量，需要质押: " . number_format($trxNeededForStaking, 2) . " TRX\n";
        
        // 比较质押和直接支付费用哪个更划算
        if ($trxNeededForStaking < $energyEstimate['suggested_fee_limit_trx']) {
            $savingPercentage = (1 - $trxNeededForStaking / $energyEstimate['suggested_fee_limit_trx']) * 100;
            echo "质押TRX获取能量比直接支付费用便宜约 " . number_format($savingPercentage, 2) . "%\n";
            echo "建议: 如果您经常进行交易，质押TRX获取能量将更经济\n";
        } else {
            echo "对于单次交易，直接支付费用比质押TRX获取能量更经济\n";
            echo "建议: 如果这是临时交易，直接支付费用更方便\n";
        }
    } catch (Exception $e) {
        echo "估算能量失败: " . $e->getMessage() . "\n";
    }
    
    // 7. 尝试从Shasta测试网获取能量价格
    echo "\n7. 从Shasta测试网获取能量价格\n";
    echo "-----------------------------------\n";
    
    try {
        $shastaPrice = $tron->getEnergyPriceFromNetwork('shasta');
        
        if ($shastaPrice['success']) {
            echo "Shasta测试网能量价格获取成功!\n";
            echo "当前价格: " . $shastaPrice['current_price'] . " sun/能量\n";
            
            if (!empty($shastaPrice['price_history'])) {
                echo "\nShasta测试网能量价格历史:\n";
                echo "---------------------------------------------\n";
                echo "    时间点        |  价格(sun)  |  对应兑换比例\n";
                echo "---------------------------------------------\n";
                
                foreach ($shastaPrice['price_history'] as $pricePoint) {
                    echo sprintf("%-17s | %11s | %11s\n", 
                        $pricePoint['date'], 
                        $pricePoint['price'], 
                        "1 TRX ≈ " . formatEnergyValue($pricePoint['energy_per_trx']));
                }
                echo "---------------------------------------------\n";
            } else {
                echo "没有可用的价格历史数据\n";
            }
        } else {
            echo "无法从Shasta获取价格: " . ($shastaPrice['message'] ?? "未知错误") . "\n";
            if (isset($shastaPrice['error'])) {
                echo "错误详情: " . $shastaPrice['error'] . "\n";
            }
        }
    } catch (Exception $e) {
        echo "获取Shasta能量价格失败: " . $e->getMessage() . "\n";
    }
    
    echo "\n比较主网与测试网:\n";
    echo "- 主网能量价格: " . $energyPrice . " sun/能量\n";
    echo "- 测试网能量价格: " . ($shastaPrice['success'] ? $shastaPrice['current_price'] : "获取失败") . " sun/能量\n";
    echo "注意: 测试网的价格和规则可能与主网不同，仅用于开发测试\n";
    
    echo "\n提示: 能量价格和质押兑换比例会随网络情况变化，以上计算仅供参考\n";
    echo "随着更多用户质押TRX获取能量，兑换比例会相应变化\n";
    echo "如果您频繁进行交易，质押TRX获取能量通常比支付费用更经济\n";
    echo "\n=== API使用建议 ===\n";
    echo "1. 考虑申请TronGrid API密钥以提高速率限制: https://www.trongrid.io/dashboard\n";
    echo "2. 在开发和测试阶段，可以使用Shasta测试网络: https://api.shasta.trongrid.io\n";
    echo "3. 在生产环境中，可以考虑缓存能量价格数据，避免频繁API调用\n";
    
} catch (TronException $e) {
    echo "Tron错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
} 