# Zhifu Tron API

基于 iexbase/tron-api 二次开发的增强版波场（TRON）API接口库。提供更完善的功能和更稳定的表现，简化TRON区块链应用开发。

## 功能特性

* **完整的波场API支持** - 支持波场网络上的所有主要操作
* **增强的代币支持** - 全面支持TRC10和TRC20代币操作
* **能量和带宽管理** - 质押TRX获取能量和带宽的完整解决方案
* **高级交易功能** - 支持批量转账、能量估算等高级功能
* **钱包管理工具** - 提供完整的钱包创建、导入和验证功能
* **交易监控机制** - 实现自动充值检测和通知
* **完善的错误处理** - 提供更详细的错误信息和异常处理
* **丰富的示例代码** - 提供多种场景的实际应用示例

## 安装

```bash
# 标准安装
composer require zhifu/tron-api

# 如果安装过程中遇到扩展或PHP版本兼容性问题，可以使用以下命令忽略平台要求
composer require zhifu/tron-api --ignore-platform-reqs
```

如果遇到依赖问题，有以下几种解决方案：

### GMP扩展问题

#### 方案1：安装GMP扩展（推荐）

这是最佳方案，可以使用所有功能：

```bash
# Ubuntu/Debian
sudo apt-get install php7.4-gmp
sudo service apache2 restart

# CentOS/RHEL
sudo yum install php-gmp
sudo systemctl restart httpd

# macOS (使用Homebrew)
brew install php@7.4-gmp
```

#### 方案2：在项目中绕过GMP扩展检查

如果无法安装GMP扩展，可以在项目的`composer.json`中添加如下配置：

```json
{
    "config": {
        "platform": {
            "ext-gmp": "1.0.0"
        }
    }
}
```

### PHP版本兼容性问题

本库支持PHP 7.4及以上版本。对于底层依赖`iexbase/tron-api`，我们兼容3.x, 4.x和5.x版本：

- 使用PHP 7.4：会自动选择兼容的3.x或4.x版本
- 使用PHP 8.0+：会优先使用最新的5.x版本

如果安装时遇到PHP版本兼容性问题，可以尝试以下方案：

```bash
# 方案1：在安装时忽略平台要求（不推荐）
composer require zhifu/tron-api --ignore-platform-reqs

# 方案2：在项目的composer.json中设置platform配置（推荐）
# 在composer.json中添加：
{
    "config": {
        "platform": {
            "php": "7.4.33"
        }
    }
}
```

注意：方案2会影响所有依赖的安装，确保这符合您的项目需求。

## 要求

支持以下PHP版本：

* PHP 7.4 及以上
* 必须安装BCMath扩展（用于高精度计算）
* 使用钱包功能时需要安装GMP扩展（用于私钥生成和地址验证）

### 安装GMP扩展（可选，仅用于钱包功能）

如果您需要使用钱包创建、导入和验证功能，则需要安装GMP扩展：

对于Ubuntu/Debian：
```bash
sudo apt-get install php7.4-gmp
sudo service apache2 restart  # 或 php-fpm
```

对于CentOS/RHEL：
```bash
sudo yum install php-gmp
sudo systemctl restart httpd  # 或 php-fpm
```

对于macOS (使用Homebrew)：
```bash
brew install php@7.4-gmp
```

### 跳过GMP扩展检查（不推荐）

如果您确定不会使用钱包功能，可以在composer.json中添加以下配置来绕过GMP扩展检查：

```json
{
    "config": {
        "platform": {
            "ext-gmp": "1.0.0"
        }
    }
}
```

## 基本用法

```php
use Zhifu\TronAPI\Tron;

// 初始化节点提供者
$fullNode = 'https://api.trongrid.io';
$solidityNode = 'https://api.trongrid.io';
$eventServer = 'https://api.trongrid.io';

try {
    $tron = new Tron($fullNode, $solidityNode, $eventServer);
    
    // 设置地址
    $tron->setAddress('您的钱包地址');
    
    // 查询余额（以TRX为单位）
    $balance = $tron->getBalance(null, true);
    echo "余额: " . $balance . " TRX\n";
    
    // 查询USDT余额
    $usdtBalance = $tron->getUsdtBalance();
    echo "USDT余额: " . $usdtBalance . " USDT\n";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage();
}
```

