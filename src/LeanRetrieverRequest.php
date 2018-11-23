<?php

namespace Leankoala\RetrieverConnector;

use GuzzleHttp\Psr7\Uri;
use Leankoala\Devices\DeviceFactory;
use Leankoala\Devices\SimpleDevice;
use Leankoala\Devices\Viewport;
use phm\HttpWebdriverClient\Http\Request\BrowserRequest;
use phm\HttpWebdriverClient\Http\Request\TimeoutAwareRequest;

class LeanRetrieverRequest extends BrowserRequest implements TimeoutAwareRequest
{
    const DEFAULT_TIMEOUT = 30000;

    private $timeout;

    /**
     * Helper function for handling the request array
     *
     * @param string $key the key
     * @param string[] $array the array where to check for the given key
     * @param bool $fallback The fallback value if key does not exist
     * @param bool $mandatory
     * @return string|array
     */
    private static function setIfExists($key, $array, $fallback = false, $mandatory = true)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        } elseif ($fallback) {
            return $fallback;
        } elseif (!$mandatory) {
            return null;
        } else {
            throw new \RuntimeException('Mandatory parameter "' . $key . '" is missing. Please check your POST raw body.');
        }
    }

    public static function fromArray($requestArray)
    {
        $url = new Uri(self::setIfExists('url', $requestArray));
        $method = self::setIfExists('method', $requestArray, BrowserRequest::METHOD_GET);
        $userAgent = self::setIfExists('user-agent', $requestArray, false, false);
        $timeout = self::setIfExists('timeout', $requestArray, self::DEFAULT_TIMEOUT);
        $headers = self::setIfExists('headers', $requestArray, false, false);

        $viewport = self::setIfExists('viewport', $requestArray, false, false);
        $allowCache = self::setIfExists('allowCache', $requestArray, true, false);
        $deviceName = self::setIfExists('deviceName', $requestArray, null, false);

        $request = new self($method, $url);

        if ($userAgent) {
            $request->setUserAgent($userAgent);
        }

        if ($headers) {
            foreach ($headers as $key => $value) {
                $request = $request->withAddedHeader($key, $value);
            }
        }

        if ($deviceName) {
            $factory = new DeviceFactory();
            $device = $factory->create($deviceName);
            $request->setDevice($device);
        } else if ($viewport) {
            $viewport = new Viewport($viewport['height'], $viewport['width']);
            $request->setViewport($viewport);
        }

        $request->setTimeout($timeout);
        $request->setIsCacheAllowed($allowCache);

        return $request;
    }

    /**
     * Create a LeanRetrieverRequest user the raw POST body as JSON
     *
     * @return LeanRetrieverRequest
     */
    public static function fromPost()
    {
        $rawRequest = file_get_contents("php://input");
        $requestArray = json_decode($rawRequest, true);

        if (!$requestArray) {
            throw new \RuntimeException('The given JSON string is not valid');
        }

        $request = self::fromArray($requestArray);

        return $request;
    }

    /**
     * @return integer
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param integer $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }
}
