<?php

namespace Zhifu\TronAPI;

use Zhifu\TronAPI\Exception\TronException;
use Zhifu\TronAPI\Provider\HttpProvider;

/**
 * 增强版Tron API主类
 */
class Tron extends \IEXBase\TronAPI\Tron
{
    /**
     * 节点API版本
     *
     * @var string
     */
    protected $apiVersion = 'v1';
    
    /**
     * API密钥
     *
     * @var string|null
     */
    protected $apiKey = null;
    
    /**
     * 构造函数
     *
     * @param HttpProvider|string|null $fullNode
     * @param HttpProvider|string|null $solidityNode
     * @param HttpProvider|string|null $eventServer
     * @param string|null $privateKey
     * @param string|null $apiKey TRON API密钥
     * @throws TronException
     */
    public function __construct(
        $fullNode = null,
        $solidityNode = null,
        $eventServer = null,
        ?string $privateKey = null,
        ?string $apiKey = null
    ) {
        // 保存API密钥
        if ($apiKey !== null) {
            $this->apiKey = $apiKey;
        }
        
        // 如果传入字符串参数，自动创建HttpProvider实例
        if (is_string($fullNode)) {
            $fullNode = new HttpProvider($fullNode, 30000, [], $apiKey);
        } elseif ($fullNode instanceof HttpProvider && $apiKey !== null) {
            $fullNode->setApiKey($apiKey);
        }
        
        if (is_string($solidityNode)) {
            $solidityNode = new HttpProvider($solidityNode, 30000, [], $apiKey);
        } elseif ($solidityNode instanceof HttpProvider && $apiKey !== null) {
            $solidityNode->setApiKey($apiKey);
        }
        
        if (is_string($eventServer)) {
            $eventServer = new HttpProvider($eventServer, 30000, [], $apiKey);
        } elseif ($eventServer instanceof HttpProvider && $apiKey !== null) {
            $eventServer->setApiKey($apiKey);
        }
        
        parent::__construct($fullNode, $solidityNode, $eventServer, $privateKey);
    }
    
    /**
     * 设置节点API版本
     *
     * @param string $version
     * @return $this
     */
    public function setApiVersion(string $version): self
    {
        $this->apiVersion = $version;
        return $this;
    }
    
