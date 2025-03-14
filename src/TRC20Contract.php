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
     * 
     * @var array
     */
    protected static $decimalsCache = [];
    
    /**
     * 缓存合约的名称
     * 
     * @var array
     */
    protected static $nameCache = [];
    
    /**
     * 缓存合约的符号
     * 
     * @var array
     */
    protected static $symbolCache = [];
    
    /**
     * 获取代币名称（带缓存）
     *
     * @return string
     */
    public function name(): string
    {
        $contractAddress = $this->getContractAddress();
        
        if (!isset(self::$nameCache[$contractAddress])) {
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
            self::$decimalsCache[$contractAddress] = parent::decimals();
        }
        
        return self::$decimalsCache[$contractAddress];
    }
    
    /**
     * 获取代币余额
     * 重写父类方法但保持签名一致
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
            return bcdiv($balance, bcpow('10', (string)$decimals, 18), 18);
        }
        
        return $balance;
    }
    
    /**
     * 批量查询账户余额
     *
     * @param array $addresses 要查询的地址数组
     * @param bool $scaled 是否格式化余额（根据小数位数转换）
     * @return array 地址=>余额的关联数组
     * @throws TronException
     */
    public function batchBalanceOf(array $addresses, bool $scaled = true): array
    {
        $results = [];
        
        foreach ($addresses as $address) {
            try {
                $results[$address] = $this->balanceOf($address, $scaled);
            } catch (\Exception $e) {
                $results[$address] = [
                    'error' => $e->getMessage(),
                    'success' => false
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * 批量转账
     *
     * @param array $receivers 接收地址和金额的数组：[['to' => 地址, 'amount' => 金额], ...]
     * @return array 交易结果
     * @throws TronException
     */
    public function batchTransfer(array $receivers): array
    {
        if (empty($receivers)) {
            throw new TronException('接收者列表不能为空', 1010);
        }
        
        $results = [];
        $decimals = $this->decimals();
        $multiplier = bcpow('10', (string)$decimals, 0);
        
        foreach ($receivers as $receiver) {
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
                
                // 执行转账
                $result = $this->transfer($receiver['to'], $amount);
                
                $results[] = [
                    'success' => true,
                    'data' => $result,
                    'to' => $receiver['to'],
                    'amount' => $receiver['amount']
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'data' => $receiver
                ];
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