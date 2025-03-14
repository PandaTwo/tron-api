<?php

namespace Zhifu\TronAPI\Test;

use PHPUnit\Framework\TestCase;
use Zhifu\TronAPI\Tron;
use Zhifu\TronAPI\Provider\HttpProvider;

/**
 * Tron API 单元测试类
 */
class TronTest extends TestCase
{
    /**
     * @var Tron
     */
    protected $tron;
    
    /**
     * 设置测试环境
     */
    protected function setUp(): void
    {
        // 使用测试网络，而非主网
        $fullNode = new HttpProvider('https://api.shasta.trongrid.io');
        $solidityNode = new HttpProvider('https://api.shasta.trongrid.io');
        $eventServer = new HttpProvider('https://api.shasta.trongrid.io');
        
        $this->tron = new Tron($fullNode, $solidityNode, $eventServer);
    }
    
    /**
     * 测试创建实例
     */
    public function testInstance(): void
    {
        $this->assertInstanceOf(Tron::class, $this->tron);
    }
    
    /**
     * 测试API版本设置
     */
    public function testApiVersion(): void
    {
        $this->assertEquals('v1', $this->tron->getApiVersion());
        
        $this->tron->setApiVersion('v2');
        $this->assertEquals('v2', $this->tron->getApiVersion());
    }
    
    /**
     * 测试字符串URL参数
     */
    public function testStringUrlParameter(): void
    {
        $tron = new Tron('https://api.shasta.trongrid.io');
        $this->assertInstanceOf(Tron::class, $tron);
    }
    
    /**
     * 测试地址验证方法
     */
    public function testAddressValidation(): void
    {
        // 有效的Tron地址
        $validAddress = 'TRVdp6sPmmgJzQtj4iUeMiRiZfzYQKgZHP';
        $this->assertTrue($this->tron->isAddress($validAddress));
        
        // 无效的地址
        $invalidAddress = 'invalid_address';
        $this->assertFalse($this->tron->isAddress($invalidAddress));
    }
} 