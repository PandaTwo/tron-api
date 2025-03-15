<?php
namespace Zhifu\TronAPI\Provider;

interface HttpProviderInterface
{
    /**
     * 获取主机名
     *
     * @return string
     */
    public function getHost(): string;

    /**
     * 获取请求超时时间
     *
     * @return int
     */
    public function getTimeout(): int;

    /**
     * 获取用户代理
     *
     * @return string
     */
    public function getUser(): ?string;

    /**
     * 发送请求
     *
     * @param string $url
     * @param array $payload
     * @param string $method
     * @return array
     */
    public function request($url, array $payload = [], string $method = 'get'): array;

    /**
     * 状态检查
     *
     * @return bool
     */
    public function isConnected(): bool;
} 