    /**
     * 获取节点API版本
     *
     * @return string
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }
    
    /**
     * 增强版的获取账户信息方法
     *
     * @param string|null $address
     * @return array
     * @throws TronException
     */
    public function getAccount(string $address = null): array
    {
        $address = $address ?: $this->address;
        
        // 处理地址可能是数组的情况
        if (is_array($address) && isset($address['base58'])) {
            $address = $address['base58'];
        }
        
        if (!$this->isAddress($address)) {
            throw TronException::invalidAddress($address);
        }
        
        try {
            // 使用GET方法而不是默认的POST方法
            $response = $this->manager->request("/{$this->apiVersion}/accounts/{$address}", [], 'get');
            
            // 直接返回完整的响应，而不是只返回data部分
            // 这样在getBalance等方法中可以根据需要处理不同的响应结构
            return $response;
        } catch (\IEXBase\TronAPI\Exception\TronException $e) {
            throw new TronException($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * 查询Token余额（支持TRC10和TRC20）
     * 扩展原有方法支持TRC10和TRC20
     *
     * @param int|string $tokenId TRC10代币ID或TRC20合约地址
     * @param string $address 要查询的地址
     * @param bool $fromTron 是否为TRX单位转换 (与原方法保持兼容)
     * @return float|string 代币余额
     * @throws TronException
     */
    public function getTokenBalance($tokenId, string $address, bool $fromTron = false)
    {
        // 检查是否为TRC20代币（如果是字符串形式地址）
        $isTrc20 = is_string($tokenId) && $this->isAddress($tokenId);
        
        // 如果地址为空，使用当前设置的地址
        if (empty($address)) {
            $address = $this->address;
        }
        
        if (!$this->isAddress($address)) {
            throw TronException::invalidAddress($address);
        }
        
        if ($isTrc20) {
            // 查询TRC20代币余额
            $contract = $this->contract($tokenId);
            $balance = $contract->balanceOf($address);
            
            // 获取代币小数位数
            $decimals = $contract->decimals();
            
            // 根据小数位数转换余额
            return bcdiv($balance, bcpow(10, $decimals, 18), 18);
        } else {
            // 使用父类方法查询TRC10代币余额
            return parent::getTokenBalance($tokenId, $address, $fromTron);
        }
    }
    
    /**
     * 批量转账TRC20代币
     *
     * @param string $contractAddress TRC20合约地址
     * @param array $transfers 转账信息，格式：[['to' => '地址', 'amount' => 金额], ...]
     * @return array 交易结果
     * @throws TronException
     */
    public function batchTransferTrc20(string $contractAddress, array $transfers): array
    {
        if (empty($transfers)) {
            throw new TronException('转账列表不能为空', 1010);
        }
        
        $contract = $this->contract($contractAddress);
        $decimals = $contract->decimals();
        $multiplier = bcpow(10, $decimals, 18);
        
        $results = [];
        
        foreach ($transfers as $transfer) {
            if (!isset($transfer['to']) || !isset($transfer['amount'])) {
                $results[] = [
                    'success' => false,
                    'error' => '无效的转账数据格式',
                    'data' => $transfer
                ];
                continue;
            }
            
            if (!$this->isAddress($transfer['to'])) {
                $results[] = [
                    'success' => false,
                    'error' => '无效的接收地址',
                    'data' => $transfer
                ];
                continue;
            }
            
            try {
                // 转换金额为合约需要的数值
                $amount = bcmul($transfer['amount'], $multiplier, 0);
                
                // 执行转账
                $result = $contract->transfer($transfer['to'], $amount);
                
                $results[] = [
                    'success' => true,
                    'data' => $result,
                    'to' => $transfer['to'],
                    'amount' => $transfer['amount']
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'data' => $transfer
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * 获取交易状态
     *
     * @param string $txID 交易ID
     * @return array 交易状态信息
     * @throws TronException
     */
    public function getTransactionStatus(string $txID): array
    {
        try {
            $info = $this->getTransactionInfo($txID);
            
            if (empty($info)) {
                return [
                    'success' => false,
                    'status' => 'pending',
                    'message' => '交易尚未被确认',
                    'data' => null
                ];
            }
            
            if (isset($info['result']) && $info['result'] === 'FAILED') {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => isset($info['resMessage']) ? $info['resMessage'] : '交易执行失败',
                    'data' => $info
                ];
            }
            
            return [
                'success' => true,
                'status' => 'confirmed',
                'message' => '交易已被确认',
                'block' => isset($info['blockNumber']) ? $info['blockNumber'] : null,
                'energy_used' => isset($info['receipt']['energy_usage_total']) ? $info['receipt']['energy_usage_total'] : 0,
                'data' => $info
            ];
        } catch (\Exception $e) {
            throw new TronException('获取交易状态失败: ' . $e->getMessage(), 1020, $e);
        }
    }
    
    /**
     * 生成钱包地址
     * 
     * 使用椭圆曲线加密库生成新钱包
     *
     * @return array 包含私钥和地址的数组
     * @throws TronException
     */
    public function generateWallet(): array
    {
        try {
            // 使用父类的方法生成地址
            $tronAddress = parent::generateAddress();
            
            // 返回格式化的结果
            return [
                'privateKey' => $tronAddress->getPrivateKey(),
                'address' => $tronAddress->getAddress(true), // Base58格式的地址
                'hexAddress' => $tronAddress->getAddress(false), // Hex格式的地址
                'publicKey' => $tronAddress->getPublicKey()
            ];
        } catch (\Exception $e) {
            throw new TronException('生成钱包地址失败: ' . $e->getMessage(), 1030, $e);
        }
    }
    
    /**
     * 根据私钥获取地址
     *
     * 使用椭圆曲线加密库从私钥获取地址
     *
     * @param string $privateKey 私钥
     * @return array 包含地址的数组
     * @throws TronException
     */
    public function getAddressFromPrivateKey(string $privateKey): array
    {
        try {
            // 验证私钥格式
            if (!preg_match('/^[0-9a-f]{64}$/i', $privateKey)) {
                throw new TronException('无效的私钥格式');
            }
            
            // 使用椭圆曲线库生成公钥
            $ec = new \Elliptic\EC('secp256k1');
            $key = $ec->keyFromPrivate($privateKey);
            $pubKeyHex = $key->getPublic(false, "hex");
            
            // 使用父类方法计算地址
            $pubKeyBin = hex2bin($pubKeyHex);
            $addressHex = parent::getAddressHex($pubKeyBin);
            $addressBase58 = parent::getBase58CheckAddress(hex2bin($addressHex));
            
            return [
                'address' => $addressBase58,
                'hexAddress' => $addressHex,
                'publicKey' => $pubKeyHex
            ];
        } catch (\Exception $e) {
            throw new TronException('根据私钥获取地址失败: ' . $e->getMessage(), 1031, $e);
        }
    }
    
    /**
     * 验证私钥和地址是否匹配
     *
     * @param string $privateKey 私钥
     * @param string $address 地址（Base58格式）
     * @return bool 是否匹配
     * @throws TronException
     */
    public function validatePrivateKey(string $privateKey, string $address): bool
    {
        try {
            $addressInfo = $this->getAddressFromPrivateKey($privateKey);
            return $addressInfo['address'] === $address;
        } catch (\Exception $e) {
            throw new TronException('验证私钥失败: ' . $e->getMessage(), 1032, $e);
        }
    }
    
    /**
     * 获取账户的TRC20代币交易历史
     *
     * @param string $address TRON账户地址
     * @param string|null $contractAddress TRC20代币合约地址(不填则查询所有TRC20代币)
     * @param int $limit 查询数量限制(最大100)
     * @param string|null $fingerprint 分页指纹
     * @param int|null $minTimestamp 最小时间戳(毫秒)
     * @param bool $onlyConfirmed 是否只返回已确认的交易
     * @param bool $onlyTo 是否只返回转入交易
     * @param bool $onlyFrom 是否只返回转出交易
     * @return array 交易历史列表
     * @throws TronException
     */
    public function getTrc20TransactionsByAccount(
        string $address,
        string $contractAddress = null,
        int $limit = 20,
        string $fingerprint = null,
        int $minTimestamp = null,
        bool $onlyConfirmed = true,
        bool $onlyTo = false,
        bool $onlyFrom = false
    ): array {
        if (!$this->isAddress($address)) {
            throw TronException::invalidAddress($address);
        }
        
        if ($contractAddress !== null && !$this->isAddress($contractAddress)) {
            throw new TronException('无效的合约地址', 1040);
        }
        
        if ($limit < 1 || $limit > 100) {
            throw new TronException('limit参数必须在1-100之间', 1041);
        }
        
        try {
            $params = [
                'limit' => $limit
            ];
            
            if ($fingerprint !== null) {
                $params['fingerprint'] = $fingerprint;
            }
            
            if ($minTimestamp !== null) {
                $params['min_timestamp'] = $minTimestamp;
            }
            
            if ($onlyConfirmed) {
                $params['only_confirmed'] = 'true';
            }
            
            if ($onlyTo) {
                $params['only_to'] = 'true';
            }
            
            if ($onlyFrom) {
                $params['only_from'] = 'true';
            }
            
            $url = "/{$this->apiVersion}/accounts/{$address}/transactions/trc20";
            
            if ($contractAddress !== null) {
                $params['contract_address'] = $contractAddress;
            }
            
            $response = $this->manager->request($url, $params, 'get');
            
            return [
                'success' => isset($response['success']) ? $response['success'] : true,
                'data' => isset($response['data']) ? $response['data'] : $response,
                'meta' => isset($response['meta']) ? $response['meta'] : null
            ];
        } catch (\Exception $e) {
            throw new TronException('获取TRC20交易历史失败: ' . $e->getMessage(), 1042, $e);
        }
    }
    
    /**
     * 检查地址是否收到特定金额的TRC20代币付款
     *
     * @param string $address 要检查的地址
     * @param string $contractAddress TRC20代币合约地址
     * @param float $amount 预期收到的金额
     * @param int $checkTimespan 检查的时间范围(毫秒)，默认1小时
     * @param array $existingTxIds 已存在的交易ID(用于排除已处理的交易)
     * @return array|null 如果找到匹配的交易则返回交易详情，否则返回null
     * @throws TronException
     */
    public function checkTrc20Payment(
        string $address,
        string $contractAddress,
        float $amount,
        int $checkTimespan = 3600000,
        array $existingTxIds = []
    ): ?array {
        // 获取代币小数位数
        $contract = $this->contract($contractAddress);
        $decimals = $contract->decimals();
        $multiplier = bcpow(10, $decimals, 0);
        
        // 转换为整数金额(考虑小数位数)
        $expectedAmount = bcmul($amount, $multiplier, 0);
        
        // 计算起始时间戳
        $minTimestamp = (time() * 1000) - $checkTimespan;
        
        // 只查询转入的交易
        $transactions = $this->getTrc20TransactionsByAccount(
            $address,
            $contractAddress,
            100, // 查询最多100条
            null,
            $minTimestamp,
            true, // 只查询已确认的
            true, // 只查询转入的
            false
        );
        
        if (empty($transactions['data'])) {
            return null;
        }
        
        // 遍历交易，寻找匹配的
        foreach ($transactions['data'] as $tx) {
            // 检查交易ID是否已存在于已处理列表中
            if (in_array($tx['transaction_id'], $existingTxIds)) {
                continue;
            }
            
            // 检查是否是转入交易
            if ($tx['to'] !== $address) {
                continue;
            }
            
            // 检查金额是否匹配
            if ($tx['value'] == $expectedAmount) {
                return [
                    'transaction_id' => $tx['transaction_id'],
                    'from' => $tx['from'],
                    'to' => $tx['to'],
                    'amount' => bcdiv($tx['value'], $multiplier, $decimals),
                    'block_timestamp' => $tx['block_timestamp'],
                    'token_info' => $tx['token_info'] ?? null
                ];
            }
        }
        
        return null;
    }
    
    /**
     * 查询USDT余额
     * 
     * USDT是最常用的TRC20代币，此方法提供了一个快捷方式来查询USDT余额
     * USDT在波场上的小数位数为6位
     *
     * @param string|null $address 要查询的地址，默认使用当前设置的地址
     * @param bool $formatted 是否格式化为带小数点的余额，默认为true
     * @return float|string USDT余额
     * @throws TronException
     */
    public function getUsdtBalance(string $address = null, bool $formatted = true)
    {
        // USDT合约地址
        $usdtContractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
        
        // 使用地址参数或当前设置的地址
        if ($address === null) {
            $address = is_array($this->address) ? $this->address['base58'] : $this->address;
        }
        
        try {
            // 查询余额
            $contract = $this->contract($usdtContractAddress);
            $balanceResult = $contract->balanceOf($address);
            
            // 在我们的测试中，发现balanceOf返回的是合约值里的值
            // 比如"10.0701 USDT"返回值是"10.070100"
            
            // 如果返回值包含小数点，直接使用这个值
            if (strpos($balanceResult, '.') !== false) {
                // 去掉多余的尾随零
                $cleanValue = rtrim(rtrim($balanceResult, '0'), '.');
                return $formatted ? (float)$cleanValue : $cleanValue;
            }
            
            // 如果是整数，按照6位小数转换
            $convertedValue = bcdiv($balanceResult, '1000000', 6);
            return $formatted ? (float)$convertedValue : $convertedValue;
        } catch (\Exception $e) {
            throw new TronException('查询USDT余额失败: ' . $e->getMessage(), 1060, $e);
        }
    }
    
    /**
     * 设置API密钥
     *
     * @param string $apiKey
     * @return $this
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        
        // 设置所有HTTP提供者的API密钥
        if ($this->manager) {
            // 为fullNode设置API密钥
            try {
                $fullNode = $this->manager->fullNode();
                if ($fullNode instanceof \Zhifu\TronAPI\Provider\HttpProvider) {
                    $fullNode->setApiKey($apiKey);
                }
            } catch (\Exception $e) {
                // 忽略错误，继续处理其他节点
            }
            
            // 为solidityNode设置API密钥
            try {
                $solidityNode = $this->manager->solidityNode();
                if ($solidityNode instanceof \Zhifu\TronAPI\Provider\HttpProvider) {
                    $solidityNode->setApiKey($apiKey);
                }
            } catch (\Exception $e) {
                // 忽略错误，继续处理其他节点
            }
            
            // 为eventServer设置API密钥
            try {
                $eventServer = $this->manager->eventServer();
                if ($eventServer instanceof \Zhifu\TronAPI\Provider\HttpProvider) {
                    $eventServer->setApiKey($apiKey);
                }
            } catch (\Exception $e) {
                // 忽略错误，继续处理其他节点
            }
        }
        
        return $this;
    }
    
    /**
     * 获取当前设置的API密钥
     *
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }
    
    /**
     * 获取账户资源情况
     *
     * @param string|null $address
     * @return array
     * @throws TronException
     */
    public function getAccountResources(string $address = null): array
    {
        if (is_null($address)) {
            $address = is_array($this->address) ? $this->address['hex'] : $this->address;
        }
        
        // 处理地址可能是数组的情况
        if (is_array($address) && isset($address['hex'])) {
            $address = $address['hex'];
        }
        
        try {
            // 使用正确的API路径获取账户资源
            // 对于v1版本API，直接从账户信息中提取资源数据
            if (strpos($this->apiVersion, 'v1') === 0) {
                // 确保地址是base58格式
                if (strlen($address) === 42 && substr($address, 0, 2) === '41') {
                    // 如果是hex格式，转换为base58
                    $address = $this->hexString2Address($address);
                }
                
                // 获取账户完整信息
                $accountInfo = $this->getAccount($address);
                
                // 处理v1 API的嵌套响应结构
                if (isset($accountInfo['data']) && is_array($accountInfo['data']) && !empty($accountInfo['data'])) {
                    $accountData = $accountInfo['data'][0];
                } else {
                    $accountData = $accountInfo;
                }
                
                // 提取资源相关信息
                $resources = [
                    'freeNetLimit' => $accountData['free_net_limit'] ?? 0,
                    'freeNetUsed' => $accountData['free_net_used'] ?? 0,
                    'NetLimit' => $accountData['net_limit'] ?? 0,
                    'NetUsed' => $accountData['net_used'] ?? 0,
                    'EnergyLimit' => $accountData['energy_limit'] ?? 0,
                    'EnergyUsed' => $accountData['energy_used'] ?? 0,
                    'TotalEnergyLimit' => $accountData['energy_limit'] ?? 0,
                    'TotalEnergyWeight' => $accountData['energy_weight'] ?? 0,
                    'TotalNetLimit' => $accountData['net_limit'] ?? 0,
                    'TotalNetWeight' => $accountData['net_weight'] ?? 0,
                    'balance' => $accountData['balance'] ?? 0
                ];
                
                return $resources;
            } else {
                // 对于老版本API，使用原来的方式
                return $this->manager->request('/wallet/getaccountresource', [
                    'address' => $this->toHex($address)
                ]);
            }
        } catch (\Exception $e) {
            throw new TronException('获取账户资源信息失败: ' . $e->getMessage(), 1070, $e);
        }
    }
    
    /**
     * 重写父类getBalance方法，增强对API v1的支持
     *
     * @param string|null $address 地址
     * @param bool $fromTron 是否转换为TRX单位
     * @return float 账户余额
     */
    public function getBalance(?string $address = null, bool $fromTron = false): float
    {
        $address = $address ?: $this->address;
        
        // 处理地址可能是数组的情况
        if (is_array($address) && isset($address['base58'])) {
            $address = $address['base58'];
        }

        try {
            // 尝试使用v1 API直接获取余额
            if (strpos($this->apiVersion, 'v1') === 0) {
                $account = $this->getAccount($address);
                
                // 处理v1 API的嵌套响应结构
                if (isset($account['data']) && is_array($account['data']) && !empty($account['data'])) {
                    // 如果响应包含data数组，从第一个元素获取余额
                    $balance = $account['data'][0]['balance'] ?? 0;
                } else {
                    // 直接尝试获取余额字段
                    $balance = $account['balance'] ?? 0;
                }
            } else {
                // 使用原来的方式
                $response = $this->manager->request('/wallet/getaccount', [
                    'address' => $this->toHex($address)
                ]);
                $balance = $response['balance'] ?? 0;
            }

            if($fromTron) {
                return (float)$this->fromTron($balance);
            }
            
            return (float)$balance;
        } catch (\Exception $e) {
            // 记录错误但不抛出异常，返回0
            error_log("获取TRX余额失败: " . $e->getMessage());
            return $fromTron ? 0.0 : 0.0;
        }
    }
    
    /**
     * 估算交易所需能量（Energy）
     *
     * @param string $contractAddress 合约地址
     * @param string $functionSelector 函数选择器，例如 "transfer(address,uint256)"
     * @param array $params 函数参数
     * @param string $fromAddress 发送方地址
     * @return array 能量估算结果
     * @throws TronException
     */
    public function estimateEnergy(
        string $contractAddress,
        string $functionSelector,
        array $params = [],
        string $fromAddress = null
    ): array {
        if (!$this->isAddress($contractAddress)) {
            throw new TronException('无效的合约地址', 1080);
        }
        
        // 使用当前地址或指定的地址
        $fromAddress = $fromAddress ?: (is_array($this->address) ? $this->address['hex'] : $this->address);
        
        // 确保地址格式正确
        if (is_array($fromAddress) && isset($fromAddress['hex'])) {
            $fromAddress = $fromAddress['hex'];
        } elseif (strlen($fromAddress) === 34) {
            // 如果是base58格式，转换为hex
            $fromAddress = $this->toHex($fromAddress);
        }
        
        try {
            // 准备参数
            $requestParams = [
                'owner_address' => $fromAddress,
                'contract_address' => $this->toHex($contractAddress),
                'function_selector' => $functionSelector,
            ];
            
            if (!empty($params)) {
                // 对于参数需要编码
                $requestParams['parameter'] = $this->encodeParamsForEstimate($params, $functionSelector);
            }
            
            // 尝试使用新的estimateenergy API (如果节点支持)
            try {
                $estimateResult = $this->manager->request('/walletsolidity/estimateenergy', $requestParams);
                
                if (isset($estimateResult['energy_required'])) {
                    // 获取当前能量价格
                    $energyPrice = $this->getCurrentEnergyPrice();
                    $energyUsed = $estimateResult['energy_required'];
                    
                    // 计算建议的feeLimit (能量 × 当前能量价格)
                    $suggestedFeeLimit = (int)($energyUsed * $energyPrice);
                    
                    // 确保费用限制在合理范围内 (最高500 TRX)
                    $maxFeeLimit = 500 * 1000000; // 500 TRX
                    if ($suggestedFeeLimit > $maxFeeLimit) {
                        $suggestedFeeLimit = $maxFeeLimit;
                    }
                    
                    return [
                        'success' => true,
                        'energy_used' => $energyUsed,
                        'energy_price' => $energyPrice,
                        'suggested_fee_limit' => $suggestedFeeLimit,
                        'suggested_fee_limit_trx' => $this->fromTron($suggestedFeeLimit),
                        'estimation_method' => 'estimateenergy',
                        'result' => $estimateResult
                    ];
                }
            } catch (\Exception $e) {
                // 如果新API调用失败，记录错误信息但不中断处理
                error_log('estimateenergy API调用失败: ' . $e->getMessage() . '，将使用备用方法');
            }
            
            // 回退到使用triggerconstantcontract API
            $result = $this->manager->request('/wallet/triggerconstantcontract', $requestParams);
            
            if (!isset($result['energy_used'])) {
                throw new TronException('估算能量失败，返回结果中没有energy_used字段', 1081);
            }
            
            // 获取当前能量价格
            $energyPrice = $this->getCurrentEnergyPrice();
            $energyUsed = $result['energy_used'];
            
            // 计算建议的feeLimit (能量 * 当前能量价格)
            // 使用保守估计，考虑智能合约执行的可变性
            $suggestedFeeLimit = (int)($energyUsed * $energyPrice * 1.3); // 增加30%的缓冲
            
            // 对于普通交易，使用更保守的值，确保不超过50 TRX
            if ($suggestedFeeLimit > 50 * 1000000) {
                $suggestedFeeLimit = 50 * 1000000; // 50 TRX上限
            }
            
            // 确保fee_limit不超过500 TRX (作为额外安全措施)
            $maxFeeLimit = 500 * 1000000;
            if ($suggestedFeeLimit > $maxFeeLimit) {
                $suggestedFeeLimit = $maxFeeLimit;
            }
            
            return [
                'success' => true,
                'energy_used' => $energyUsed,
                'energy_price' => $energyPrice,
                'suggested_fee_limit' => $suggestedFeeLimit,
                'suggested_fee_limit_trx' => $this->fromTron($suggestedFeeLimit),
                'estimation_method' => 'triggerconstantcontract',
                'result' => $result
            ];
        } catch (\Exception $e) {
            throw new TronException('估算能量失败：' . $e->getMessage(), 1082, $e);
        }
    }
    
    /**
     * 为TRC20代币转账估算能量消耗
     *
     * @param string $contractAddress TRC20代币合约地址
     * @param string $toAddress 接收方地址
     * @param float|string $amount 转账金额
     * @param string $fromAddress 发送方地址，默认为当前设置的地址
     * @return array 能量估算结果
     * @throws TronException
     */
    public function estimateTrc20TransferEnergy(
        string $contractAddress,
        string $toAddress,
        $amount,
        string $fromAddress = null
    ): array {
        if (!$this->isAddress($contractAddress)) {
            throw new TronException('无效的合约地址', 1083);
        }
        
        if (!$this->isAddress($toAddress)) {
            throw new TronException('无效的接收方地址', 1084);
        }
        
        try {
            // 获取代币精度
            $contract = $this->contract($contractAddress);
            $decimals = $contract->decimals();
            
            // 转换金额为合约需要的格式
            $rawAmount = bcmul((string)$amount, bcpow('10', (string)$decimals, 0), 0);
            
            // 准备transfer函数参数
            // 参数1：address _to - 需要去掉0x前缀并补齐到64位
            $toAddressHex = $this->toHex($toAddress);
            if (strpos($toAddressHex, '0x') === 0) {
                $toAddressHex = substr($toAddressHex, 2);
            }
            $toAddressParam = str_pad($toAddressHex, 64, '0', STR_PAD_LEFT);
            
            // 参数2：uint256 _value - 需要补齐到64位
            $amountHex = dechex($rawAmount);
            $amountParam = str_pad($amountHex, 64, '0', STR_PAD_LEFT);
            
            // 构建参数数组
            $params = [$toAddressParam, $amountParam];
            
            // 调用estimateEnergy
            $result = $this->estimateEnergy(
                $contractAddress,
                'transfer(address,uint256)',
                $params,
                $fromAddress
            );
            
            // 这里我们对估算结果进行额外处理，以便获得更安全的费用限制
            // 对于TRC20转账，建议使用更保守的费用限制
            $safetyFactor = 1.2; // 20%的安全裕度
            $maxFeeLimit = 50 * 1000000; // 最大50 TRX限制
            
            // 确保费用限制不超过最大限制
            $suggestedFeeLimit = (int)($result['energy_used'] * $result['energy_price'] * $safetyFactor);
            if ($suggestedFeeLimit > $maxFeeLimit) {
                $suggestedFeeLimit = $maxFeeLimit;
            }
            
            // 更新结果数组
            $result['suggested_fee_limit'] = $suggestedFeeLimit;
            $result['suggested_fee_limit_trx'] = $this->fromTron($suggestedFeeLimit);
            $result['contract_address'] = $contractAddress;
            $result['to_address'] = $toAddress;
            $result['amount'] = $amount;
            $result['token_decimals'] = $decimals;
            
            return $result;
        } catch (\Exception $e) {
            // 如果估算失败，返回一个安全的默认值
            $currentEnergyPrice = $this->getCurrentEnergyPrice();
            return [
                'success' => true,
                'energy_used' => 100000, // 假设的能量值
                'energy_price' => $currentEnergyPrice,
                'suggested_fee_limit' => 50 * 1000000, // 50 TRX
                'suggested_fee_limit_trx' => 50.0,
                'error' => $e->getMessage(),
                'message' => '能量估算失败，使用默认安全值',
                'estimation_method' => 'default_fallback'
            ];
        }
    }
    
    /**
     * 编码参数用于能量估算
     *
     * @param array $params 参数数组
     * @param string $functionSelector 函数选择器
     * @return string 编码后的参数
     */
    private function encodeParamsForEstimate(array $params, string $functionSelector): string
    {
        // 简单实现：直接连接所有参数
        return implode('', $params);
    }
    
    /**
     * 质押TRX获取能量（增强版）
     *
     * @param float $trxAmount 要质押的TRX数量
     * @param string|null $receiverAddress 接收能量的地址(如果不指定，默认为当前地址)
     * @param string|null $ownerAddress 质押TRX的地址(如果不指定，默认为当前地址)
     * @return array 质押结果
     * @throws TronException
     */
    public function freezeBalanceForEnergyV2(float $trxAmount, string $receiverAddress = null, string $ownerAddress = null): array
    {
        return $this->freezeBalanceV2(
            $trxAmount * 1000000, // 转换为SUN单位
            1, // 资源类型 - 能量，在质押2.0中为类型1
            $receiverAddress,
            $ownerAddress
        );
    }
    
    /**
     * 质押TRX获取带宽（增强版）
     *
     * @param float $trxAmount 要质押的TRX数量
     * @param string|null $receiverAddress 接收带宽的地址(如果不指定，默认为当前地址)
     * @param string|null $ownerAddress 质押TRX的地址(如果不指定，默认为当前地址)
     * @return array 质押结果
     * @throws TronException
     */
    public function freezeBalanceForBandwidthV2(float $trxAmount, string $receiverAddress = null, string $ownerAddress = null): array
    {
        return $this->freezeBalanceV2(
            $trxAmount * 1000000, // 转换为SUN单位
            0, // 资源类型 - 带宽
            $receiverAddress,
            $ownerAddress
        );
    }
    
    /**
     * 解除TRX质押（增强版）
     *
     * @param float $trxAmount 要解除质押的TRX数量
     * @param int $resourceType 资源类型 (0: 带宽, 1: 能量, 2: TronPower)
     * @param string|null $receiverAddress 接收资源的地址(如果不指定，默认为当前地址)
     * @param string|null $ownerAddress 质押TRX的地址(如果不指定，默认为当前地址)
     * @return array 解除质押结果
     * @throws TronException
     */
    public function unfreezeBalanceV2(float $trxAmount, int $resourceType = 0, string $receiverAddress = null, string $ownerAddress = null): array
    {
        // 质押2.0中的资源类型:
        // 0: 带宽 (BANDWIDTH), 1: 能量 (ENERGY), 2: TRON Power
        if (!in_array($resourceType, [0, 1, 2])) {
            throw new TronException('无效的资源类型。在质押2.0中，有效值为: 0(带宽), 1(能量), 2(TRON Power)', 1090);
        }
        
        // 使用当前地址或指定的地址
        $ownerAddress = $ownerAddress ?: (is_array($this->address) ? $this->address['hex'] : $this->address);
        
        // 确保地址格式正确
        if (is_array($ownerAddress) && isset($ownerAddress['hex'])) {
            $ownerAddress = $ownerAddress['hex'];
        } elseif (strlen($ownerAddress) === 34) {
            // 如果是base58格式，转换为hex
            $ownerAddress = $this->toHex($ownerAddress);
        }
        
        $receiverAddress = $receiverAddress ?: $ownerAddress;
        if (strlen($receiverAddress) === 34) {
            $receiverAddress = $this->toHex($receiverAddress);
        }
        
        try {
            $params = [
                'owner_address' => $ownerAddress,
                'receiver_address' => $receiverAddress,
                'resource' => (int)$resourceType, // 强制转换为整数，确保数据类型一致
                'balance' => floor($trxAmount * 1000000) // 转换为SUN单位
            ];
            
            // 调试日志
            error_log('解质押参数: ' . json_encode($params));
            
            $result = $this->manager->request('/wallet/unfreezebalancev2', $params);
            
            // 调试信息
            error_log('API响应: ' . json_encode($result));
            
            if (!$result || !isset($result['result'])) {
                throw new TronException('解除质押失败，返回结果中没有result字段', 1091);
            }
            
            // 如果有错误信息
            if (isset($result['Error'])) {
                throw new TronException($result['Error'], 1092);
            }
            
            // 如果需要签名交易
            if (array_key_exists('transaction', $result)) {
                $transaction = $result['transaction'];
                if ($this->privateKey) {
                    $signedTransaction = $this->signTransaction($transaction);
                    $result = $this->sendRawTransaction($signedTransaction);
                } else {
                    throw new TronException('解除质押失败，需要私钥来签名交易', 1093);
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            throw new TronException('解除质押失败：' . $e->getMessage(), 1094);
        }
    }
    
    /**
     * 质押TRX（增强版，支持V2质押API）
     *
     * @param int $amount 要质押的金额 (SUN单位)
     * @param int $resourceType 资源类型 (0: 带宽, 1: 能量, 2: TronPower)
     * @param string|null $receiverAddress 接收资源的地址(如果不指定，默认为当前地址)
     * @param string|null $ownerAddress 质押TRX的地址(如果不指定，默认为当前地址)
     * @return array 质押结果
     * @throws TronException
     */
    public function freezeBalanceV2(int $amount, int $resourceType = 0, string $receiverAddress = null, string $ownerAddress = null): array
    {
        // 质押2.0中的资源类型:
        // 0: 带宽 (BANDWIDTH), 1: 能量 (ENERGY), 2: TRON Power
        if (!in_array($resourceType, [0, 1, 2])) {
            throw new TronException('无效的资源类型。在质押2.0中，有效值为: 0(带宽), 1(能量), 2(TRON Power)', 1095);
        }
        
        // 使用当前地址或指定的地址
        $ownerAddress = $ownerAddress ?: (is_array($this->address) ? $this->address['hex'] : $this->address);
        
        // 确保地址格式正确
        if (is_array($ownerAddress) && isset($ownerAddress['hex'])) {
            $ownerAddress = $ownerAddress['hex'];
        } elseif (strlen($ownerAddress) === 34) {
            // 如果是base58格式，转换为hex
            $ownerAddress = $this->toHex($ownerAddress);
        }
        
        $receiverAddress = $receiverAddress ?: $ownerAddress;
        if ($receiverAddress && strlen($receiverAddress) === 34) {
            $receiverAddress = $this->toHex($receiverAddress);
        }
        
        try {
            // 确保资源类型保持为数字(0, 1, 2)，而不是字符串(BANDWIDTH, ENERGY, TRON_POWER)
            $params = [
                'owner_address' => $ownerAddress,
                'receiver_address' => $receiverAddress,
                'resource' => (int)$resourceType, // 强制转换为整数，确保数据类型一致
                'frozen_balance' => $amount
            ];
            
            // 调试日志
            error_log('质押参数: ' . json_encode($params));
            
            // 使用波场质押2.0 API
            $result = $this->manager->request('/wallet/freezebalancev2', $params);
            
            // 调试信息
            error_log('API响应: ' . json_encode($result));
            
            // 处理可能的错误
            if (isset($result['Error'])) {
                throw new TronException($result['Error'], 1097);
            }
            
            // 如果响应中有transaction字段，则说明需要签名
            if (array_key_exists('transaction', $result)) {
                $transaction = $result['transaction'];
                if ($this->privateKey) {
                    $signedTransaction = $this->signTransaction($transaction);
                    $result = $this->sendRawTransaction($signedTransaction);
                } else {
                    throw new TronException('质押失败，需要私钥来签名交易', 1098);
                }
                return $result;
            }
            
            // 如果没有result字段但有txID字段，也认为是成功的
            if (isset($result['txID'])) {
                return [
                    'result' => true,
                    'txid' => $result['txID'],
                    'transaction' => $result
                ];
            }
            
            // 如果响应为空或没有预期字段，可能是API响应格式异常
            if (empty($result) || (!isset($result['result']) && !isset($result['txID']))) {
                throw new TronException('质押API响应格式异常', 1096);
            }
            
            return $result;
        } catch (TronException $e) {
            // 捕获所有异常，提供详细错误信息
            throw new TronException('质押失败：' . $e->getMessage() . 
                                  '。TronGrid API可能不支持质押操作，建议使用全节点或私有节点。', 1099);
        }
    }
    
    /**
     * 获取账户资源委派信息
     *
     * @param string|null $address 要查询的地址
     * @return array 资源委派信息
     * @throws TronException
     */
    public function getAccountDelegatedResource(string $address = null): array
    {
        $address = $address ?: $this->address;
        
        // 处理地址可能是数组的情况
        if (is_array($address) && isset($address['hex'])) {
            $address = $address['hex'];
        }
        
        if (!$this->isAddress($address)) {
            throw TronException::invalidAddress($address);
        }
        
        try {
            $hexAddress = $this->toHex($address);
            $result = $this->manager->request('/wallet/getdelegatedresource', [
                'fromAddress' => $hexAddress,
                'toAddress' => $hexAddress
            ]);
            
            return $result;
        } catch (\Exception $e) {
            throw new TronException('获取资源委派信息失败：' . $e->getMessage(), 1100, $e);
        }
    }
    
    /**
     * 获取能量价格并计算TRX兑换能量比例
     *
     * @return array 包含当前能量价格、历史价格和换算信息
     * @throws TronException
     */
    public function getEnergyPrice(): array
    {
        try {
            // 调用API获取历史能量价格
            $result = $this->manager->request('/wallet/getenergyprices', [], 'get');
            
            if (!isset($result['prices']) || empty($result['prices'])) {
                // 如果API不可用或返回结果为空，使用默认价格
                return [
                    'success' => false,
                    'current_price' => 420, // 默认能量价格 (sun/能量)
                    'energy_per_trx' => floor(1000000 / 420), // 默认1 TRX可兑换的能量
                    'price_history' => [],
                    'message' => '无法获取能量价格，使用默认值'
                ];
            }
            
            // 解析价格历史字符串
            $pricesStr = $result['prices'];
            $pricesList = explode(',', $pricesStr);
            $priceHistory = [];
            $currentPrice = null;
            
            // 解析每个价格点
            foreach ($pricesList as $pricePoint) {
                $parts = explode(':', $pricePoint);
                if (count($parts) == 2) {
                    $timestamp = $parts[0];
                    $price = (int)$parts[1];
                    
                    $priceHistory[] = [
                        'timestamp' => $timestamp,
                        'date' => date('Y-m-d H:i:s', (int)($timestamp / 1000)),
                        'price' => $price,
                        'energy_per_trx' => floor(1000000 / $price)
                    ];
                    
                    // 最后一个价格为当前价格
                    $currentPrice = $price;
                }
            }
            
            // 如果没有有效的价格，使用默认值
            if ($currentPrice === null) {
                $currentPrice = 420;
            }
            
            // 计算1 TRX可兑换的能量
            $energyPerTrx = floor(1000000 / $currentPrice);
            
            return [
                'success' => true,
                'current_price' => $currentPrice,
                'energy_per_trx' => $energyPerTrx,
                'price_history' => $priceHistory,
                'raw_data' => $result
            ];
            
        } catch (\Exception $e) {
            // 如果API调用失败，使用默认价格
            return [
                'success' => false,
                'current_price' => 420, // 默认能量价格 (sun/能量)
                'energy_per_trx' => floor(1000000 / 420), // 默认1 TRX可兑换的能量
                'error' => $e->getMessage(),
                'message' => '获取能量价格失败，使用默认值'
            ];
        }
    }
    
    /**
     * 从指定网络获取能量价格
     *
     * @param string $network 要查询的网络，可选值: 'mainnet', 'shasta'(测试网)
     * @param int $retries 重试次数
     * @param int $delay 重试间隔(毫秒)
     * @return array 包含当前能量价格、历史价格和换算信息
     */
    public function getEnergyPriceFromNetwork(string $network = 'mainnet', int $retries = 3, int $delay = 1000): array
    {
        // 设置网络API URL
        $apiUrl = '';
        switch (strtolower($network)) {
            case 'mainnet':
                $apiUrl = 'https://api.trongrid.io';
                break;
            case 'shasta':
                $apiUrl = 'https://api.shasta.trongrid.io';
                break;
            default:
                return [
                    'success' => false,
                    'current_price' => 420,
                    'message' => '不支持的网络类型: ' . $network
                ];
        }
        
        $attempt = 0;
        $lastError = null;
        
        while ($attempt < $retries) {
            try {
                // 创建一个HTTP客户端
                $client = new \GuzzleHttp\Client([
                    'base_uri' => $apiUrl,
                    'timeout' => 10, // 超时时间设置为10秒
                ]);
                
                $response = $client->request('GET', '/wallet/getenergyprices', [
                    'headers' => [
                        'Accept' => 'application/json',
                        'TRON-PRO-API-KEY' => $this->apiKey ?? ''
                    ]
                ]);
                
                if ($response->getStatusCode() === 200) {
                    $result = json_decode($response->getBody()->getContents(), true);
                    
                    if (!isset($result['prices']) || empty($result['prices'])) {
                        // 如果API返回结果为空，等待后重试
                        $attempt++;
                        if ($attempt < $retries) {
                            usleep($delay * 1000); // 转换为微秒
                            continue;
                        }
                        
                        return [
                            'success' => false,
                            'current_price' => 420,
                            'message' => '无法从' . $network . '获取能量价格，返回结果为空'
                        ];
                    }
                    
                    // 解析价格历史字符串
                    $pricesStr = $result['prices'];
                    $pricesList = explode(',', $pricesStr);
                    $priceHistory = [];
                    $currentPrice = null;
                    
                    // 解析每个价格点
                    foreach ($pricesList as $pricePoint) {
                        $parts = explode(':', $pricePoint);
                        if (count($parts) == 2) {
                            $timestamp = $parts[0];
                            $price = (int)$parts[1];
                            
                            $date = $timestamp > 0 ? date('Y-m-d H:i:s', (int)($timestamp / 1000)) : '初始价格';
                            
                            $priceHistory[] = [
                                'timestamp' => $timestamp,
                                'date' => $date,
                                'price' => $price,
                                'energy_per_trx' => floor(1000000 / $price)
                            ];
                            
                            // 最后一个价格为当前价格
                            $currentPrice = $price;
                        }
                    }
                    
                    // 如果没有有效的价格，使用默认值
                    if ($currentPrice === null) {
                        $currentPrice = 420;
                    }
                    
                    // 计算1 TRX可兑换的能量
                    $energyPerTrx = floor(1000000 / $currentPrice);
                    
                    return [
                        'success' => true,
                        'network' => $network,
                        'current_price' => $currentPrice,
                        'energy_per_trx' => $energyPerTrx,
                        'price_history' => $priceHistory,
                        'raw_data' => $result
                    ];
                } else {
                    // 状态码不是200，等待后重试
                    $lastError = '非200状态码: ' . $response->getStatusCode();
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }
            
            $attempt++;
            if ($attempt < $retries) {
                usleep($delay * 1000); // 转换为微秒
            }
        }
        
        // 所有重试失败后返回默认值
        return [
            'success' => false,
            'network' => $network,
            'current_price' => 420,
            'energy_per_trx' => floor(1000000 / 420),
            'error' => $lastError,
            'message' => '无法从' . $network . '获取能量价格，使用默认值'
        ];
    }
    
    /**
     * 获取当前能量价格 (简化版)
     * 
     * @return int 当前能量价格 (sun/能量)
     */
    public function getCurrentEnergyPrice(): int
    {
        try {
            $priceInfo = $this->getEnergyPrice();
            return $priceInfo['current_price'];
        } catch (\Exception $e) {
            // 返回默认价格
            return 420;
        }
    }
    
    /**
     * 获取网络能量总质押量
     *
     * @return int 网络中用于获取能量的TRX总质押量 (单位: TRX)
     */
    public function getTotalEnergyStaked(): int
    {
        try {
            // 查询网络资源情况
            $result = $this->manager->request('/wallet/getchainparameters', []);
            
            if (isset($result['chainParameter'])) {
                foreach ($result['chainParameter'] as $param) {
                    if ($param['key'] == 'getTotalEnergyWeight') {
                        // 返回值是以sun为单位，转换为TRX
                        return (int)($param['value'] / 1000000);
                    }
                }
            }
            
            // 如果没有找到参数，返回估算值
            // 根据我们观察到的1000 TRX ≈ 11183能量，反推总质押量约为16,096,397 TRX
            return 16096397;
            
        } catch (\Exception $e) {
            // 如果API调用失败，使用估算值
            return 16096397;
        }
    }
    
    /**
     * 计算指定TRX数量可兑换的能量
     *
     * @param float $trxAmount TRX数量
     * @return int 可兑换的能量数量
     */
    public function calculateEnergyFromTrx(float $trxAmount): int
    {
        try {
            // 获取网络总质押量
            $totalEnergyStaked = $this->getTotalEnergyStaked();
            
            // 使用TRON网络的能量计算公式:
            // 能量获取 = 用户质押的TRX数量 / TRON网络中用于获取能量的TRX总质押量 * 180,000,000,000
            $energyAmount = floor(($trxAmount / $totalEnergyStaked) * 180000000000);
            
            return (int)$energyAmount;
        } catch (\Exception $e) {
            // 如果计算失败，使用固定兑换比例（根据观察到的1000 TRX ≈ 11183能量）
            $conversionRate = 11.183; // 每TRX能获得的能量数量
            return floor($trxAmount * $conversionRate);
        }
    }
    
    /**
     * 计算获取指定能量需要的TRX数量
     *
     * @param int $energyAmount 所需能量数量
     * @return float 需要的TRX数量
     */
    public function calculateTrxForEnergy(int $energyAmount): float
    {
        try {
            // 获取网络总质押量
            $totalEnergyStaked = $this->getTotalEnergyStaked();
            
            // 使用TRON网络的能量计算公式反推:
            // TRX数量 = 所需能量 * TRON网络中用于获取能量的TRX总质押量 / 180,000,000,000
            $trxNeeded = ($energyAmount * $totalEnergyStaked) / 180000000000;
            
            return $trxNeeded;
        } catch (\Exception $e) {
            // 如果计算失败，使用固定兑换比例（根据观察到的11183能量 ≈ 1000 TRX）
            $trxPerEnergy = 1000 / 11183; // 每单位能量需要的TRX
            return $energyAmount * $trxPerEnergy;
        }
    }
} 