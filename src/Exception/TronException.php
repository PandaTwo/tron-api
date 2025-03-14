<?php

namespace Zhifu\TronAPI\Exception;

/**
 * 扩展的Tron异常类
 */
class TronException extends \IEXBase\TronAPI\Exception\TronException
{
    /**
     * 异常代码映射
     *
     * @var array
     */
    protected static $codeMessages = [
        1000 => '网络连接失败',
        1001 => '无效的地址格式',
        1002 => '合约调用错误',
        1003 => '交易广播失败',
        1004 => '账户不存在',
        1005 => '余额不足',
        1006 => '签名验证失败',
        1007 => '当前网络拥堵，请稍后重试',
    ];
    
    /**
     * 获取友好的错误消息
     *
     * @param int $code
     * @return string|null
     */
    public static function getMessageByCode(int $code): ?string
    {
        return self::$codeMessages[$code] ?? null;
    }
    
    /**
     * 创建网络错误异常
     *
     * @param string $message
     * @return static
     */
    public static function networkError(string $message = ''): self
    {
        $errorMsg = empty($message) ? self::$codeMessages[1000] : $message;
        return new static($errorMsg, 1000);
    }
    
    /**
     * 创建地址格式错误异常
     *
     * @param string $address
     * @return static
     */
    public static function invalidAddress(string $address): self
    {
        return new static(
            sprintf('地址[%s]格式无效. %s', $address, self::$codeMessages[1001]),
            1001
        );
    }
} 