<?php
namespace Paymentsense\RemotePayments\Model\Connect;

use Laminas\Http\Client as LaminasClient;
use Laminas\Http\Request as LaminasRequest;
use Magento\Framework\HTTP\ClientFactory;
use Psr\Log\LoggerInterface;

class Client
{
    /**
     * @var \Magento\Framework\HTTP\ClientFactory $httpClientFactory
     */
    private $httpClientFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Framework\HTTP\ClientFactory $httpClientFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientFactory $httpClientFactory,
        LoggerInterface $logger
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->logger = $logger;
    }

    /**
     * Performs HTTP GET requests
     *
     * @param array $request Request data
     *
     * @return array
     */
    public function get(array $request): array
    {
        return $this->request(LaminasRequest::METHOD_GET, $request);
    }

    /**
     * Performs HTTP POST requests
     *
     * @param array $request Request data
     *
     * @return array
     */
    public function post(array $request): array
    {
        return $this->request(LaminasRequest::METHOD_POST, $request);
    }

    /**
     * Performs HTTP requests
     *
     * @param string $method  HTTP method
     * @param array  $request Request data
     *
     * @return array
     */
    private function request(string $method, array $request): array
    {
          $config = [
            'ssltransport'  => 'tls',
            'sslverifypeer' => true,
            'strict'        => false,
            'persistent'    => true,
            'timeout'       => 30
        ];

        $result = [
            'HttpStatusCode' => null,
            'ResponseBody'   => null,
            'Data'           => null
        ];

        try {
            if (array_key_exists('sslallowselfsigned', $request)) {
                $config['sslallowselfsigned'] = $request['sslallowselfsigned'];
            }

            // Use Laminas client
            $client = new LaminasClient();
            $client->setOptions($config);
            $client->setMethod($method);
            $client->setUri($request['url']);
            if (array_key_exists('headers', $request)) {
                $client->setHeaders($request['headers']);
            }

            if (array_key_exists('data', $request)) {
                // Use setContent to set raw JSON data
                $client->setRawBody(json_encode($request['data']));  // Use setContent() instead of setBody()
            }

            // Send the request and get the response
            $httpResponse = $client->send();

            // Check the response and return the result
            if ($httpResponse->getStatusCode() === 200) {
                $result['HttpStatusCode'] = $httpResponse->getStatusCode();
                $result['ResponseBody']   = $httpResponse->getBody();
                $result['Data']           = json_decode($result['ResponseBody'], true);
            } else {
                $result['HttpStatusCode'] = $httpResponse->getStatusCode();
                $result['ResponseBody'] = $httpResponse->getBody();
            }

        } catch (\Exception $e) {
            $this->logger->error('Request failed: ' . $e->getMessage());
        }
        return $result;
    }
}
