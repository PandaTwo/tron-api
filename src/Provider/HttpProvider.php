<?php

namespace Zhifu\TronAPI\Provider;

/**
 * 扩展的HTTP提供者类
 */
class HttpProvider extends \IEXBase\TronAPI\Provider\HttpProvider
{
    /**
     * TRON API密钥
     *
     * @var string|null
     */
    protected $apiKey = null;
    
    /**
     * 增强的构造函数，支持设置额外的配置选项
     *
     * @param string $host
     * @param int $timeout
     * @param array $options 额外的选项
     * @param string|null $apiKey TRON API密钥
     */
    public function __construct(string $host, int $timeout = 30000, array $options = [], ?string $apiKey = null)
    {
        // 调用父类构造函数
        parent::__construct($host, $timeout);
        
        // 设置API密钥
        if ($apiKey !== null) {
            $this->setApiKey($apiKey);
        }
        
        // 增加额外的配置选项处理
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }
    
    /**
     * 设置TRON API密钥
     *
     * @param string $apiKey
     * @return $this
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        
        // 将API密钥添加到请求头中
        if (!empty($apiKey)) {
            $this->headers['TRON-PRO-API-KEY'] = $apiKey;
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
     * 增强的请求重试功能
     *
     * @param string $url 接口路径
     * @param array $payload 请求参数
     * @param string $method 请求方法
     * @param int $maxRetries 最大重试次数
     * @return array
     */
    public function requestWithRetry($url, array $payload = [], string $method = 'get', int $maxRetries = 3)
    {
        $attempts = 0;
        $lastException = null;
        
        while ($attempts < $maxRetries) {
            try {
                return $this->request($url, $payload, $method);
            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;
                // 指数退避重试
                usleep(min(pow(2, $attempts) * 100000, 1000000));
            }
        }
        
        // 如果全部尝试都失败，抛出最后捕获的异常
        throw $lastException;
    }
    
    /**
     * 重写父类的请求方法，确保在每个请求中包含API密钥
     *
     * @param string $url
     * @param array $payload
     * @param string $method
     * @return array
     * @throws \IEXBase\TronAPI\Exception\TronException
     */
    public function request($url, array $payload = [], string $method = 'get'): array
    {
        // 确保API密钥被添加到请求头中
        if (!empty($this->apiKey) && !isset($this->headers['TRON-PRO-API-KEY'])) {
            $this->headers['TRON-PRO-API-KEY'] = $this->apiKey;
        }
        
        // 调用父类的请求方法
        return parent::request($url, $payload, $method);
    }
    
    /**
     * 设置请求超时时间
     *
     * @param int $timeout
     * @return $this
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }
} 