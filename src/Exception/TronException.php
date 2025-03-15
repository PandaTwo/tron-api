<?php
namespace Zhifu\TronAPI\Exception;

class TronException extends \Exception {
    //
    
    /**
     * 无效地址错误
     *
     * @param string $address
     * @return TronException
     */
    public static function invalidAddress(string $address): self
    {
        return new self(sprintf('Invalid address provided: %s', $address));
    }
}
