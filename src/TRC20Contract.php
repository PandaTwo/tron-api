<?php

namespace Zhifu\TronAPI;

use Zhifu\TronAPI\Exception\TronException;

/**
 * 增强版TRC20合约类
 */
class TRC20Contract extends \IEXBase\TronAPI\TRC20Contract
{
    /**
     * 缓存合约的小数位数
     * 最多保存50个合约信息
     * 
     * @var array
     */
    protected static $decimalsCache = [];
    
    /**
     * 缓存合约的名称
     * 最多保存50个合约信息
     * 
     * @var array
     */
    protected static $nameCache = [];
    
    /**
     * 缓存合约的符号
     * 最多保存50个合约信息
     * 
     * @var array
     */
    protected static $symbolCache = [];
    
    /**
     * 最大缓存条目数
     */
    const MAX_CACHE_ENTRIES = 50;
    
    /**
     * 是否自动清理缓存
     * 默认为true，可以通过setAutoCleaning(false)关闭
     * 
     * @var bool
     */
    protected static $autoCleaning = true;
    
    /**
     * 设置是否自动清理缓存
     * 
     * @param bool $enabled 是否启用自动清理
     * @return void
     */
    public static function setAutoCleaning(bool $enabled): void
    {
        self::$autoCleaning = $enabled;
    }
    
    /**
     * 获取代币名称（带缓存）
     *
     * @return string
     */
    public function name(): string
    {
        $contractAddress = $this->getContractAddress();
        
        if (!isset(self::$nameCache[$contractAddress])) {
            // 如果缓存过大，清除最旧的条目
            if (count(self::$nameCache) >= self::MAX_CACHE_ENTRIES) {
                self::$nameCache = array_slice(self::$nameCache, -self::MAX_CACHE_ENTRIES + 1, null, true);
            }
            
            self::$nameCache[$contractAddress] = parent::name();
        }
        
        return self::$nameCache[$contractAddress];
    }
    
    /**
     * 获取代币符号（带缓存）
     *
     * @return string
     */
    public function symbol(): string
    {
        $contractAddress = $this->getContractAddress();
        
        if (!isset(self::$symbolCache[$contractAddress])) {
            // 如果缓存过大，清除最旧的条目
            if (count(self::$symbolCache) >= self::MAX_CACHE_ENTRIES) {
                self::$symbolCache = array_slice(self::$symbolCache, -self::MAX_CACHE_ENTRIES + 1, null, true);
            }
            
            self::$symbolCache[$contractAddress] = parent::symbol();
        }
        
        return self::$symbolCache[$contractAddress];
    }
    
    /**
     * 获取代币小数位数（带缓存）
     *
     * @return int
     */
    public function decimals(): int
    {
        $contractAddress = $this->getContractAddress();
        
        if (!isset(self::$decimalsCache[$contractAddress])) {
            // 如果缓存过大，清除最旧的条目
            if (count(self::$decimalsCache) >= self::MAX_CACHE_ENTRIES) {
                self::$decimalsCache = array_slice(self::$decimalsCache, -self::MAX_CACHE_ENTRIES + 1, null, true);
            }
            
            self::$decimalsCache[$contractAddress] = parent::decimals();
        }
        
        return self::$decimalsCache[$contractAddress];
    }
    
    /**
     * 清除合约缓存
     * 
     * @param string|null $contractAddress 指定要清除的合约地址，null表示清除所有缓存
     * @return void
     */
    public static function clearCache(string $contractAddress = null): void
    {
        if ($contractAddress === null) {
            self::$decimalsCache = [];
            self::$nameCache = [];
            self::$symbolCache = [];
        } else {
            unset(self::$decimalsCache[$contractAddress]);
            unset(self::$nameCache[$contractAddress]);
            unset(self::$symbolCache[$contractAddress]);
        }
    }
    
