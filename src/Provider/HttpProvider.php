<?php declare(strict_types=1);

namespace Zhifu\TronAPI\Provider;

use GuzzleHttp\{Psr7\Request, Client, ClientInterface};
use Psr\Http\Message\StreamInterface;
use Zhifu\TronAPI\Exception\{NotFoundException, TronException};
use Zhifu\TronAPI\Support\Utils;

class HttpProvider implements HttpProviderInterface
{
    /**
     * HTTP Client Handler
     *
     * @var ClientInterface.
     */
    protected $httpClient;

    /**
     * Server or RPC URL
     *
     * @var string
    */
    protected $host;

    /**
     * Waiting time
     *
     * @var int
     */
    protected $timeout;

    /**
     * Username for authentication
     *
     * @var string
     */
    protected $user;

    /**
     * Password for authentication
     *
     * @var string
     */
    protected $password;

    /**
     * Headers for auth
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Status Page
     *
     * @var string
     */
    protected $statusPage = '/';

    /**
     * TRON API密钥
     *
     * @var string|null
     */
    protected $apiKey = null;

    /**
     * Create a new HttpProvider instance
     *
     * @param string $host
     * @param int $timeout
     * @param string $user
     * @param string $password
     * @param array $headers
     * @param string $statusPage
     * @throws TronException
     */
    public function __construct(string $host, int $timeout = 30000,
                                $user = false, $password = false,
                                array $headers = [], string $statusPage = '/')
    {
        if(!Utils::isValidUrl($host)) {
            throw new TronException('Invalid URL provided to HttpProvider');
        }

        $this->host = $host;
        $this->timeout = $timeout;
        $this->user = $user;
        $this->password = $password;
        $this->statusPage = $statusPage;
        $this->headers = $headers;

        $this->httpClient = new Client([
            'base_uri'  =>  $host,
            'timeout'   =>  $timeout,
            'auth'      =>  $user && $password ? [$user, $password] : null,
            'headers'   =>  $headers
        ]);
    }

    /**
     * Change the status page
     *
     * @param string $page
     */
    public function setStatusPage(string $page = '/'): void
    {
        $this->statusPage = $page;
    }

    /**
     * Check connection
     *
     * @return bool
     */
    public function isConnected() : bool
    {
        try {
            $request = new Request('GET', $this->statusPage);
            $response = $this->httpClient->send($request);

            return $response->getStatusCode() == 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Getting host
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Getting timeout
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get the User
     *
     * @return string|null
     */
    public function getUser(): ?string
    {
        return is_string($this->user) ? $this->user : null;
    }

    /**
     * @param string $url
     * @param array $payload
     * @param string $method
     * @return array
     * @throws TronException
     */
    public function request($url, array $payload = [], string $method = 'get'): array
    {
        try {
            $request = new Request($method, $url);
            $response = $this->httpClient->send($request, ['json' => $payload]);
            return $this->decodeBody($response->getBody(), $response->getStatusCode());
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new TronException($e->getMessage());
        }
    }

    /**
     * @param StreamInterface $stream
     * @param int $status
     * @return array
     * @throws NotFoundException
     * @throws TronException
     */
    protected function decodeBody(StreamInterface $stream, int $status): array
    {
        $decodedBody = json_decode($stream->getContents(), true);

        if((string)$status == '404') {
            throw new NotFoundException('Page not found');
        }

        if((string)$status == '400') {
            if(isset($decodedBody['Error'])) {
                throw new TronException($decodedBody['Error']);
            }

            return $decodedBody;
        }

        return $decodedBody;
    }

    /**
     * 设置API密钥
     *
     * @param string $apiKey
     * @return self
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        
        // 更新headers中的API密钥
        $this->headers['TRON-PRO-API-KEY'] = $apiKey;
        
        // 如果httpClient已经初始化，更新其配置
        if ($this->httpClient) {
            $this->httpClient = new Client([
                'base_uri'  =>  $this->host,
                'timeout'   =>  $this->timeout,
                'auth'      =>  $this->user && $this->password ? [$this->user, $this->password] : null,
                'headers'   =>  $this->headers
            ]);
        }
        
        return $this;
    }
    
    /**
     * 获取API密钥
     *
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }
}
