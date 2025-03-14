<?php
/**
 * Zhifu Tron API - TRC20代币交易查询和充值监控示例
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
    
    $tron = new Tron($fullNode, $solidityNode, $eventServer);
    
    // 设置API版本
    $tron->setApiVersion('v1');
    
    echo "===== TRC20代币交易查询和充值监控示例 =====\n\n";
    
    // 示例地址
    $address = 'TJoq53NiXhrgC9G2KNvpKv2s6UkcdNRgFP';
    
    // USDT合约地址
    $usdtContract = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
    
    // 1. 查询最近交易历史
    echo "1. 查询账户 {$address} 的USDT交易历史\n";
    $transactions = $tron->getTrc20TransactionsByAccount(
        $address, 
        $usdtContract, 
        10,  // 只查询10条记录
        null,
        null,
        true, // 只查询已确认的
        false, // 不过滤转入/转出
        false
    );
    
    if (isset($transactions['data']) && !empty($transactions['data'])) {
        echo "查询到 " . count($transactions['data']) . " 条交易记录：\n";
        
        foreach ($transactions['data'] as $index => $tx) {
            $direction = ($tx['from'] === $address) ? "转出" : "转入";
            $amount = isset($tx['value']) ? $tx['value'] : '未知金额';
            $symbol = isset($tx['token_info']['symbol']) ? $tx['token_info']['symbol'] : 'TRC20';
            $time = date('Y-m-d H:i:s', floor($tx['block_timestamp']/1000));
            
            echo "\n交易 #" . ($index + 1) . ":\n";
            echo "  交易ID: " . $tx['transaction_id'] . "\n";
            echo "  方向: " . $direction . "\n";
            echo "  从: " . $tx['from'] . "\n";
            echo "  到: " . $tx['to'] . "\n";
            echo "  金额: " . $amount . " " . $symbol . "\n";
            echo "  时间: " . $time . "\n";
        }
    } else {
        echo "没有查询到交易记录\n";
    }
    echo "\n";
    
    // 2. 模拟充值监控实现
    echo "2. 模拟充值监控实现\n";
    
    $walletAddress = $address; // 这里假设是用户的充值地址
    $expectedAmount = 100.00; // 期望收到的USDT金额
    
    // 假设这些是已经处理过的交易ID
    $processedTxIds = [
        // 示例，实际应用中应该从数据库读取
        '5a1c24340094a870802eba326b8b3f9d348c9cef552a4f13afe8bfea60fd9cb3'
    ];
    
    echo "监控钱包地址: " . $walletAddress . "\n";
    echo "预期收到金额: " . $expectedAmount . " USDT\n";
    echo "开始检查是否收到付款...\n\n";
    
    try {
        $payment = $tron->checkTrc20Payment(
            $walletAddress,
            $usdtContract,
            $expectedAmount,
            86400000, // 查询最近24小时的交易
            $processedTxIds
        );
        
        if ($payment) {
            echo "🎉 找到匹配的付款交易!\n";
            echo "交易ID: " . $payment['transaction_id'] . "\n";
            echo "发送方: " . $payment['from'] . "\n";
            echo "金额: " . $payment['amount'] . " USDT\n";
            echo "时间: " . date('Y-m-d H:i:s', floor($payment['block_timestamp']/1000)) . "\n";
            
            // 此处应进行数据库操作，将交易标记为已处理，并给用户充值
            echo "\n在实际应用中，此处应:\n";
            echo "1. 将交易ID保存到数据库，标记为已处理\n";
            echo "2. 为用户账户增加相应金额\n";
            echo "3. 可选: 将资金归集到主钱包\n";
        } else {
            echo "❌ 未找到匹配的付款交易\n";
            echo "请确认付款是否已经完成，或者稍后再次检查\n";
        }
    } catch (TronException $e) {
        echo "检查付款时出错: " . $e->getMessage() . "\n";
    }
    
    // 3. 实际应用中的自动监控示例代码（仅作参考）
    echo "\n3. 实际应用中的自动监控示例代码\n";
    echo "以下是一个自动监控脚本的伪代码：\n\n";
    echo "```php\n";
    echo "// 这部分代码应该放在一个由计划任务定时执行的脚本中\n";
    echo "function monitorDeposits() {\n";
    echo "    // 1. 从数据库获取所有等待充值确认的订单\n";
    echo "    \$pendingOrders = DB::getPendingDepositOrders();\n\n";
    echo "    foreach (\$pendingOrders as \$order) {\n";
    echo "        // 2. 检查每个订单对应的钱包地址是否收到付款\n";
    echo "        \$processedTxIds = DB::getProcessedTransactionIds(\$order['address']);\n";
    echo "        \$payment = \$tron->checkTrc20Payment(\n";
    echo "            \$order['address'],\n";
    echo "            \$order['contract_address'],\n";
    echo "            \$order['amount'],\n";
    echo "            \$order['valid_timespan'],\n";
    echo "            \$processedTxIds\n";
    echo "        );\n\n";
    echo "        if (\$payment) {\n";
    echo "            // 3. 如果找到匹配的付款，更新订单状态\n";
    echo "            DB::markOrderAsPaid(\$order['id'], \$payment['transaction_id']);\n";
    echo "            \n";
    echo "            // 4. 为用户账户增加余额\n";
    echo "            DB::increaseUserBalance(\$order['user_id'], \$payment['amount']);\n";
    echo "            \n";
    echo "            // 5. 发送通知给用户\n";
    echo "            NotificationService::sendDepositConfirmation(\$order['user_id'], \$payment);\n";
    echo "            \n";
    echo "            // 6. 可选: 将资金归集到主钱包\n";
    echo "            CollectionService::scheduleCollection(\$order['address'], \$payment['amount']);\n";
    echo "        }\n";
    echo "    }\n";
    echo "}\n";
    echo "```\n";
    
} catch (TronException $e) {
    echo "错误: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
} 