# 底层库直接整合说明

## 为什么要直接整合底层库？

本项目原本依赖`iexbase/tron-api`库作为底层实现，但在实际使用中发现该库存在一些问题，特别是内存泄漏问题。为了从根本上解决这些问题，我们决定将关键组件直接整合到我们的项目中。这样做有以下优势：

1. **完全控制代码质量**：可以直接修改源代码，而不是通过补丁修复
2. **消除依赖**：减少对外部库的依赖，提高稳定性
3. **性能优化**：针对我们的使用场景进行定制化优化
4. **内存管理增强**：彻底解决底层库的内存泄漏问题
5. **简化部署**：不再需要安装后运行补丁脚本

## 目录结构

整合后的库位于`lib`目录下，主要包含以下内容：

```
lib/
├── IEXBase/
│   ├── Exception/
│   │   └── TRC20Exception.php
│   ├── Support/
│   │   └── BcNumber.php
│   ├── TRC20Contract.php
│   ├── Tron.php
│   └── trc20.json
```

## 主要改进

与原始库相比，我们的整合版本进行了以下关键改进：

1. **内存优化**：
   - 添加了`clearLocalVars()`方法，主动触发垃圾回收
   - 在关键方法结束时清理不再需要的变量
   - 使用静态缓存限制条目数量，防止无限增长

2. **代码质量**：
   - 改进了类型声明和类型安全
   - 添加了全面的异常处理
   - 提供了完整的方法文档

3. **新功能**：
   - 批量转账功能（`batchTransfer`方法）
   - 缓存管理功能（`clearCache`方法）
   - 内存使用监控功能

## 如何使用

整合库已通过Composer自动加载配置集成到项目中，可以通过以下命名空间访问：

```php
use Zhifu\TronAPI\Lib\IEXBase\Tron;
use Zhifu\TronAPI\Lib\IEXBase\TRC20Contract;
```

### 基本用法示例

```php
// 创建Tron实例
$tron = new Tron();

// 设置USDT合约地址
$contractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';

// 创建TRC20合约实例
$contract = new TRC20Contract($tron, $contractAddress);

// 设置手续费上限 (5 TRX)
$contract->setFeeLimit(5);

// 获取代币信息
$name = $contract->name();
$symbol = $contract->symbol();
$decimals = $contract->decimals();
$totalSupply = $contract->totalSupply();

// 查询余额
$balance = $contract->balanceOf('TW24cyXW4EcgBV6aFjw9kCZ7oMz9C5j7nK');

// 转账
$result = $contract->transfer('TW24cyXW4EcgBV6aFjw9kCZ7oMz9C5j7nK', '10.5');

// 批量转账
$receivers = [
    ['to' => 'TW24cyXW4EcgBV6aFjw9kCZ7oMz9C5j7nK', 'amount' => '10.5'],
    ['to' => 'TVDGpn4hCSzJ5BQmjR96S9xAgwf8ZNjtY1', 'amount' => '25.75']
];
$results = $contract->batchTransfer($receivers);

// 清理缓存，减少内存使用
TRC20Contract::clearCache();
```

### 内存管理

为了优化内存使用，特别是在高频调用场景下，建议：

1. 在不需要时主动清理缓存：`TRC20Contract::clearCache()`
2. 定期监控内存使用：`memory_get_usage()`
3. 使用完合约对象后设置为null：`$contract = null;`
4. 对于批量操作，处理完一批后触发GC：`gc_collect_cycles()`

## 迁移指南

如果你之前使用的是标准的`zhifu/tron-api`库并通过补丁解决内存问题，现在可以直接使用整合版本。主要变更是命名空间从：

```php
use Zhifu\TronAPI\TRC20;  // 旧版
```

变更为：

```php
use Zhifu\TronAPI\Lib\IEXBase\TRC20Contract;  // 新版
```

方法签名和功能保持兼容，无需修改现有代码逻辑。

## 示例

完整的使用示例可以在`examples/lib_usage_demo.php`文件中找到。 