<?php

class SimpleJsonRequest
{
    private static function makeRequest(string $method, string $url, array $parameters = null, array $data = null)
    {
        $opts = [
            'http' => [
                'method'  => $method,
                'header'  => 'Content-type: application/json',
                'content' => $data ? json_encode($data) : null
            ]
        ];

        $url .= ($parameters ? '?' . http_build_query($parameters) : '');
        return file_get_contents($url, false, stream_context_create($opts));
    }

    public static function get(string $url, array $parameters = null)
    {
        if(self::checkCached($url, $parameters)) return json_decode(self::readCache($url, $parameters),TRUE);
        return json_decode(self::writeCache($url, $parameters, self::makeRequest('GET', $url, $parameters)),TRUE);
    }

    public static function post(string $url, array $parameters = null, array $data)
    {
        return json_decode(self::makeRequest('POST', $url, $parameters, $data));
    }

    public static function put(string $url, array $parameters = null, array $data)
    {
        if(self::checkCached($url, $parameters)) self::removeCached($url, $parameters);
        return json_decode(self::makeRequest('PUT', $url, $parameters, $data));
    }   

    public static function patch(string $url, array $parameters = null, array $data)
    {
        if(self::checkCached($url, $parameters)) self::removeCached($url, $parameters);
        return json_decode(self::makeRequest('PATCH', $url, $parameters, $data));
    }

    public static function delete(string $url, array $parameters = null, array $data = null)
    {
        if(self::checkCached($url, $parameters)) self::removeCached($url, $parameters);
        return json_decode(self::makeRequest('DELETE', $url, $parameters, $data));
    }

    /**
     * This function check if a request was already cached. It simply runs a
     * `file_exists` built-in function over the `convertFilePath` result of the
     * URL+Parameters pair and return the boolean result, indicating if it's
     * cached (TRUE) or not (FALSE).
     *
     * @param      string   $url         The request url
     * @param      array    $parameters  The request parameters
     *
     * @return     boolean  True if file exsists (cached request), false if not
     */
    private static function checkCached(string $url, array $parameters = null)
    {
        return file_exists(self::convertFilePath($url,$parameters));
    }

    /**
     * this function read the result of a previous cached request. It simply
     * runs a `file_get_contents` built-in function over the `convertFilePath`
     * result of the URL+Parameters pair and return the file contents. This may
     * cause an E_WARNING if the file is not found, but as this function should
     * be always run after a trully return of the `checkCached`one, it shouldn't
     * be a problem.
     *
     * @param      string  $url         The request url
     * @param      array   $parameters  The request parameters
     *
     * @return     string  The cached request result
     */
    private static function readCache(string $url, array $parameters = null)
    {
        return file_get_contents(self::convertFilePath($url,$parameters));
    }

    /**
     * This function writes the result of a request to the cache. It simply runs
     * a `file_put_contents` built-in function over the `convertFilePath` result
     * of the URL+Parameters pair, writing the the result of the `makeRequest`
     * function to the file.
     *
     * @param      string  $url          The request url
     * @param      array   $parameters   The request parameters
     * @param      string  $requestData  The request return data
     *
     * @return     string  The bypassed request return data
     */
    private static function writeCache(string $url, array $parameters = null, $requestData = null)
    {
        file_put_contents(self::convertFilePath($url, $parameters), $requestData);
        return $requestData;
    }

    /**
     * This function removes a cached result of a request. It simply runs an
     * `unlink` built-in function over the `convertFilePath` result of the
     * URL+Parameters pair.
     *
     * @param      string  $url         The request url
     * @param      array   $parameters  The request parameters
     */
    private static function removeCached(string $url, array $parameters = null)
    {
        unlink(self::convertFilePath($url, $parameters));
    }

    /**
     * This function converts the pair (URL+Parameters) into a cache filepath.
     * It basically creates the full URL and replace anything out of ranges A-Z,
     * a-z and 0-9 with a hyphen ('-'). This may imply in a problem if the
     * characters that are used to identify the record in the URL (or in the
     * parameters) are outside of the given ranges. Anyway, as it's not defined
     * on the challenge, I'll leave it like this, but it's easy to change it
     * later.
     *
     * @param      string        $url         The request url
     * @param      array|string  $parameters  The request parameters
     *
     * @return     string        The cache filepath
     */
    private static function convertFilePath(string $url, array $parameters = null){
        $url .= ($parameters ? '?' . http_build_query($parameters) : '');
        return __DIR__."/cached/".strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $url)));
    }
}