    /**
     * 转账后清理资源
     * 主动释放内存并触发垃圾回收
     * 
     * @return void
     */
    public function cleanupAfterTransfer(): void
    {
        // 清理当前合约的缓存（通常只需保留当前使用的合约信息）
        if (self::$autoCleaning) {
            $currentContract = $this->getContractAddress();
            
            // 保留当前合约的缓存，清除其他合约的缓存
            foreach (self::$decimalsCache as $address => $value) {
                if ($address !== $currentContract) {
                    unset(self::$decimalsCache[$address]);
                }
            }
            
            foreach (self::$nameCache as $address => $value) {
                if ($address !== $currentContract) {
                    unset(self::$nameCache[$address]);
                }
            }
            
            foreach (self::$symbolCache as $address => $value) {
                if ($address !== $currentContract) {
                    unset(self::$symbolCache[$address]);
                }
            }
        }
        
        // 主动触发垃圾回收
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
    
    /**
     * 重写父类的transfer方法，增加自动清理功能
     * 
     * @param string $to 接收地址
     * @param float|string|int $amount 转账金额
     * @param string|null $from 发送地址，默认为当前设置的地址
     * @return array 交易结果
     */
    public function transfer(string $to, $amount, ?string $from = null): array
    {
        try {
            // 调用父类的transfer方法
            $result = parent::transfer($to, $amount, $from);
            
            // 转账后自动清理资源
            $this->cleanupAfterTransfer();
            
            return $result;
        } catch (\Exception $e) {
            // 即使发生异常也尝试清理资源
            $this->cleanupAfterTransfer();
            throw $e;
        }
    }
    
    /**
     * 获取代币余额
     * 重写父类方法但保持签名一致
     * 优化内存使用
     *
     * @param string|null $address
     * @param bool $scaled
     * @return string
     */
    public function balanceOf(string $address = null, bool $scaled = true): string
    {
        $balance = parent::balanceOf($address, false); // 获取原始余额
        
        // 如果需要格式化余额（根据小数位数转换）
        if ($scaled) {
            $decimals = $this->decimals();
            $result = bcdiv($balance, bcpow('10', (string)$decimals, 0), 18);
            
            // 清除内部变量以节省内存
            unset($balance, $decimals);
            
            return $result;
        }
        
        return $balance;
    }
    
    /**
     * 批量查询账户余额
     * 优化内存使用，分批处理
     *
     * @param array $addresses 要查询的地址数组
     * @param bool $scaled 是否格式化余额（根据小数位数转换）
     * @param int $batchSize 每批处理的地址数量
     * @return array 地址=>余额的关联数组
     * @throws TronException
     */
    public function batchBalanceOf(array $addresses, bool $scaled = true, int $batchSize = 20): array
    {
        $results = [];
        
        // 分批处理地址
        $addressBatches = array_chunk($addresses, $batchSize);
        
        foreach ($addressBatches as $batch) {
            foreach ($batch as $address) {
                try {
                    $results[$address] = $this->balanceOf($address, $scaled);
                } catch (\Exception $e) {
                    $results[$address] = [
                        'error' => $e->getMessage(),
                        'success' => false
                    ];
                }
            }
            
            // 每批处理完后清理临时变量和可能的循环引用
            unset($batch);
            
            // 主动触发垃圾回收
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        // 批量操作完成后自动清理
        $this->cleanupAfterTransfer();
        
        return $results;
    }
    
    /**
     * 批量转账
     * 优化内存使用，分批处理
     *
     * @param array $receivers 接收地址和金额的数组：[['to' => 地址, 'amount' => 金额], ...]
     * @param int $batchSize 每批处理的交易数量
     * @return array 交易结果
     * @throws TronException
     */
    public function batchTransfer(array $receivers, int $batchSize = 5): array
    {
        if (empty($receivers)) {
            throw new TronException('接收者列表不能为空', 1010);
        }
        
        $results = [];
        $decimals = $this->decimals();
        $multiplier = bcpow('10', (string)$decimals, 0);
        
        // 分批处理转账
        $receiverBatches = array_chunk($receivers, $batchSize);
        
        foreach ($receiverBatches as $batch) {
            foreach ($batch as $receiver) {
                if (!isset($receiver['to']) || !isset($receiver['amount'])) {
                    $results[] = [
                        'success' => false,
                        'error' => '无效的转账数据格式',
                        'data' => $receiver
                    ];
                    continue;
                }
                
                try {
                    // 转换金额为合约需要的数值
                    $amount = bcmul($receiver['amount'], $multiplier, 0);
                    
                    // 执行转账 (此处会自动调用cleanupAfterTransfer)
                    $result = $this->transfer($receiver['to'], $amount);
                    
                    $results[] = [
                        'success' => true,
                        'data' => $result,
                        'to' => $receiver['to'],
                        'amount' => $receiver['amount']
                    ];
                    
                    // 清理不需要的变量
                    unset($amount, $result);
                    
                } catch (\Exception $e) {
                    $results[] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'data' => $receiver
                    ];
                }
            }
            
            // 每批处理完后清理临时变量
            unset($batch);
            
            // 主动触发垃圾回收
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        return $results;
    }
    
    /**
     * 获取TRC20代币的完整信息
     *
     * @return array
     */
    public function getTokenInfo(): array
    {
        return [
            'name' => $this->name(),
            'symbol' => $this->symbol(),
            'decimals' => $this->decimals(),
            'address' => $this->getContractAddress(),
            'totalSupply' => $this->totalSupply()
        ];
    }
    
    /**
     * 获取合约地址（用于检查原始类是否有这个方法）
     *
     * @return string
     */
    private function getContractAddress(): string
    {
        // 尝试从array()方法获取合约地址
        $info = parent::array();
        return $info['contract_address'] ?? '';
    }
} 