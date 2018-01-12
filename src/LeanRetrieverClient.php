<?php

namespace Leankoala\RetrieverConnector;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use phm\HttpWebdriverClient\Http\Client\HttpClient;
use phm\HttpWebdriverClient\Http\Request\UserAgentAwareRequest;
use phm\HttpWebdriverClient\Http\Request\ViewportAwareRequest;
use Psr\Http\Message\RequestInterface;

class LeanRetrieverClient implements HttpClient
{
    const CLIENT_TYPE = 'LEANRETRIEVER_CLIENT';

    private $leanRetrieverEndpoint;

    public function __construct($leanRetreverEndpoint = 'http://localhost:8000')
    {
        $this->leanRetrieverEndpoint = $leanRetreverEndpoint;
    }

    public function sendRequest(RequestInterface $request)
    {
        $client = new Client();

        $requestArray = [
            'url' => (string)$request->getUri(),
            'headers' => $request->getHeaders(),
            'method' => $request->getMethod()
        ];

        if ($request instanceof ViewportAwareRequest) {
            $viewport = $request->getViewport();
            $requestArray['viewport'] = [
                'width' => $viewport->getWidth(),
                'height' => $viewport->getHeight()
            ];
        }

        if ($request instanceof UserAgentAwareRequest) {
            $requestArray['user-agent'] = $request->getUserAgent();
        }

        $leanRequest = new Request('GET', $this->leanRetrieverEndpoint, [], json_encode($request));

        $response = $client->send($leanRequest);

        var_dump($response);
    }

    public function sendRequests(array $requests)
    {
        $responses = [];

        foreach ($requests as $request) {
            $responses[] = $this->sendRequest($request);
        }

        return $responses;
    }

    public function getClientType()
    {
        return self::CLIENT_TYPE;
    }

    public function setOption($key, $value)
    {
        throw new \RuntimeException('This function is not implemented yet');
    }

}