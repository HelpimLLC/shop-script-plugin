<?php
/**
 * PHP version 5.2
 *
 * HTTP client
 *
 * @category helpim
 * @package  api-client-php
 * @author   Helpim <it@help-im.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://help-im.ru
 */

class shopHelpimHttpClient
{
    protected $defaultFields;
    protected $curl;
    protected $url = 'https://api.help-im.ru/in';
    protected $httpHeaders = array(
        'Accept: application/json',
        'Connection: keep-alive',
        'Content-Type: application/json',
    );

    /**
     * Client constructor
     *
     * @param array  $defaultFields Default request fields
     * @param string $url           API URI
     */
    public function __construct(array $defaultFields = array(), $url = null)
    {
        $this->defaultFields = $defaultFields;

        if (!empty($url)) {
            $this->url = $url;
        }

        $this->init();
    }

    public function __destruct() {
        curl_close($this->curl);
    }

    /**
     * Setup cURL connection
     */
    private function init() {
        $this->curl = curl_init($this->url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->curl, CURLOPT_FAILONERROR, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->httpHeaders);
    }

    /**
     * Make HTTP request
     *
     * @param array $data Data to send
     *
     * @throws shopHelpimCurlException
     * @throws shopHelpimInvalidJsonException
     *
     * @return shopHelpimClientResponse
     */
    public function request(array $data = array())
    {
        $data = array_merge($this->defaultFields, $data);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($data));

        $responseBody = curl_exec($this->curl);
        $statusCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $errno = curl_errno($this->curl);
        $error = curl_error($this->curl);

        if ($errno) {
            throw new shopHelpimCurlException($error, $errno);
        }

        return new shopHelpimClientResponse($statusCode, $responseBody);
    }
}
