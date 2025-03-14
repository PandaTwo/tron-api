# Change Log
所有显著变化都将记录在此文件中。

## [v1.2.0] - 2024-03-18
### 重构
- 重构项目结构，将lib目录代码迁移至src目录
- 简化命名空间，从`Zhifu\TronAPI\Lib\IEXBase`变更为`Zhifu\TronAPI`
- 优化composer自动加载配置
- 修复BcNumber类中的divide方法调用问题
- 提高代码可读性和可维护性
- 移除冗余目录和文件
- 使用自己fork的`pandatwo/web3.php`替代官方包，确保长期可用性

## [v1.1.0] - 2023-06-XX
### 新增
- 直接整合底层库到项目中，彻底解决内存泄漏问题
- 新增`lib`目录，包含优化过的核心组件
- 添加`TRC20Contract`类的批量转账功能
- 改进的缓存管理系统，有效控制内存使用
- 新增`lib_usage_demo.php`示例，展示整合库的用法
- 新增`LIB_INTEGRATION.md`文档，详细说明整合库的优势和使用方法

### 优化
- 重写底层合约交互代码，专注内存优化
- 改进命名空间结构，避免与原库冲突
- 增强类型安全，符合现代PHP标准
- 优化`composer.json`自动加载配置

### 修复
- 修复底层库导致的严重内存溢出问题
- 解决大量交易场景下的内存泄漏

## [v1.0.6] - 2023-06-XX
### 新增
- 自动修补底层库内存泄漏功能
- 添加post-install和post-update自动修补脚本
- 优化底层库`TRC20Contract.php`垃圾回收机制

### 修复
- 修复底层库导致的严重内存溢出问题
- 解决1GB内存限制下的应用崩溃问题
- 提高长时间运行脚本的稳定性

## [1.0.6] - 2024-03-15

### 改进
- 添加底层库内存泄漏自动修补功能
- 解决iexbase/tron-api库中的严重内存溢出问题
- 添加post-install和post-update自动修补脚本
- 优化底层TRC20Contract.php的垃圾回收机制
- 适用于1GB以上内存限制环境的应用
- 增强长时间运行脚本的稳定性

## [1.0.5] - 2024-03-15

### 改进
- 添加自动清理缓存功能，显著降低内存占用
- 重写transfer方法，每次转账后自动清理不需要的缓存
- 为批量查询和转账操作增加自动清理功能
- 引入setAutoCleaning方法允许用户控制自动清理功能
- 优化缓存管理策略，只保留当前使用的合约信息
- 解决反复调用时内存持续增长的问题

## [1.0.4] - 2024-03-15

### 改进
- 显著优化TRC20Contract类的内存使用效率
- 限制缓存条目数量，防止无限增长
- 添加静态方法clearCache()用于主动清理缓存
- 在批量操作中实现分批处理机制
- 增加主动触发垃圾回收，减少内存占用
- 优化bcmath计算精度，降低资源消耗

## [1.0.3] - 2024-03-15

### 改进
- 增强与PHP 7.4的兼容性，允许使用更低版本的iexbase/tron-api
- 在platform配置中添加PHP版本设置，确保正确的兼容性检查
- 通过放宽依赖版本要求，提高与各种环境的兼容性

## [1.0.2] - 2024-03-15

### 改进
- 通过平台配置完全绕过GMP扩展检查
- 更新安装说明，提供两种解决方案
- 优化错误处理，在没有GMP扩展时提供更友好的错误信息

## [1.0.1] - 2024-03-14

### 改进
- 移除GMP扩展的强制依赖，使其成为可选依赖
- 在使用钱包功能时才检查GMP扩展
- 添加了友好的错误提示，指导用户如何安装GMP扩展或绕过检查

## [1.0.0] - 2024-03-14

### 新增
- 基于 iexbase/tron-api 5.0 版本进行全面增强
- 添加能量价格查询和能量估算功能
- 支持质押2.0 API
- 增加TRC20代币批量转账功能
- 增加USDT余额快速查询功能
- 增加自动充值检测功能
- 添加钱包管理相关功能
- 支持从Shasta测试网获取能量价格

### 改进
- 优化API错误处理
- 增强TRC20代币转账功能
- 优化API请求和响应处理
- 提高交易安全性
- 改进余额查询稳定性

### 修复
- 修复能量估算问题
- 修复质押2.0资源类型使用错误
- 修复USDT转账费用限制设置问题 