## API密钥支持

当使用公共节点进行大量请求时，您可能需要使用API密钥来避免请求限制。

### 获取API密钥

在 [TRON Grid 官网](https://www.trongrid.io/dashboard) 注册账户并获取API密钥。

### 设置API密钥

```php
// 在创建Tron实例时设置API密钥
$apiKey = '您的TRON API密钥';
$tron = new Tron($fullNode, $solidityNode, $eventServer, null, $apiKey);

// 或者在实例创建后设置
$tron->setApiKey('您的TRON API密钥');
```

详细示例请参考 `examples/api_key_demo.php`。

## 钱包管理

### 创建新钱包

```php
// 生成新钱包
$wallet = $tron->generateWallet();
echo "私钥: " . $wallet['privateKey'] . "\n";
echo "地址: " . $wallet['address'] . "\n";
```

### 从私钥导入钱包

```php
// 从私钥获取地址
$privateKey = "您的私钥";
$addressInfo = $tron->getAddressFromPrivateKey($privateKey);
echo "地址: " . $addressInfo['address'] . "\n";
```

### 验证私钥与地址

```php
// 验证私钥与地址是否匹配
$isValid = $tron->validatePrivateKey($privateKey, '地址');
echo "验证结果: " . ($isValid ? '匹配' : '不匹配') . "\n";
```

详细示例请参考 `examples/wallet_demo.php`。

## TRC20代币操作

### 查询代币余额

```php
// 查询USDT余额
$usdtBalance = $tron->getUsdtBalance('钱包地址');
echo "USDT余额: " . $usdtBalance . " USDT\n";

// 查询其他TRC20代币
$tokenContract = '代币合约地址';
$tokenBalance = $tron->getTokenBalance($tokenContract, '钱包地址');
echo "代币余额: " . $tokenBalance . "\n";
```

### 转账TRC20代币

```php
// 设置发送方地址和私钥
$tron->setAddress('发送方地址');
$tron->setPrivateKey('私钥');

// 获取合约对象
$contract = $tron->contract('代币合约地址');

// 转账 - 自动设置安全的费用限制
$result = $contract->transfer('接收方地址', '金额');

// 检查结果
if (isset($result['result']) && $result['result'] === true) {
    echo "转账成功，交易ID: " . $result['txid'] . "\n";
} else {
    echo "转账失败\n";
}
```

详细转账示例请参考 `examples/transfer_demo.php`。

### 查询TRC20代币交易历史

```php
// 设置API版本
$tron->setApiVersion('v1');

// 查询USDT交易记录
$usdtContract = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'; // USDT合约地址
$transactions = $tron->getTrc20TransactionsByAccount(
    '钱包地址',
    $usdtContract,
    20 // 查询条数
);

// 显示交易记录
foreach ($transactions['data'] as $tx) {
    echo "交易ID: " . $tx['transaction_id'] . "\n";
    echo "金额: " . $tx['value'] . "\n";
}
```

详细示例请参考 `examples/trc20_transactions_demo.php`。

## 自动充值检测

```php
// 已处理的交易ID列表
$processedTxIds = [
    // 从数据库中获取已处理的交易ID
];

// 检查是否收到指定金额的USDT付款
$payment = $tron->checkTrc20Payment(
    '接收地址',
    'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', // USDT合约地址
    100.00, // 预期金额
    3600000, // 检查最近1小时内的交易
    $processedTxIds
);

if ($payment) {
    echo "收到匹配的充值，金额: " . $payment['amount'] . " USDT\n";
}
```

## 质押TRX获取资源

波场网络上的交易需要消耗能量和带宽资源，可以通过质押TRX获取这些资源。

### 质押获取能量

```php
// 设置钱包地址和私钥
$tron->setAddress('钱包地址');
$tron->setPrivateKey('私钥');

// 质押10个TRX获取能量
$result = $tron->freezeBalanceForEnergyV2(10);

if (isset($result['result']) && $result['result'] === true) {
    echo "质押成功，交易ID: " . $result['txid'] . "\n";
} else {
    echo "质押失败\n";
}
```

### 质押获取带宽

```php
// 质押5个TRX获取带宽
$result = $tron->freezeBalanceForBandwidthV2(5);
```

### 解除质押

```php
// 解除质押 (10 TRX, 能量)
$result = $tron->unfreezeBalanceV2(10, 1);
```

详细质押示例请参考 `examples/stake_energy_demo.php`。

## 能量估算

在转账前，可以估算交易所需的能量，避免因能量不足导致交易失败。

```php
// 估算TRC20转账所需能量
$energyEstimate = $tron->estimateTrc20TransferEnergy(
    'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', // USDT合约地址
    '接收方地址',
    10.5, // 转账金额
    '发送方地址'
);

echo "预估所需能量: " . $energyEstimate['energy_used'] . "\n";
echo "建议费用限制: " . $energyEstimate['suggested_fee_limit_trx'] . " TRX\n";
```

## 查询账户资源

```php
// 查询账户的资源情况
$resources = $tron->getAccountResources('钱包地址');

echo "能量上限: " . ($resources['EnergyLimit'] ?? 0) . "\n";
echo "已使用能量: " . ($resources['EnergyUsed'] ?? 0) . "\n";
echo "带宽上限: " . ($resources['NetLimit'] ?? 0) . "\n";
echo "已使用带宽: " . ($resources['NetUsed'] ?? 0) . "\n";
```

## 批量操作

### 批量查询余额

```php
// 批量查询USDT余额
$contract = $tron->contract('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');
$balances = $contract->batchBalanceOf([
    'TJoq53NiXhrgC9G2KNvpKv2s6UkcdNRgFP',
    'TFpS9NJ4Djm29RTmax3VonXL8HumgrC4zW'
]);

foreach ($balances as $address => $balance) {
    echo $address . ": " . $balance . " USDT\n";
}
```

### 批量转账TRC20代币

```php
// 设置发送方地址和私钥
$tron->setAddress('发送方地址');
$tron->setPrivateKey('私钥');

// 获取合约对象
$contract = $tron->contract('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');

// 批量转账
$transfers = [
    ['to' => '接收方地址1', 'amount' => 1.5],
    ['to' => '接收方地址2', 'amount' => 2.3]
];

$results = $contract->batchTransfer($transfers);

foreach ($results as $result) {
    echo ($result['success'] ? "成功" : "失败") . "\n";
}
```

详细的批量转账示例请参考 `examples/batch_transfer_usdt_demo.php`。

## 示例文件说明

本库提供了多个示例文件，位于 `examples/` 目录下：

- **basic_usage.php** - 基本用法示例
- **wallet_demo.php** - 钱包管理示例
- **balance_demo.php** - 余额查询示例
- **transfer_demo.php** - TRC20代币转账示例
- **batch_transfer_usdt_demo.php** - TRC20代币批量转账示例
- **trc20_transactions_demo.php** - TRC20交易记录查询示例
- **api_key_demo.php** - API密钥使用示例
- **stake_energy_demo.php** - 质押TRX获取能量示例
- **energy_guide.php** - 能量使用指南
- **staking_guide.md** - 质押指南文档
- **check_balance.php** - 余额检查工具
- **trx_balance.php** - TRX余额查询工具
- **production_balance.php** - 生产环境余额查询示例

## 错误处理

本库提供了详细的错误信息和异常处理，使用 try-catch 捕获异常：

```php
try {
    $result = $tron->send('接收方地址', 100);
} catch (TronException $e) {
    echo "Tron错误: " . $e->getMessage() . "\n";
    echo "错误代码: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "一般错误: " . $e->getMessage() . "\n";
}
```

## 注意事项

1. **节点选择** - 建议使用私有节点或付费API服务，公共节点可能会有请求限制和稳定性问题
2. **费用设置** - TRC20转账时，确保设置适当的费用限制，避免因能量不足导致交易失败
3. **API密钥** - 在生产环境中，强烈建议使用API密钥
4. **质押操作** - 质押和解除质押操作在某些公共节点上可能受限，建议使用私有节点
5. **能量管理** - 在进行TRC20交易前，确保账户有足够的能量或TRX余额

## 赞助

如果您觉得这个项目有帮助，可以给予赞助：

**Tron(TRX)**: TJoq53NiXhrgC9G2KNvpKv2s6UkcdNRgFP

## 许可证

本项目采用 MIT 许可证 