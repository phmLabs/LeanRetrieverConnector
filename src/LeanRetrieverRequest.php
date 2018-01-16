<?php

namespace Leankoala\Retriever\Request;

use GuzzleHttp\Psr7\Uri;
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

    /**
     * Create a LeanRetrieverRequest user the raw POST body as JSON
     *
     * @return LeanRetrieverRequest
     */
    public static function fromPost()
    {
        $rawRequest = file_get_contents("php://input");
        $rawRequestObj = json_decode($rawRequest, true);

        if (!$rawRequestObj) {
            throw new \RuntimeException('The given JSON string is not valid');
        }

        $url = new Uri(self::setIfExists('url', $rawRequestObj));
        $method = self::setIfExists('user-agent', $rawRequestObj, BrowserRequest::METHOD_GET);
        $userAgent = self::setIfExists('user-agent', $rawRequestObj, false, false);
        $timeout = self::setIfExists('timeout', $rawRequestObj, self::DEFAULT_TIMEOUT);
        $headers = self::setIfExists('headers', $rawRequestObj, false, false);

        $viewport = self::setIfExists('viewport', $rawRequestObj, false, false);
        $allowCache = self::setIfExists('allowCache', $rawRequestObj, true, false);

        $request = new self($method, $url);

        if ($userAgent) {
            $request->setUserAgent($userAgent);
        }

        if ($headers) {
            foreach ($headers as $key => $value) {
                $request = $request->withAddedHeader($key, $value);
            }
        }

        if ($viewport) {
            $viewport = new Viewport($viewport['height'], $viewport['width']);
            $request->setViewport($viewport);
        }

        $request->setTimeout($timeout);
        $request->setIsCacheAllowed($allowCache);

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
