<?php

namespace Leankoala\RetrieverConnector;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use phm\HttpWebdriverClient\Http\Client\HttpClient;
use phm\HttpWebdriverClient\Http\Request\CacheAwareRequest;
use phm\HttpWebdriverClient\Http\Request\DeviceAwareRequest;
use phm\HttpWebdriverClient\Http\Request\UserAgentAwareRequest;
use phm\HttpWebdriverClient\Http\Request\ViewportAwareRequest;
use phm\HttpWebdriverClient\Http\Response\BrowserResponse;
use Psr\Http\Message\RequestInterface;

/**
 * @todo test headers
 *
 * Class LeanRetrieverClient
 * @package Leankoala\RetrieverConnector
 */
class LeanRetrieverClient implements HttpClient
{
    const CLIENT_TYPE = 'LEANRETRIEVER_CLIENT';

    const ENDPOINT_ENV_VAR = 'RETRIEVER_HOST';

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

        if ($request instanceof CacheAwareRequest) {
            $requestArray['allowCache'] = $request->isCacheAllowed();
        } else {
            $requestArray['allowCache'] = true;
        }

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

        $leanRequest = new Request('GET', $this->leanRetrieverEndpoint, [], json_encode($requestArray));

        $response = $client->send($leanRequest);

        $plainBody = (string)$response->getBody();

        if (!$plainBody) {
            throw new LeanRetrieverException('The returned value was empty.');
        }

        $responseObj = json_decode($plainBody);

        if (!$responseObj) {
            throw new LeanRetrieverException('The returned value was not a valid json string.');
        }

        if ($responseObj->status == 'error') {
            throw new LeanRetrieverException($responseObj->message);
        }

        $browserResponse = unserialize($responseObj->serializedResponse);
        /** @var BrowserResponse $browserResponse */

        if ($request instanceof DeviceAwareRequest) {
            $attachedRequest = $browserResponse->getRequest();
            if ($attachedRequest instanceof DeviceAwareRequest) {
                $browserResponse->getRequest()->setDevice($attachedRequest->getDevice());
            }
        }

        return $browserResponse;
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
        throw new LeanRetrieverException('This function is not implemented yet');
    }

    public function close()
    {

    }

    public static function guessEndpoint()
    {
        if ($endpoint = getenv(self::ENDPOINT_ENV_VAR)) {
            return $endpoint;
        }
        return 'http://localhost:8000';
    }
}